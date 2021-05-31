<?php

// https://openid.net/specs/openid-connect-core-1_0.html#AuthRequest
//
use \Tsugi\Util\U;
use \Tsugi\Util\LTI13;
use \Firebase\JWT\JWT;
use \Tsugi\Core\LTIX;
use \Tsugi\Crypt\AesCtr;

require_once "../config.php";

// target_link_uri and lti_message_hint are not required by Tsugi
$login_hint = U::get($_REQUEST, 'login_hint');
$iss = U::get($_REQUEST, 'iss');
$issuer_guid = U::get($_REQUEST, 'guid');

// Allow a format where the parameter is the primary key of the lti_key row
$key_id = null;
if ( is_numeric($issuer_guid) ) $key_id = intval($issuer_guid);

// echo("<pre>\n");var_dump($_REQUEST); LTIX::abort_with_error_log();

if ( ! $login_hint ) {
    LTIX::abort_with_error_log('Missing login_hint');
}

if ( ! $iss ) {
    LTIX::abort_with_error_log('Missing iss');
}

$PDOX = \Tsugi\Core\LTIX::getConnection();

$key_sha256 = LTI13::extract_issuer_key_string($iss);

error_log("iss=".$iss." sha256=".$key_sha256);
if ( $key_id ) {
     $sql = "SELECT key_id, issuer_client, lti13_oidc_auth,
         issuer_key, lti13_kid, lti13_keyset_url, lti13_keyset, lti13_platform_pubkey, lti13_privkey
         FROM {$CFG->dbprefix}lti_issuer AS I
            JOIN {$CFG->dbprefix}lti_key AS K ON
                K.issuer_id = I.issuer_id
            WHERE K.key_id = :KID AND I.issuer_sha256 = :SHA";
    $row = $PDOX->rowDie($sql, array(":KID" => $key_id, ":SHA" => $key_sha256));
} else {
    if ( $issuer_guid ) {
        $query_where = "WHERE issuer_sha256 = :SHA AND issuer_guid = :issuer_guid AND issuer_client IS NOT NULL AND lti13_oidc_auth IS NOT NULL";
        $query_where_params = array(":SHA" => $key_sha256, ":issuer_guid" => $issuer_guid);
    } else {
        $query_where = "WHERE issuer_sha256 = :SHA AND issuer_client IS NOT NULL AND lti13_oidc_auth IS NOT NULL";
        $query_where_params = array(":SHA" => $key_sha256);
    }

    $row = $PDOX->rowDie(
        "SELECT key_id, issuer_client, lti13_oidc_auth,
        issuer_key, lti13_kid, lti13_keyset_url, lti13_keyset, lti13_platform_pubkey, lti13_privkey
        FROM {$CFG->dbprefix}lti_issuer $query_where",
        $query_where_params);
}

if ( ! is_array($row) || count($row) < 1 ) {
    LTIX::abort_with_error_log('Login could not find issuer '.htmlentities($iss)." issuer_guid=".$issuer_guid);
}
$client_id = trim($row['issuer_client']);
$key_id = trim($row['key_id']);
$redirect = trim($row['lti13_oidc_auth']);

$issuer_key = $row['issuer_key'];
$platform_public_key = $row['lti13_platform_pubkey'];
$our_kid = $row['lti13_kid'];
$our_keyset_url = $row['lti13_keyset'];
$our_keyset = $row['lti13_keyset'];
$tool_private_key = $row['lti13_privkey'];

$signature = \Tsugi\Core\LTIX::getBrowserSignature();

$payload = array();
$payload['signature'] = $signature;
$payload['time'] = time();
// Someday we might do something clever with this...
if ( U::get($_REQUEST,'target_link_uri') ) {
    $payload['target_link_uri'] = $_REQUEST['target_link_uri'];
}

$state = JWT::encode($payload, $CFG->cookiesecret, 'HS256');

// Make a short-lived session
$sid = substr("log-".md5($state), 0, 20);
error_log(" =============== oidc_login ===================== $sid");
session_id($sid);
session_start();
$_SESSION['state'] = $state;
$_SESSION['issuer_key'] = $issuer_key;
$_SESSION['platform_public_key'] = $platform_public_key;

$_SESSION['our_kid'] = $our_kid;
$_SESSION['our_keyset_url'] = $our_keyset_url;
$_SESSION['our_keyset'] = $our_keyset;

$encr = AesCtr::encrypt($tool_private_key, $CFG->cookiesecret, 256) ;
$_SESSION['tool_private_key_encr'] = $encr;

$redirect = U::add_url_parm($redirect, "scope", "openid");
$redirect = U::add_url_parm($redirect, "response_type", "id_token");
$redirect = U::add_url_parm($redirect, "response_mode", "form_post");
$redirect = U::add_url_parm($redirect, "prompt", "none");
$redirect = U::add_url_parm($redirect, "nonce", uniqid());

// client_id - Required, per OIDC spec, the tool’s client id for this issuer.
$redirect = U::add_url_parm($redirect, "client_id", $client_id);
$redirect = U::add_url_parm($redirect, "login_hint", $login_hint);
if ( U::get($_REQUEST,'lti_message_hint') ) {
    $redirect = U::add_url_parm($redirect, "lti_message_hint", $_REQUEST['lti_message_hint']);
}
$redirect = U::add_url_parm($redirect, "redirect_uri", $CFG->wwwroot . '/lti/oidc_launch');
$redirect = U::add_url_parm($redirect, "state", $state);

error_log("oidc_login redirect: ".$redirect);
// Store it in a session cookie - likely won't work
setcookie("TSUGI_STATE", $state);
header("Location: ".$redirect);

