<?php

namespace Tsugi\Controllers;

use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Tsugi\Core\LTIX;

use \Tsugi\Grades\GradeUtil;

class Badges extends Tool {

    const ROUTE = '/badges';
    const NAME = 'Badges';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Badges@get');
        $app->router->get($prefix.'/', 'Badges@get');
        $app->router->get($prefix.'/analytics', 'Badges@analytics');
    }

    public function get(Request $request)
    {
        global $CFG, $OUTPUT;

        // Ensure database connection
        LTIX::getConnection();

        // Check if user is logged in
        $this->requireAuth();

        if ( ! isset($CFG->lessons) ) {
            die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
        }

        // Record learner analytics (synthetic lti_link in this context)
        $this->lmsRecordLaunchAnalytics(self::ROUTE, self::NAME);

        // Check if user is instructor/admin for analytics button
        $show_analytics = $this->isInstructor() || $this->isAdmin();

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

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $menu = false;
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        if ( $show_analytics ) {
            $analytics_url = $this->toolHome(self::ROUTE) . '/analytics';
            echo('<span style="position: fixed; right: 10px; top: 75px; z-index: 999; background-color: white; padding: 2px;"><a href="'.$analytics_url.'" class="btn btn-default"><span class="glyphicon glyphicon-signal"></span> Analytics</a></span>');
        }
        $l->renderBadges($allgrades, false);
        $OUTPUT->footer();

    }

    /**
     * Analytics view for badges
     */
    public function analytics(Request $request)
    {
        return $this->showAnalytics(self::ROUTE, self::NAME);
    }
}
