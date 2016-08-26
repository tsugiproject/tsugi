<?php
use \Tsugi\Util\LTI;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\Lessons;
use \Tsugi\Grades\GradeUtil;

require_once "top.php";
$OUTPUT->flashMessages();
if ( ! isset($CFG->lessons) ) {
    die_with_error_log('Cannot find lessons.json');
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

$OUTPUT->bodyStart();
$OUTPUT->topNav();

$l->renderAssignments($allgrades);

$OUTPUT->footer();
