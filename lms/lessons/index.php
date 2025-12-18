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

if ( ! isset($_SESSION['context_id']) ) {
    die('Context required');
}

// Record learner analytics (synthetic lti_link in this context)
lmsRecordLaunchAnalytics('/lms/lessons', 'Lessons');

// Check if user is instructor/admin for analytics button
$is_instructor = isInstructor();
$is_admin = isAdmin();
$show_analytics = $is_instructor || $is_admin;

if ( ! isset($CFG->lessons) ) {
    die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
}

// Get anchor from URL path
$anchor = null;
// Check PATH_INFO first (set by Apache rewrite)
if ( isset($_SERVER['PATH_INFO']) && U::strlen($_SERVER['PATH_INFO']) > 0 ) {
    $anchor = ltrim($_SERVER['PATH_INFO'], '/');
} else {
    $path = U::rest_path();
    if ( isset($path->action) && U::strlen($path->action) > 0 ) {
        $anchor = $path->action;
    } else if ( isset($path->parameters) && count($path->parameters) > 0 ) {
        $anchor = $path->parameters[0];
    }
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
$l = new Lessons($CFG->lessons, $anchor);

$OUTPUT->header();
$OUTPUT->bodyStart();
$menu = false;
$OUTPUT->topNav();
$OUTPUT->flashMessages();
$l->header();
if ( $show_analytics ) {
    echo('<span style="position: fixed; right: 10px; top: 75px; z-index: 999; background-color: white; padding: 2px;"><a href="analytics.php" class="btn btn-default"><span class="glyphicon glyphicon-signal"></span> Analytics</a></span>');
}
echo('<div class="container">');
ob_start();
$l->render();
$content = ob_get_clean();
// Replace launch URLs to always use /lessons/launch/ instead of /lessons/{anchor}_launch/ or /lessons/{anchor}/launch/
// This ensures launch URLs are always relative to the lessons base, not the module/anchor
$content = preg_replace('/(href=["\'])([^"\']*\/lessons\/)[^\/]+(_launch\/|launch\/)([^"\']*)(["\'])/', '$1$2launch/$4$5', $content);
echo($content);
echo('</div>');
$OUTPUT->footerStart();
$l->footer();
$OUTPUT->footerEnd();
