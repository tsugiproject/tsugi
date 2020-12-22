<?php

namespace Tsugi\Controllers;
use Laravel\Lumen\Routing\Controller;
use Laravel\Lumen\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Tsugi\Lumen\Application;

use \Tsugi\Crypt\SecureCookie;

class Logout extends Controller {

    const ROUTE = '/logout';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, function (Request $request) use ($app) {
            global $CFG;
            session_unset();
            SecureCookie::delete();
            return $app->tsugiRedirectHome();
        });
    }
}
