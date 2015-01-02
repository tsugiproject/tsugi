<?php
require_once "../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

use \Tsugi\Core\LTIX;

// Sanity checks
$LTI = LTIX::requireData();
$p = $CFG->dbprefix;

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();
$OUTPUT->welcomeUserCourse();

echo("<p>Hello world</p>\n");

