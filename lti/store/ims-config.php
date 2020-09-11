<?php

require_once "../../config.php";

use Tsugi\Util\U;

// Based on:
// https://openid.net/specs/openid-connect-registration-1_0.html
// https://openid.net/specs/openid-connect-discovery-1_0.html

// See also:
// https://tools.ietf.org/html/rfc7591

$guid = U::get($_GET,"guid",false);

$json = new \stdClass();

$json->application_type = "web";
$json->token_endpoint_auth_method = "private_key_jwt";
$json->response_types = array("id_token");
$json->grant_types = array("implicit", "client_credentials");

$json->iniate_login_uri = $CFG->wwwroot . '/lti/oidc_login/' . urlencode($guid);
$json->redirect_uris = array($CFG->wwwroot . '/lti/oidc_launch');
$json->jwks_uri = $CFG->wwwroot . '/lti/keyset/' . urlencode($guid);
if ( $CFG->apphome) {
    $json->client_uri = $CFG->apphome;
}

if ( isset($CFG->privacy_url) && $CFG->privacy_url ) {
    $json->tos_uri = $CFG->privacy_url;
}

if ( isset($CFG->sla_url) && $CFG->sla_url ) {
    $json->policy_uri = $CFG->sla_url;
}

if ( isset($CFG->servicename) && $CFG->servicename ) {
    $json->client_name = $CFG->servicename;
}

if ( isset($CFG->owneremail) && $CFG->owneremail ) {
    $json->contacts = array($CFG->owneremail);
    $contact = new \stdClass();
    $contact->email = $CFG->owneremail;
    if ( isset($CFG->ownername) && $CFG->ownername ) $contact->display_name = $CFG->ownername;
    $json->better_contacts = array($contact);
}

$toolconfig = new \stdClass();

$toolconfig->domain = "https://wtf.is.this.here.for?";
if ( isset($CFG->servicedesc) && $CFG->servicedesc && strlen($CFG->servicedesc) > 0 ) {
    $toolconfig->description = $CFG->servicedesc;
}
// Shouldn't this be title?
$toolconfig->label = $CFG->servicename;
$toolconfig->{'title'} = $CFG->servicename;
if ( isset($CFG->servicedesc) && $CFG->servicedesc ) {
    $toolconfig->{'description'} = $CFG->servicedesc;
}

$toolconfig->target_link_uri = $CFG->wwwroot . '/lti/store/';
$toolconfig->scopes = array(
    "https://purl.imsglobal.org/spec/lti-ags/scope/lineitem",
    "https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly",
    "https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly",
    "https://purl.imsglobal.org/spec/lti-ags/scope/score",
    "https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly"
);
$toolconfig->claims = array("iss", "sub", "name", "given_name", "family_name");

$deeplink = new \stdClass();
$deeplink->{'type'} = "LTIDeepLinkingRequest";
$deeplink->allowLearner = false;  // Should remove
$deeplink->allow_learner = false;
$deeplink->placements = array("assignment_selection", "link_selection",  "editor_button");

$toolconfig->messages = array ( $deeplink);

$json->{'https://purl.imsglobal.org/spec/lti-tool-configuration'} = $toolconfig;

header("Content-type: application/json");
echo(json_encode($json, JSON_PRETTY_PRINT));
