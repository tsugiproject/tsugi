<?php

use \Tsugi\UI\Lessons;
use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../../config.php";
require_once "../lms-util.php";

LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( ! isset($CFG->lessons) ) {
    die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
}

// Record learner analytics (synthetic lti_link in this context) - only if logged in with context
if ( U::get($_SESSION,'id') && isset($_SESSION['context_id']) ) {
    lmsRecordLaunchAnalytics('/lms/discussions', 'Discussions');
}

// Check if user is instructor/admin for analytics button (handles missing id/context gracefully)
$is_instructor = isInstructor();
$is_admin = isAdmin();
$show_analytics = $is_instructor || $is_admin;

// Load the Lesson
$l = new Lessons($CFG->lessons);

$OUTPUT->header();
$OUTPUT->bodyStart();
$menu = false;
$OUTPUT->topNav();
$OUTPUT->flashMessages();
if ( $show_analytics ) {
    echo('<span style="position: fixed; right: 10px; top: 75px; z-index: 999; background-color: white; padding: 2px;"><a href="analytics.php" class="btn btn-default"><span class="glyphicon glyphicon-signal"></span> Analytics</a></span>');
}
$content = $l->renderDiscussions(true);
// Ths is a hack but it works.  Someday we might want to adjust rest_path so there is always a controller even when there is a / at the end of the url
$content = str_replace('/_launch/', '/discussions_launch/', $content);
echo($content);

$OUTPUT->footer();
