<?php
use \Tsugi\Util\LTI;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\Lessons;

require_once "top.php";

if ( ! isset($CFG->lessons) ) {
    die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
}

// Load the Lesson
$l = new Lessons($CFG->lessons);

$l->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();

echo('<div id="container">'."\n");

$OUTPUT->flashMessages();

$l->render();

echo('</div> <!-- container -->'."\n");

$OUTPUT->footerStart();
$l->footer();
$OUTPUT->footerend();
