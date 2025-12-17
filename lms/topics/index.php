<?php

use \Tsugi\UI\Topics;
use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../../config.php";

LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( ! isset($CFG->topics) ) {
    die_with_error_log('Cannot find topics.json ($CFG->topics)');
}

// Get anchor from URL path
$path = U::rest_path();
$anchor = null;
if ( isset($path->action) && U::strlen($path->action) > 0 ) {
    $anchor = $path->action;
} else if ( isset($path->parameters) && count($path->parameters) > 0 ) {
    $anchor = $path->parameters[0];
}

// Turning on and off styling
if ( isset($_GET['nostyle']) ) {
    if ( $_GET['nostyle'] == 'yes' ) {
        $_SESSION['nostyle'] = 'yes';
    } else {
        unset($_SESSION['nostyle']);
    }
}

// Load the Topic
$t = new Topics($CFG->topics, $anchor);

$OUTPUT->header();
$OUTPUT->bodyStart();
$menu = false;
$OUTPUT->topNav($menu);
$OUTPUT->flashMessages();
$t->header();
echo('<div class="container">');
$t->render();
echo('</div>');
$OUTPUT->footerStart();
$t->footer();
$OUTPUT->footerEnd();
