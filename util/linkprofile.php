<?php

if ( ! isset($CFG) ) return; // Only from within tsugi.php

use \Tsugi\Core\LTIX;
use \Tsugi\UI\Output;
use \Tsugi\Crypt\SecureCookie;

Output::headerJson();

// TODO: Make these 500's

// Not turned on
if ( !isset($CFG->unify) || ! $CFG->unify ) {
    echo(json_encode(array("error" => "Not enabled")));
    return;
}

// Nothing for us to do
if ( ! isset($_GET[session_name()]) ) {
    echo(json_encode(array("error" => "No session")));
    return;
}

// Grab the session
$LAUNCH = LTIX::requireData();

// See if the LTI login can be linked to the site login...
if ( ! isset($_SESSION['lti']) ) {
    echo(json_encode(array("error" => "Not an LTI Session")));
    return;
}

if ( ! isset($_COOKIE[$CFG->cookiename]) ) {
    echo(json_encode(array("error" => "No Cookie for login")));
    return;
}

// Contemplate: Do we care if the lti email matches the cookie email?

$ct = $_COOKIE[$CFG->cookiename];
$pieces = SecureCookie::extract($ct);
$lti = $_SESSION['lti'];
if ( count($pieces) != 3 ) {
    echo(json_encode(array("error" => "Cookie decode failure")));
    return;
}
if ( ! isset($lti['user_id']) ) {
    echo(json_encode(array("error" => "user_id not found in LTI session")));
    return;
}
// echo("Cookie user ".$pieces[0]. " Cookie email ".$pieces[1]." LTI User ".$lti['user_id']." LTI Profile ".$lti['profile_id']." LTI email ".$lti['user_email']."\n");
$row = $PDOX->rowDie("SELECT profile_id FROM {$CFG->dbprefix}lti_user WHERE user_id = :UID;",
    array(':UID' => $pieces[0] )
);
if ( $row === false || ! isset($row['profile_id']) ) {
    echo(json_encode(array("error" => "No profile_id for cookie user")));
    return;
}
$stmt = $PDOX->queryDie("UPDATE {$CFG->dbprefix}lti_user SET profile_id = :PID WHERE user_id = :UID",
    array(':UID' => $lti['user_id'], ':PID' => $row['profile_id'])
);
error_log("Updated Cookie user ".$pieces[0]. " email ".$pieces[1]." profile ".$row['profile_id']." LTI User ".$lti['user_id']." LTI email ".$lti['user_email']);

echo(json_encode(array("success" => "Profile linked")));
