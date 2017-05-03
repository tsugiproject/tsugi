<?php

namespace Tsugi\Controllers;

use Silex\Application;

use \Tsugi\Crypt\SecureCookie;

class Logout {

    const ROUTE = '/logout';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->get($prefix, function () {
            global $CFG;
            session_unset();
            SecureCookie::delete();
            header('Location: '.$CFG->apphome.'/index.php');
        });
    }
}
