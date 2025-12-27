<?php

namespace Koseu\Controllers;

use Tsugi\UI\SimpleApplication;
use Symfony\Component\HttpFoundation\Request;

use \Tsugi\Grades\GradeUtil;

class Assignments {

    const ROUTE = '/assignments';

    public static function routes(SimpleApplication $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Assignments@get');
        $app->router->get($prefix.'/', 'Assignments@get');
    }

    public function get(Request $request)
    {
        global $CFG, $OUTPUT;

        if ( ! isset($CFG->lessons) ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }

        // Load the Lesson
        $l = new \Tsugi\UI\Lessons($CFG->lessons);

        // Load all the Grades so far
        $allgrades = array();
        $alldates = array();
        if ( isset($_SESSION['id']) && isset($_SESSION['context_id'])) {
            $rows = GradeUtil::loadGradesForCourse($_SESSION['id'], $_SESSION['context_id']);
            foreach($rows as $row) {
                $allgrades[$row['resource_link_id']] = $row['grade'];
                $alldates[$row['resource_link_id']] = $row['updated_at'];
            }
        }

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $menu = false;
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        $l->renderAssignments($allgrades, $alldates, false);
        $OUTPUT->footer();
    }
}
