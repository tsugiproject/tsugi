<?php

require_once "../../config.php";

use Tsugi\Util\U;

// See the end of the file for some documentation references

$guid = U::get($_GET,"guid",false);

$json = new \stdClass();

$json->oidcConnect = $CFG->wwwroot . '/lti/oidc_login/' . urlencode($guid);
$json->oidcRedirect = $CFG->wwwroot . '/lti/oidc_launch';
$json->keySetUrl = $CFG->wwwroot . '/lti/keyset/';
$json->deepLinkUrl = $CFG->wwwroot . '/lti/store/';
$json->tsugiRoot = $CFG->wwwroot;
if ( $CFG->apphome) {
    $json->tsugiApp = $CFG->apphome;
    $json->client_uri = $CFG->apphome;
}

if ( isset($CFG->privacy_url) && $CFG->privacy_url ) {
    $json->privacyUrl = $CFG->privacy_url;
    $json->policy_uri = $CFG->privacy_url;
}

if ( isset($CFG->sla_url) && $CFG->sla_url ) {
    $json->tos_uri = $CFG->sla_url;
}

if ( isset($CFG->servicename) && $CFG->servicename ) {
    $json->title = $CFG->servicename;
}

if ( isset($CFG->servicedesc) && $CFG->servicedesc ) {
    $json->description = $CFG->servicedesc;
}

/* From IMS */

$json->application_type = "web";
$json->token_endpoint_auth_method = "private_key_jwt";
$json->response_types = array("id_token");
$json->grant_types = array("implicit", "client_credentials");

$json->iniate_login_uri = $CFG->wwwroot . '/lti/oidc_login/' . urlencode($guid);
$json->redirect_uris = array($CFG->wwwroot . '/lti/oidc_launch');
$json->jwks_uri = $CFG->wwwroot . '/lti/keyset/';

if ( isset($CFG->owneremail) && $CFG->owneremail ) {
    $json->contacts = array($CFG->owneremail);
    $contact = new \stdClass();
    $contact->email = $CFG->owneremail;
    if ( isset($CFG->ownername) && $CFG->ownername ) $contact->display_name = $CFG->ownername;
    $json->better_contacts = array($contact);
}

header("Content-type: application/json");
echo(json_encode($json, JSON_PRETTY_PRINT));
