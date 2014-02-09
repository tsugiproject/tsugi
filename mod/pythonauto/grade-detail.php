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

// Get the user's grade data
$row = loadGrade($pdo, $_REQUEST['user_id']);

// View 
headerContent();
startBody();
flashMessages();
welcomeUserCourse($LTI);

echo('<p><a href="'.sessionize("grades.php").'">Back to All Grades</a>'."</p><p>\n");
echo("User Name: ".htmlent_utf8($row['displayname'])."<br/>\n");
echo("User Email: ".htmlent_utf8($row['email'])."<br/>\n");
echo("Last Submision: ".htmlent_utf8($row['updated_at'])."<br/>\n");
echo("Score: ".htmlent_utf8($row['grade'])."<br/>\n");
echo("</p>\n");
echo("<p>Submission:</p>\n");
$json = json_decode($row['json']);
if ( is_object($json)) {
    echo("<pre>\n");
    echo(htmlent_utf8($json->code));
    echo("\n");
    echo("</pre>\n");
}


footerContent();
