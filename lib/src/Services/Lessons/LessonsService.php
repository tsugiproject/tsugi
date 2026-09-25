<?php


namespace Tsugi\Services\Lessons;

use \Tsugi\Util\U;
use \Tsugi\Util\CCFileBase;
use \Tsugi\Util\LTI;
use \Tsugi\Controllers\Courses;
use \Tsugi\Controllers\Tool;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Membership;
use \Tsugi\Services\Quiz1\Quiz1Repository;

if ( ! class_exists(__NAMESPACE__.'\\LessonsNormalize', false) ) {
    require_once __DIR__ . '/LessonsNormalize.php';
}

class LessonsService {

    /**
     * All the lessons
     */
    public $lessons;

    /**
     * The individual module
     */
    public $module;

    /*
     ** The anchor of the module
     */
    public $anchor;

    /*
     ** The position of the module
     */
    public $position;

    /**
     * Index by resource_link
     */
    public $resource_links;

    /**
     * Mounted lessons path (e.g. /lessons or /courses/2/lessons).
     * Set by the Lessons controller; otherwise derived from REQUEST_URI.
     */
    public $toolHome = '';

    /** @var array Grades by resource_link_id for due badges on single-module view */
    private $lessonModuleGradesForBadges = array();

    /** @var array Due rows by link_key from GradeUtil::loadDueDatesForDisplay */
    private $lessonModuleDueDatesForBadges = array();

    /**
     * Quiz1 id => published. Null until loaded. Missing id means the quiz is not in this course.
     *
     * @var array<int,bool>|null
     */
    private $quiz1IdSet = null;

    /** @var bool|null */
    private $lessonsViewerIsInstructor = null;

    /**
     * Grades and due dates for progress badges while one module is rendered.
     * The lessons controller sets this before item HTML reads it.
     *
     * @param array<string,float> $grades
     * @param array<string,array<string,mixed>> $duedates
     */
    public function setModuleProgressContext(array $grades, array $duedates) {
        $this->lessonModuleGradesForBadges = $grades;
        $this->lessonModuleDueDatesForBadges = $duedates;
    }

    /** @return array<string,float> */
    public function moduleProgressGrades() {
        return $this->lessonModuleGradesForBadges;
    }

    /** @return array<string,array<string,mixed>> */
    public function moduleProgressDueDates() {
        return $this->lessonModuleDueDatesForBadges;
    }

    /**
     * get a setting for the lesson
     */
    public function getSetting($key, $default=false) {
        if ( ! isset($this->lessons) ) return $default;
        if ( ! isset($this->lessons->settings) ) return $default;
        if ( ! isset($this->lessons->settings->{$key}) ) return $default;
        return $this->lessons->settings->{$key};
    }

    /**
     * Mounted lessons path: /lessons or /courses/{id}/lessons.
     */
    public function lessonsHome() {
        if ( is_string($this->toolHome) && $this->toolHome !== '' ) {
            return $this->toolHome;
        }
        return \Tsugi\Controllers\Tool::determineToolHome('/lessons');
    }

    /**
     * LTI launch URL for a lessons resource_link_id (sibling _launch route).
     */
    public function lessonsLaunchPath($resource_link_id) {
        return $this->lessonsHome() . '_launch/' . $resource_link_id;
    }

    /**
     * When true, structural errors throw InvalidArgumentException instead of dying.
     * Used by {@see tryFromJson()} so authoring can refuse a bad save.
     */
    private static $throwOnError = false;

    /**
     * Load JSON without killing the request.
     *
     * @return LessonsService|string LessonsService on success, error message on failure
     */
    public static function tryFromJson($json_str, $anchor=null, $index=null)
    {
        $prev = self::$throwOnError;
        self::$throwOnError = true;
        try {
            return self::fromJson($json_str, $anchor, $index);
        } catch (\Throwable $e) {
            return $e->getMessage();
        } finally {
            self::$throwOnError = $prev;
        }
    }

    /**
     * Build a LessonsService object from a JSON string (file or manifest document).
     */
    public static function fromJson($json_str, $anchor=null, $index=null)
    {
        $lessons = json_decode($json_str);
        if ( $lessons === null ) {
            $msg = 'Problem parsing lessons.json: ' . json_last_error_msg();
            if ( self::$throwOnError ) {
                throw new \InvalidArgumentException($msg);
            }
            echo("<pre>\n");
            echo($msg);
            echo("\n");
            echo($json_str);
            die();
        }
        return new self($lessons, $anchor, $index);
    }

    /**
     * Fail a document load: throw when validating, otherwise die.
     */
    private static function fail($msg) {
        if ( self::$throwOnError ) {
            throw new \InvalidArgumentException($msg);
        }
        die_with_error_log($msg);
    }

