<?php

// https://openid.net/specs/openid-connect-core-1_0.html#AuthRequest
//
use \Tsugi\Util\U;
use \Tsugi\Util\LTI13;
use \Firebase\JWT\JWT;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\Output;

require_once "../config.php";

// target_link_uri and lti_message_hint are not required by Tsugi
$login_hint = U::get($_REQUEST, 'login_hint');
$iss = U::get($_REQUEST, 'iss');
$issuer_guid = U::get($_REQUEST, 'guid');

$headers = U::apache_request_headers();
$accept = U::get($headers,'Accept', ' ');
$do_json = isset($CFG->oidc_login_json) && $CFG->oidc_login_json && strpos($accept, 'application/json') !== false;
if ( $do_json ) Output::headerJson();

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
     $sql = "SELECT issuer_client, lti13_oidc_auth
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
        "SELECT issuer_client, lti13_oidc_auth
        FROM {$CFG->dbprefix}lti_issuer $query_where",
        $query_where_params);
}

if ( ! is_array($row) || count($row) < 1 ) {
    LTIX::abort_with_error_log('Login could not find issuer '.htmlentities($iss)." issuer_guid=".$issuer_guid);
}
$client_id = trim($row['issuer_client']);
$redirect = trim($row['lti13_oidc_auth']);
$nonce = uniqid();
$lti_message_hint = U::get($_REQUEST, 'lti_message_hint');

$payload = array();
if ( $do_json ) {
    $payload['type'] = 'json';
} else {
    $payload['type'] = 'browser';
    $raw = \Tsugi\Core\LTIX::getBrowserSignatureRaw();
    if (  U::apcAvailable() ) {
        apc_store('oidc_login_state', $raw);
    } else {
        error_log('oidc_login '.$raw);
    }
    $signature = \Tsugi\Core\LTIX::getBrowserSignature();
    $payload['signature'] = $signature;
};
$payload['time'] = time();
$payload['nonce'] = $nonce;
$redirect_uri = $CFG->wwwroot . '/lti/oidc_launch';

// Someday we might do something clever with this...
if ( U::get($_REQUEST,'target_link_uri') ) {
    $payload['target_link_uri'] = $_REQUEST['target_link_uri'];
}

$state = JWT::encode($payload, $CFG->cookiesecret, 'HS256');

$parm = array();
$parm["scope"] = "openid";
$parm["response_type"] = "id_token";
$parm["response_mode"] = "form_post";
$parm["prompt"] = "none";
$parm["nonce"] = $nonce;

// client_id - Required, per OIDC spec, the tool’s client id for this issuer.
$parm["client_id"] = $client_id;
$parm["login_hint"] = $login_hint;
if ( $lti_message_hint ) {
    $parm["lti_message_hint"] = $lti_message_hint;
}
$parm["redirect_uri"] = $redirect_uri;
$parm["state"] = $state;


if ( $do_json ) {
    $retval = new \stdClass();
    foreach($parm as $p => $v){
        $retval->{$p} = $v;
    }
    echo(json_encode($retval));
} else {
    foreach($parm as $p => $v){
        $redirect = U::add_url_parm($redirect, $p,$v);
    };
    error_log("oidc_login redirect: ".$redirect);
    header("Location: ".$redirect);
}

