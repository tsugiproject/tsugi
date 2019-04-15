<?php
require_once "../config.php";

/** Handle RPC requests
 */

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Core\LTIX;
use \Tsugi\Crypt\Aes;
use \Tsugi\Crypt\AesCtr;


function rpc_failure($message) {
    Net::send400($message);
    $detail = array('detail' => $message);
    print(json_encode($detail));
    return;
}

$token = U::get($_REQUEST, 'token');
if ( ! $token ) rpc_failure('No token');

$decr = AesCtr::decrypt($token, $CFG->cookiesecret, 256);
$pieces = explode('::', $decr);
if ( count($pieces) != 2 ) return rpc_failure('Bad token format');
if ( $pieces[0] != $CFG->cookiepad ) return rpc_failure('Bad token prefix');

$session_id = $pieces[1];
error_log('RPC session: '.$session_id."\n");

session_id($session_id);

session_start();
$LTI = $_SESSION['lti'];
if ( ! $LTI ) return rpc_failure('Invalid session');

$LAUNCH = LTIX::buildLaunch($LTI);

// print_r($LAUNCH);

$object = U::get($_POST, 'object');
if ( ! $object ) return rpc_failure('Missing object');
$method = U::get($_POST, 'method');
if ( ! $method ) return rpc_failure('Missing method');
$p1 = U::get($_POST, 'p1');
if ( ! $p1 ) return rpc_failure('Missing parameter');


$retval = $LAUNCH->{$object}->{$method}($p1);

print_r($retval);

