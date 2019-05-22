<?php

use \Tsugi\Util\U;
use \Tsugi\Util\LTI13;
use \Firebase\JWT\JWT;
use \Tsugi\Core\LTIX;

require_once "../config.php";

$id_token = U::get($_POST, 'id_token');
$state = U::get($_POST, 'state');

if ( ! $state || ! $id_token ) {
    LTIX::abort_with_error_log('Missing id_token and/or state');
}

try {
    $decoded = JWT::decode($state, $CFG->cookiesecret, array('HS256'));
} catch(Exception $e) {
    LTIX::abort_with_error_log('Unable to decode state value');
}

if ( ! is_object($decoded) ) {
    LTIX::abort_with_error_log('Incorrect state value');
}

if ( ! isset($decoded->time) ) {
    LTIX::abort_with_error_log('No time in state');
}

$delta = abs($decoded->time - time());
if ( $delta > 60 ) {
    LTIX::abort_with_error_log('Bad time value');
}

if ( ! isset($decoded->signature) ) {
    LTIX::abort_with_error_log('No signature in state');
}
$signature = \Tsugi\Core\LTIX::getBrowserSignature();

if ( $signature != $decoded->signature ) {
    LTIX::abort_with_error_log("Invalid state signature value");
}

$url_claim = "https://purl.imsglobal.org/spec/lti/claim/target_link_uri";

$jwt = LTI13::parse_jwt($id_token);

if ( ! $jwt ) {
    LTIX::abort_with_error_log("Unable to parse JWT");
}

if ( ! isset($jwt->body) ) {
    LTIX::abort_with_error_log("Missing body in JWT");
}

$launch_url = false;
if ( isset($jwt->body->{$url_claim}) && is_string($jwt->body->{$url_claim}) ) {
    $launch_url = $jwt->body->{$url_claim};
    error_log("target_link_uri from id_token ".$launch_url);
}

// TODO: Remove these alternate claim forumulations
$url_claim2 = "https://purl.imsglobal.org/spec/lti/claim/launch_url";
if ( ! $launch_url && isset($jwt->body->{$url_claim2}) && is_string($jwt->body->{$url_claim2}) ) {
    $launch_url = $jwt->body->{$url_claim2};
    error_log("launch_url from id_token ".$launch_url);
}

// TODO: Remove these alternate claim forumulations
$url_claim3 = "https://purl.imsglobal.org/spec/lti/claim/resource_url";
if ( ! $launch_url && isset($jwt->body->{$url_claim3}) && is_string($jwt->body->{$url_claim3}) ) {
    $launch_url = $jwt->body->{$url_claim3};
    error_log("resource_url from id_token ".$launch_url);
}

if ( ! $launch_url && isset($decoded->target_link_uri) && is_string($decoded->target_link_uri) ) {
    $launch_url = $decoded->target_link_uri;
    error_log("*WARNING* Launch url from unsigned target_link_uri ".$launch_url);
}

if ( ! $launch_url ) {
    LTIX::abort_with_error_log("Missing or incorrect launch_url claim in body");
}

// Check for bad places to go
$oidc_login = $CFG->wwwroot . '/lti/oidc_login';
$oidc_launch = $CFG->wwwroot . '/lti/oidc_launch';
if ( strpos($launch_url, $oidc_login) === 0 ) {
    LTIX::abort_with_error_log("target_link_uri cannot be the same as oidc_login - ".$launch_url);
}
if ( strpos($launch_url, $oidc_launch) === 0 ) {
    LTIX::abort_with_error_log("target_link_uri cannot be the same as oidc_launch - ".$launch_url);
}

if ( ! U::startsWith($launch_url, $CFG->apphome) ) {
    LTIX::abort_with_error_log("Launch_url must start with ".$CFG->apphome);
}

// Looks good - time to forward
?>
<form method="POST" id="oidc_forward" action="<?= htmlspecialchars($launch_url) ?>">
<input type="hidden" name="id_token" value="<?= htmlspecialchars($id_token) ?>">
<input type="hidden" name="state" value="<?= htmlspecialchars($state) ?>">
</form>
<script>
document.getElementById("oidc_forward").submit();
</script>
