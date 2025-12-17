<?php

use \Tsugi\UI\Lessons;
use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../../config.php";

LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();

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
echo('<div class="container">');
$l->render();
echo('</div>');
$OUTPUT->footerStart();
$l->footer();
$OUTPUT->footerEnd();
