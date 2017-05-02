<?php

namespace Tsugi\Views;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use \Tsugi\Core\Settings;
use \Tsugi\Util\Net;

class Map {
    public function get(Request $request, Application $app)
    {
        global $CFG;
        if ( !isset($_SESSION['id']) ) {
            return $app['twig']->render('@Tsugi/Error.twig',
                array('error' => '<p>You are not logged in.</p>')
                );
        }

        if ( !isset($CFG->google_map_api_key) ) {
            return $app['twig']->render('@Tsugi/Error.twig',
                array('error' => '<p>There is no MAP api key ($CFG->google_map_api_key)</p>')
                );
        }

        return $app['twig']->render('@Tsugi/Map.twig');
    }
}
