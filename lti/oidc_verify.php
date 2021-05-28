<?php

use \Tsugi\Util\U;
use \Tsugi\Util\LTI13;
use \Firebase\JWT\JWT;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\Output;

require_once "../config.php";

$sid = U::get($_GET, 'sid');
if ( !is_string($sid) || strlen($sid) < 0 ) {
    LTIX::abort_with_error_log('Missing sid parameter');
}

$verifydata = U::get($_POST, 'postverify');
if ( !is_string($verifydata) || strlen($verifydata) < 0 ) {
    LTIX::abort_with_error_log('Missing verify data in POST');
}

    ini_set('session.use_cookies', '0');
    ini_set('session.use_only_cookies',0);
    ini_set('session.use_trans_sid',1);
session_id($sid);
session_start();
$session_state = U::get($_SESSION, 'state');
if ( !is_string($session_state) || strlen($session_state) < 0 ) {
    LTIX::abort_with_error_log('Could not find state in session');
}

$platform_public_key = U::get($_POST, 'platform_public_key');
if ( !is_string($platform_public_key) || strlen($platform_public_key) < 0 ) {
    LTIX::abort_with_error_log('Missing platform_public_key in session');
}

$subject = U::get($_POST, 'subject');
if ( !is_string($subject) || strlen($subject) < 0 ) {
    LTIX::abort_with_error_log('Missing subject in session');
}


        $verify_jwt = false;
        $verify_sub = false;
        error_log("Returned JWT $verifydata");
        $verify_jwt = LTI13::parse_jwt($verifydata);
        error_log("Platform public ".$platform_public_key);

        $e = LTI13::verifyPublicKey($verifydata, $platform_public_key, array($verify_jwt->header->alg));
        if ( $e !== true ) {
            LTIX::abort_with_error_log('Postverify validation fail key='.$issuer_key.' error='.$e->getMessage());
        }

        if ( isset($verify_jwt->body->sub) && is_string($verify_jwt->body->sub) ) {
            $verify_sub = $verify_jwt->body->sub;
        } else {
            error_log("Unable to parse postverify JWT ".$verifydata);
            LTIX::abort_with_error_log("Unable to parse postverify JWT");
        }


        if ( $verify_sub != $sub ) {
            error_log("Subject $sub does not match verified_subject of $verify_sub");
            LTIX::abort_with_error_log("Unable to verify subject - ".$sub);
        }

$_SESSION['verified'] = 'yes';


