<?php

namespace Koseu\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use \Tsugi\UI\Lessons;
use \Tsugi\Grades\GradeUtil;

class Badges {

    const ROUTE = '/badges';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->get($prefix, 'Koseu\\Controllers\\Badges::get');
        $app->get($prefix.'/', 'Koseu\\Controllers\\Badges::get');
    }

    public function get(Request $request, Application $app)
    {
        global $CFG;
        $tsugi = $app['tsugi'];

        if ( ! isset($CFG->lessons) ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }

        // Load the Lesson
        $l = new \Tsugi\UI\Lessons($CFG->lessons);

        // Load all the Grades so far
        $allgrades = array();
        if ( isset($_SESSION['id']) && isset($_SESSION['context_id'])) {
            $rows = GradeUtil::loadGradesForCourse($_SESSION['id'], $_SESSION['context_id']);
            foreach($rows as $row) {
                $allgrades[$row['resource_link_id']] = $row['grade'];
            }
        }

        return $app['twig']->render('@Koseu/Badges.twig',
            array('data' => $l->renderBadges($allgrades, true))
        );

    }
}
