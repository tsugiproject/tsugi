<?php
require_once "../../config.php";
require_once $CFG->dirroot."/db.php";
require_once $CFG->dirroot."/lib/lti_util.php";

session_start();

// Sanity checks
if ( !isset($_SESSION['lti']) ) {
	die('This tool need to be launched using LTI');
}
$LTI = $_SESSION['lti'];
if ( !isset($LTI['user_id']) || !isset($LTI['link_id']) ) {
	die('A user_id and link_id are required for this tool to function.');
}
$instructor = isset($LTI['role']) && $LTI['role'] == 1 ;
$p = $CFG->dbprefix;

// Session is automatically added to hrefs
echo('<a href="play.php" target="_blank">Open</a>');
