<?php

namespace Koseu\Controllers;

use Tsugi\Util\U;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Lessons {

    const ROUTE = '/lessons';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->get($prefix, 'Koseu\\Controllers\\Lessons::get');
        $app->get($prefix.'/', 'Koseu\\Controllers\\Lessons::get');
        $app->get($prefix.'/{anchor}', 'Koseu\\Controllers\\Lessons::get');
    }

    public function get(Request $request, Application $app, $anchor=null)
    {
        global $CFG;
        $tsugi = $app['tsugi'];

        if ( ! isset($CFG->lessons) ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }

        // Turning on and off styling
        if ( isset($_GET['nostyle']) ) {
            if ( $_GET['nostyle'] == 'yes' ) {
                $_SESSION['nostyle'] = 'yes';
            } else {
                unset($_SESSION['nostyle']);
            }
        }

        // Load the Lesson
        $l = new \Tsugi\UI\Lessons($CFG->lessons,$anchor);

        $context = array();
        $context['head'] = $l->header(true);
        $context['container'] = $l->render(true);
        $context['footer'] = $l->footer(true);

        return $app['twig']->render('@Koseu/Lessons.twig',$context);

    }
}
