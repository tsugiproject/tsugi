<?php

namespace Tsugi\Controllers;

use Laravel\Lumen\Routing\Controller;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;

use \Tsugi\Util\U;

class Analytics extends Controller {

    const ROUTE = '/analytics';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix.'/json', 'Analytics@getjson');
        $app->router->get($prefix, function (Request $request) use ($app) {
            return Analytics::getAnalytics($app);
        });
        $app->router->get($prefix.'/', function (Request $request) use ($app) {
            return Analytics::getAnalytics($app);
        });
    }

    public static function getAnalytics(Application $app)
    {
        global $CFG;
        $tsugi = $app['tsugi'];
        if ( !isset($tsugi->user) ) {
            return view('Error', ['error' => '<p>You are not logged in.</p>']);
        }

        $analytics_url = U::addSession($CFG->wwwroot."/api/analytics");

        $menu = new \Tsugi\UI\MenuSet();
        $menu->addLeft(__('Back'), 'index.php');
        $tsugi->tsugi_menu = $tsugi->output->topNav($menu);
        return view('Analytics', ['analytics_url' => $analytics_url]);
    }

}
