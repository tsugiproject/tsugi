<?php

use \Tsugi\Util\U;
use \Tsugi\Util\LTI13;
use \Firebase\JWT\JWT;

require_once "../config.php";

$id_token = U::get($_POST, 'id_token');
$state = U::get($_POST, 'state');

if ( ! $state || ! $id_token ) {
    die('Missing id_token and/or state');
}

try {
    $decoded = JWT::decode($state, $CFG->cookiesecret, array('HS256'));
} catch(Exception $e) {
    die('Unable to decode state value');
}

if ( ! is_object($decoded) ) {
    die('Incorrect state value');
}

if ( ! isset($decoded->time) ) {
    die('No time in state');
}

$delta = abs($decoded->time - time());
if ( $delta > 60 ) {
    die('Bad time value');
}

if ( ! isset($decoded->signature) ) {
    die('No signature in state');
}
$signature = \Tsugi\Core\LTIX::getBrowserSignature();

if ( $signature != $decoded->signature ) {
    die("Invalid state signature value");
}

$url_claim = "https://purl.imsglobal.org/spec/lti/claim/target_link_uri";

$jwt = LTI13::parse_jwt($id_token);

if ( ! $jwt ) {
    die("Unable to parse JWT");
}

if ( ! isset($jwt->body) ) {
    die("Missing body in JWT");
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
    die("Missing or incorrect launch_url claim in body");
}


if ( ! U::startsWith($launch_url, $CFG->apphome) ) {
    die("Launch_url must start with ".$CFG->apphome);
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
