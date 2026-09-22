<?php


namespace Tsugi\UI;

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

class Lessons {

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

    /** @var array<int,bool>|null Quiz1 ids in the current course; lazy. */
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
     * CSS for module cards, LTI progress/due badges (lessons and labs catalog).
     */
    public static function printLtiProgressStyles() {
        echo('<style>
.card {
    display: inline-block;
    padding: 0.5em;
    margin: 12px;
    border: 1px solid black;
    height: 9em;
    overflow-y: hidden;
}
.card div {
    height: 8em;
    overflow-y: hidden;
    text-overflow: ellipsis;
}
.progress-badge {
    display: inline-block;
    margin-left: 0.35em;
    vertical-align: middle;
    font-size: 0.75em;
    line-height: 1.2;
}
.progress-badge-check {
    background-color: #28a745;
    color: #fff;
    padding: 0.15em 0.45em;
    border-radius: 0.25em;
    font-weight: bold;
}
.progress-badge-percent {
    background-color: #007bff;
    color: #fff;
    padding: 0.15em 0.45em;
    border-radius: 0.25em;
    font-weight: bold;
}
.progress-badge-not-started {
    background-color: #e9ecef;
    color: #495057;
    padding: 0.15em 0.45em;
    border-radius: 0.25em;
    font-weight: normal;
}
.tsugi-lti-link-meta {
    display: inline;
    margin-left: 0.25em;
    white-space: nowrap;
}
.tsugi-assignments-due-badge {
    display: inline-block;
    margin-left: 0.35em;
    padding: 0.12em 0.45em;
    border-radius: 0.25em;
    font-size: 0.75em;
    line-height: 1.2;
    vertical-align: middle;
    border: 1px solid transparent;
}
.tsugi-assignments-due-completed {
    background: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}
.tsugi-assignments-due-past {
    background: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}
.tsugi-assignments-due-soon {
    background: #fff3cd;
    color: #856404;
    border-color: #ffeeba;
}
.tsugi-assignments-due-future {
    background: #e9ecef;
    color: #495057;
    border-color: #dee2e6;
}
.tsugi-assignments-due-neutral {
    background: #f8f9fa;
    color: #6c757d;
    border-color: #dee2e6;
}
.tsugi-assignments-due-state {
    font-weight: bold;
}
.tsugi-assignments-due-detail {
    font-weight: normal;
}
.tsugi-assignments-rl-sig-sep {
    color: #767676;
    margin: 0 0.25em;
    font-weight: normal;
}
.tsugi-assignments-rl-sig {
    font-family: monospace;
    font-size: 0.9em;
    color: #343a40;
    font-weight: 500;
}
.tsugi-link-modal-content {
    background-color: #fff;
    width: 90%;
    max-width: 1100px;
    height: calc(100vh - 80px);
    display: flex;
    flex-direction: column;
    text-align: left;
}
.tsugi-link-modal-titlebar {
    display: flex;
    align-items: center;
    gap: 0.75em;
    padding: 0.5em 2.75em 0.5em 0.75em;
    border-bottom: 1px solid #ddd;
    background: #f5f5f5;
    position: relative;
}
.tsugi-link-modal-title {
    flex: 1 1 auto;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.tsugi-link-modal-open-new {
    flex: 0 0 auto;
    font-size: 0.85em;
    white-space: nowrap;
}
.tsugi-link-modal-close {
    position: absolute;
    top: 50%;
    right: 10px;
    transform: translateY(-50%);
    background: rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.2);
    color: #333;
}
.tsugi-link-modal-close:hover {
    background: rgba(0, 0, 0, 0.15);
}
.tsugi-link-modal-frame {
    flex: 1 1 auto;
    width: 100%;
    border: 0;
    background: #fff;
}
</style>'."\n");
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
     * @return Lessons|string Lessons on success, error message on failure
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
     * Build a Lessons object from a JSON string (file or manifest document).
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

    /*
     * A Nostyle URL Link with title
     */
    public static function nostyleUrl($title, $url) {
        $url = self::expandLink($url);
        echo('<a href="'.$url.'" target="_blank" rel="noopener noreferrer" typeof="oer:SupportingMaterial">'.htmlentities($url)."</a>\n");
        if ( isset($_SESSION['gc_count']) ) {
            echo('<div class="g-sharetoclassroom" data-size="16" data-url="'.$url.'" ');
	    echo(' data-title="'.htmlentities($title).'" ');
	    echo('></div>');
        }
    }

    /*
     * A Nostyle URL Link with title as the href text
     */
    public static function nostyleLink($title, $url) {
        $url = self::expandLink($url);
        echo('<a href="'.$url.'" target="_blank" rel="noopener noreferrer" class="tsugi-lessons-link" typeof="oer:SupportingMaterial">'.htmlentities($title)."</a>\n");
        if ( isset($_SESSION['gc_count']) ) {
            echo('<div class="g-sharetoclassroom" data-size="16" data-url="'.$url.'" ');
	    echo(' data-title="'.htmlentities($title).'" ');
	    echo('></div>');
        }
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
     * HTML for the result signature (optional separator + monospace code).
     */
    public static function resultLinkSignatureMarkup($resource_link_id, $link_id, $with_separator = true) {
        $rl_sig = self::resultLinkSignature($resource_link_id, $link_id);
        $sig_lbl = htmlspecialchars(__('Result signature').': '.$rl_sig, ENT_QUOTES, 'UTF-8');
        $sig = '<span class="tsugi-assignments-rl-sig" title="'.$sig_lbl.'" aria-label="'.$sig_lbl.'">'
            . htmlspecialchars($rl_sig)
            . '</span>';
        if ( $with_separator ) {
            return ' <span class="tsugi-assignments-rl-sig-sep" aria-hidden="true">|</span> ' . $sig;
        }
        return $sig;
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
     * Grade percent badge for one graded LTI link (0–100%).
     *
     * @param array<string,float> $allgrades
     */
    public static function echoLtiGradePercentBadge($resource_link_id, $allgrades) {
        if ( $resource_link_id === '' || $resource_link_id === null ) {
            return;
        }
        if ( ! isset($allgrades[$resource_link_id]) || ! is_numeric($allgrades[$resource_link_id]) ) {
            return;
        }
        $grade = (float) $allgrades[$resource_link_id];
        $pct = (int) round($grade * 100);
        if ( $grade > 0.8 ) {
            echo('<span class="progress-badge progress-badge-check" title="'.htmlspecialchars(__('Complete').': 100%', ENT_QUOTES, 'UTF-8').'">100%</span>');
        } elseif ( $pct > 0 ) {
            $tip = __('Score').': '.$pct.'%';
            echo('<span class="progress-badge progress-badge-percent" title="'.htmlspecialchars($tip, ENT_QUOTES, 'UTF-8').'">'.$pct.'%</span>');
        }
    }

    /**
     * Due date, score percent, and not-started indicators for a graded LTI link (lessons + labs).
     *
     * @param object|null $lti_item lessons.json LTI item
     * @param array<string,float> $allgrades
     * @param array<string,array<string,mixed>> $duedates
     */
    public static function echoLtiLinkProgressIndicators($resource_link_id, $lti_item, $allgrades, $duedates) {
        if ( ! self::ltiLaunchIsGraded($lti_item) ) {
            return;
        }
        if ( $resource_link_id === '' || $resource_link_id === null ) {
            return;
        }

        echo('<span class="tsugi-lti-link-meta">');

        $has_due_badge = false;
        $due_end = null;
        if ( isset($duedates[$resource_link_id]) && is_array($duedates[$resource_link_id]) ) {
            $due_end = U::get($duedates[$resource_link_id], 'end_datetime');
            if ( U::isNotEmpty($due_end) ) {
                $has_due_badge = true;
            }
        }

        if ( $has_due_badge ) {
            self::echoDueDateBadgeForResourceLink($resource_link_id, $allgrades, $duedates, true);
            $mod = self::assignmentsDueBadgeModifier($resource_link_id, $due_end, $allgrades);
            if ( $mod === 'tsugi-assignments-due-completed' ) {
                echo('</span>');
                return;
            }
        }

        if ( isset($allgrades[$resource_link_id]) && is_numeric($allgrades[$resource_link_id]) ) {
            self::echoLtiGradePercentBadge($resource_link_id, $allgrades);
        } elseif ( ! $has_due_badge ) {
            echo('<span class="progress-badge progress-badge-not-started" title="'.htmlspecialchars(__('Not started'), ENT_QUOTES, 'UTF-8').'">');
            echo(htmlspecialchars(__('Not started')));
            echo('</span>');
        }

        echo('</span>');
    }

    /**
     * Due badge markup for one resource link (assignments page or module LTI line).
     *
     * @param array<string,array<string,mixed>> $duedates
     * @param array<string,float> $allgrades
     * @param bool $grades_affect_completion When false (ungraded LTI), due badge ignores stored grade for completed styling
     */
    public static function echoDueDateBadgeForResourceLink($resource_link_id, $allgrades, $duedates, $grades_affect_completion = true) {
        if ( $resource_link_id === '' || $resource_link_id === null ) {
            return;
        }
        if ( ! isset($duedates[$resource_link_id]) || ! is_array($duedates[$resource_link_id]) ) {
            return;
        }
        $end = U::get($duedates[$resource_link_id], 'end_datetime');
        if ( ! U::isNotEmpty($end) ) {
            return;
        }
        $t = strtotime($end);
        $dateDisp = $t ? date('M j, Y', $t) : $end;
        $grades_for_mod = $allgrades;
        if ( ! $grades_affect_completion ) {
            unset($grades_for_mod[$resource_link_id]);
        }
        $mod = self::assignmentsDueBadgeModifier($resource_link_id, $end, $grades_for_mod);
        $stateText = self::assignmentsDueStateVisibleLabel($mod);
        echo('<span class="tsugi-assignments-due tsugi-assignments-due-badge '.$mod.'">');
        echo('<span class="tsugi-assignments-due-state">'.htmlspecialchars($stateText).'</span>');
        echo(' <span class="tsugi-assignments-due-detail"><span class="tsugi-assignments-due-lbl">'.__('Due').'</span> ');
        echo(htmlspecialchars($dateDisp).'</span>');
        echo('</span>');
    }

    /**
     * Short visible label for due badge state (must not rely on color alone for accessibility).
     */
    public static function assignmentsDueStateVisibleLabel($mod) {
        switch ( $mod ) {
            case 'tsugi-assignments-due-completed':
                return __('Completed');
            case 'tsugi-assignments-due-past':
                return __('Late');
            case 'tsugi-assignments-due-soon':
                return __('Up next');
            case 'tsugi-assignments-due-future':
                return __('Upcoming');
            default:
                return __('Due date');
        }
    }

    /**
     * CSS modifier for due-date badge on assignments list (completed / past / soon / future).
     */
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
     *             $l = new Lessons($CFG->lessons);
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
    private static function urlLooksLikePdf($url) {
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
     * Get icon class for an item type
     */
    private static function getItemTypeIcon($type, $url_for_icon = null) {
        $pdf_types = array('slide', 'slides', 'reference', 'assignment', 'solution',
            'web_link', 'file', 'html_page', 'pdf');
        if ( $url_for_icon !== null && self::urlLooksLikePdf($url_for_icon)
            && in_array($type, $pdf_types, true) ) {
            return 'fa-file-pdf-o';
        }
        $icons = array(
            'video' => 'fa-play-circle',
            'reference' => 'fa-external-link',
            'discussion' => 'fa-comments',
            'lti' => 'fa-puzzle-piece',
            'quiz' => 'fa-puzzle-piece',
            'quiz1' => 'fa-check-square-o',
            'autograder' => 'fa-puzzle-piece',
            'peer_grade' => 'fa-puzzle-piece',
            'assignment' => 'fa-file-text',
            'slide' => 'fa-file-powerpoint-o',
            'slides' => 'fa-file-powerpoint-o',
            'solution' => 'fa-unlock',
            'text' => 'fa-file-text-o',
            'header' => 'fa-header',
            'heading' => 'fa-header',
            'web_link' => 'fa-external-link',
            'html_page' => 'fa-file-text-o',
            'file' => 'fa-file-o',
            'pdf' => 'fa-file-pdf-o'
        );
        return isset($icons[$type]) ? $icons[$type] : 'fa-circle';
    }

    /**
     * Get background color for an item type icon
     */
    private static function getItemTypeColor($type, $url_for_icon = null) {
        $pdf_types = array('slide', 'slides', 'reference', 'assignment', 'solution',
            'web_link', 'file', 'html_page', 'pdf');
        if ( $url_for_icon !== null && self::urlLooksLikePdf($url_for_icon)
            && in_array($type, $pdf_types, true) ) {
            return '#b30b00';
        }
        $colors = array(
            'video' => '#dc3545',
            'reference' => '#17a2b8',
            'discussion' => '#ffc107',
            'lti' => '#28a745',
            'quiz' => '#28a745',
            'quiz1' => '#20c997',
            'autograder' => '#28a745',
            'peer_grade' => '#28a745',
            'assignment' => '#fd7e14',
            'slide' => '#6f42c1',
            'slides' => '#6f42c1',
            'solution' => '#6c757d',
            'text' => '#6c757d',
            'header' => 'transparent',
            'heading' => 'transparent',
            'web_link' => '#17a2b8',
            'html_page' => '#fd7e14',
            'file' => '#6c757d',
            'pdf' => '#b30b00'
        );
        return isset($colors[$type]) ? $colors[$type] : '#6c757d';
    }

    /**
     * Render an icon for an item type with styling
     *
     * @param string $type item type key
     * @param string|null $url_for_icon expanded href; used to pick PDF icon for link-like types
     */
    private static function renderItemIcon($type, $url_for_icon = null) {
        $css_type = $type;
        if ( is_string($type) && preg_match('/^fa-[a-z0-9-]+$/', $type) ) {
            $icon = $type;
            $color = '#6c757d';
            $css_type = 'custom';
        } else {
            $icon = self::getItemTypeIcon($type, $url_for_icon);
            $color = self::getItemTypeColor($type, $url_for_icon);
        }
        $iconColor = ($type === 'discussion') ? '#333' : 'white';
        $pdf_types = array('slide', 'slides', 'reference', 'assignment', 'solution',
            'web_link', 'file', 'html_page', 'pdf');
        $pdf_class = ($url_for_icon !== null && self::urlLooksLikePdf($url_for_icon)
            && in_array($type, $pdf_types, true))
            ? ' tsugi-lessons-pdf-icon' : '';
        $css_type = preg_replace('/[^a-z0-9_-]/i', '', $css_type);
        $icon_attr = htmlspecialchars($icon, ENT_QUOTES, 'UTF-8');
        echo('<span class="tsugi-item-type-icon tsugi-item-type-'.$css_type.$pdf_class.'" style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 3px; font-size: 14px; background-color: '.$color.'; margin-right: 8px; vertical-align: middle;">');
        echo('<i class="fa '.$icon_attr.'" aria-hidden="true" style="color: '.$iconColor.';"></i>');
        echo('</span>');
    }

    /**
     * Render a single item from the items array
     */
    public function renderItem($item, $module, $nostyle=false) {
        global $CFG, $OUTPUT;
        
        if ( is_array($item) ) {
            $item = (object) $item;
        }
        if ( ! isset($item->type) ) {
            return; // Skip items without a type
        }
        $item = LessonsNormalize::normalizeItemObject($item);
        $type = $item->type;
        $kind = LessonsNormalize::presentationKind($item);
        
        switch($type) {
            case 'heading':
            case 'header':
                $this->renderItemHeader($item);
                break;
            case 'text':
                $this->renderItemText($item);
                break;
            case 'web_link':
            case 'html_page':
            case 'file':
                $this->renderCanonicalResource($item, $module, $kind, $nostyle);
                break;
            case 'lti':
                if ( $kind === 'discussion' ) {
                    $this->renderItemDiscussion($item, $module, $nostyle);
                } else {
                    $this->renderItemLti($item, $module, $nostyle);
                }
                break;
            case 'video':
                $this->renderItemVideo($item, $nostyle);
                break;
            case 'slide':
                $this->renderItemSlide($item, $nostyle);
                break;
            case 'reference':
                $this->renderItemReference($item, $nostyle);
                break;
            case 'discussion':
                $this->renderItemDiscussion($item, $module, $nostyle);
                break;
            case 'quiz':
                $this->renderItemQuiz1($item, $nostyle);
                break;
            // Legacy plural types - convert to singular and re-render (backward compatibility)
            case 'videos':
            case 'references':
            case 'discussions':
            case 'ltis':
            case 'slides':
                // Convert plural to singular and render items
                $singular_type = rtrim($type, 's'); // Remove trailing 's'
                if (isset($item->items) && is_array($item->items)) {
                    foreach($item->items as $subitem) {
                        $subitem_obj = is_array($subitem) ? (object)$subitem : $subitem;
                        if (!isset($subitem_obj->type)) $subitem_obj->type = $singular_type;
                        $this->renderItem($subitem_obj, $module, $nostyle);
                    }
                } else if ($type == 'slides' && (isset($item->href) || isset($item->url))) {
                    // Handle single slide object (legacy format)
                    $item->type = 'slide';
                    $this->renderItem($item, $module, $nostyle);
                }
                break;
            case 'assignment':
                $this->renderItemAssignment($item, $nostyle);
                break;
            case 'solution':
                $this->renderItemSolution($item, $nostyle);
                break;
            case 'chapters':
                $this->renderItemChapters($item);
                break;
            case 'carousel':
                $this->renderItemCarousel($item, $nostyle);
                break;
            default:
                // Unknown type, skip
                break;
        }
    }

    /**
     * Dispatch a normalized web_link / html_page / file to the matching presentation.
     */
    private function renderCanonicalResource($item, $module, $kind, $nostyle=false) {
        if ( $kind === 'video' ) {
            $this->renderItemVideo($item, $nostyle);
            return;
        }
        if ( $kind === 'slide' ) {
            $this->renderItemSlide($item, $nostyle);
            return;
        }
        if ( $kind === 'reference' ) {
            $this->renderItemReference($item, $nostyle);
            return;
        }
        if ( $kind === 'assignment' ) {
            $this->renderItemAssignment($item, $nostyle);
            return;
        }
        if ( $kind === 'solution' ) {
            $this->renderItemSolution($item, $nostyle);
            return;
        }
        $this->renderItemGenericLink($item, $kind, $nostyle);
    }

    /**
     * Allow local paths and http(s) only. Encode for an href attribute.
     */
    private static function safeWebHref($href) {
        if ( ! is_string($href) ) {
            return '';
        }
        $href = trim($href);
        if ( $href === '' || str_starts_with($href, '//') ) {
            return '';
        }
        if ( preg_match('/^([a-z][a-z0-9+.-]*):/i', $href, $m) ) {
            $scheme = strtolower($m[1]);
            if ( $scheme !== 'http' && $scheme !== 'https' ) {
                return '';
            }
        }
        return htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generic fallback for foundational file / web_link / html_page items.
     */
    private function renderItemGenericLink($item, $kind, $nostyle=false) {
        $title = isset($item->title) ? $item->title : (isset($item->text) ? $item->text : (isset($item->filename) ? $item->filename : ''));
        $href = isset($item->href) ? $item->href : (isset($item->url) ? $item->url : '');
        $href = self::expandLink($href);
        $css = $kind !== '' ? $kind : 'web_link';
        $icon_key = LessonsNormalize::iconKey($item);

        echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-'.$css.'">');
        if ( $nostyle ) {
            echo(htmlentities($title).':');
            self::nostyleUrl($title, $href);
        } else {
            $this->renderWebLinkOpenControl($item, $href, $title, $icon_key);
        }
        echo("</li>\n");
    }

    /**
     * How a web link should open. Legacy items with no target stay new-tab.
     *
     * @param mixed $item
     * @return 'self'|'blank'|'modal'
     */
    public static function webLinkOpenMode($item) {
        $target = '';
        if ( is_object($item) && isset($item->target) && is_string($item->target) ) {
            $target = $item->target;
        } else if ( is_array($item) && isset($item['target']) && is_string($item['target']) ) {
            $target = $item['target'];
        }
        if ( $target === '_self' ) {
            return 'self';
        }
        if ( $target === 'modal' ) {
            return 'modal';
        }
        return 'blank';
    }

    /**
     * Anchor target for a web link. Legacy items with no target stay new-tab.
     *
     * @param mixed $item
     * @return string
     */
    public static function webLinkTargetAttrs($item) {
        if ( self::webLinkOpenMode($item) === 'self' ) {
            return '';
        }
        if ( self::webLinkOpenMode($item) === 'modal' ) {
            return '';
        }
        return ' target="_blank" rel="noopener noreferrer"';
    }

    /**
     * Render a web link as same-page, new-tab, or in-page modal.
     *
     * @param string $href Expanded URL (not yet HTML-encoded)
     */
    private function renderWebLinkOpenControl($item, $href, $title, $icon_key, $css_class='tsugi-lessons-link') {
        $safe_href = self::safeWebHref($href);
        if ( self::webLinkOpenMode($item) === 'modal' && $safe_href !== '' ) {
            $this->renderWebLinkModal($item, $safe_href, $title, $icon_key, $css_class);
            return;
        }
        echo('<a href="'.$safe_href.'"'.self::webLinkTargetAttrs($item).' class="'.$css_class.'" typeof="oer:SupportingMaterial" style="display: inline-flex; align-items: center;">');
        if ( $icon_key !== null && $icon_key !== false ) {
            self::renderItemIcon($icon_key, $href);
        }
        echo(htmlentities($title).'</a>');
    }

    /**
     * In-page iframe overlay for target=modal web links.
     *
     * @param string $safe_href Already HTML-encoded href
     */
    private function renderWebLinkModal($item, $safe_href, $title, $icon_key, $css_class) {
        static $n = 0;
        $n++;
        $id = 'tsugi-link-modal-'.md5($n.'|'.$safe_href);
        $title_esc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $open_lbl = htmlspecialchars(__('Open in a new window'), ENT_QUOTES, 'UTF-8');
?>
<div id="<?= $id ?>" class="w3schools-overlay tsugi-link-modal" role="dialog" aria-modal="true" aria-label="<?= $title_esc ?>">
  <div class="w3schools-overlay-content tsugi-link-modal-content">
    <div class="tsugi-link-modal-titlebar">
      <span class="tsugi-link-modal-title"><?= htmlentities($title) ?></span>
      <a class="tsugi-link-modal-open-new" href="<?= $safe_href ?>" target="_blank" rel="noopener noreferrer"><?= $open_lbl ?></a>
      <button type="button" class="tsugi-overlay-close tsugi-link-modal-close" aria-label="Close" onclick="tsugiCloseLinkModal('<?= $id ?>');">×</button>
    </div>
    <iframe class="tsugi-link-modal-frame" title="<?= $title_esc ?>" data-src="<?= $safe_href ?>" src="about:blank"></iframe>
  </div>
</div>
<button type="button" class="<?= htmlspecialchars($css_class, ENT_QUOTES, 'UTF-8') ?> tsugi-video-play-btn" style="display: inline-flex; align-items: center;" onclick="tsugiOpenLinkModal('<?= $id ?>');">
<?php
        if ( $icon_key !== null && $icon_key !== false ) {
            self::renderItemIcon($icon_key, $safe_href);
        }
        echo(htmlentities($title));
        echo("</button>");
    }

    /**
     * Render a header item
     */
    private function renderItemHeader($item) {
        $text = isset($item->title) ? $item->title : (isset($item->text) ? $item->text : '');
        $class = isset($item->class) ? ' class="'.$item->class.'"' : '';
        echo("<h2{$class}>".htmlentities($text)."</h2>\n");
    }

    /**
     * Render a text item
     */
    private function renderItemText($item) {
        $text = isset($item->text) ? $item->text : (isset($item->content) ? $item->content : '');
        $tag = isset($item->tag) ? $item->tag : 'p';
        $class = isset($item->class) ? ' class="'.$item->class.'"' : '';
        echo("<{$tag}{$class}>".$text."</{$tag}>\n");
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
     * Emit the tsugi-kaltura-video web component (trigger + modal).
     */
    public function renderKalturaOverlay($title, $embed_url, $with_icon=true, $tab_url=null) {
        if ( !is_string($tab_url) || $tab_url === '' ) {
            $tab_url = $embed_url;
        }
        $safe_title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safe_embed = htmlspecialchars($embed_url, ENT_QUOTES, 'UTF-8');
        $safe_tab = htmlspecialchars($tab_url, ENT_QUOTES, 'UTF-8');
        $open_lbl = htmlspecialchars(__('Open in a new window'), ENT_QUOTES, 'UTF-8');
        $show_icon = $with_icon ? ' show-icon' : '';
?>
<tsugi-kaltura-video
    title="<?= $safe_title ?>"
    embed-url="<?= $safe_embed ?>"
    tab-url="<?= $safe_tab ?>"
    open-label="<?= $open_lbl ?>"<?= $show_icon ?>
></tsugi-kaltura-video>
<?php
    }

    /**
     * Render a single video item
     */
    private function renderItemVideo($item, $nostyle=false) {
        global $CFG, $OUTPUT;
        
        $media_folder = $CFG->getExtension('media_folder', null);
        $media_base = $CFG->getExtension('media_base', null);
        $media_file = isset($item->media) ? $item->media : null;
        $kaltura_url = self::kalturaEmbedUrl($item);
        
        echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-video">');
        
        if ( $kaltura_url ) {
            if ( $nostyle ) {
                self::nostyleUrl($item->title, self::kalturaTabUrl($item));
            } else {
                $this->renderKalturaOverlay(
                    $item->title,
                    $kaltura_url,
                    true,
                    self::kalturaTabUrl($item)
                );
            }
        } else if ( is_string($media_file) && is_string($media_base) && is_string($media_folder) &&
            file_exists($media_folder . '/' . $media_file) ) {
            $media_path = $media_base . '/' . $media_file;
            echo('<a href="'.$media_path.'" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center;">');
            self::renderItemIcon(LessonsNormalize::iconKey($item));
            echo(htmlentities($item->title).'</a>');
        } else {
            $youtube = isset($item->youtube) ? $item->youtube : '';
            if ( $youtube ) {
                $yurl = U::youtubeWatchUrl($youtube);
                if ( !empty($CFG->youtube_use_labnol) ) {
                static $lecno = 0;
                $lecno = $lecno + 1;
                $navid = md5($lecno.$yurl);
?>
<div id="<?= $navid ?>" class="w3schools-overlay" role="dialog" aria-modal="true" aria-label="Video: <?= htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8') ?>">
  <div class="w3schools-overlay-content" style="background-color: black;">
  <button type="button" class="tsugi-overlay-close" aria-label="Close" onclick="document.getElementById('<?= $navid ?>').style.display='none'; if(typeof labnolStopPlayers==='function') labnolStopPlayers();">×</button>
  <div class="youtube-player" data-id="<?= $youtube ?>"></div>
  </div>
</div>
<button type="button" class="tsugi-video-play-btn" onclick="document.getElementById('<?= $navid ?>').style.display = 'block';"><?php self::renderItemIcon(LessonsNormalize::iconKey($item)); ?><?= htmlentities($item->title) ?></button>
<?php
                } else {
                echo('<a href="'.htmlspecialchars($yurl).'" target="_blank" style="display: inline-flex; align-items: center;">');
                self::renderItemIcon(LessonsNormalize::iconKey($item));
                echo(htmlentities($item->title).'</a>');
                }
            } else {
                echo(htmlentities($item->title));
            }
        }
        echo("</li>\n");
    }

    /**
     * Render slides item (can be single slide or array)
     */
    private function renderItemSlides($item, $nostyle=false) {
        if (isset($item->href) || isset($item->url)) {
            // Single slide
            $this->renderItemSlide($item, $nostyle);
        } else if (isset($item->items) && is_array($item->items)) {
            // Multiple slides
            $singular = 'slide';
            $plural = 'slides';
            echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-'.$plural.'">');
            echo("<p>");
            $slidestitle = isset($item->title) ? $item->title : __('Slides');
            echo(htmlentities($slidestitle));
            echo("</p>");
            echo('<ul class="tsugi-lessons-module-'.$plural.'-ul">'."\n");
            foreach($item->items as $slide) {
                $slide_obj = is_array($slide) ? (object)$slide : $slide;
                $slide_obj->type = 'slide';
                $this->renderItemSlide($slide_obj, $nostyle);
            }
            echo("</ul></li>\n");
        }
    }

    /**
     * Render a single slide item
     */
    private function renderItemSlide($item, $nostyle=false) {
        $slide_title = isset($item->title) ? $item->title : (isset($item->text) ? $item->text : basename(isset($item->href) ? $item->href : (isset($item->url) ? $item->url : '')));
        $slide_href = isset($item->href) ? $item->href : (isset($item->url) ? $item->url : '');
        // Process {apphome} and other macros in the URL
        $slide_href = self::expandLink($slide_href);
        
        echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-slide">');
        echo('<span class="tsugi-lessons-module-slide-link">');
        $this->renderWebLinkOpenControl($item, $slide_href, $slide_title, LessonsNormalize::iconKey($item));
        echo("\n</span>\n");
        echo('</li>'."\n");
    }

    /**
     * Render a reference item
     */
    private function renderItemReference($item, $nostyle=false) {
        $title = isset($item->title) ? $item->title : '';
        $href = isset($item->href) ? $item->href : (isset($item->url) ? $item->url : '');
        // Process {apphome} and other macros in the URL
        $href = self::expandLink($href);
        
        echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-reference">');
        echo('<span class="tsugi-lessons-module-reference-link">');
        $this->renderWebLinkOpenControl($item, $href, $title, LessonsNormalize::iconKey($item));
        echo("\n</span>\n");
        echo('</li>'."\n");
    }

    /**
     * Render a discussion item
     */
    private function renderItemDiscussion($item, $module, $nostyle=false) {
        $resource_link_title = isset($item->title) ? $item->title : $module->title;
        $launch = LessonsNormalize::launchUrlForItem($item);
        $resource_link_id = isset($item->resource_link_id) ? $item->resource_link_id : '';
        
        // Not logged in
        if ( ! isset($_SESSION['secret']) ) {
            echo('<li typeof="oer:discussion" class="tsugi-lessons-module-discussion">');
            self::renderItemIcon(LessonsNormalize::iconKey($item));
            echo(htmlentities($resource_link_title).' ('.__('Login Required').') <br/>'."\n");
            echo("\n</li>\n");
            return;
        }
        
        // Logged in
        if ( U::get($_SESSION,'secret') && U::get($_SESSION,'context_key')
            && U::get($_SESSION,'user_key') && U::get($_SESSION,'displayname') && U::get($_SESSION,'email') )
        {
            if ( $nostyle ) {
                echo('<li typeof="oer:discussion" class="tsugi-lessons-module-discussion">');
                echo('<span style="display: inline-flex; align-items: center;">');
                self::renderItemIcon(LessonsNormalize::iconKey($item));
                echo(htmlentities($resource_link_title).' (Login Required)');
                echo('</span><br/>'."\n");
                $discussionurl = U::add_url_parm($launch, 'inherit', $resource_link_id);
                echo('<span style="color:green">'.htmlentities($discussionurl)."</span>\n");
                echo("\n</li>\n");
                return;
            }
            
            $launch_path = $this->lessonsLaunchPath($resource_link_id);
            echo('<li class="tsugi-lessons-module-discussion">');
            echo('<a href="'.$launch_path.'" style="display: inline-flex; align-items: center;">');
            self::renderItemIcon(LessonsNormalize::iconKey($item));
            echo(htmlentities($resource_link_title).'</a></li>'."\n");
        }
    }

    /**
     * Render an LTI item
     */
    private function renderItemLti($item, $module, $nostyle=false) {
        global $CFG;
        
        $resource_link_title = isset($item->title) ? $item->title : $module->title;
        $launch = isset($item->launch) ? $item->launch : '';
        $resource_link_id = isset($item->resource_link_id) ? $item->resource_link_id : '';
        $target = isset($item->target) ? $item->target : false;
        
        // Not logged in
        if ( ! isset($_SESSION['secret']) ) {
            echo('<li typeof="oer:assessment" class="tsugi-lessons-module-lti">');
            echo('<span style="display: inline-flex; align-items: center;">');
            self::renderItemIcon(LessonsNormalize::iconKey($item));
            echo(htmlentities($resource_link_title).' ('.__('Login Required').')');
            echo('</span><br/>'."\n");
            echo("\n</li>\n");
            return;
        }
        
        // Logged in
        if ( U::get($_SESSION,'secret') && U::get($_SESSION,'context_key')
            && U::get($_SESSION,'user_key') && U::get($_SESSION,'displayname') && U::get($_SESSION,'email') )
        {
            if ( $nostyle ) {
                echo('<li typeof="oer:assessment" class="tsugi-lessons-module-lti">');
                echo('<span style="display: inline-flex; align-items: center;">');
                self::renderItemIcon(LessonsNormalize::iconKey($item));
                echo(htmlentities($resource_link_title).' (Login Required)');
                echo('</span><br/>'."\n");
                $ltiurl = U::add_url_parm($launch, 'inherit', $resource_link_id);
                echo('<span style="color:green">'.htmlentities($ltiurl)."</span>\n");
                echo("\n</li>\n");
                return;
            }
            
            $launch_path = $this->lessonsLaunchPath($resource_link_id);
            $title = isset($item->title) ? $item->title : "Autograder";
            
            $rl_dom_id = self::domIdForResourceLink($resource_link_id);
            echo('<li class="tsugi-lessons-module-lti" id="'.htmlspecialchars($rl_dom_id, ENT_QUOTES, 'UTF-8').'">');
            echo('<a');
            if ( $target == "_blank" ) echo(' target="_blank" rel="noopener noreferrer" onclick="alert(\'Link will open in a new browser tab...\');" ');
            echo(' href="'.$launch_path.'" style="display: inline-flex; align-items: center;">');
            self::renderItemIcon(LessonsNormalize::iconKey($item));
            echo(htmlentities($title).'</a>');
            self::echoLtiLinkProgressIndicators($resource_link_id, $item, $this->lessonModuleGradesForBadges, $this->lessonModuleDueDatesForBadges);
            echo('</li>'."\n");
        }
    }

    /**
     * Native Quiz1 lesson item. Missing quizzes are hidden from students and
     * shown as unsatisfied references to instructors (Sakai-style).
     */
    private function renderItemQuiz1($item, $nostyle=false) {
        $title = isset($item->title) && is_string($item->title) && $item->title !== ''
            ? $item->title
            : __('Quiz');
        $quiz_id = LessonsNormalize::quizIdOf($item);
        $exists = $this->quiz1ExistsInCourse($quiz_id);
        if ( ! $exists ) {
            if ( ! $this->lessonsViewerIsInstructor() ) {
                return;
            }
            echo('<li typeof="oer:assessment" class="tsugi-lessons-module-quiz1 tsugi-lessons-quiz-missing">');
            if ( $nostyle ) {
                echo(htmlentities($title).' ('.__('Quiz not found').')');
            } else {
                echo('<span style="display: inline-flex; align-items: center;">');
                self::renderItemIcon(LessonsNormalize::iconKey($item));
                echo(htmlentities($title).' ('.__('Quiz not found').')');
                echo('</span>');
            }
            echo("</li>\n");
            return;
        }

        $href = '';
        $logged_in = U::isLoggedIn();
        if ( $quiz_id > 0 && $logged_in && class_exists('\\Tsugi\\Controllers\\Quiz1') ) {
            $home = \Tsugi\Controllers\Tool::determineToolHome(\Tsugi\Controllers\Quiz1::ROUTE);
            if ( is_string($home) && $home !== '' ) {
                $href = U::addSession(\Tsugi\Controllers\Tool::joinToolHome($home, (string) $quiz_id));
            }
        }

        echo('<li typeof="oer:assessment" class="tsugi-lessons-module-quiz1">');
        if ( $nostyle ) {
            echo(htmlentities($title));
            if ( $href !== '' ) {
                echo(': <a href="'.htmlspecialchars($href, ENT_QUOTES, 'UTF-8').'">'.htmlentities($title).'</a>');
            }
        } else if ( $href !== '' ) {
            echo('<a href="'.htmlspecialchars($href, ENT_QUOTES, 'UTF-8').'" style="display: inline-flex; align-items: center;">');
            self::renderItemIcon(LessonsNormalize::iconKey($item));
            echo(htmlentities($title).'</a>');
        } else {
            echo('<span style="display: inline-flex; align-items: center;">');
            self::renderItemIcon(LessonsNormalize::iconKey($item));
            echo(htmlentities($title));
            if ( ! $logged_in ) {
                echo(' ('.__('Login Required').')');
            }
            echo('</span>');
        }
        echo("</li>\n");
    }

    /**
     * @return bool
     */
    private function quiz1ExistsInCourse($quiz_id) {
        $quiz_id = (int) $quiz_id;
        if ( $quiz_id < 1 ) {
            return false;
        }
        if ( $this->quiz1IdSet === null ) {
            $this->quiz1IdSet = array();
            $context_id = U::currentContextId();
            if ( $context_id > 0 ) {
                try {
                    foreach ( Quiz1Repository::listForContext($context_id) as $quiz ) {
                        $this->quiz1IdSet[(int) $quiz->id] = true;
                    }
                } catch ( \Exception $e ) {
                    $this->quiz1IdSet = array();
                }
            }
        }
        return isset($this->quiz1IdSet[$quiz_id]);
    }

    /**
     * @return bool
     */
    private function lessonsViewerIsInstructor() {
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

    /**
     * Render an assignment item
     */
    private function renderItemAssignment($item, $nostyle=false) {
        $title = isset($item->title) ? $item->title : (isset($item->text) ? $item->text : __('Assignment Specification'));
        $url = isset($item->href) ? $item->href : (isset($item->url) ? $item->url : '');
        // Process {apphome} and other macros in the URL
        $url = self::expandLink($url);
        
        if ( $nostyle ) {
            echo('<li typeof="oer:assessment" class="tsugi-lessons-module-assignment">');
            echo(htmlentities($title).':');
            self::nostyleUrl($title, $url);
            echo('</li>'."\n");
        } else {
            echo('<li typeof="oer:assessment" class="tsugi-lessons-module-assignment">');
            $this->renderWebLinkOpenControl($item, $url, $title, LessonsNormalize::iconKey($item));
            echo('</li>'."\n");
        }
    }

    /**
     * Render a solution item
     */
    private function renderItemSolution($item, $nostyle=false) {
        $url = isset($item->href) ? $item->href : (isset($item->url) ? $item->url : '');
        // Process {apphome} and other macros in the URL
        $url = self::expandLink($url);
        
        if ( $nostyle ) {
            echo('<li typeof="oer:assessment" class="tsugi-lessons-module-solution">');
            echo(__('Assignment Solution').':');
            self::nostyleUrl(__('Assignment Solution'), $url);
            echo('</li>'."\n");
        } else {
            echo('<li typeof="oer:assessment" class="tsugi-lessons-module-solution">');
            $this->renderWebLinkOpenControl($item, $url, __('Assignment Solution'), LessonsNormalize::iconKey($item));
            echo('</li>'."\n");
        }
    }

    /**
     * Render chapters item
     */
    private function renderItemChapters($item) {
        $chapters = isset($item->text) ? $item->text : (isset($item->chapters) ? $item->chapters : '');
        echo('<li typeof="SupportingMaterial">'.__('Chapters').': '.htmlentities($chapters).'</li>'."\n");
    }

    /**
     * Render carousel item
     */
    private function renderItemCarousel($item, $nostyle=false) {
        global $CFG, $OUTPUT;
        
        if (!isset($item->items) || !is_array($item->items)) {
            return;
        }
        
        $videotitle = __(self::getSetting('videos-title', 'Videos'));
        echo($nostyle ? $videotitle . ': <ul>' : '<ul class="bxslider">'."\n");
        foreach($item->items as $video) {
            echo('<li>');
            if ( $nostyle ) {
                echo(htmlentities($video->title)."<br/>");
                $yurl = U::youtubeWatchUrl($video->youtube);
                self::nostyleUrl($video->title, $yurl);
            } else if ( !empty($CFG->youtube_use_labnol) ) {
                $OUTPUT->embedYouTube($video->youtube, $video->title);
            } else {
                $yurl = U::youtubeWatchUrl($video->youtube);
                echo('<a href="'.htmlspecialchars($yurl).'" target="_blank">'.htmlentities($video->title).'</a>');
            }
            echo('</li>');
        }
        echo("</ul>\n");
    }

}
