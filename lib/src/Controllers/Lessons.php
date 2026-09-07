<?php

namespace Tsugi\Controllers;

use Tsugi\Util\U;
use Tsugi\Core\LTIX;
use Tsugi\Core\Manifest;
use Tsugi\Lumen\Application;
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
        $l->header();
        echo('<div class="container">');
        $l->render();
        echo('</div>');
        $OUTPUT->footerStart();
        $l->footer();
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

        $files_json_url = U::addSession($this->toolHome(Files::ROUTE) . '/json');
        $files_home_url = U::addSession($this->toolHome(Files::ROUTE));
        $pages_home = $this->toolHome(Pages::ROUTE);
        $pages_json_url = U::addSession($pages_home . '/json');
        $lessons_json_url = U::addSession($pages_home . '/lessons-json');
        $pages_base = $pages_home;
        $app_home = (isset($CFG->apphome) && is_string($CFG->apphome)) ? rtrim($CFG->apphome, '/') : '';
        $lessons_url = U::addSession($this->toolHome(self::ROUTE));
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
        if ( Manifest::activeId() < 1 || ! Manifest::currentIsV2() ) {
            return 'Lesson authoring only saves Lessons JSON v2 to the course manifest';
        }
        $context_id = U::currentContextId();
        if ( $context_id < 1 ) {
            return 'No course context';
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

}
