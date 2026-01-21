<?php

namespace Tsugi\Controllers;

use Laravel\Lumen\Routing\Controller;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;

use \Tsugi\Util\U;

class Analytics extends Controller {

    const ROUTE = '/analytics';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, function (Request $request) use ($app) {
            return Analytics::getAnalytics($app);
        });
        $app->router->get($prefix.'/', function (Request $request) use ($app) {
            return Analytics::getAnalytics($app);
        });
    }

    public static function getAnalytics(Application $app)
    {
        global $CFG, $OUTPUT;
        $tsugi = $app['tsugi'];
        // echo("<pre>\n");var_dump($tsugi);
        if ( !isset($tsugi->user) ) {
            $app->tsugiFlashError(__('You are not logged in.'));
            return redirect($redirect_path);
        }

        $analytics_url = U::addSession($CFG->wwwroot."/api/analytics");

        $menu = new \Tsugi\UI\MenuSet();
        $menu->addLeft(__('Back'), 'index.php');
        $OUTPUT->buffer = false;
        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav($menu);
        $OUTPUT->flashMessages();
        echo(\Tsugi\UI\Analytics::graphBody());
        $OUTPUT->footerStart();
        echo(\Tsugi\UI\Analytics::graphScript($analytics_url));
        $OUTPUT->footerEnd();
        return;
    }

}
