<?php
require_once __DIR__ . "/../config.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Grades\GradeUtil;

LTIX::requireData();

// Get the user's grade data also checks session
$row = GradeUtil::gradeLoad($_REQUEST['user_id']);

$menu = new \Tsugi\UI\MenuSet();
$menu->addLeft(__('Back to all grades'), "grades.php");

// View
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav($menu);

// Show the basic info for this user
GradeUtil::gradeShowInfo($row, /* Don't show link back */ false);

$OUTPUT->footer();
