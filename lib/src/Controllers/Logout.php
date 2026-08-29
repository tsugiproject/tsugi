<?php

namespace Tsugi\Controllers;
use Tsugi\Lumen\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Tsugi\Lumen\Application;

use \Tsugi\Core\Cache;

class Logout extends Controller {

    const ROUTE = '/logout';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $handler = function (Request $request) use ($app) {
            // Redundant with session_unset() (removes all $_SESSION keys). Explicit intent: flush cache_* on logout.
            Cache::clearAllSessionCaches();
            session_unset();
            global $CFG;
            if ( isset($CFG->logout_return_url) && is_string($CFG->logout_return_url) && $CFG->logout_return_url ) {
                return new RedirectResponse($CFG->logout_return_url);
            }
            return $app->tsugiRedirectHome();
        };
        $app->router->get($prefix, $handler);
        $app->router->get($prefix.'/', $handler);
        // Legacy bookmark / LMS return URLs still hit logout.php after the script was removed.
        $app->router->get($prefix.'.php', $handler);
    }
}
