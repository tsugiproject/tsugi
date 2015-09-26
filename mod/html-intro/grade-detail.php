<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once $CFG->dirroot."/core/gradebook/lib.php";

session_start();

// Get the user's grade data also checks session
$row = gradeLoad($_REQUEST['user_id']);

// View
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();

// Show the basic info for this user
gradeShowInfo($row);

// Unique detail
echo("<p>Submitted URL:</p>\n");
$json = json_decode($row['json']);
if ( is_object($json) && isset($json->url)) {
    echo("<p><a href=\"".safe_href($json->url)."\" target=\"_new\">");;
    echo(htmlent_utf8($json->url));
    echo("</a></p>\n");
}

$OUTPUT->footer();
