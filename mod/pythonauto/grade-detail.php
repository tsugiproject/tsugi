<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once $CFG->dirroot."/core/gradebook/lib.php";

session_start();

// Get the user's grade data also checks session
$row = load_grade($pdo, $_REQUEST['user_id']);

// View 
$OUTPUT->header();
$OUTPUT->start_body();
$OUTPUT->flash_messages();

// Show the basic info for this user
show_grade_info($row);

// Unique detail
echo("<p>Submission:</p>\n");
$json = json_decode($row['json']);
if ( is_object($json) && isset($json->code)) {
    echo("<pre>\n");
    echo(htmlent_utf8($json->code));
    echo("\n");
    echo("</pre>\n");
}

$OUTPUT->footer();
