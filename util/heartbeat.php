<?php

if ( isset($_GET[session_name()]) ) {
    $cookie = false;
} else {
    define('COOKIE_SESSION', true);
    $cookie = true;
}

require_once "../../../../config.php";
require_once $CFG->vendorinclude . "/lms_lib.php";

headerJson();

session_start();

// See how long since the last update of the activity time
$seconds = 0;
$now = time();
if ( isset($_SESSION['LAST_ACTIVITY']) ) {
    $seconds = $now - $_SESSION['LAST_ACTIVITY'];
}
$_SESSION['LAST_ACTIVITY'] = $now; // update last activity time stamp

// Count the successive heartbeats without a request/response cycle
if ( ! isset($_SESSION['HEARTBEAT_COUNT']) ) $_SESSION['HEARTBEAT_COUNT'] = 0;
$count = $_SESSION['HEARTBEAT_COUNT']++;

if ( $count > 10 && ( $count % 100 ) == 0 ) {
    error_log("Heartbeat.php ".session_id().' '.$count);
}

$retval = array("success" => true, "seconds" => $seconds,
        "now" => $now, "count" => $count, "cookie" => $cookie,
        "id" => session_id());
$retval['lti'] = isset($_SESSION['lti']);
// $retval['lti'] = false;
$retval['sessionlifetime'] = $CFG->sessionlifetime;
echo(json_encode($retval));
