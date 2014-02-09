<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lti_util.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once $CFG->dirroot."/core/gradebook/lib.php";
require_once "classes.php";

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

$detail = new PythonGradeDetail();

showGrades($stmt, $detail);

footerContent();
