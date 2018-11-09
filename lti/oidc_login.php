<?php

use \Tsugi\Util\U;
use \Firebase\JWT\JWT;

require_once "../config.php";
require_once "oidc_util.php";

$redirect = "http://localhost:8080/imsblis/lti13/oidc_auth";

if ( ! isset($_GET['login_hint']) ) {
    die('Missing login_hint');
}

$signature = getBrowserSignature();

$payload = array();
$payload['signature'] = $signature;
$payload['time'] = time();

$state = JWT::encode($payload, $CFG->cookiesecret, 'HS256');

$redirect = U::add_url_parm($redirect, "login_hint", $_GET['login_hint']);
$redirect = U::add_url_parm($redirect, "state", $state);

header("Location: ".$redirect);

