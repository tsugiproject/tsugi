<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once "lib.php";

session_start();

// Sanity checks
$LTI = requireData(array('user_id', 'link_id', 'role','context_id', "result_id"));
$instructor = isInstructor($LTI);
if ( ! $instructor ) die("Requires instructor role");
$p = $CFG->dbprefix;

// Get basic grade data
$stmt = loadGrades($pdo);

// View 
headerContent();
startBody();
flashMessages();
welcomeUserCourse($LTI);

if ( isset($GRADE_DETAIL_CLASS) && is_object($GRADE_DETAIL_CLASS) ) {
    showGrades($stmt, $GRADE_DETAIL_CLASS);
} else {
    showGrades($stmt, false);
}

footerContent();
