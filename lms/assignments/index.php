<?php

use \Tsugi\UI\Lessons;
use \Tsugi\Grades\GradeUtil;
use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../../config.php";
require_once "../lms-util.php";

LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( ! U::get($_SESSION,'id') ) {
    die('Must be logged in');
}

// Record learner analytics (synthetic lti_link in this context)
lmsRecordLaunchAnalytics('/lms/assignments', 'Assignments');

if ( ! isset($CFG->lessons) ) {
    die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
}

// Check if user is instructor/admin for analytics button
$is_instructor = isInstructor();
$is_admin = isAdmin();
$show_analytics = $is_instructor || $is_admin;

// Load the Lesson
$l = new Lessons($CFG->lessons);

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
if ( $show_analytics ) {
    echo('<p style="text-align: right;"><a href="analytics.php" class="btn btn-default"><span class="glyphicon glyphicon-signal"></span> Analytics</a></p>');
}
$l->renderAssignments($allgrades, $alldates, false);
$OUTPUT->footer();
