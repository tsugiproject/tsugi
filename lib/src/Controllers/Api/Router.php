<?php

namespace Tsugi\Controllers\Api;

/**
 * Maps the first /api/ path segment to the class that used to be that script.
 */
class Router {

    public static function dispatch(string $name): void
    {
        switch ($name) {
            case 'notifications':
                (new Notifications())->handle();
                return;
            case 'analytics_cookie':
                (new AnalyticsCookie())->handle();
                return;
            case 'analytics':
                (new Analytics())->handle();
                return;
            case 'settings':
                (new Settings())->handle();
                return;
            case 'socket':
                (new Socket())->handle();
                return;
            case 'grade-submit':
                (new GradeSubmit())->handle();
                return;
            case 'record-attempt':
                (new RecordAttempt())->handle();
                return;
            case 'rpc':
                (new Rpc())->handle();
                return;
            case 'poxresult':
                (new PoxResult())->handle();
                return;
            case 'ltiextroster':
                (new LtiExtRoster())->handle();
                return;
            case 'annotate':
                self::presentAs('annotate');
                (new Annotate())->handle();
                return;
            case 'stickygrader':
                self::presentAs('stickygrader');
                (new StickyGrader())->handle();
                return;
            default:
                self::notFound();
        }
    }

    /**
     * Annotate and stickygrader used to run as index.php inside their own
     * folders. rest_path() takes the controller from SCRIPT_NAME's directory,
     * and the navigation check exempts a script directory of exactly "api"
     * (it reads SCRIPT_FILENAME). Both have to keep looking like the old
     * index.php or a tool session would be accepted and the session id in
     * the path would be parsed as the word "annotate".
     */
    private static function presentAs(string $folder): void
    {
        global $CFG;
        $prefix = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $_SERVER['SCRIPT_NAME'] = $prefix.'/'.$folder.'/index.php';
        if ( isset($CFG->dirroot) ) {
            $_SERVER['SCRIPT_FILENAME'] = rtrim($CFG->dirroot, '/').'/api/'.$folder.'/index.php';
        }
    }

    private static function notFound(): void
    {
        global $OUTPUT;
        http_response_code(404);
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        echo("<h2>Page not found.</h2>\n");
        $OUTPUT->footer();
    }

}
