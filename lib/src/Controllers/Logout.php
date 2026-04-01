<?php

namespace Tsugi\Controllers;
use Tsugi\Lumen\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Tsugi\Lumen\Application;

use \Tsugi\Crypt\SecureCookie;
use \Tsugi\Core\Cache;

class Logout extends Controller {

    const ROUTE = '/logout';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, function (Request $request) use ($app) {
            global $CFG;
            // Redundant with session_unset() (removes all $_SESSION keys). Explicit intent: flush cache_* on logout.
            Cache::clearAllSessionCaches();
            session_unset();
            SecureCookie::delete();
            return $app->tsugiRedirectHome();
        });
    }
}
