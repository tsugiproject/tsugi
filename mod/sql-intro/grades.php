<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once $CFG->dirroot."/core/gradebook/lib.php";

$GRADE_DETAIL_CLASS = new \Tsugi\Grades\SimpleGradeDetail();

// Use the provided gradebook with basic detail
require_once $CFG->dirroot."/core/gradebook/grades.php";
