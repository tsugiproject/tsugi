<?php

namespace Tsugi\Controllers;

use Tsugi\Util\U;
use Tsugi\Core\LTIX;
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
        global $CFG, $OUTPUT;

        if ( ! isset($CFG->lessons) ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }

        if ( ! empty($_SESSION[Tool::SESSION_LESSONS_GRADE_REFRESH_AFTER_LAUNCH]) ) {
            \Tsugi\Grades\GradeUtil::invalidateGradesCurrentUser();
            unset($_SESSION[Tool::SESSION_LESSONS_GRADE_REFRESH_AFTER_LAUNCH]);
        }

        // Turning on and off styling
        if ( isset($_GET['nostyle']) ) {
            if ( $_GET['nostyle'] == 'yes' ) {
                $_SESSION['nostyle'] = 'yes';
            } else {
                unset($_SESSION['nostyle']);
            }
        }

        $l = new \Tsugi\UI\Lessons($CFG->lessons, $anchor);

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
        // Show Author link for instructors when authoring is allowed
        if ( $CFG->canAuthor() && $this->isInstructor() ) {
            $author_url = U::addSession($this->toolHome(self::ROUTE) . '/_author');
            echo('<span style="position: fixed; right: 10px; top: 75px; z-index: 999; background-color: white; padding: 4px 8px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.2);">');
            echo('<a href="'.htmlspecialchars($author_url).'" class="btn btn-default btn-sm"><i class="fa fa-pencil" aria-hidden="true"></i> '.__('Author').'</a>');
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
     * Lesson authoring interface - localhost and instructor only
     */
    public function author(Request $request)
    {
        global $CFG, $OUTPUT, $PDOX;

        if ( ! $CFG->canAuthor() ) {
            return new Response('Lesson authoring is not enabled', 403);
        }
        $this->requireInstructor(U::addSession($this->toolHome(self::ROUTE)));

        if ( ! isset($CFG->lessons) ) {
            return new Response('Cannot find lessons file ($CFG->lessons)', 500);
        }
        $lessons_file = $CFG->lessons;

        if ( ! file_exists($lessons_file) ) {
            return new Response('Lessons file not found: ' . htmlspecialchars($lessons_file), 404);
        }

        LTIX::getConnection();
        $lessons_json = file_get_contents($lessons_file);
        $lessons_data = json_decode($lessons_json, true);
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new Response('Error parsing JSON: ' . json_last_error_msg(), 500);
        }

        $lessons_title = htmlspecialchars($lessons_data['title'] ?? 'Untitled');
        $lessons_file_escaped = htmlspecialchars($lessons_file);
        $lessons_json = json_encode($lessons_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $lessons_url = U::addSession($this->toolHome(self::ROUTE));
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        echo('<span style="position: fixed; right: 10px; top: 75px; z-index: 999; background-color: white; padding: 4px 8px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.2);">');
        echo('<a href="'.htmlspecialchars($lessons_url).'" class="btn btn-default btn-sm"><i class="fa fa-arrow-left" aria-hidden="true"></i> '.__('Back to Lessons').'</a>');
        echo('</span>');

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
        global $CFG;

        if ( ! $CFG->canAuthor() ) {
            return new Response(json_encode(['success' => false, 'error' => 'Not allowed']), 403, ['Content-Type' => 'application/json']);
        }
        $this->requireInstructor(U::addSession($this->toolHome(self::ROUTE)));

        if ( ! isset($CFG->lessons) ) {
            return new Response(json_encode(['success' => false, 'error' => 'Lessons not configured']), 500, ['Content-Type' => 'application/json']);
        }
        $lessons_file = $CFG->lessons;

        $action = U::get($_POST, 'action');
        if ( $action !== 'save' ) {
            return new Response(json_encode(['success' => false, 'error' => 'Unknown action']), 400, ['Content-Type' => 'application/json']);
        }

        $data = U::get($_POST, 'data');
        if ( ! $data ) {
            return new Response(json_encode(['success' => false, 'error' => 'No data provided']), 400, ['Content-Type' => 'application/json']);
        }

        $lessons_data = json_decode($data, true);
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new Response(json_encode(['success' => false, 'error' => 'Invalid JSON: ' . json_last_error_msg()]), 400, ['Content-Type' => 'application/json']);
        }

        $json_output = json_encode($lessons_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $result = @file_put_contents($lessons_file, $json_output);
        if ( $result === false ) {
            return new Response(json_encode(['success' => false, 'error' => 'Failed to write file']), 500, ['Content-Type' => 'application/json']);
        }

        return new Response(json_encode(['success' => true, 'message' => 'File saved successfully']), 200, ['Content-Type' => 'application/json']);
    }

    public static function launch(Application $app, $anchor=null)
    {
        global $CFG;

        $path = U::rest_path();
        $redirect_path = U::addSession($path->parent);
        if ( $redirect_path == '') $redirect_path = '/';

        if ( ! isset($CFG->lessons) ) {
            $app->tsugiFlashError(__('Cannot find lessons.json ($CFG->lessons)'));
            return new RedirectResponse($redirect_path);
        }

        $l = new \Tsugi\UI\Lessons($CFG->lessons);
        if ( ! $l ) {
            $app->tsugiFlashError(__('Cannot load lessons.'));
            return new RedirectResponse($redirect_path);
        }

        $lti = $l->getLtiByRlid($anchor);
        if ( ! $lti ) {
            $app->tsugiFlashError(__('Cannot find lti resource link id'));
            return new RedirectResponse($redirect_path);
        }

        $module = $l->getModuleByRlid($anchor);

        $lessons_base = $path->parent . '/' . str_replace('_launch', '', $path->controller);
        $return_url = $module
            ? $lessons_base . '/' . $module->anchor
            : $lessons_base;

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
