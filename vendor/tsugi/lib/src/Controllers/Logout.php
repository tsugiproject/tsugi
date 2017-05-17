<?php

namespace Tsugi\Controllers;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Silex\Application;

use \Tsugi\Crypt\SecureCookie;

class Logout {

    const ROUTE = '/logout';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->get($prefix, 'Tsugi\\Controllers\\Logout::get');
    }

    public function get(Request $request, Application $app)
    {
            global $CFG;
            session_unset();
            SecureCookie::delete();
            return $app->tsugiRedirectHome();
    }
}
