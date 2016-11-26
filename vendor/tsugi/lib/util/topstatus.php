<?php

require_once "../../../../config.php";
require_once $CFG->vendorinclude . "/lms_lib.php";

use \Tsugi\Core\LTIX;
use \Tsugi\UI\Output;

Output::headerJson();

// Nothing for us to do
if ( ! isset($_GET[session_name()]) ) {
    echo(json_encode(array("error" => "No session")));
    return;
}

if( isset($_COOKIE[session_name()]) ) {
    echo(json_encode(array("status" => 'done')));
    return;
}

if ( !isset($_GET['top']) ) {
    echo(json_encode(array("error" => "Need top= parameter")));
    return;
}

// Grab the session
$LAUNCH = LTIX::requireData(LTIX::USER);

// This has already been set by someone so nothing to do
if (isset($_COOKIE['TSUGI_TOP_SESSION']) ) {
    unset($_SESSION['TOP_CHECK']);  // No point in further checks
    echo(json_encode(array("top_session" => $_COOKIE['TSUGI_TOP_SESSION'])));
    return;
}

// We are not the top frame
if ($_GET['top'] != 'true' ) {
    unset($_SESSION['TOP_CHECK']);
}

// No more checks are needed
if ( (!isset($_SESSION['TOP_CHECK'])) || $_SESSION['TOP_CHECK'] < 1) {
    echo(json_encode(array("status" => 'done')));
    return;
}

// We are the top frame, the cookie has not yet been set.
// Lets try to set the cookie in JavaScript - but in case that fails,
// We will try to set the session cookie on our next request response
// cycle in LTIX::requireData()

$_SESSION['SET_TOP_COOKIE'] = 1;
echo(json_encode(array("session_name" => session_name(), "cookie_name" => 'TSUGI_TOP_SESSION',
    "cookie_value" => session_id())));

