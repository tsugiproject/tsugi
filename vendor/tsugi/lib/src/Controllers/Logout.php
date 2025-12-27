<?php

namespace Tsugi\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Tsugi\UI\SimpleApplication;

use \Tsugi\Crypt\SecureCookie;

class Logout {

    const ROUTE = '/logout';

    public static function routes(SimpleApplication $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, function (Request $request) use ($app) {
            global $CFG;
            session_unset();
            SecureCookie::delete();
            return $app->tsugiRedirectHome();
        });
    }
}
