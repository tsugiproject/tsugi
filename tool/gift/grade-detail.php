<?php
require_once "../config.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Grades\GradeUtil;

$LAUNCH = LTIX::requireData();

// Get the user's grade data also checks session
$row = GradeUtil::gradeLoad($_REQUEST['user_id']);


$menu = new \Tsugi\UI\MenuSet();
$menu->addLeft('Back to all grades', "grades.php");

// View
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav($menu);

// Show the basic info for this user
GradeUtil::gradeShowInfo($row, /* Don't show link back */ false);

// Unique detail
$json = json_decode($row['json']);
if ( is_object($json) ) {
    echo("<p>JSON:</p>\n");
    echo("<pre>\n");
    echo(htmlentities(json_encode($json, JSON_PRETTY_PRINT)));
    echo("\n</pre>\n");
}

$OUTPUT->footer();