    /*
     ** Load up the JSON from a file path, or from an already-decoded object.
     **/
    public function __construct($name='lessons.json', $anchor=null, $index=null)
    {
        global $CFG;

        if ( is_object($name) ) {
            $lessons = $name;
        } else {
            $json_str = file_get_contents($name);
            $lessons = json_decode($json_str);
            $this->resource_links = array();

            if ( $lessons === null ) {
                echo("<pre>\n");
                echo("Problem parsing lessons.json: ");
                echo(json_last_error_msg());
                echo("\n");
                echo($json_str);
                die();
            }
        }

        $this->resource_links = array();

        if ( ! is_object($lessons) ) {
            self::fail('lessons.json must be a JSON object');
        }
        if ( ! isset($lessons->modules) || ! is_array($lessons->modules) ) {
            self::fail('lessons.json must have a modules array');
        }

        // Empty modules is a valid new-course outline.
        foreach($lessons->modules as $module) {
            if ( !isset($module->title) ) {
                self::fail('All modules in a lesson must have a title');
            }
            if ( !isset($module->anchor) ) {
                self::fail('All modules must have an anchor: '.$module->title);
            }
        }

        // Demand that every module have required elments
        if ( isset($lessons->badges) ) foreach($lessons->badges as $badge) {
            if ( !isset($badge->title) ) {
                self::fail('All badges in a lesson must have a title');
            }
            if ( !isset($badge->assignments) ) {
                self::fail('All badges must have assignments: '.$badge->title);
            }
        }

        // Filter modules based on login
        if ( ! U::isLoggedIn() ) {
            $filtered_modules = array();
            $filtered = false;
            foreach($lessons->modules as $module) {
	            if ( isset($module->login) && $module->login ) {
                    $filtered = true;
                    continue;
                }
                $filtered_modules[] = $module;
            }
            if ( $filtered ) $lessons->modules = $filtered_modules;
        }
        $this->lessons = $lessons;

        // In-memory canonical model (does not rewrite the source JSON file).
        // Item-level junk must be logged and dropped here; this load cannot abort.
        for($i=0;$i<count($this->lessons->modules);$i++) {
            if ( isset($this->lessons->modules[$i]->items) && is_array($this->lessons->modules[$i]->items) ) {
                foreach ( $this->lessons->modules[$i]->items as $j => $item ) {
                    $this->lessons->modules[$i]->items[$j] = LessonsNormalize::normalizeItemObject($item);
                }
            }
        }
        if ( isset($this->lessons->launches) && is_array($this->lessons->launches) ) {
            foreach ( $this->lessons->launches as $j => $launch ) {
                $this->lessons->launches[$j] = LessonsNormalize::normalizeItemObject($launch);
            }
        }
        if ( isset($this->lessons->discussions) && is_array($this->lessons->discussions) ) {
            foreach ( $this->lessons->discussions as $j => $discussion ) {
                $this->lessons->discussions[$j] = LessonsNormalize::normalizeItemObject($discussion);
            }
        }

        // Pretty up the data structure
        for($i=0;$i<count($this->lessons->modules);$i++) {
            if ( isset($this->lessons->modules[$i]->carousel) ) self::adjustArray($this->lessons->modules[$i]->carousel);
            if ( isset($this->lessons->modules[$i]->videos) ) self::adjustArray($this->lessons->modules[$i]->videos);
            if ( isset($this->lessons->modules[$i]->references) ) self::adjustArray($this->lessons->modules[$i]->references);
            if ( isset($this->lessons->modules[$i]->assignments) ) self::adjustArray($this->lessons->modules[$i]->assignments);
            if ( isset($this->lessons->modules[$i]->slides) ) self::adjustArray($this->lessons->modules[$i]->slides);
            if ( isset($this->lessons->modules[$i]->lti) ) self::adjustArray($this->lessons->modules[$i]->lti);
            if ( isset($this->lessons->modules[$i]->discussions) ) self::adjustArray($this->lessons->modules[$i]->discussions);

            // Non arrays
            if ( isset($this->lessons->modules[$i]->assignment) ) {
                if ( ! is_string($this->lessons->modules[$i]->assignment) ) self::fail('Assignment must be a string: '.$this->lessons->modules[$i]->title);
                self::absolute_url_ref($this->lessons->modules[$i]->assignment);
            }
            if ( isset($this->lessons->modules[$i]->solution) ) {
                if ( ! is_string($this->lessons->modules[$i]->solution) ) self::fail('Solution must be a string: '.$this->lessons->modules[$i]->title);
                self::absolute_url_ref($this->lessons->modules[$i]->solution);
            }

            // Items array: same URL normalization as adjustArray() on legacy lti/discussions (launch, href, url)
            if ( isset($this->lessons->modules[$i]->items) && is_array($this->lessons->modules[$i]->items) ) {
                foreach ( $this->lessons->modules[$i]->items as $item ) {
                    if ( is_object($item) ) {
                        self::adjustItemsEntryUrls($item);
                    }
                }
            }
        }

        // Patch badges
        if ( isset($this->lessons->badges) ) for($i=0;$i<count($this->lessons->badges);$i++) {
            if ( ! isset($this->lessons->badges[$i]->threshold) ) {
                $this->lessons->badges[$i]->threshold = 1.0;
            }
        }

        // Remember resource links (author-supplied duplicates are allowed)
        foreach($this->lessons->modules as $module) {
            // Items array takes precedence - if present, skip legacy arrays
            if ( isset($module->items) ) {
                foreach($module->items as $item) {
                    if ( ! LessonsNormalize::isLtiLaunch($item) || ! isset($item->resource_link_id) ) continue;
                    if ( ! isset($this->resource_links[$item->resource_link_id]) ) {
                        $this->resource_links[$item->resource_link_id] = $module->anchor;
                    }
                }
            } else {
                // Process legacy lti array only if items is not present
                if ( isset($module->lti) ) {
                    $ltis = $module->lti;
                    if ( ! is_array($ltis) ) $ltis = array($ltis);
                    foreach($ltis as $lti) {
                        if ( ! isset($lti->title) ) {
                            self::fail('Missing lti title in module:'. $module->title);
                        }
                        if ( ! isset($lti->resource_link_id) ) {
                            self::fail('Missing resource link in Lessons '. $lti->title);
                        }
                        if ( ! isset($this->resource_links[$lti->resource_link_id]) ) {
                            $this->resource_links[$lti->resource_link_id] = $module->anchor;
                        }
                    }
                }
                // Process legacy discussions array only if items is not present
                if ( isset($module->discussions) ) {
                    $discussions = $module->discussions;
                    if ( ! is_array($discussions) ) $discussions = array($discussions);
                    foreach($discussions as $discussion) {
                        if ( ! isset($discussion->title) ) {
                            self::fail('Missing discussion title in module:'. $module->title);
                        }
                        if ( ! isset($discussion->resource_link_id) ) {
                            self::fail('Missing resource link in Lessons '. $discussion->title);
                        }
                        if ( ! isset($this->resource_links[$discussion->resource_link_id]) ) {
                            $this->resource_links[$discussion->resource_link_id] = $module->anchor;
                        }
                    }
                }
            }
        }

        // Top-level course launches (LTI tools not tied to a module)
        if ( isset($this->lessons->launches) ) {
            self::adjustArray($this->lessons->launches);
            foreach ( $this->lessons->launches as $launch ) {
                if ( ! isset($launch->title) ) {
                    self::fail('All launches in lessons must have a title');
                }
                if ( ! isset($launch->resource_link_id) ) {
                    self::fail('All launches must have resource_link_id: '.$launch->title);
                }
                if ( ! isset($launch->launch) ) {
                    self::fail('All launches must have launch URL: '.$launch->title);
                }
                if ( ! isset($this->resource_links[$launch->resource_link_id]) ) {
                    $this->resource_links[$launch->resource_link_id] = '';
                }
            }
        }

        $anchor = isset($_GET['anchor']) ? $_GET['anchor'] : $anchor;
        $index = isset($_GET['index']) ? $_GET['index'] : $index;

        // Search for the selected anchor or index position
        $count = 0;
        $module = false;
        if ( $anchor || $index ) {
            foreach($lessons->modules as $mod) {
                $count++;
                if ( $anchor !== null && isset($mod->anchor) && $anchor != $mod->anchor ) continue;
                if ( $index !== null && $index != $count ) continue;
                if ( $anchor == null && isset($mod->anchor) ) $anchor = $mod->anchor;
                $this->module = $mod;
                $this->position = $count;
                if ( $mod->anchor ) $this->anchor = $mod->anchor;
                break; // Found the module, exit loop
            }
        }

        return true;
    }

