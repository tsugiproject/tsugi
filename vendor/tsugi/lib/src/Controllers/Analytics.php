<?php

namespace Tsugi\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use \Tsugi\Core\LTIX;

class Analytics {

    const ROUTE = '/analytics';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->get($prefix.'/json', 'Tsugi\\Controllers\\Analytics::getjson');
        $app->get($prefix, 'Tsugi\\Controllers\\Analytics::get');
        $app->get($prefix.'/', 'Tsugi\\Controllers\\Analytics::get');
    }

    public function get(Request $request, Application $app)
    {
        global $CFG;
        $tsugi = $app['tsugi'];
        if ( !isset($tsugi->user) ) {
            return $app['twig']->render('@Tsugi/Error.twig',
                array('error' => '<p>You are not logged in.</p>')
                );
        }

        $analytics_url = addSession($CFG->wwwroot."/api/analytics");

        return $app['twig']->render('@Tsugi/Analytics.twig', 
            array('analytics_url' => $analytics_url) );
    }

}
