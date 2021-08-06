<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Core\LTIX;

require_once "../config.php";

// To make abort_with_error_log do its JSON thing.
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Net::send400('Must send a post');
    return;
}

$state = U::get($_POST, 'state');

if ( ! $state ) {
    LTIX::abort_with_error_log('oidc_postmesage Missing state');
}

$sid = substr("log-".md5($state), 0, 20);
error_log(" =============== oidc_postmessage ===================== $sid");
error_log($state);
session_id($sid);
session_start();
$session_state = U::get($_SESSION, 'state');
$session_password = U::get($_SESSION, 'password');

if ( ! $session_password ) {
    var_dump($_SESSION);
    LTIX::abort_with_error_log('oidc_postmessage could not find session password');
}

if ( $state != $session_state ) {
    LTIX::abort_with_error_log('oidc_postmessage state mis-match');
}


$headers = U::apache_request_headers();
$auth_header = U::get($headers, 'X-Tsugi-Authorization');
if ( ! $auth_header ) {
    LTIX::abort_with_error_log('oidc_postmessage Missing Authorization Header');
}

error_log("Header ".$auth_header);

$pieces = explode(' ', $auth_header);
if ( count($pieces) != 2 || $pieces[0] != 'TsugiOAuthVerify' ) {
    LTIX::abort_with_error_log('oidc_postmessage Bad format for Authorization Header');
}

error_log("Compare ".$pieces[1].' ? '.$session_password);
if ( $pieces[1] != $session_password ) {
    LTIX::abort_with_error_log('oidc_postmessage Authorization failed');
}

error_log("Verify_success");
$_SESSION['verified'] = 'yes';

// Because JQuery :)
echo("{ }");
