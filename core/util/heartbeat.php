<?php
require_once "../../config.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

session_start();
$_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp

// TODO: Remove when tested
$filename = isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '';
// error_log("Heartbeat.php ".session_id().' '.$filename);

$retval = array("success" => true);
$retval['lti'] = isset($_SESSION['lti']);
$retval['sessionlifetime'] = $CFG->sessionlifetime;
echo(json_encode($retval));
