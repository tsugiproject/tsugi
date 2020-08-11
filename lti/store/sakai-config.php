<?php

require_once "../../config.php";

use Tsugi\Util\U;

// See the end of the file for some documentation references

$guid = U::get($_GET,"guid",false);

$json = new \stdClass();

$json->oidcConnect = $CFG->wwwroot . '/lti/oidc_login/' . urlencode($guid);
$json->oidcRedirect = $CFG->wwwroot . '/lti/oidc_launch';
$json->keySetUrl = $CFG->wwwroot . '/lti/keyset/' . urlencode($guid);
$json->deepLinkUrl = $CFG->wwwroot . '/lti/store/';
$json->tsugiRoot = $CFG->wwwroot;
if ( $CFG->apphome) {
    $json->tsugiApp = $CFG->apphome;
}

if ( isset($CFG->privacy_url) && $CFG->privacy_url ) {
    $json->privacyUrl = $CFG->apphome;
}

if ( isset($CFG->sla_url) && $CFG->sla_url ) {
    $json->privacyUrl = $CFG->sla_url;
}

if ( isset($CFG->servicename) && $CFG->servicename ) {
    $json->title = $CFG->servicename;
}

if ( isset($CFG->servicedesc) && $CFG->servicedesc ) {
    $json->description = $CFG->servicedesc;
}

header("Content-type: application/json");
echo(json_encode($json, JSON_PRETTY_PRINT));