    /**
     * Make non-array into an array and adjust paths
     */
    public static function adjustArray(&$entry) {
        global $CFG;
        if ( isset($entry) && !is_array($entry) ) {
            $entry = array($entry);
        }
        for($i=0; $i < count($entry); $i++ ) {
            if ( is_string($entry[$i]) ) self::absolute_url_ref($entry[$i]);
            if ( isset($entry[$i]->href) && is_string($entry[$i]->href) ) self::absolute_url_ref($entry[$i]->href);
            if ( isset($entry[$i]->launch) && is_string($entry[$i]->launch) ) self::absolute_url_ref($entry[$i]->launch);
        }
    }

    /**
     * Apply absolute_url_ref to launch/href/url on one items-array entry and nested item lists.
     * Matches legacy adjustArray() behavior; expandLink() runs first so existing {apphome}/{wwwroot} stay correct.
     */
    private static function adjustItemsEntryUrls($item) {
        if ( ! is_object($item) ) {
            return;
        }
        if ( isset($item->launch) && is_string($item->launch) ) {
            $u = $item->launch;
            self::absolute_url_ref($u);
            $item->launch = $u;
        }
        if ( isset($item->href) && is_string($item->href) ) {
            $u = $item->href;
            self::absolute_url_ref($u);
            $item->href = $u;
        }
        if ( isset($item->url) && is_string($item->url) ) {
            $u = $item->url;
            self::absolute_url_ref($u);
            $item->url = $u;
        }
        if ( isset($item->items) && is_array($item->items) ) {
            foreach ( $item->items as $child ) {
                if ( is_object($child) ) {
                    self::adjustItemsEntryUrls($child);
                }
            }
        }
    }

    /**
     * Indicate we are in a single lesson
     */
    public function isSingle() {
        return ( $this->anchor !== null || $this->position !== null );
    }

    /**
     * True when there is no visible Lessons outline (new course, or all modules hidden).
     */
    public function isEmpty() {
        if ( ! isset($this->lessons->modules) || ! is_array($this->lessons->modules) ) {
            return true;
        }
        foreach ( $this->lessons->modules as $module ) {
            if ( isset($module->hidden) && $module->hidden ) {
                continue;
            }
            if ( isset($module->login) && $module->login && ! U::isLoggedIn() ) {
                continue;
            }
            return false;
        }
        return true;
    }

    /**
     * Get a module associated with an anchor
     */
    public function getModuleByAnchor($anchor)
    {
        foreach($this->lessons->modules as $mod) {
            if ( $mod->anchor == $anchor) return $mod;
        }
        return null;
    }

    /**
     * Course-level launches from lessons JSON (top-level "launches" array).
     *
     * @return array<int, object> List of launch objects (type, title, launch URL, resource_link_id, etc.)
     */
    public function getLaunches() {
        if ( ! isset($this->lessons->launches) ) {
            return array();
        }
        $launches = $this->lessons->launches;
        return is_array($launches) ? $launches : array($launches);
    }

    /**
     * Get an LTI or Discussion associated with a resource link ID
     */
    public function getLtiByRlid($resource_link_id)
    {
        if (isset($this->lessons->discussions) ) {
            foreach($this->lessons->discussions as $discussion) {
                if ( $discussion->resource_link_id == $resource_link_id) return $discussion;
            }
        }

        if ( isset($this->lessons->launches) ) {
            foreach ( $this->getLaunches() as $launch ) {
                if ( ! LessonsNormalize::isLtiLaunch($launch) ) {
                    continue;
                }
                if ( isset($launch->resource_link_id) && $launch->resource_link_id == $resource_link_id ) {
                    return $launch;
                }
            }
        }

        foreach($this->lessons->modules as $mod) {
            if ( isset($mod->lti) ) {
                foreach($mod->lti as $lti ) {
                    if ( $lti->resource_link_id == $resource_link_id) return $lti;
                }
            }
            if ( isset($mod->discussions) ) {
                foreach($mod->discussions as $discussion ) {
                    if ( $discussion->resource_link_id == $resource_link_id) return $discussion;
                }
            }
            // Scan items array for LTI and discussion items
            if ( isset($mod->items) && is_array($mod->items) ) {
                foreach($mod->items as $item) {
                    $item_obj = is_array($item) ? (object)$item : $item;
                    if ( LessonsNormalize::isLtiLaunch($item_obj) && isset($item_obj->resource_link_id)
                        && $item_obj->resource_link_id == $resource_link_id ) {
                        return $item_obj;
                    }
                }
            }
        }
        return null;
    }

