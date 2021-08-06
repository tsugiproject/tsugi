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
    LTIX::abort_with_error_log('Missing state');
}

$sid = substr("log-".md5($state), 0, 20);
error_log(" =============== oidc_launch ===================== $sid");
session_id($sid);
session_start();
$session_state = U::get($_SESSION, 'state');
$session_password = U::get($_SESSION, 'password');

if ( $state == $session_state ) {
    LTIX::abort_with_error_log('State mis-match');
}

$headers = U::apache_request_headers();
$auth_header = U::get($headers, 'Authorization');
if ( ! $auth_header ) {
    LTIX::abort_with_error_log('Missing Authorization Header');
}

error_log("Header ".$auth_header);

$pieces = explode(' ', $auth_header);
if ( count($pieces) != 3 || $pieces[1] != 'TsugiOauthVerify' ) {
    LTIX::abort_with_error_log('Bad format for Authorization Header');
}

if ( $pieces[2] != $session_password ) {
    LTIX::abort_with_error_log('Bad format for Authorization Header');
}

$_SESSION['verified'] = 'yes';

