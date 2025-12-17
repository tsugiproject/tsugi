<?php

use \Tsugi\UI\Lessons;
use \Tsugi\Grades\GradeUtil;
use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../../config.php";

LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( ! U::get($_SESSION,'id') ) {
    die('Must be logged in');
}

if ( ! isset($CFG->lessons) ) {
    die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
}

// Load the Lesson
$l = new Lessons($CFG->lessons);

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
$OUTPUT->topNav();
$OUTPUT->flashMessages();
$l->renderBadges($allgrades, false);
$OUTPUT->footer();