    /**
     * Get a module associated with a resource link ID
     */
    public function getModuleByRlid($resource_link_id)
    {
        foreach($this->lessons->modules as $mod) {
            if ( isset($mod->lti) ) {
                foreach($mod->lti as $lti ) {
                    if ( $lti->resource_link_id == $resource_link_id) return $mod;
                }
            }
            if ( isset($mod->discussions) ) {
                foreach($mod->discussions as $discussion ) {
                    if ( $discussion->resource_link_id == $resource_link_id) return $mod;
                }
            }
            // Scan items array for LTI and discussion items
            if ( isset($mod->items) && is_array($mod->items) ) {
                foreach($mod->items as $item) {
                    $item_obj = is_array($item) ? (object)$item : $item;
                    if ( LessonsNormalize::isLtiLaunch($item_obj) && isset($item_obj->resource_link_id)
                        && $item_obj->resource_link_id == $resource_link_id ) {
                        return $mod;
                    }
                }
            }
        }
        return null;
    }

    public static function absolute_url_ref(&$url) {
        $url = trim($url);
        $url = self::expandLink($url);
        $base = self::lessonsCourseBaseUrl();
        $rewritten = CCFileBase::rewriteUrl($url, $base, 'expand', Tool::courseLocalPrefixes());
        if ( $rewritten !== $url ) {
            $url = $rewritten;
            return;
        }
        $url = U::absolute_url($url);
    }

    /**
     * Course mount for expanding /files and /pages hrefs (not site apphome alone).
     *
     * A site-root /files/download/{sha} link leaves /courses/{id} and
     * restoreSiteLoginContext() looks up the blob in the wrong course.
     *
     * @return string
     */
    private static function lessonsCourseBaseUrl() {
        global $CFG;
        $home = '';
        if ( isset($CFG->apphome) && is_string($CFG->apphome) && trim($CFG->apphome) !== '' ) {
            $home = $CFG->apphome;
        } else if ( isset($CFG->wwwroot) && is_string($CFG->wwwroot) && trim($CFG->wwwroot) !== '' ) {
            $home = $CFG->wwwroot;
        }
        $parent = Courses::toolPathPrefix();
        if ( $parent === '' ) {
            $parent = Tool::determineParentPath('/lessons');
        }
        return CCFileBase::courseBaseUrl($parent, $home);
    }

    /*
     * Do macro substitution on a link
     */
    public static function expandLink($url) {
        global $CFG;
        
        $search = array(
            "{apphome}",
            "{wwwroot}",
        );
        $replace = array(
            $CFG->apphome,
            $CFG->wwwroot,
        );
        $url = str_replace($search, $replace, $url);

        return $url;
    }

    /**
     * Whether this lessons LTI item is graded (counts toward rollups, assignments list, badges).
     * JSON "result": false means ungraded; if result is omitted, the launch is treated as graded.
     *
     * @param object|null $lti_item Item with type lti, or legacy module lti object
     * @return bool true if graded; false if explicitly ungraded
     */
    public static function ltiLaunchIsGraded($lti_item) {
        if ( $lti_item === null || ! is_object($lti_item) ) {
            return true;
        }
        if ( ! property_exists($lti_item, 'result') ) {
            return true;
        }
        return $lti_item->result !== false;
    }

    /**
     * LTI assignment totals for module progress (items array, else legacy lti).
     * When $duedates_for_display is non-empty ({@see GradeUtil::loadDueDatesForDisplay}), only
     * resource links with a non-empty end_datetime in the current context are counted (due-date mode).
     * When it is empty (no course due dates, or the learner has hidden due dates), every graded LTI
     * launch in the module counts so percent-complete badges still work.
     * LTI entries with "result": false are excluded from points, actuals, and rollup rlids.
     *
     * @param array<string,array<string,mixed>> $duedates_for_display
     * @return array{0:float,1:float,2:string[]} possible points, actual points, resource_link_ids
     */
    public function moduleLtiProgressPoints($module, $allgrades, $duedates_for_display) {
        $possible = 0.0;
        $actual = 0.0;
        $rlids = array();
        $require_scheduled = ($duedates_for_display !== array());
        if ( isset($module->items) ) {
            foreach ( $module->items as $item ) {
                if ( ! LessonsNormalize::isAssignmentLti($item) ) {
                    continue;
                }
                if ( ! self::ltiLaunchIsGraded($item) ) {
                    continue;
                }
                if ( $require_scheduled && ! $this->resourceLinkHasDueDateInContext($item->resource_link_id, $duedates_for_display) ) {
                    continue;
                }
                $possible += 1.0;
                $rlids[] = $item->resource_link_id;
                if ( isset($allgrades[$item->resource_link_id]) && is_numeric($allgrades[$item->resource_link_id]) ) {
                    $actual += $allgrades[$item->resource_link_id];
                }
            }
        } elseif ( isset($module->lti) ) {
            $ltis = $module->lti;
            if ( ! is_array($ltis) ) {
                $ltis = array($ltis);
            }
            foreach ( $ltis as $lti ) {
                if ( ! isset($lti->resource_link_id) ) {
                    continue;
                }
                if ( ! self::ltiLaunchIsGraded($lti) ) {
                    continue;
                }
                if ( $require_scheduled && ! $this->resourceLinkHasDueDateInContext($lti->resource_link_id, $duedates_for_display) ) {
                    continue;
                }
                $possible += 1.0;
                $rlids[] = $lti->resource_link_id;
                if ( isset($allgrades[$lti->resource_link_id]) && is_numeric($allgrades[$lti->resource_link_id]) ) {
                    $actual += $allgrades[$lti->resource_link_id];
                }
            }
        }
        return array($possible, $actual, $rlids);
    }

