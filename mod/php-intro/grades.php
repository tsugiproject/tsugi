<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";

use \Tsugi\Grades\GradeUtil;
use \Tsugi\Grades\UI;

$GRADE_DETAIL_CLASS = new \Tsugi\Grades\SimpleGradeDetail();

UI::GradeTable($GRADE_DETAIL_CLASS);
