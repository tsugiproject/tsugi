<?php

namespace Tsugi\Controllers;

use Tsugi\Util\U;
use Tsugi\Core\LTIX;
use Tsugi\Core\Manifest;
use Tsugi\Grades\GradeUtil;
use Tsugi\Lumen\Application;
use Tsugi\Services\Quiz1\Quiz1Repository;
use Tsugi\UI\Lessons as LessonsUI;
use Tsugi\UI\LessonsNormalize;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class Lessons extends Tool {

    const ROUTE = '/lessons';

    const REDIRECT = 'tsugi_controllers_lessons';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Lessons@get');
        $app->router->get($prefix.'/', 'Lessons@get');
        $app->router->get('/'.self::REDIRECT, 'Lessons@get');
        // Author route must precede {anchor} so "_author" is not captured as anchor
        $app->router->get($prefix.'/_author/export', 'Lessons@authorExport');
        $app->router->get($prefix.'/_author/export-v2', 'Lessons@authorExportV2');
        $app->router->post($prefix.'/_author/import', 'Lessons@authorImport');
        $app->router->get($prefix.'/_author', 'Lessons@author');
        $app->router->post($prefix.'/_author', 'Lessons@authorPost');
        $app->router->get($prefix.'/{anchor}', 'Lessons@get');
        // Catch /lessons/foo/bar or deeper - redirect to /lessons (avoids 404)
        $app->router->get($prefix.'/{anchor}/{path:.*}', 'Lessons@redirectToIndex');
        $app->router->get($prefix.'_launch/{anchor}', function(Request $request, $anchor = null) use ($app) {
            return Lessons::launch($app, $anchor);
        });
    }

    public function get(Request $request, $anchor=null)
    {
        global $OUTPUT;

        $l = Manifest::currentLessons($anchor);
        if ( ! $l ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons) or an active course manifest');
        }

        Tool::applyGradeRefreshAfterLaunchReturn();

        // Turning on and off styling
        if ( isset($_GET['nostyle']) ) {
            if ( $_GET['nostyle'] == 'yes' ) {
                $_SESSION['nostyle'] = 'yes';
            } else {
                unset($_SESSION['nostyle']);
            }
        }

        $l->toolHome = $this->toolHome(self::ROUTE);

        // If we have an anchor in the path but it doesn't exist, redirect to /lessons
        // (avoids rendering "all lessons" from /lessons/bob which breaks relative URLs)
        if ( $anchor !== null && $anchor !== '' && $l->getModuleByAnchor($anchor) === null ) {
            $url = U::addSession($this->toolHome(self::ROUTE));
            return new RedirectResponse($url);
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $menu = false;
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        if ( $this->isInstructor() ) {
            echo('<span style="position: fixed; right: 10px; top: 75px; z-index: 999; background-color: white; padding: 4px 8px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.2);">');
            if ( $this->canAuthorLessons() ) {
                $author_url = U::addSession($this->toolHome(self::ROUTE) . '/_author');
                echo('<a href="'.htmlspecialchars($author_url).'" class="btn btn-default btn-sm"><i class="fa fa-pencil" aria-hidden="true"></i> '.__('Author').'</a>');
            } else if ( $this->canExportLessonsV2() ) {
                $export_v2_url = U::addSession($this->toolHome(self::ROUTE) . '/_author/export-v2');
                echo('<a href="'.htmlspecialchars($export_v2_url).'" class="btn btn-default btn-sm"><i class="fa fa-download" aria-hidden="true"></i> '.__('Export lessons.json v2').'</a>');
            }
            echo('</span>');
        }
        self::header($l);
        echo('<div class="container">');
        self::render($l);
        echo('</div>');
        $OUTPUT->footerStart();
        self::footer($l);
        $OUTPUT->footerEnd();
    }

    /**
     * Redirect multi-segment invalid paths (e.g. /lessons/bob/bob) to /lessons
     */
    public function redirectToIndex(Request $request, $anchor=null, $path=null)
    {
        return new RedirectResponse($this->toolHome(self::ROUTE));
    }

    /**
     * Authoring is only for a database-backed Lessons JSON v2 course.
     * File-backed $CFG->lessons and classic manifests are view-only.
     */
    private function canAuthorLessons() {
        return Manifest::canAuthorCurrent();
    }

    /**
     * Instructors can download a normalized v2 export of the current document
     * (classic file or manifest) without opening the author UI.
     */
    private function canExportLessonsV2() {
        return Manifest::currentDocument() !== false;
    }

    /**
     * Lesson authoring interface — database-backed Lessons JSON v2 only.
     */
    public function author(Request $request)
    {
        global $CFG, $OUTPUT, $PDOX;

        if ( ! $this->canAuthorLessons() ) {
            return new Response('Lesson authoring is not enabled', 403);
        }
        $this->requireInstructor(U::addSession($this->toolHome(self::ROUTE)));

        $doc = Manifest::currentDocument();
        if ( ! $doc ) {
            return new Response('Cannot find lessons file or course manifest', 500);
        }

        LTIX::getConnection();
        $lessons_data = json_decode($doc['json'], true);
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new Response('Error parsing JSON: ' . json_last_error_msg(), 500);
        }

        $lessons_title = htmlspecialchars($lessons_data['title'] ?? 'Untitled');
        $lessons_file_escaped = htmlspecialchars($doc['label']);
        $lessons_json = json_encode($lessons_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $export_url = U::addSession($this->toolHome(self::ROUTE) . '/_author/export');
        $export_v2_url = U::addSession($this->toolHome(self::ROUTE) . '/_author/export-v2');
        $import_url = U::addSession($this->toolHome(self::ROUTE) . '/_author/import');

        $files_json_url = U::addSession($this->controllerUrl(Files::ROUTE) . '/json');
        $files_home_url = U::addSession($this->controllerUrl(Files::ROUTE));
        $pages_home = $this->controllerUrl(Pages::ROUTE);
        $pages_json_url = U::addSession($pages_home . '/json');
        $pages_home_url = U::addSession($pages_home);
        $pages_add_url = U::addSession($pages_home . '/add');
        $lessons_json_url = U::addSession($pages_home . '/lessons-json');
        $pages_base = $pages_home;
        $app_home = (isset($CFG->apphome) && is_string($CFG->apphome)) ? rtrim($CFG->apphome, '/') : '';
        $lessons_url = U::addSession($this->toolHome(self::ROUTE));
        $quiz1_home_url = U::addSession($this->controllerUrl(Quiz1::ROUTE));
        $quiz1_list = array();
        try {
            $context_id = U::currentContextId();
            if ( $context_id ) {
                foreach ( Quiz1Repository::listForContext($context_id) as $quiz ) {
                    $quiz1_list[] = array(
                        'id' => (int) $quiz->id,
                        'title' => $quiz->title,
                        'question_count' => (int) $quiz->question_count,
                    );
                }
            }
        } catch ( \Exception $e ) {
            $quiz1_list = array();
        }
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        echo('<span style="position: fixed; right: 10px; top: 75px; z-index: 999; background-color: white; padding: 4px 8px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.2);">');
        echo('<a href="'.htmlspecialchars($lessons_url).'" class="btn btn-default btn-sm"><i class="fa fa-arrow-left" aria-hidden="true"></i> '.__('Back to Lessons').'</a>');
        echo('</span>');

        require_once __DIR__ . '/../UI/CKEditor.php';
        $template = __DIR__ . '/templates/Lessons/author_interface.inc.php';
        include $template;

        $OUTPUT->footerStart();
        $OUTPUT->footerEnd();
        return '';
    }

    /**
     * Handle AJAX save from lesson author - returns JSON
     */
    public function authorPost(Request $request)
    {
        if ( ! $this->canAuthorLessons() ) {
            return new Response(json_encode(['success' => false, 'error' => 'Not allowed']), 403, ['Content-Type' => 'application/json']);
        }
        $this->requireInstructor(U::addSession($this->toolHome(self::ROUTE)));
        $csrf = self::requireCsrfJson();
        if ( $csrf ) {
            return $csrf;
        }

        $action = U::get($_POST, 'action');
        if ( $action !== 'save' ) {
            return new Response(json_encode(['success' => false, 'error' => 'Unknown action']), 400, ['Content-Type' => 'application/json']);
        }

        $data = U::get($_POST, 'data');
        if ( ! $data ) {
            return new Response(json_encode(['success' => false, 'error' => 'No data provided']), 400, ['Content-Type' => 'application/json']);
        }

        $lessons_data = json_decode($data, true);
        if ( json_last_error() !== JSON_ERROR_NONE || ! is_array($lessons_data) ) {
            return new Response(json_encode(['success' => false, 'error' => 'Invalid JSON: ' . json_last_error_msg()]), 400, ['Content-Type' => 'application/json']);
        }

        $err = $this->persistLessonsArray($lessons_data, 'Author save');
        if ( $err !== null ) {
            return new Response(json_encode(['success' => false, 'error' => $err]), 400, ['Content-Type' => 'application/json']);
        }

        return new Response(json_encode(['success' => true, 'message' => 'Manifest saved']), 200, ['Content-Type' => 'application/json']);
    }

    /**
     * Download the last saved lessons.json (manifest row or $CFG->lessons file).
     */
    public function authorExport(Request $request)
    {
        if ( ! $this->canAuthorLessons() ) {
            return new Response('Lesson authoring is not enabled', 403);
        }
        $this->requireInstructor(U::addSession($this->toolHome(self::ROUTE)));

        $doc = Manifest::currentDocument();
        if ( ! $doc ) {
            return new Response('Cannot find lessons file or course manifest', 500);
        }

        $decoded = json_decode($doc['json'], true);
        $title = is_array($decoded) && isset($decoded['title']) ? $decoded['title'] : 'lessons';
        $version = isset($doc['version']) ? (int) $doc['version'] : 0;
        $filename = Manifest::exportFilename($title, $version);
        $filename = str_replace(array('\\', '"'), '', $filename);

        return new Response($doc['json'], 200, array(
            'Content-Type' => 'application/json; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store',
        ));
    }

    /**
     * Download Lessons JSON v2 (normalized foundational types + subtypes).
     * Available to instructors for classic file-backed courses as well as v2 manifests.
     */
    public function authorExportV2(Request $request)
    {
        $this->requireInstructor(U::addSession($this->toolHome(self::ROUTE)));
        if ( ! $this->canExportLessonsV2() ) {
            return new Response('Cannot find lessons file or course manifest', 404);
        }

        $doc = Manifest::currentDocument();
        if ( ! $doc ) {
            return new Response('Cannot find lessons file or course manifest', 500);
        }

        $decoded = json_decode($doc['json'], true);
        if ( ! is_array($decoded) ) {
            return new Response('Invalid lessons JSON', 500);
        }

        $json = \Tsugi\UI\LessonsNormalize::serializeV2($decoded);
        $title = isset($decoded['title']) ? $decoded['title'] : 'lessons';
        $version = isset($doc['version']) ? (int) $doc['version'] : 0;
        $filename = Manifest::exportFilename($title, $version);
        $filename = preg_replace('/\.json$/', '-v2.json', $filename);
        $filename = str_replace(array('\\', '"'), '', $filename);

        return new Response($json, 200, array(
            'Content-Type' => 'application/json; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store',
        ));
    }

    /**
     * Upload a lessons.json file and save it as a new manifest version (or replace the file).
     */
    public function authorImport(Request $request)
    {
        $author_url = U::addSession($this->toolHome(self::ROUTE) . '/_author');
        if ( ! $this->canAuthorLessons() ) {
            return new Response('Lesson authoring is not enabled', 403);
        }
        $this->requireInstructor($author_url);
        $csrf = self::requireCsrf($author_url);
        if ( $csrf ) {
            return $csrf;
        }

        if ( empty($_FILES['file']) || ! is_array($_FILES['file']) ) {
            U::flashError(__('No file uploaded.'));
            return new RedirectResponse($author_url);
        }
        $file = $_FILES['file'];
        if ( ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK ) {
            U::flashError(__('Upload failed.'));
            return new RedirectResponse($author_url);
        }
        $size = isset($file['size']) ? (int) $file['size'] : 0;
        if ( $size < 1 || $size > 5 * 1024 * 1024 ) {
            U::flashError(__('File must be between 1 byte and 5 MB.'));
            return new RedirectResponse($author_url);
        }
        $tmp = isset($file['tmp_name']) ? $file['tmp_name'] : '';
        if ( $tmp === '' || ! is_uploaded_file($tmp) ) {
            U::flashError(__('Upload failed.'));
            return new RedirectResponse($author_url);
        }

        $raw = file_get_contents($tmp);
        $lessons_data = json_decode($raw, true);
        if ( json_last_error() !== JSON_ERROR_NONE || ! is_array($lessons_data) ) {
            U::flashError(__('Invalid JSON: ') . json_last_error_msg());
            return new RedirectResponse($author_url);
        }

        $err = $this->persistLessonsArray($lessons_data, 'Import');
        if ( $err !== null ) {
            U::flashError($err);
            return new RedirectResponse($author_url);
        }

        U::flashSuccess(__('Imported lessons.json.'));
        return new RedirectResponse($author_url);
    }

    /**
     * Validate and persist a decoded lessons document. Returns an error message or null.
     *
     * @param array<string, mixed> $lessons_data
     * @return string|null
     */
    private function persistLessonsArray($lessons_data, $comment) {
        if ( ! Manifest::currentIsV2() ) {
            return 'Lesson authoring only saves Lessons JSON v2 to the course manifest';
        }
        $context_id = U::currentContextId();
        if ( $context_id < 1 ) {
            return 'No course context';
        }

        $err = \Tsugi\UI\LessonsNormalize::invalidQuizIdError($lessons_data);
        if ( $err !== null ) {
            return $err;
        }

        $lessons_data = \Tsugi\UI\LessonsNormalize::normalizeDocument($lessons_data);
        $lessons_data['lessons_json_version'] = \Tsugi\UI\LessonsNormalize::FORMAT_VERSION;

        $json = Manifest::encode($lessons_data);
        $err = Manifest::validateJson($json);
        if ( $err !== null ) {
            return $err;
        }

        try {
            Manifest::saveNewVersion($context_id, $lessons_data, U::loggedInUserId(), $comment);
        } catch ( \InvalidArgumentException $e ) {
            return $e->getMessage();
        } catch ( \Exception $e ) {
            return 'Failed to save manifest';
        }
        return null;
    }

    public static function launch(Application $app, $anchor=null)
    {
        global $CFG;

        $toolHome = self::determineToolHome(self::ROUTE);
        $redirect_path = U::addSession(self::determineParentPath(self::ROUTE));
        if ( $redirect_path == '') $redirect_path = '/';

        $l = Manifest::currentLessons();
        if ( ! $l ) {
            $app->tsugiFlashError(__('Cannot find lessons.json ($CFG->lessons) or an active course manifest'));
            return new RedirectResponse($redirect_path);
        }

        $lti = $l->getLtiByRlid($anchor);
        if ( ! $lti ) {
            $app->tsugiFlashError(__('Cannot find lti resource link id'));
            return new RedirectResponse($redirect_path);
        }

        $module = $l->getModuleByRlid($anchor);

        $return_url = $module
            ? $toolHome . '/' . $module->anchor
            : $toolHome;

        $fallback_title = ( $module && isset($module->title) ) ? $module->title : '';

        return Tool::sendLti11LaunchFromLessonsItem(
            $app,
            $lti,
            $return_url,
            $redirect_path,
            $fallback_title,
            Tool::SESSION_LESSONS_GRADE_REFRESH_AFTER_LAUNCH
        );
    }

    /**
     * emit the header material
     */
    public static function header(\Tsugi\UI\Lessons $lessons, $buffer=false) {
        global $CFG;
        ob_start();
        LessonsUI::printLtiProgressStyles();
        // See if there are any carousels in the lessons
        $carousel = false;
        foreach($lessons->lessons->modules as $module) {
            if ( isset($module->carousel) ) $carousel = true;
        }
        if ( $carousel ) {
?>
<link rel="stylesheet" href="<?= $CFG->staticroot ?>/plugins/jquery.bxslider/jquery.bxslider.css" type="text/css"/>
<?php
        }
        if ( isset($lessons->lessons->headers) && is_array($lessons->lessons->headers) ) {
            foreach($lessons->lessons->headers as $header) {
                $header = LessonsUI::expandLink($header);
                echo($header);
                echo("\n");
            }
        }
        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $buffer ) return $ob_output;
        echo($ob_output);
    }

    public static function footer(\Tsugi\UI\Lessons $lessons, $buffer=false)
    {
        global $CFG;
        ob_start();
        if ( $lessons->isSingle() ) {
// http://bxslider.com/examples/video
?>
<script>
$(document).ready(function() {
    $('.w3schools-overlay').on('click', function(event) {
        if ( event.target.id == event.currentTarget.id ) {
            // Stop our embedded YouTube Players
            if (typeof labnolStopPlayers === 'function') labnolStopPlayers();
            // https://stackoverflow.com/questions/4071872/html5-video-force-abort-of-buffering
            // https://stackoverflow.com/a/34058996
            $('.w3schools-overlay audio, video').each(function (i,e) {
                    var tmp_src = this.src;
                    var playtime = this.currentTime;
                    this.src = '';
                    this.load();
                    this.src = tmp_src;
                    this.currentTime = playtime;

            });
            // Stop any iframes in this overlay
            $(event.currentTarget).find('iframe').each(function() {
                this.src = '';
            });
            event.target.style.display = 'none';
        } else {
            event.stopPropagation();
        }
    })
});
function tsugiOpenLinkModal(id) {
    var overlay = document.getElementById(id);
    if (!overlay) {
        return;
    }
    var iframe = overlay.querySelector('iframe.tsugi-link-modal-frame');
    if (iframe && iframe.getAttribute('data-src')) {
        iframe.src = iframe.getAttribute('data-src');
    }
    overlay.style.display = 'block';
}
function tsugiCloseLinkModal(id) {
    var overlay = document.getElementById(id);
    if (!overlay) {
        return;
    }
    $(overlay).find('iframe').each(function() {
        this.src = '';
    });
    overlay.style.display = 'none';
}
</script>
<script type="module" src="<?= htmlspecialchars(\Tsugi\Controllers\StaticFiles::url('Lessons', 'tsugi-kaltura-video.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= $CFG->staticroot ?>/plugins/jquery.bxslider/plugins/jquery.fitvids.js">
</script>
<script src="<?= $CFG->staticroot ?>/plugins/jquery.bxslider/jquery.bxslider.js">
</script>
<script>
$(document).ready(function() {
    $('.bxslider').bxSlider({
        video: true,
        useCSS: false,
        adaptiveHeight: false,
        slideWidth: "350px",
        infiniteLoop: false,
        maxSlides: 2
    });
});
</script>
<?php
        } else { // isSingle()
// https://github.com/LinZap/jquery.waterfall
?>
<script type="text/javascript" src="<?= $CFG->staticroot ?>/js/waterfall-light.js"></script>
<script>
$(function(){
    $('#box').waterfall({refresh: 0})
});
</script>
<?php
        }
        if ( isset($lessons->lessons->footers) && is_array($lessons->lessons->footers) ) {
            foreach($lessons->lessons->footers as $footer) {
                $footer = LessonsUI::expandLink($footer);
                echo($footer);
                echo("\n");
            }
        }
        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $buffer ) return $ob_output;
        echo($ob_output);

    } // end footer

    /*
     ** render
     */
    public static function render(\Tsugi\UI\Lessons $lessons, $buffer=false) {
        if ( ! $lessons->isSingle() && $lessons->isEmpty() ) {
            $title = isset($lessons->lessons->title) ? (string) $lessons->lessons->title : '';
            $html = '<div typeof="Course">'."\n";
            if ( $title !== '' ) {
                $html .= '<h1>'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8')."</h1>\n";
            }
            $html .= '<p>'.htmlspecialchars(__('There is no Lessons content.'), ENT_QUOTES, 'UTF-8')."</p>\n";
            $html .= '</div>'."\n";
            if ( $buffer ) {
                return $html;
            }
            echo($html);
            return;
        }
        if ( $lessons->isSingle() ) {
            return self::renderSingle($lessons, $buffer);
        } else {
            return self::renderAll($lessons, $buffer);
        }
    }

    /**
     * Progress or due rollup badge for module cards and single-module title (percent mode vs due mode).
     *
     * @param string[] $rlids
     * @param array<string,float> $allgrades
     * @param array<string,array<string,mixed>> $duedates
     * @param bool $show_rollup_due_date If false (all-modules cards), status-only due badge (no date). Rollup Upcoming: show percent if above zero; at 0% cards show nothing, single-module title still shows upcoming due badge with date.
     */
    private static function echoModuleAggregateProgressBadge(\Tsugi\UI\Lessons $lessons, $possible_points, $actual_points, $rlids, $allgrades, $duedates, $show_rollup_due_date = true) {
        if ( $possible_points <= 0 ) {
            return;
        }
        $percent = (int) round(($actual_points / $possible_points) * 100);
        $hasDue = $lessons->moduleHasLtiDueDateInContext($rlids, $duedates);
        if ( ! $hasDue ) {
            if ( $percent == 0 ) {
                return;
            }
            if ( $percent == 100 ) {
                echo('<span class="progress-badge progress-badge-check" title="Complete: 100%">100%</span>');
            } else {
                echo('<span class="progress-badge progress-badge-percent" title="Progress: '.$percent.'%">'.$percent.'%</span>');
            }
            return;
        }
        $rollup = $lessons->moduleWorstDueRollup($rlids, $allgrades, $duedates);
        if ( $rollup === null ) {
            if ( $percent > 0 ) {
                echo('<span class="progress-badge progress-badge-percent" title="Progress: '.$percent.'%">'.$percent.'%</span>');
            }
            return;
        }
        if ( $rollup['modifier'] === 'tsugi-assignments-due-future' ) {
            if ( $percent > 0 ) {
                echo('<span class="progress-badge progress-badge-percent" title="Progress: '.$percent.'%">'.$percent.'%</span>');
                return;
            }
            if ( ! $show_rollup_due_date ) {
                return;
            }
            /* single-module title at 0%: fall through — show Upcoming + due date */
        }
        $stateText = LessonsUI::assignmentsDueStateVisibleLabel($rollup['modifier']);
        $a11yExtras = '';
        if ( ! $show_rollup_due_date ) {
            $tip = __('Due').' '.$rollup['date_disp'];
            $label = $stateText.'. '.$tip;
            $a11yExtras = ' title="'.htmlspecialchars($tip, ENT_QUOTES, 'UTF-8').'" aria-label="'.
                htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'"';
        }
        echo('<span class="tsugi-assignments-due tsugi-assignments-due-badge '.$rollup['modifier'].'"'.$a11yExtras.'>');
        echo('<span class="tsugi-assignments-due-state">'.htmlspecialchars($stateText).'</span>');
        if ( $show_rollup_due_date ) {
            echo(' <span class="tsugi-assignments-due-detail"><span class="tsugi-assignments-due-lbl">'.__('Due').'</span> ');
            echo(htmlspecialchars($rollup['date_disp']).'</span>');
        }
        echo('</span>');
    }

    /**
     * Card icon color: percent mode uses blue/green; due mode uses rollup (100% stays green).
     *
     * @param array{modifier:string}|null $rollup from moduleWorstDueRollup when due mode and not 100%
     */
    private static function moduleCardIconColorStyle($percent, $hasDueMode, $rollup) {
        if ( ! $hasDueMode ) {
            if ( $percent == 100 ) {
                return 'color: #155724;';
            }
            if ( $percent > 0 && $percent < 100 ) {
                return 'color: #084298;';
            }
            return '';
        }
        if ( $percent == 100 ) {
            return 'color: #155724;';
        }
        if ( $rollup === null ) {
            if ( $percent > 0 && $percent < 100 ) {
                return 'color: #084298;';
            }
            return '';
        }
        switch ( $rollup['modifier'] ) {
            case 'tsugi-assignments-due-past':
                return 'color: #b02a37;';
            case 'tsugi-assignments-due-soon':
                return 'color: #856404;';
            case 'tsugi-assignments-due-future':
                return 'color: #495057;';
            case 'tsugi-assignments-due-completed':
                return 'color: #155724;';
            case 'tsugi-assignments-due-neutral':
            default:
                return 'color: #6c757d;';
        }
    }

    /**
     * Single-module h1 matching the all-modules card header (image, index:title, progress or due rollup badge).
     */
    private static function echoModuleTitleBarMatchingCard(\Tsugi\UI\Lessons $lessons, $module, $allgrades, $duedates) {
        $modProgress = $lessons->moduleLtiProgressPoints($module, $allgrades, $duedates);
        $possible_points = $modProgress[0];
        $actual_points = $modProgress[1];
        $rlids = $modProgress[2];
        echo('<h1 property="oer:name" class="tsugi-lessons-module-title tsugi-lessons-module-title-cardmatch">');
        if ( isset($module->image) ) {
            echo('<img class="tsugi-all-modules-image-icon" aria-hidden="true" style="float: left; width: 2em; padding-right: 5px;" src="'.LessonsUI::expandLink($module->image).'">');
        }
        $title_line = $lessons->position . ': ' . $module->title;
        echo(htmlentities($title_line));
        self::echoModuleAggregateProgressBadge($lessons, $possible_points, $actual_points, $rlids, $allgrades, $duedates);
        echo("</h1>\n");
        if ( isset($module->image) ) {
            echo('<div class="tsugi-lessons-module-title-clearfix" style="clear:both;"></div>'."\n");
        }
    }

    /*
     * render a lesson
     */
    public static function renderSingle(\Tsugi\UI\Lessons $lessons, $buffer=false) {
        global $CFG, $OUTPUT;
        ob_start();
        if ( isset($_GET['nostyle']) ) {
            if ( $_GET['nostyle'] == 'yes' ) {
                $_SESSION['nostyle'] = 'yes';
            } else {
                unset($_SESSION['nostyle']);
            }
        }
        $nostyle = isset($_SESSION['nostyle']);

        $module = $lessons->module;
        
        // Ensure module is set
        if ( !$module ) {
            echo('<p>Error: Module not found.</p>');
            $ob_output = ob_get_contents();
            ob_end_clean();
            if ( $buffer ) return $ob_output;
            echo($ob_output);
            return;
        }
        

        // Load all the Grades so far for progress badges
        $allgrades = array();
        $rows = GradeUtil::loadGradesCurrentUser();
        foreach ( $rows as $row ) {
            $allgrades[$row['resource_link_id']] = $row['grade'];
        }
        $moduleDueDates = array();
        if ( U::currentContextId() !== 0 ) {
            $moduleDueDates = GradeUtil::loadDueDatesForDisplay(U::currentContextId());
        }
        $lessons->setModuleProgressContext($allgrades, $moduleDueDates);

	if ( $nostyle && isset($_SESSION['gc_count']) ) {
?>
<script src="https://apis.google.com/js/platform.js" async defer></script>
<div id="iframe-dialog" title="Read Only Dialog" style="display: none;">
   <iframe name="iframe-frame" style="height:200px" id="iframe-frame" title="Install assignment in classroom"
    src="<?= $OUTPUT->getSpinnerUrl() ?>"></iframe>
</div>
<?php
        }
            if ( $lessons->getSetting('prev-next') == "right" ) {
                echo('<div typeof="oer:Lesson" style="float:right; padding-left: 5px; vertical-align: text-top;"><ul class="pager tsugi-lessons-pager">'."\n");
            } else if ( $lessons->getSetting('prev-next') == "left" ) {
                echo('<div typeof="oer:Lesson" style="float:left; padding-left: 5px; vertical-align: text-top;"><ul class="pager tsugi-lessons-pager">'."\n");
            } else {
                echo('<div typeof="oer:Lesson" style="padding-left: 5px; vertical-align: text-top;"><ul class="pager tsugi-lessons-pager">'."\n");
            }
            $all = U::get_rest_parent();
            if ( $lessons->position == 1 ) {
                echo('<li class="previous" style="visibility:hidden" aria-hidden="true">&larr; '.__('Previous').'</li>'."\n");
            } else {
                $prev = $all . '/' . urlencode($lessons->lessons->modules[$lessons->position-2]->anchor);
                echo('<li class="previous"><a href="'.$prev.'">&larr; '.__('Previous').'</a></li>'."\n");
            }
            echo('<li><a href="'.$all.'">'.__('All').' ('.$lessons->position.' / '.count($lessons->lessons->modules).')</a></li>');
            if ( $lessons->position >= count($lessons->lessons->modules) ) {
                echo('<li class="next" style="visibility:hidden" aria-hidden="true">&rarr; '.__('Next').'</li>'."\n");
            } else {
                $next = $all . '/' . urlencode($lessons->lessons->modules[$lessons->position]->anchor);
                echo('<li class="next"><a href="'.$next.'">&rarr; '.__('Next').'</a></li>'."\n");
            }
            echo("</ul></div>\n");
            self::echoModuleTitleBarMatchingCard($lessons, $module, $allgrades, $moduleDueDates);
            $lessonurl = $CFG->apphome . U::get_rest_path();
            if ( $nostyle ) {
                LessonsUI::nostyleUrl($module->title, $lessonurl);
                echo("<hr/>\n");
            }

            // Check if module uses items array (new format)
            // Direct access to items property - json_decode creates stdClass objects, arrays stay as arrays
            $has_items = false;
            $items_array = null;
            if ( isset($module->items) ) {
                $items_array = $module->items;
                // json_decode keeps JSON arrays as PHP arrays, so this should work
                if ( is_array($items_array) && count($items_array) > 0 ) {
                    $has_items = true;
                }
            }
            $has_legacy = isset($module->videos) || isset($module->lti) || isset($module->discussions) || 
                         isset($module->references) || isset($module->slides) || isset($module->assignment) || 
                         isset($module->solution) || isset($module->carousel);
            
            // Check debug flag
            $debug_conversion = $CFG->getExtension('lessons_debug_conversion', false);
            
            if ( $has_items ) {
                // New format: render items array
                if ( $debug_conversion && $has_legacy ) {
                    echo('<div style="border: 2px solid blue; padding: 10px; margin: 10px 0;"><h3 style="color: blue;">NEW FORMAT (items array):</h3>');
                }
                // Render description if present (module image is shown in title bar with the card-style header)
                if ( isset($module->description) ) {
                    echo('<p property="oer:description" class="tsugi-lessons-module-description">'.$module->description."</p>\n");
                }
                
                // Render items in order. Headings (and text / non-list items) sit
                // outside the list. One <ul> runs until the next heading — subtype
                // does not start a new list.
                $in_list = false;
                foreach($items_array as $item) {
                    $item_obj = is_array($item) ? (object)$item : $item;
                    // Skip malformed items (e.g., objects that should be arrays)
                    if ( isset($item_obj->type) && $item_obj->type == 'ltis' && isset($item_obj->items) && !is_array($item_obj->items) ) {
                        continue;
                    }

                    $type = isset($item_obj->type) ? $item_obj->type : '';
                    $is_heading = LessonsNormalize::isHeading($item_obj);
                    $is_text = ($type == 'text');
                    $in_content_list = ( ! $is_heading && ! $is_text
                        && LessonsNormalize::sectionGroup($item_obj) );

                    if ( ! $in_content_list ) {
                        if ( $in_list ) {
                            echo("</ul>\n");
                            $in_list = false;
                        }
                        $lessons->renderItem($item_obj, $module, $nostyle);
                        continue;
                    }

                    if ( ! $in_list ) {
                        echo('<ul class="tsugi-lessons-content-list">'."\n");
                        $in_list = true;
                    }

                    $lessons->renderItem($item_obj, $module, $nostyle);
                }

                if ( $in_list ) {
                    echo("</ul>\n");
                }
                
                if ( $debug_conversion && $has_legacy ) {
                    echo('</div>');
                    echo('<div style="border: 2px solid red; padding: 10px; margin: 10px 0;"><h3 style="color: red;">LEGACY FORMAT (old arrays):</h3>');
                }
                
                // If debug is off or no legacy format, return early (only render items)
                if ( !$debug_conversion || !$has_legacy ) {
                    if ( $nostyle ) {
                        $styleoff = U::get_rest_path() . '?nostyle=no';
                        echo('<p><a href="'.$styleoff.'">');
                        echo(__('Turn styling back on'));
                        echo("</a>\n");
                    }
                    
                    $ob_output = ob_get_contents();
                    ob_end_clean();
                    if ( $buffer ) return $ob_output;
                    echo($ob_output);
                    return;
                }
                // Otherwise continue to render legacy format below
            }

            // Legacy format: continue with existing rendering
            if ( isset($module->carousel) ) {
                $carousel = $module->carousel;
                $videotitle = __($lessons->getSetting('videos-title', 'Videos'));
                echo($nostyle ? $videotitle . ': <ul>' : '<ul class="bxslider">'."\n");
                foreach($carousel as $video ) {
                    echo('<li>');
                    if ( $nostyle ) {
                        echo(htmlentities($video->title)."<br/>");
                        $yurl = U::youtubeWatchUrl($video->youtube);
                        LessonsUI::nostyleUrl($video->title, $yurl);
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

            if ( isset($module->description) ) {
                echo('<p property="oer:description" class="tsugi-lessons-module-description">'.$module->description."</p>\n");
            }

            echo("<ul>\n");

            if ( isset($module->videos) ) {
                $videos = $module->videos;
                $media_folder = $CFG->getExtension('media_folder', null);
                $media_base = $CFG->getExtension('media_base', null);
                echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-videos">');
                $videotitle = __($lessons->getSetting('videos-title', 'Videos'));
                echo("<p>");
                echo($videotitle);
                echo("</p>");
                echo('<ul class="tsugi-lessons-module-videos-ul">'."\n");
                $lecno = 0;
                foreach($videos as $video ) {
                    $media_file = $video->media ?? null;
                    echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-video">');
                    $kaltura_url = LessonsUI::kalturaEmbedUrl($video);
                    if ( $kaltura_url ) {
                        if ( $nostyle ) {
                            LessonsUI::nostyleUrl($video->title, LessonsUI::kalturaTabUrl($video));
                        } else {
                            $lessons->renderKalturaOverlay(
                                $video->title,
                                $kaltura_url,
                                false,
                                LessonsUI::kalturaTabUrl($video)
                            );
                        }
                    } else if ( is_string($media_file) && is_string($media_base) && is_string($media_folder) &&
                        file_exists($media_folder . '/' . $media_file) ) {
                        $media_path = $media_base . '/' . $media_file;
?>
<a href="<?= $media_path ?>" target="_blank" rel="noopener noreferrer"><?= htmlentities($video->title) ?></a>
<?php
                    } else {
                        $yurl = U::youtubeWatchUrl($video->youtube);
                        if ( !empty($CFG->youtube_use_labnol) ) {
                        $lecno = $lecno + 1;
                        $navid = md5($lecno.$yurl);
                        // https://www.w3schools.com/howto/howto_js_fullscreen_overlay.asp
?>
<div id="<?= $navid ?>" class="w3schools-overlay" role="dialog" aria-modal="true" aria-label="Video: <?= htmlspecialchars($video->title, ENT_QUOTES, 'UTF-8') ?>">
  <div class="w3schools-overlay-content" style="background-color: black;">
  <button type="button" class="tsugi-overlay-close" aria-label="Close" onclick="document.getElementById('<?= $navid ?>').style.display='none'; if(typeof labnolStopPlayers==='function') labnolStopPlayers();">×</button>
  <div class="youtube-player" data-id="<?= $video->youtube ?>"></div>
  </div>
</div>
<button type="button" class="tsugi-video-play-btn" onclick="document.getElementById('<?= $navid ?>').style.display = 'block';"><?= htmlentities($video->title) ?></button>
<?php
                        } else {
                        echo('<a href="'.htmlspecialchars($yurl).'" target="_blank">'.htmlentities($video->title).'</a>');
                        }
                    }
                    echo("</li>\n");
                }
                echo("</ul></li>\n");
            }

            if ( isset($module->lectures) ) {
                $lectures = $module->lectures;
                echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-lectures">');
                $lecturetitle = __($lessons->getSetting('lectures-title', 'Lectures'));
                echo("<p>");
                echo($lecturetitle);
                echo("</p>");
                echo('<ul class="tsugi-lessons-module-lectures-ul">'."\n");
                $lecno = 1;
                foreach($lectures as $lecture ) {
                    $lecno = $lecno + 1;
                    if ( isset($lecture->youtube) ) {
                        echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-lecture tsugi-lessons-module-lecture-youtube">');
                        $yurl = U::youtubeWatchUrl($lecture->youtube);
                        if ( !empty($CFG->youtube_use_labnol) ) {
                        // https://www.w3schools.com/howto/howto_js_fullscreen_overlay.asp
                        $navid = md5($lecno.$yurl);
?>
<div id="<?= $navid ?>" class="w3schools-overlay" role="dialog" aria-modal="true" aria-label="Video: <?= htmlspecialchars($lecture->title, ENT_QUOTES, 'UTF-8') ?>">
  <div class="w3schools-overlay-content" style="background-color: black;">
  <button type="button" class="tsugi-overlay-close" aria-label="Close" onclick="document.getElementById('<?= $navid ?>').style.display='none'; if(typeof labnolStopPlayers==='function') labnolStopPlayers();">×</button>
  <div class="youtube-player" data-id="<?= $lecture->youtube ?>"></div>
  </div>
</div>
<button type="button" class="tsugi-video-play-btn" onclick="document.getElementById('<?= $navid ?>').style.display = 'block';"><?= htmlentities($lecture->title) ?></button>
<?php
                        } else {
                        echo('<a href="'.htmlspecialchars($yurl).'" target="_blank">'.htmlentities($lecture->title).'</a>');
                        }
                        echo('</li>');
                    } else if ( isset($lecture->audio) ) {
                        echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-lecture tsugi-lessons-module-lecture-audio">');
                        // LessonsUI::nostyleLink($lecture->title, $lecture->audio);
                        $navid = md5($lecno.$lecture->audio);
?>
<div id="<?= $navid ?>" class="w3schools-overlay" role="dialog" aria-modal="true" aria-label="Audio: <?= htmlspecialchars($lecture->title, ENT_QUOTES, 'UTF-8') ?>">
  <div class="w3schools-overlay-content" style="background-color: black;">
  <button type="button" class="tsugi-overlay-close" aria-label="Close" onclick="document.getElementById('<?= $navid ?>').style.display='none';">×</button>
<h2><?= htmlentities($lecture->title) ?></h2>
  <audio controls preload='none' src="<?= LessonsUI::expandLink($lecture->audio) ?>"></audio>
  </div>
</div>
<button type="button" class="tsugi-video-play-btn" onclick="document.getElementById('<?= $navid ?>').style.display = 'block';"><?= htmlentities($lecture->title) ?></button>
<?php
                        echo('</li>');
                    } else if ( isset($lecture->video) ) {
                        echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-lecture tsugi-lessons-module-lecture-video">');
                        $yurl = U::youtubeWatchUrl($lecture->video);
                        // LessonsUI::nostyleLink($lecture->title, $lecture->video);
                        $navid = md5($lecno.$lecture->video);
?>
<div id="<?= $navid ?>" class="w3schools-overlay" role="dialog" aria-modal="true" aria-label="Video: <?= htmlspecialchars($lecture->title, ENT_QUOTES, 'UTF-8') ?>">
  <div class="w3schools-overlay-content" style="background-color: black;">
  <button type="button" class="tsugi-overlay-close" aria-label="Close" onclick="document.getElementById('<?= $navid ?>').style.display='none';">×</button>
  <video controls style="width:95%;" preload="none" src="<?= LessonsUI::expandLink($lecture->video) ?>"></video>
  </div>
</div>
<button type="button" class="tsugi-video-play-btn" onclick="document.getElementById('<?= $navid ?>').style.display = 'block';"><?= htmlentities($lecture->title) ?></button>
<?php
                        echo('</li>');
                    }
                }
                echo("</ul></li>\n");
            }

            if ( isset($module->slides) ) {
                $singular = 'slide';
                $plural = $singular.'s';
                echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-slides">');
                echo("<p>");
                $slidestitle = __($lessons->getSetting($plural.'title', ucfirst($plural)));
                echo(__($slidestitle));
                echo("</p>");
                echo('<ul class="tsugi-lessons-module-'.$plural.'-ul">'."\n");
                foreach($module->slides as $slide ) {
                    if ( is_string($slide) ) {
                        $slide_title = basename($slide);
                        $slide_href = $slide;
                    } else {
                        $slide_title = $slide->title ;
                        $slide_href = $slide->href ;
                    }
                    echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-'.$singular.'">');
                    echo('<span class="tsugi-lessons-module-'.$singular.'-icon"></span>');
                    echo('<span class="tsugi-lessons-module-'.$singular.'-link">');
                    LessonsUI::nostyleLink($slide_title, $slide_href);
                    echo("</span>\n");
                    echo('</li>'."\n");
                }
                if ( count($module->slides) > 0 ) {
                    echo("</ul></li>\n");
                }
            }
            if ( isset($module->chapters) ) {
                echo('<li typeof="SupportingMaterial">'.__('Chapters').': '.$module->chapters.'</a></li>'."\n");
            }
            if ( isset($module->assignment) ) {
                if ( $nostyle ) {
                    echo('<li typeof="oer:assessment">'.__('Assignment Specification').':');
                    LessonsUI::nostyleUrl(__('Assignment Specification'), $module->assignment);
                    echo('</li>'."\n");
                } else {
                    echo('<li typeof="oer:assessment"><a href="'.$module->assignment.'" target="_blank" rel="noopener noreferrer">'.__('Assignment Specification').'</a></li>'."\n");
                }
            }
            if ( isset($module->solution) ) {
                if ( $nostyle ) {
                    echo('<li typeof="oer:assessment">'.__('Assignment Solution').':');
                    LessonsUI::nostyleUrl(__('Assignment Solution'), $module->solution);
                    echo('</li>'."\n");
                } else {
                    echo('<li typeof="oer:assessment"><a href="'.$module->solution.'" target="_blank" rel="noopener noreferrer">'.__('Assignment Solution').'</a></li>'."\n");
                }
            }

            // Reference like entries
            $lists = array("reference", "assignment");
            foreach($lists as $list) {
                $singular = $list;
                $plural = $list."s";
                if ( isset($module->{$plural}) ) {
                    echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-'.$plural.'">');
                    $list_title = __($lessons->getSetting($plural.'-title', ucfirst($plural)));
                    echo("<p>");
                    echo(__($list_title));
                    echo("</p>");
                    echo('<ul class="tsugi-lessons-module-'.$plural.'-ul">'."\n");
                    foreach($module->{$plural} as $reference ) {
                        echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-'.$singular.'">');
                        echo('<span class="tsugi-lessons-module-'.$singular.'-icon"></span>');
                        echo('<span class="tsugi-lessons-module-'.$singular.'-link">');
                        LessonsUI::nostyleLink($reference->title, $reference->href);
                        echo("</span>\n");
                        echo('</li>'."\n");
                    }
                    echo("</ul></li>\n");
                }
            }

            // DISCUSSIONs not logged in
            if ( isset($module->discussions) && ! isset($_SESSION['secret']) ) {
                $discussions = $module->discussions;
                echo('<li typeof="oer:discussion" class="tsugi-lessons-module-discussions">');
                echo(__('Discussions:'));
                echo('<ul class="tsugi-lessons-module-discussions-ul"> <!-- start of discussions -->'."\n");
                foreach($discussions as $discussion ) {
                    $resource_link_title = isset($discussion->title) ? $discussion->title : $module->title;
                    echo('<li typeof="oer:discussion" class="tsugi-lessons-module-discussion">'.htmlentities($resource_link_title).' ('.__('Login Required').') <br/>'."\n");
                    echo("\n</li>\n");
                }
                echo("</li></ul><!-- end of discussions -->\n");
            }

            // DISCUSSIONs logged in
            if ( isset($module->discussions)
                && U::get($_SESSION,'secret') && U::get($_SESSION,'context_key')
                && U::get($_SESSION,'user_key') && U::get($_SESSION,'displayname') && U::get($_SESSION,'email') )
            {
                $discussions = $module->discussions;
                echo('<li typeof="oer:discussion" class="tsugi-lessons-module-discussions">');
                echo(__('Discussions:'));
                echo('<ul class="tsugi-lessons-module-discussions-ul"> <!-- start of discussions -->'."\n");
                $count = 0;
                foreach($discussions as $discussion ) {
                    $resource_link_title = isset($discussion->title) ? $discussion->title : $module->title;

                    if ( $nostyle ) {
                        echo('<li typeof="oer:discussion" class="tsugi-lessons-module-discussion">'.htmlentities($resource_link_title).' (Login Required) <br/>'."\n");
                        $discussionurl = U::add_url_parm(LessonsNormalize::launchUrlForItem($discussion), 'inherit', $discussion->resource_link_id);
                        echo('<span style="color:green">'.htmlentities($discussionurl)."</span>\n");
                        if ( isset($_SESSION['gc_count']) ) {
                            echo('<a href="'.$CFG->wwwroot.'/gclass/assign?rlid='.$discussion->resource_link_id);
                            echo('" title="Install Assignment in Classroom" target="iframe-frame"'."\n");
                            echo("onclick=\"showModalIframe(this.title, 'iframe-dialog', 'iframe-frame', _TSUGI.spinnerUrl, true);\" >\n");
                            echo('<img height=16 width=16 src="https://www.gstatic.com/classroom/logo_square_48.svg"></a>'."\n");
                        }
                        echo("\n</li>\n");
                        continue;
                    }

                    $launch_path = $lessons->lessonsLaunchPath($discussion->resource_link_id);
                    $title = isset($discussion->title) ? $discussion->title : "Discussion";
                    echo('<li class="tsugi-lessons-module-discussion"><a href="'.$launch_path.'">'.htmlentities($title).'</a></li>'."\n");
                    echo("\n</li>\n");
                }

                echo("</li></ul><!-- end of discussions -->\n");
            }

            // LTIs not logged in
            if ( isset($module->lti) && ! isset($_SESSION['secret']) ) {
                $ltis = $module->lti;
                echo('<li typeof="oer:assessment" class="tsugi-lessons-module-ltis">');
                echo(__('Tools:'));
                echo('<ul class="tsugi-lessons-module-ltis-ul"> <!-- start of ltis -->'."\n");
                foreach($ltis as $lti ) {
                    $resource_link_title = isset($lti->title) ? $lti->title : $module->title;
                    echo('<li typeof="oer:assessment" class="tsugi-lessons-module-lti">'.htmlentities($resource_link_title).' ('.__('Login Required').') <br/>'."\n");
                    echo("\n</li>\n");
                }
                echo("</li></ul><!-- end of ltis -->\n");
            }

            // LTIs logged in
            if ( isset($module->lti) && U::get($_SESSION,'secret') && U::get($_SESSION,'context_key')
                && U::get($_SESSION,'user_key') && U::get($_SESSION,'displayname') && U::get($_SESSION,'email') )
            {
                $ltis = $module->lti;
                echo('<li typeof="oer:assessment" class="tsugi-lessons-module-ltis">');
                echo(__('Tools:'));
                echo('<ul class="tsugi-lessons-module-ltis-ul"> <!-- start of ltis -->'."\n");
                $count = 0;
                foreach($ltis as $lti ) {
                    $resource_link_title = isset($lti->title) ? $lti->title : $module->title;

                    if ( $nostyle ) {
                        echo('<li typeof="oer:assessment" class="tsugi-lessons-module-lti">'.htmlentities($resource_link_title).' (Login Required) <br/>'."\n");
                        $ltiurl = U::add_url_parm($lti->launch, 'inherit', $lti->resource_link_id);
                        echo('<span style="color:green">'.htmlentities($ltiurl)."</span>\n");
                        if ( isset($_SESSION['gc_count']) ) {
                            echo('<a href="'.$CFG->wwwroot.'/gclass/assign?rlid='.$lti->resource_link_id);
                            echo('" title="Install Assignment in Classroom" target="iframe-frame"'."\n");
                            echo("onclick=\"showModalIframe(this.title, 'iframe-dialog', 'iframe-frame', _TSUGI.spinnerUrl, true);\" >\n");
                            echo('<img height=16 width=16 src="https://www.gstatic.com/classroom/logo_square_48.svg"></a>'."\n");
                        }
                        echo("\n</li>\n");
                        continue;
                    }

                    $launch_path = $lessons->lessonsLaunchPath($lti->resource_link_id);
                    $title = isset($lti->title) ? $lti->title : "Autograder";
                    $target = isset($lti->target) ? $lti->target : false;

                    echo('<li class="tsugi-lessons-module-lti"><a');
                    if ( $target == "_blank" ) echo(' target="_blank" rel="noopener noreferrer" onclick="alert(\'Link will open in a new browser tab...\');" ');
                    echo(' href="'.$launch_path.'">'.htmlentities($title).'</a>');
                    $rlid = isset($lti->resource_link_id) ? $lti->resource_link_id : '';
                    LessonsUI::echoLtiLinkProgressIndicators($rlid, $lti, $allgrades, $moduleDueDates);
                    echo('</li>'."\n");
                }

                echo("</li></ul><!-- end of ltis -->\n");
            }

        echo("</ul>\n");
        
        // Close legacy format div if it was opened (debug mode only)
        if ( $debug_conversion && $has_items && $has_legacy ) {
            echo('</div>');
        }

        if ( $nostyle ) {
            $styleoff = U::get_rest_path() . '?nostyle=no';
            echo('<p><a href="'.$styleoff.'">');
            echo(__('Turn styling back on'));
            echo("</a>\n");
        }

        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $buffer ) return $ob_output;
        echo($ob_output);
    } // End of renderSingle

    public static function renderAll(\Tsugi\UI\Lessons $lessons, $buffer=false)
    {
        ob_start();

         // Load all the Grades so far
         $allgrades = array();
         $rows = GradeUtil::loadGradesCurrentUser();
         foreach ( $rows as $row ) {
             $allgrades[$row['resource_link_id']] = $row['grade'];
         }

        $duedates = array();
        if ( U::currentContextId() !== 0 ) {
            $duedates = GradeUtil::loadDueDatesForDisplay(U::currentContextId());
        }

        echo('<div typeof="Course">'."\n");
        echo('<h1>'.$lessons->lessons->title."</h1>\n");
        echo('<p property="description">'.$lessons->lessons->description."</p>\n");
        echo('<div id="box">'."\n");
        $count = 0;

        foreach($lessons->lessons->modules as $module) {
        if ( isset($module->hidden) && $module->hidden ) continue;
	    if ( isset($module->login) && $module->login && ! U::isLoggedIn() ) continue;

            $modProgress = $lessons->moduleLtiProgressPoints($module, $allgrades, $duedates);
            $possible_points = $modProgress[0];
            $actual_points = $modProgress[1];
            $rlids = $modProgress[2];
            $count++;
            $percent = 0;
            if ( $possible_points > 0 ) {
                $percent = (int) round(($actual_points / $possible_points)*100);
            }
            $hasDueMode = $possible_points > 0 && $lessons->moduleHasLtiDueDateInContext($rlids, $duedates);
            $rollupForIcon = null;
            if ( $hasDueMode && $percent < 100 ) {
                $rollupForIcon = $lessons->moduleWorstDueRollup($rlids, $allgrades, $duedates);
                if ( $rollupForIcon !== null && $rollupForIcon['modifier'] === 'tsugi-assignments-due-future' ) {
                    $rollupForIcon = null;
                }
            }
            
            echo('<article class="card"><div>'."\n");
            $href = U::get_rest_path() . '/' . urlencode($module->anchor);
            $link_label = $count.': '.$module->title;
            echo('<a class="tsugi-lessons-module-card-link" href="'.$href.'" aria-label="'.htmlspecialchars($link_label, ENT_QUOTES, 'UTF-8').'">'."\n");
            if ( isset($module->icon) ) {
                $icon_color = self::moduleCardIconColorStyle($percent, $hasDueMode, $rollupForIcon);
                echo('<i class="fa '.$module->icon.' fa-2x" aria-hidden="true" style="float: left; padding-right: 5px;'.$icon_color.'"></i>');
            }
            if ( isset($module->image) ) {
                echo('<img class="tsugi-all-modules-image-icon" aria-hidden="true" style="float: left; width: 2em; padding-right: 5px;" src="'.LessonsUI::expandLink($module->image).'">');
            }
            echo($link_label);
            self::echoModuleAggregateProgressBadge($lessons, $possible_points, $actual_points, $rlids, $allgrades, $duedates, false);
            if ( isset($module->description) ) {
                $desc = $module->description;
                if ( strlen($desc) > 1000 ) $desc = substr($desc, 0, 1000);
                echo('<br clear="all"><p class="tsugi-card-description">'.$desc."</p>\n");
            }
            echo("</a></div></article>\n");
            echo("<!--\n");
            print_r($allgrades);
            print_r($rlids);
            echo("\n-->\n");
        }
        echo('</div> <!-- box -->'."\n");
        echo('</div> <!-- typeof="Course" -->'."\n");
        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $buffer ) return $ob_output;
        echo($ob_output);
    }

}
