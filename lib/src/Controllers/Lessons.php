<?php

namespace Tsugi\Controllers;

use Tsugi\Util\U;
use Tsugi\Core\LTIX;
use Tsugi\Core\Manifest;
use Tsugi\Grades\GradeUtil;
use Tsugi\Lumen\Application;
use Tsugi\Services\Quiz1\Quiz1Repository;
use Tsugi\Services\Lessons\LessonsService;
use Tsugi\Services\Lessons\LessonsNormalize;
use Tsugi\Services\Files\FileRepository;
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

        $json = \Tsugi\Services\Lessons\LessonsNormalize::serializeV2($decoded);
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

        $err = \Tsugi\Services\Lessons\LessonsNormalize::invalidQuizIdError($lessons_data);
        if ( $err !== null ) {
            return $err;
        }

        $lessons_data = \Tsugi\Services\Lessons\LessonsNormalize::normalizeDocument($lessons_data);
        $lessons_data['lessons_json_version'] = \Tsugi\Services\Lessons\LessonsNormalize::FORMAT_VERSION;

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
    public static function header(\Tsugi\Services\Lessons\LessonsService $lessons, $buffer=false) {
        global $CFG;
        ob_start();
        self::printLtiProgressStyles();
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
                $header = LessonsService::expandLink($header);
                echo($header);
                echo("\n");
            }
        }
        $ob_output = ob_get_contents();
        ob_end_clean();
        if ( $buffer ) return $ob_output;
        echo($ob_output);
    }

    public static function footer(\Tsugi\Services\Lessons\LessonsService $lessons, $buffer=false)
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
                $footer = LessonsService::expandLink($footer);
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
    public static function render(\Tsugi\Services\Lessons\LessonsService $lessons, $buffer=false) {
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
    private static function echoModuleAggregateProgressBadge(\Tsugi\Services\Lessons\LessonsService $lessons, $possible_points, $actual_points, $rlids, $allgrades, $duedates, $show_rollup_due_date = true) {
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
        $stateText = self::assignmentsDueStateVisibleLabel($rollup['modifier']);
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
    private static function echoModuleTitleBarMatchingCard(\Tsugi\Services\Lessons\LessonsService $lessons, $module, $allgrades, $duedates) {
        $modProgress = $lessons->moduleLtiProgressPoints($module, $allgrades, $duedates);
        $possible_points = $modProgress[0];
        $actual_points = $modProgress[1];
        $rlids = $modProgress[2];
        echo('<h1 property="oer:name" class="tsugi-lessons-module-title tsugi-lessons-module-title-cardmatch">');
        if ( isset($module->image) ) {
            echo('<img class="tsugi-all-modules-image-icon" aria-hidden="true" style="float: left; width: 2em; padding-right: 5px;" src="'.LessonsService::expandLink($module->image).'">');
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
    public static function renderSingle(\Tsugi\Services\Lessons\LessonsService $lessons, $buffer=false) {
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
                self::nostyleUrl($module->title, $lessonurl);
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
                        self::renderItem($lessons, $item_obj, $module, $nostyle);
                        continue;
                    }

                    if ( ! $in_list ) {
                        echo('<ul class="tsugi-lessons-content-list">'."\n");
                        $in_list = true;
                    }

                    self::renderItem($lessons, $item_obj, $module, $nostyle);
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
                    $kaltura_url = LessonsService::kalturaEmbedUrl($video);
                    if ( $kaltura_url ) {
                        if ( $nostyle ) {
                            self::nostyleUrl($video->title, LessonsService::kalturaTabUrl($video));
                        } else {
                            self::renderKalturaOverlay(
                                $video->title,
                                $kaltura_url,
                                false,
                                LessonsService::kalturaTabUrl($video)
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
                        // self::nostyleLink($lecture->title, $lecture->audio);
                        $navid = md5($lecno.$lecture->audio);
?>
<div id="<?= $navid ?>" class="w3schools-overlay" role="dialog" aria-modal="true" aria-label="Audio: <?= htmlspecialchars($lecture->title, ENT_QUOTES, 'UTF-8') ?>">
  <div class="w3schools-overlay-content" style="background-color: black;">
  <button type="button" class="tsugi-overlay-close" aria-label="Close" onclick="document.getElementById('<?= $navid ?>').style.display='none';">×</button>
<h2><?= htmlentities($lecture->title) ?></h2>
  <audio controls preload='none' src="<?= LessonsService::expandLink($lecture->audio) ?>"></audio>
  </div>
</div>
<button type="button" class="tsugi-video-play-btn" onclick="document.getElementById('<?= $navid ?>').style.display = 'block';"><?= htmlentities($lecture->title) ?></button>
<?php
                        echo('</li>');
                    } else if ( isset($lecture->video) ) {
                        echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-lecture tsugi-lessons-module-lecture-video">');
                        $yurl = U::youtubeWatchUrl($lecture->video);
                        // self::nostyleLink($lecture->title, $lecture->video);
                        $navid = md5($lecno.$lecture->video);
?>
<div id="<?= $navid ?>" class="w3schools-overlay" role="dialog" aria-modal="true" aria-label="Video: <?= htmlspecialchars($lecture->title, ENT_QUOTES, 'UTF-8') ?>">
  <div class="w3schools-overlay-content" style="background-color: black;">
  <button type="button" class="tsugi-overlay-close" aria-label="Close" onclick="document.getElementById('<?= $navid ?>').style.display='none';">×</button>
  <video controls style="width:95%;" preload="none" src="<?= LessonsService::expandLink($lecture->video) ?>"></video>
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
                    self::nostyleLink($slide_title, $slide_href);
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
                    self::nostyleUrl(__('Assignment Specification'), $module->assignment);
                    echo('</li>'."\n");
                } else {
                    echo('<li typeof="oer:assessment"><a href="'.$module->assignment.'" target="_blank" rel="noopener noreferrer">'.__('Assignment Specification').'</a></li>'."\n");
                }
            }
            if ( isset($module->solution) ) {
                if ( $nostyle ) {
                    echo('<li typeof="oer:assessment">'.__('Assignment Solution').':');
                    self::nostyleUrl(__('Assignment Solution'), $module->solution);
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
                        self::nostyleLink($reference->title, $reference->href);
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
                    self::echoLtiLinkProgressIndicators($rlid, $lti, $allgrades, $moduleDueDates);
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

    public static function renderAll(\Tsugi\Services\Lessons\LessonsService $lessons, $buffer=false)
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
                echo('<img class="tsugi-all-modules-image-icon" aria-hidden="true" style="float: left; width: 2em; padding-right: 5px;" src="'.LessonsService::expandLink($module->image).'">');
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

    /*
     * A Nostyle URL Link with title
     */
    public static function nostyleUrl($title, $url) {
        $url = LessonsService::expandLink($url);
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
        $url = LessonsService::expandLink($url);
        echo('<a href="'.$url.'" target="_blank" rel="noopener noreferrer" class="tsugi-lessons-link" typeof="oer:SupportingMaterial">'.htmlentities($title)."</a>\n");
        if ( isset($_SESSION['gc_count']) ) {
            echo('<div class="g-sharetoclassroom" data-size="16" data-url="'.$url.'" ');
	    echo(' data-title="'.htmlentities($title).'" ');
	    echo('></div>');
        }
    }

    /**
     * HTML for the result signature (optional separator + monospace code).
     */
    public static function resultLinkSignatureMarkup($resource_link_id, $link_id, $with_separator = true) {
        $rl_sig = LessonsService::resultLinkSignature($resource_link_id, $link_id);
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
        if ( ! LessonsService::ltiLaunchIsGraded($lti_item) ) {
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
            $mod = LessonsService::assignmentsDueBadgeModifier($resource_link_id, $due_end, $allgrades);
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
        $mod = LessonsService::assignmentsDueBadgeModifier($resource_link_id, $end, $grades_for_mod);
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
     * Get icon class for an item type
     */
    private static function getItemTypeIcon($type, $url_for_icon = null) {
        $pdf_types = array('slide', 'slides', 'reference', 'assignment', 'solution',
            'web_link', 'file', 'html_page', 'pdf');
        if ( $url_for_icon !== null && LessonsService::urlLooksLikePdf($url_for_icon)
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
        if ( $url_for_icon !== null && LessonsService::urlLooksLikePdf($url_for_icon)
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
        $pdf_class = ($url_for_icon !== null && LessonsService::urlLooksLikePdf($url_for_icon)
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
    public static function renderItem($lessons, $item, $module, $nostyle=false) {
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
                self::renderItemHeader($item);
                break;
            case 'text':
                self::renderItemText($item);
                break;
            case 'web_link':
            case 'html_page':
            case 'file':
                self::renderCanonicalResource($item, $module, $kind, $nostyle);
                break;
            case 'lti':
                if ( $kind === 'discussion' ) {
                    self::renderItemDiscussion($lessons, $item, $module, $nostyle);
                } else {
                    self::renderItemLti($lessons, $item, $module, $nostyle);
                }
                break;
            case 'video':
                self::renderItemVideo($item, $nostyle);
                break;
            case 'slide':
                self::renderItemSlide($item, $nostyle);
                break;
            case 'reference':
                self::renderItemReference($item, $nostyle);
                break;
            case 'discussion':
                self::renderItemDiscussion($lessons, $item, $module, $nostyle);
                break;
            case 'quiz':
                self::renderItemQuiz1($lessons, $item, $nostyle);
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
                        self::renderItem($lessons, $subitem_obj, $module, $nostyle);
                    }
                } else if ($type == 'slides' && (isset($item->href) || isset($item->url))) {
                    // Handle single slide object (legacy format)
                    $item->type = 'slide';
                    self::renderItem($lessons, $item, $module, $nostyle);
                }
                break;
            case 'assignment':
                self::renderItemAssignment($item, $nostyle);
                break;
            case 'solution':
                self::renderItemSolution($item, $nostyle);
                break;
            case 'chapters':
                self::renderItemChapters($item);
                break;
            case 'carousel':
                self::renderItemCarousel($lessons, $item, $nostyle);
                break;
            default:
                // Unknown type, skip
                break;
        }
    }

    /**
     * Dispatch a normalized web_link / html_page / file to the matching presentation.
     */
    private static function renderCanonicalResource($item, $module, $kind, $nostyle=false) {
        if ( $kind === 'video' ) {
            self::renderItemVideo($item, $nostyle);
            return;
        }
        if ( $kind === 'slide' ) {
            self::renderItemSlide($item, $nostyle);
            return;
        }
        if ( $kind === 'reference' ) {
            self::renderItemReference($item, $nostyle);
            return;
        }
        if ( $kind === 'assignment' ) {
            self::renderItemAssignment($item, $nostyle);
            return;
        }
        if ( $kind === 'solution' ) {
            self::renderItemSolution($item, $nostyle);
            return;
        }
        self::renderItemGenericLink($item, $kind, $nostyle);
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
    private static function renderItemGenericLink($item, $kind, $nostyle=false) {
        $title = isset($item->title) ? $item->title : (isset($item->text) ? $item->text : (isset($item->filename) ? $item->filename : ''));
        $href = isset($item->href) ? $item->href : (isset($item->url) ? $item->url : '');
        $href = LessonsService::expandLink($href);
        $css = $kind !== '' ? $kind : 'web_link';
        $icon_key = LessonsNormalize::iconKey($item);

        echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-'.$css.'">');
        if ( $nostyle ) {
            echo(htmlentities($title).':');
            self::nostyleUrl($title, $href);
        } else {
            self::renderWebLinkOpenControl($item, $href, $title, $icon_key);
        }
        echo("</li>\n");
    }

    /**
     * How a web link should open. Legacy items with no target stay new-tab.
     * Course pages carry the site navigation, so an html_page with no target
     * stays in this window. An uploaded course file always opens in a new tab.
     *
     * @param mixed $item
     * @return 'self'|'blank'|'modal'
     */
    public static function webLinkOpenMode($item) {
        $target = '';
        $type = '';
        $href = '';
        if ( is_object($item) ) {
            if ( isset($item->target) && is_string($item->target) ) {
                $target = $item->target;
            }
            if ( isset($item->type) && is_string($item->type) ) {
                $type = $item->type;
            }
            if ( isset($item->href) && is_string($item->href) ) {
                $href = $item->href;
            } else if ( isset($item->url) && is_string($item->url) ) {
                $href = $item->url;
            }
        } else if ( is_array($item) ) {
            if ( isset($item['target']) && is_string($item['target']) ) {
                $target = $item['target'];
            }
            if ( isset($item['type']) && is_string($item['type']) ) {
                $type = $item['type'];
            }
            if ( isset($item['href']) && is_string($item['href']) ) {
                $href = $item['href'];
            } else if ( isset($item['url']) && is_string($item['url']) ) {
                $href = $item['url'];
            }
        }
        if ( FileRepository::isUploadedFileHref($href) ) {
            return 'blank';
        }
        if ( $target === '_self' ) {
            return 'self';
        }
        if ( $target === 'modal' ) {
            return 'modal';
        }
        if ( $target === '' && $type === 'html_page' ) {
            return 'self';
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
    private static function renderWebLinkOpenControl($item, $href, $title, $icon_key, $css_class='tsugi-lessons-link') {
        $safe_href = self::safeWebHref($href);
        if ( self::webLinkOpenMode($item) === 'modal' && $safe_href !== '' ) {
            self::renderWebLinkModal($item, $safe_href, $title, $icon_key, $css_class);
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
    private static function renderWebLinkModal($item, $safe_href, $title, $icon_key, $css_class) {
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
    private static function renderItemHeader($item) {
        $text = isset($item->title) ? $item->title : (isset($item->text) ? $item->text : '');
        $class = isset($item->class) ? ' class="'.$item->class.'"' : '';
        echo("<h2{$class}>".htmlentities($text)."</h2>\n");
    }

    /**
     * Render a text item
     */
    private static function renderItemText($item) {
        $text = isset($item->text) ? $item->text : (isset($item->content) ? $item->content : '');
        $tag = isset($item->tag) ? $item->tag : 'p';
        $class = isset($item->class) ? ' class="'.$item->class.'"' : '';
        echo("<{$tag}{$class}>".$text."</{$tag}>\n");
    }

    /**
     * Emit the tsugi-kaltura-video web component (trigger + modal).
     */
    public static function renderKalturaOverlay($title, $embed_url, $with_icon=true, $tab_url=null) {
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
    private static function renderItemVideo($item, $nostyle=false) {
        global $CFG, $OUTPUT;
        
        $media_folder = $CFG->getExtension('media_folder', null);
        $media_base = $CFG->getExtension('media_base', null);
        $media_file = isset($item->media) ? $item->media : null;
        $kaltura_url = LessonsService::kalturaEmbedUrl($item);
        
        echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-video">');
        
        if ( $kaltura_url ) {
            if ( $nostyle ) {
                self::nostyleUrl($item->title, LessonsService::kalturaTabUrl($item));
            } else {
                self::renderKalturaOverlay(
                    $item->title,
                    $kaltura_url,
                    true,
                    LessonsService::kalturaTabUrl($item)
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
    private static function renderItemSlides($item, $nostyle=false) {
        if (isset($item->href) || isset($item->url)) {
            // Single slide
            self::renderItemSlide($item, $nostyle);
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
                self::renderItemSlide($slide_obj, $nostyle);
            }
            echo("</ul></li>\n");
        }
    }

    /**
     * Render a single slide item
     */
    private static function renderItemSlide($item, $nostyle=false) {
        $slide_title = isset($item->title) ? $item->title : (isset($item->text) ? $item->text : basename(isset($item->href) ? $item->href : (isset($item->url) ? $item->url : '')));
        $slide_href = isset($item->href) ? $item->href : (isset($item->url) ? $item->url : '');
        // Process {apphome} and other macros in the URL
        $slide_href = LessonsService::expandLink($slide_href);
        
        echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-slide">');
        echo('<span class="tsugi-lessons-module-slide-link">');
        self::renderWebLinkOpenControl($item, $slide_href, $slide_title, LessonsNormalize::iconKey($item));
        echo("\n</span>\n");
        echo('</li>'."\n");
    }

    /**
     * Render a reference item
     */
    private static function renderItemReference($item, $nostyle=false) {
        $title = isset($item->title) ? $item->title : '';
        $href = isset($item->href) ? $item->href : (isset($item->url) ? $item->url : '');
        // Process {apphome} and other macros in the URL
        $href = LessonsService::expandLink($href);
        
        echo('<li typeof="oer:SupportingMaterial" class="tsugi-lessons-module-reference">');
        echo('<span class="tsugi-lessons-module-reference-link">');
        self::renderWebLinkOpenControl($item, $href, $title, LessonsNormalize::iconKey($item));
        echo("\n</span>\n");
        echo('</li>'."\n");
    }

    /**
     * Render a discussion item
     */
    private static function renderItemDiscussion($lessons, $item, $module, $nostyle=false) {
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
            
            $launch_path = $lessons->lessonsLaunchPath($resource_link_id);
            echo('<li class="tsugi-lessons-module-discussion">');
            echo('<a href="'.$launch_path.'" style="display: inline-flex; align-items: center;">');
            self::renderItemIcon(LessonsNormalize::iconKey($item));
            echo(htmlentities($resource_link_title).'</a></li>'."\n");
        }
    }

    /**
     * Render an LTI item
     */
    private static function renderItemLti($lessons, $item, $module, $nostyle=false) {
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
            
            $launch_path = $lessons->lessonsLaunchPath($resource_link_id);
            $title = isset($item->title) ? $item->title : "Autograder";
            
            $rl_dom_id = LessonsService::domIdForResourceLink($resource_link_id);
            echo('<li class="tsugi-lessons-module-lti" id="'.htmlspecialchars($rl_dom_id, ENT_QUOTES, 'UTF-8').'">');
            echo('<a');
            if ( $target == "_blank" ) echo(' target="_blank" rel="noopener noreferrer" onclick="alert(\'Link will open in a new browser tab...\');" ');
            echo(' href="'.$launch_path.'" style="display: inline-flex; align-items: center;">');
            self::renderItemIcon(LessonsNormalize::iconKey($item));
            echo(htmlentities($title).'</a>');
            self::echoLtiLinkProgressIndicators($resource_link_id, $item, $lessons->moduleProgressGrades(), $lessons->moduleProgressDueDates());
            echo('</li>'."\n");
        }
    }

    /**
     * Native Quiz1 lesson item. Missing quizzes are hidden from students and
     * shown as unsatisfied references to instructors (Sakai-style).
     * Unpublished quizzes are hidden from students. Instructors see the title
     * with "(unpublished)" and no launch link.
     */
    private static function renderItemQuiz1($lessons, $item, $nostyle=false) {
        $title = isset($item->title) && is_string($item->title) && $item->title !== ''
            ? $item->title
            : __('Quiz');
        $quiz_id = LessonsNormalize::quizIdOf($item);
        $exists = $lessons->quiz1ExistsInCourse($quiz_id);
        $instructor = $lessons->lessonsViewerIsInstructor();
        if ( ! $exists ) {
            if ( ! $instructor ) {
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

        $published = $lessons->quiz1IsPublished($quiz_id);
        if ( ! $published && ! $instructor ) {
            return;
        }

        $href = '';
        $logged_in = U::isLoggedIn();
        if ( $published && $quiz_id > 0 && $logged_in && class_exists('\\Tsugi\\Controllers\\Quiz1') ) {
            $home = \Tsugi\Controllers\Tool::determineToolHome(\Tsugi\Controllers\Quiz1::ROUTE);
            if ( is_string($home) && $home !== '' ) {
                $href = U::addSession(\Tsugi\Controllers\Tool::joinToolHome($home, (string) $quiz_id));
            }
        }

        $suffix = '';
        if ( ! $published ) {
            $suffix = ' ('.__('unpublished').')';
        } else if ( $href === '' && ! $logged_in ) {
            $suffix = ' ('.__('Login Required').')';
        }

        echo('<li typeof="oer:assessment" class="tsugi-lessons-module-quiz1">');
        if ( $nostyle ) {
            echo(htmlentities($title).htmlentities($suffix));
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
            echo(htmlentities($title).htmlentities($suffix));
            echo('</span>');
        }
        echo("</li>\n");
    }

    /**
     * Render an assignment item
     */
    private static function renderItemAssignment($item, $nostyle=false) {
        $title = isset($item->title) ? $item->title : (isset($item->text) ? $item->text : __('Assignment Specification'));
        $url = isset($item->href) ? $item->href : (isset($item->url) ? $item->url : '');
        // Process {apphome} and other macros in the URL
        $url = LessonsService::expandLink($url);
        
        if ( $nostyle ) {
            echo('<li typeof="oer:assessment" class="tsugi-lessons-module-assignment">');
            echo(htmlentities($title).':');
            self::nostyleUrl($title, $url);
            echo('</li>'."\n");
        } else {
            echo('<li typeof="oer:assessment" class="tsugi-lessons-module-assignment">');
            self::renderWebLinkOpenControl($item, $url, $title, LessonsNormalize::iconKey($item));
            echo('</li>'."\n");
        }
    }

    /**
     * Render a solution item
     */
    private static function renderItemSolution($item, $nostyle=false) {
        $url = isset($item->href) ? $item->href : (isset($item->url) ? $item->url : '');
        // Process {apphome} and other macros in the URL
        $url = LessonsService::expandLink($url);
        
        if ( $nostyle ) {
            echo('<li typeof="oer:assessment" class="tsugi-lessons-module-solution">');
            echo(__('Assignment Solution').':');
            self::nostyleUrl(__('Assignment Solution'), $url);
            echo('</li>'."\n");
        } else {
            echo('<li typeof="oer:assessment" class="tsugi-lessons-module-solution">');
            self::renderWebLinkOpenControl($item, $url, __('Assignment Solution'), LessonsNormalize::iconKey($item));
            echo('</li>'."\n");
        }
    }

    /**
     * Render chapters item
     */
    private static function renderItemChapters($item) {
        $chapters = isset($item->text) ? $item->text : (isset($item->chapters) ? $item->chapters : '');
        echo('<li typeof="SupportingMaterial">'.__('Chapters').': '.htmlentities($chapters).'</li>'."\n");
    }

    /**
     * Render carousel item
     */
    private static function renderItemCarousel($lessons, $item, $nostyle=false) {
        global $CFG, $OUTPUT;
        
        if (!isset($item->items) || !is_array($item->items)) {
            return;
        }
        
        $videotitle = __($lessons->getSetting('videos-title', 'Videos'));
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
