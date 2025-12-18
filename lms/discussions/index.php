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

if ( ! U::get($_SESSION,'id') ) {
    die('Must be logged in');
}

// Record learner analytics (synthetic lti_link in this context)
lmsRecordLaunchAnalytics('/lms/discussions', 'Discussions');

if ( ! isset($CFG->lessons) ) {
    die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
}

// Load the Lesson
$l = new Lessons($CFG->lessons);

$OUTPUT->header();
$OUTPUT->bodyStart();
$menu = false;
$OUTPUT->topNav();
$OUTPUT->flashMessages();
$content = $l->renderDiscussions(true);
// Ths is a hack but it works.  Someday we might want to adjust rest_path so there is always a controller even when there is a / at the end of the url
$content = str_replace('/_launch/', '/discussions_launch/', $content);
echo($content);

$OUTPUT->footer();