    /**
     * True when this resource link has end_datetime set for the current context (matches due badge rows).
     *
     * @param array<string,array<string,mixed>> $duedates
     */
    private function resourceLinkHasDueDateInContext($rlid, $duedates) {
        if ( $rlid === '' || $rlid === null ) {
            return false;
        }
        if ( ! isset($duedates[$rlid]) || ! is_array($duedates[$rlid]) ) {
            return false;
        }
        $end = U::get($duedates[$rlid], 'end_datetime');
        return U::isNotEmpty($end);
    }

    /**
     * True if any LTI in this module has end_datetime set in the current context.
     *
     * @param string[] $rlids
     * @param array<string,array<string,mixed>> $duedates
     */
    public function moduleHasLtiDueDateInContext($rlids, $duedates) {
        foreach ( $rlids as $rlid ) {
            if ( $this->resourceLinkHasDueDateInContext($rlid, $duedates) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Higher = worse urgency for module rollup (matches per-assignment badge modifiers).
     */
    private function dueModifierWorstRank($mod) {
        switch ( $mod ) {
            case 'tsugi-assignments-due-past':
                return 40;
            case 'tsugi-assignments-due-soon':
                return 30;
            case 'tsugi-assignments-due-neutral':
                return 25;
            case 'tsugi-assignments-due-future':
                return 20;
            case 'tsugi-assignments-due-completed':
                return 10;
            default:
                return 0;
        }
    }

    /**
     * Worst due state among module LTI items that have an end_datetime (tie: earlier due wins).
     *
     * @param string[] $rlids
     * @param array<string,float> $allgrades
     * @param array<string,array<string,mixed>> $duedates
     * @return array{modifier:string,end:string,date_disp:string}|null
     */
    public function moduleWorstDueRollup($rlids, $allgrades, $duedates) {
        $best = null;
        foreach ( $rlids as $rlid ) {
            if ( ! isset($duedates[$rlid]) || ! is_array($duedates[$rlid]) ) {
                continue;
            }
            $end = U::get($duedates[$rlid], 'end_datetime');
            if ( ! U::isNotEmpty($end) ) {
                continue;
            }
            $mod = self::assignmentsDueBadgeModifier($rlid, $end, $allgrades);
            $rank = $this->dueModifierWorstRank($mod);
            $dueTs = strtotime($end);
            if ( $best === null || $rank > $best['rank']
                || ( $rank === $best['rank'] && $dueTs !== false && $best['due_ts'] !== false
                    && $dueTs < $best['due_ts'] ) ) {
                $t = $dueTs;
                $dateDisp = $t ? date('M j, Y', $t) : $end;
                $best = array(
                    'modifier' => $mod,
                    'end' => $end,
                    'date_disp' => $dateDisp,
                    'rank' => $rank,
                    'due_ts' => $dueTs,
                );
            }
        }
        if ( $best === null ) {
            return null;
        }
        return array(
            'modifier' => $best['modifier'],
            'end' => $best['end'],
            'date_disp' => $best['date_disp'],
        );
    }

    /**
     * First $count letters from $resource_link_id (ignoring non-letters; lowercase).
     */
    public static function resourceLinkIdLetterPrefix($resource_link_id, $count = 2) {
        $letters = '';
        $s = (string) $resource_link_id;
        $len = strlen($s);
        for ( $i = 0; $i < $len && strlen($letters) < $count; $i++ ) {
            $c = $s[$i];
            if ( ctype_alpha($c) ) {
                $letters .= strtolower($c);
            }
        }
        if ( strlen($letters) < $count ) {
            $letters = str_pad($letters, $count, 'x');
        }
        return substr($letters, 0, $count);
    }

    /**
     * Per-result signature for assignments: py345_61 from rlid + lti_link.link_id.
     */
    public static function resultLinkSignature($resource_link_id, $link_id) {
        $prefix = self::resourceLinkIdLetterPrefix($resource_link_id, 2);
        $mod = ((int) $link_id) % 1000;
        $plaintext = $prefix . sprintf('%03d', $mod) . '_42';
        $body = preg_replace('/_42$/', '', $plaintext);
        return $body . '_' . substr(md5($plaintext), 0, 2);
    }

    /**
     * Whether to show the result signature on a grade book row.
     *
     * @param float|int|string|null $grade Raw grade (0.0–1.0)
     */
    public static function shouldShowGradesResultSignature($resource_link_id, $grade, $is_instructor) {
        if ( $is_instructor ) {
            return true;
        }
        if ( $resource_link_id === '' || $resource_link_id === null ) {
            return false;
        }
        return is_numeric($grade) && (float) $grade > 0.8;
    }

    /**
     * Whether to show the per-result signature on an assignments row (instructors only).
     *
     * @param array<string,float> $allgrades
     * @param array<string,int> $alllinkids
     */
    public static function shouldShowAssignmentResultSignature($resource_link_id, $allgrades, $alllinkids, $is_instructor) {
        return (bool) $is_instructor;
    }

    /**
     * Stable DOM id for an LTI resource link (assignments page jump targets → module list).
     *
     * @param string $resource_link_id
     * @return string HTML id attribute value
     */
    public static function domIdForResourceLink($resource_link_id) {
        $s = (string) $resource_link_id;
        if ( $s === '' ) {
            return 'tsugi-rl-empty';
        }
        $slug = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $s);
        $slug = trim($slug, '-');
        if ( $slug === '' || strlen($slug) > 96 ) {
            $slug = substr(sha1($s), 0, 16);
        }
        return 'tsugi-rl-' . $slug;
    }

    public static function assignmentsDueBadgeModifier($resource_link_id, $end, $allgrades) {
        $completed = isset($allgrades[$resource_link_id]) && $allgrades[$resource_link_id] > 0.8;
        if ( $completed ) {
            return 'tsugi-assignments-due-completed';
        }
        $dueTs = strtotime($end);
        if ( $dueTs === false ) {
            return 'tsugi-assignments-due-neutral';
        }
        $now = time();
        if ( $now > $dueTs ) {
            return 'tsugi-assignments-due-past';
        }
        $sevenDays = 7 * 86400;
        if ( ($dueTs - $now) <= $sevenDays ) {
            return 'tsugi-assignments-due-soon';
        }
        return 'tsugi-assignments-due-future';
    }

    /**
     * LTI assignment rows for the same modules shown on the assignments list (items array or legacy lti).
     *
     * @param bool $for_due_date_management When true, omit items with "result": false (ungraded launches are not managed for due dates).
     * @return array[] Each element: module_index, module_title, module_anchor, item_title, resource_link_id, participates_in_grades (same as ltiLaunchIsGraded())
     */
    public function enumerateLtiAssignmentItems($for_due_date_management = false) {
        $list = array();
        foreach ( $this->lessons->modules as $modIndex => $module ) {
            if ( isset($module->items) ) {
                foreach ( $module->items as $item ) {
                    if ( ! LessonsNormalize::isAssignmentLti($item) ) {
                        continue;
                    }
                    if ( $for_due_date_management && ! self::ltiLaunchIsGraded($item) ) {
                        continue;
                    }
                    $list[] = array(
                        'module_index' => (int) $modIndex,
                        'module_title' => $module->title,
                        'module_anchor' => isset($module->anchor) ? $module->anchor : '',
                        'item_title' => isset($item->title) ? $item->title : (isset($item->text) ? $item->text : 'Assignment'),
                        'resource_link_id' => $item->resource_link_id,
                        'participates_in_grades' => self::ltiLaunchIsGraded($item),
                    );
                }
            } else if ( isset($module->lti) ) {
                $ltis = $module->lti;
                if ( ! is_array($ltis) ) {
                    $ltis = array($ltis);
                }
                foreach ( $ltis as $lti ) {
                    if ( ! isset($lti->resource_link_id) ) {
                        continue;
                    }
                    if ( $for_due_date_management && ! self::ltiLaunchIsGraded($lti) ) {
                        continue;
                    }
                    $list[] = array(
                        'module_index' => (int) $modIndex,
                        'module_title' => $module->title,
                        'module_anchor' => isset($module->anchor) ? $module->anchor : '',
                        'item_title' => $lti->title,
                        'resource_link_id' => $lti->resource_link_id,
                        'participates_in_grades' => self::ltiLaunchIsGraded($lti),
                    );
                }
            }
        }
        return $list;
    }

    public static function makeUrlResource($type,$title,$url) {
        global $CFG;
       $RESOURCE_ICONS = array(
                'video' => 'fa-video-camera',
                'slides' => 'fa-file-powerpoint-o',
                'assignment' => 'fa-lock',
                'solution' => 'fa-unlock',
                'reference' => 'fa-external-link'
        );
        $retval = new \stdClass();
        $retval->type = $type;
        if ( isset($RESOURCE_ICONS[$type]) ) {
            $retval->icon = $RESOURCE_ICONS[$type];
        } else {
            $retval->icon = 'fa-external-link';
        }
        $retval->thumbnail = $CFG->fontawesome.'/png/'.str_replace('fa-','',$retval->icon).'.png';

        if ( strpos($title,':') !== false ) {
            $retval->title = $title;
        } else {
            $retval->title = ucwords($type) . ': ' . $title;
        }
        $retval->url = $url;
        if ( is_string($url) && $url !== '' && in_array($type, array('slides', 'reference', 'assignment', 'solution'), true) ) {
            if ( self::urlLooksLikePdf(self::expandLink($url)) ) {
                $retval->icon = 'fa-file-pdf-o';
                $retval->thumbnail = $CFG->fontawesome.'/png/'.str_replace('fa-','',$retval->icon).'.png';
            }
        }
        return $retval;
    }

/* After PHP 5.6
    const RESOURCE_ICONS = array(
        'video' => 'fa-video-camera',
        'slides' => 'fa-file-powerpoint-o',
        'assignment' => 'fa-lock',
        'solution' => 'fa-unlock',
        'reference' => 'fa-external-link'
    );
*/

    public static function getUrlResources($module) {
        $resources = array();
        // Items array takes precedence - process items first
        if ( isset($module->items) ) {
            foreach($module->items as $item) {
                $kind = LessonsNormalize::presentationKind($item);
                $href = isset($item->href) ? $item->href : (isset($item->url) ? $item->url : '');
                if ( $kind == 'video' ) {
                    $vurl = self::videoUrlForItem($item);
                    if ( $vurl ) {
                        $title = isset($item->title) ? $item->title : (isset($item->text) ? $item->text : 'Video');
                        $resources[] = self::makeUrlResource('video', $title, $vurl);
                    }
                } else if ( $kind == 'slide' && $href !== '' ) {
                    $title = isset($item->title) ? $item->title : (isset($item->text) ? $item->text : __('Slides').': '.$module->title);
                    $resources[] = self::makeUrlResource('slides', $title, $href);
                } else if ( $kind == 'assignment' && $href !== '' ) {
                    $title = isset($item->title) ? $item->title : (isset($item->text) ? $item->text : 'Assignment Specification');
                    $resources[] = self::makeUrlResource('assignment', $title, $href);
                } else if ( $kind == 'solution' && $href !== '' ) {
                    $title = isset($item->title) ? $item->title : (isset($item->text) ? $item->text : 'Assignment Solution');
                    $resources[] = self::makeUrlResource('solution', $title, $href);
                } else if ( $kind == 'reference' && $href !== '' ) {
                    $title = isset($item->title) ? $item->title : (isset($item->text) ? $item->text : 'Reference');
                    $resources[] = self::makeUrlResource('reference', $title, $href);
                }
            }
        } else {
            // Process legacy arrays only if items is not present
            if ( isset($module->carousel) ) {
                foreach($module->carousel as $carousel ) {
                    $vurl = self::videoUrlForItem($carousel);
                    if ( $vurl ) {
                        $resources[] = self::makeUrlResource('video', $carousel->title, $vurl);
                    }
                }
            }
            if ( isset($module->videos) ) {
                foreach($module->videos as $video ) {
                    $vurl = self::videoUrlForItem($video);
                    if ( $vurl ) {
                        $resources[] = self::makeUrlResource('video', $video->title, $vurl);
                    }
                }
            }
            if ( isset($module->slides) ) {
                $resources[] = self::makeUrlResource('slides',__('Slides').': '.$module->title, $module->slides);
            }
            if ( isset($module->assignment) ) {
                $resources[] = self::makeUrlResource('assignment','Assignment Specification', $module->assignment);
            }
            if ( isset($module->solution) ) {
                $resources[] = self::makeUrlResource('solution','Assignment Solution', $module->solution);
            }
            if ( isset($module->references) ) {
                foreach($module->references as $reference ) {
                    if ( !isset($reference->title) || ! isset($reference->href) ) continue;
                    $resources[] = self::makeUrlResource('reference',$reference->title, $reference->href);
                }
            }
        }
        return $resources;
    }

    /**
     * Catalog discussions: top-level, then module items, then optional discussion_order.
     *
     * @return array<int, object>
     */
    public function flattenedDiscussions() {
        $discussions = array();
        if (isset($this->lessons->discussions) ) {
            foreach($this->lessons->discussions as $discussion) {
                $discussions [] = $discussion;
            }
        }

        foreach($this->lessons->modules as $module) {
            if ( isset($module->hidden) && $module->hidden ) continue;

            $has_items = isset($module->items) && is_array($module->items) && count($module->items) > 0;

            if ( $has_items ) {
                foreach($module->items as $item) {
                    $item_obj = is_array($item) ? (object)$item : $item;
                    if ( LessonsNormalize::isDiscussion($item_obj) ) {
                        $discussions [] = $item_obj;
                    }
                }
            } else {
                if ( isset($module->discussions) && is_array($module->discussions) ) {
                    foreach($module->discussions as $discussion) {
                        $discussions [] = $discussion;
                    }
                }
            }
        }

        if ( isset($this->lessons->discussion_order) && is_array($this->lessons->discussion_order) ) {
            $discussions = self::applyDiscussionOrder($discussions, $this->lessons->discussion_order);
        }
        return $discussions;
    }

    /**
     * Sort flattened discussion objects by a resource_link_id order list.
     *
     * @param array<int, object> $discussions
     * @param array<int, mixed> $order
     * @return array<int, object>
     */
    public static function applyDiscussionOrder($discussions, $order) {
        if ( ! is_array($discussions) || ! is_array($order) ) {
            return is_array($discussions) ? $discussions : array();
        }
        $by = array();
        $rest = array();
        foreach ( $discussions as $d ) {
            $rid = '';
            if ( is_object($d) && isset($d->resource_link_id) ) {
                $rid = (string) $d->resource_link_id;
            }
            if ( $rid !== '' && ! isset($by[$rid]) ) {
                $by[$rid] = $d;
            } else {
                $rest[] = $d;
            }
        }
        $out = array();
        foreach ( $order as $rid ) {
            $rid = is_string($rid) || is_int($rid) ? (string) $rid : '';
            if ( $rid !== '' && isset($by[$rid]) ) {
                $out[] = $by[$rid];
                unset($by[$rid]);
            }
        }
        foreach ( $by as $d ) {
            $out[] = $d;
        }
        foreach ( $rest as $d ) {
            $out[] = $d;
        }
        return $out;
    }

    /**
     * Check if a setting value is in a resource in a Lesson
     *
     * This solves the problems that (a) most LMS systems do not handle
     * custom well for Common Cartridge Imports and (b) some systems
     * do not handle custom at all when links are installed via
     * ContentItem.  Canvas has this problem for sure and others might
     * as well.
     *
     * The solution is to add the resource link from the Lesson as a GET
     * parameter on the launchurl URL to be a fallback:
     *
     * https://../mod/zap/?inherit=assn03
     *
     * Say the tool has custom key of "exercise" that it wants a default
     * for when the tool has not yet been configured.  First we check
     * if the LMS sent us a custom parameter and use it if present.
     *
     * If not, load up the LTI launch for the resource link id (assn03)
     * in the above example and see if there is a custom parameter set
     * in that launch and assume it was passed to us.
     *
     * Sample call:
     *
     *     $assn = Settings::linkGet('exercise');
     *     if ( ! $assn || ! isset($assignments[$assn]) ) {
     *         $rlid = isset($_GET['inherit']) ? $_GET['inherit'] : false;
     *         if ( $rlid && isset($CFG->lessons) ) {
     *             $l = new LessonsService($CFG->lessons);
     *             $assn = $l->getCustomWithInherit($rlid, 'exercise');
     *         } else {
     *             $assn = LTIX::ltiCustomGet('exercise');
     *         }
     *         Settings::linkSet('exercise', $assn);
     *     }
     *
     */
    public function getCustomWithInherit($key, $rlid=false) {
        global $CFG;

        $custom = LTIX::ltiCustomGet($key);
        if ( U::strlen($custom) > 0 ) return $custom;

        if ( $rlid === false ) return false;
        $lti = $this->getLtiByRlid($rlid);
        if ( isset($lti->custom) ) foreach($lti->custom as $custom ) {
            if (isset($custom->key) && isset($custom->value) && $custom->key == $key ) {
                return $custom->value;
            }
        }
        return false;
    }

    /**
     * Whether a URL path ends in .pdf (after expandLink-friendly strings).
     */
    public static function urlLooksLikePdf($url) {
        if ( ! is_string($url) || $url === '' ) {
            return false;
        }
        $path = parse_url($url, PHP_URL_PATH);
        if ( $path === null || $path === '' || $path === false ) {
            $path = $url;
        }
        return (bool) preg_match('/\.pdf$/i', $path);
    }

    /**
     * Expand a Kaltura URL template extension with lessons.json kaltura_id.
     * The template must include a literal {id} placeholder.
     *
     * @return string|null Absolute URL, or null if unavailable
     */
    public static function kalturaUrlFromExtension($item, $extension_key) {
        global $CFG;
        $kaltura_id = isset($item->kaltura_id) ? $item->kaltura_id : null;
        $template = $CFG->getExtension($extension_key, null);
        if ( !is_string($kaltura_id) || $kaltura_id === '' ) {
            return null;
        }
        if ( !is_string($template) || $template === '' || strpos($template, '{id}') === false ) {
            return null;
        }
        return str_replace('{id}', $kaltura_id, $template);
    }

    /**
     * Build a Kaltura iframe embed URL (kaltura_embed extension).
     *
     * @return string|null Absolute embed URL, or null if unavailable
     */
    public static function kalturaEmbedUrl($item) {
        return self::kalturaUrlFromExtension($item, 'kaltura_embed');
    }

    /**
     * Build a Kaltura "open in new tab" URL (kaltura_tab playlist template).
     * Falls back to the embed URL when kaltura_tab is not set.
     *
     * @return string|null Absolute tab URL, or null if unavailable
     */
    public static function kalturaTabUrl($item) {
        $tab = self::kalturaUrlFromExtension($item, 'kaltura_tab');
        if ( $tab !== null ) {
            return $tab;
        }
        return self::kalturaEmbedUrl($item);
    }

    /**
     * Preferred public URL for a lesson video item.
     * Prefers Kaltura embed when kaltura_embed + kaltura_id are available.
     *
     * @return string|null
     */
    public static function videoUrlForItem($item) {
        $kaltura = self::kalturaEmbedUrl($item);
        if ( $kaltura ) {
            return $kaltura;
        }
        $youtube = isset($item->youtube) ? $item->youtube : null;
        if ( is_string($youtube) && $youtube !== '' ) {
            return U::youtubeWatchUrl($youtube);
        }
        return null;
    }

    /**
     * @return bool
     */
    public function quiz1ExistsInCourse($quiz_id) {
        $quiz_id = (int) $quiz_id;
        if ( $quiz_id < 1 ) {
            return false;
        }
        $this->loadQuiz1Publication();
        return isset($this->quiz1IdSet[$quiz_id]);
    }

    /**
     * True when the quiz has a published launch link. Never-published and unpublished are false.
     *
     * @return bool
     */
    public function quiz1IsPublished($quiz_id) {
        $quiz_id = (int) $quiz_id;
        if ( $quiz_id < 1 ) {
            return false;
        }
        $this->loadQuiz1Publication();
        $row = $this->quiz1IdSet[$quiz_id] ?? null;
        return is_array($row) && ! empty($row['published']);
    }

    /**
     * Resource link id for a published quiz, or 0.
     *
     * @return int
     */
    public function quiz1LinkId($quiz_id) {
        $quiz_id = (int) $quiz_id;
        if ( $quiz_id < 1 ) {
            return 0;
        }
        $this->loadQuiz1Publication();
        $row = $this->quiz1IdSet[$quiz_id] ?? null;
        if ( ! is_array($row) || empty($row['published']) ) {
            return 0;
        }
        return (int) ($row['link_id'] ?? 0);
    }

    private function loadQuiz1Publication() {
        if ( $this->quiz1IdSet !== null ) {
            return;
        }
        $this->quiz1IdSet = array();
        $context_id = U::currentContextId();
        if ( $context_id < 1 ) {
            return;
        }
        try {
            foreach ( Quiz1Repository::listForContext($context_id) as $quiz ) {
                $this->quiz1IdSet[(int) $quiz->id] = array(
                    'published' => ((int) $quiz->published) === 1,
                    'link_id' => (int) $quiz->link_id,
                );
            }
        } catch ( \Exception $e ) {
            $this->quiz1IdSet = array();
        }
    }

    /**
     * @return bool
     */
    public function lessonsViewerIsInstructor() {
        if ( $this->lessonsViewerIsInstructor !== null ) {
            return $this->lessonsViewerIsInstructor;
        }
        $this->lessonsViewerIsInstructor = false;
        $context_id = U::currentContextId();
        $user_id = U::loggedInUserId();
        if ( $context_id && $user_id ) {
            if ( isset($_SESSION['admin']) && $_SESSION['admin'] == 'yes' ) {
                $this->lessonsViewerIsInstructor = true;
            } else {
                $m = Membership::ensureInSession($context_id, $user_id);
                $this->lessonsViewerIsInstructor = $m && $m->isInstructor();
            }
        }
        return $this->lessonsViewerIsInstructor;
    }

}

