<?php

require_once "../../config.php";
require_once "../../util/helpers.php";

use Tsugi\Util\U;

// See the end of the file for some documentation references

$issuer = U::get($_GET,"issuer",false);
$issuer_id = U::get($_GET,"issuer_id",false);
if ( strlen($issuer) < 1 && $issuer_id < 1 ) {
    die('Missing issuer or issuer_id');
}

if ( $issuer ) {
    $issuer_sha256 = hash('sha256', trim($issuer));
    $row = $PDOX->rowDie(
        "SELECT lti13_pubkey FROM {$CFG->dbprefix}lti_issuer
            WHERE issuer_sha256 = :ISH AND lti13_pubkey IS NOT NULL",
        array(":ISH" => $issuer_sha256)
    );
} else if ( $issuer_id > 0 ) {
    $row = $PDOX->rowDie(
        "SELECT * FROM {$CFG->dbprefix}lti_issuer
            WHERE issuer_id = :IID AND lti13_pubkey IS NOT NULL",
        array(":IID" => $issuer_id)
    );
} else {
    die('Missing issuer or issuer_id');
}

if ( ! $row ) die("Could not load key");

$pubkey = $row['lti13_pubkey'];

$pieces = parse_url($CFG->wwwroot);
$domain = isset($pieces['host']) ? $pieces['host'] : false;

// $pubkey = "-----BEGIN PUBLIC KEY-----
// MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvESXFmlzHz+nhZXTkjo2 9SBpamCzkd7SnpMXgdFEWjLfDeOu0D3JivEEUQ4U67xUBMY9voiJsG2oydMXjgkm GliUIVg+rhyKdBUJu5v6F659FwCj60A8J8qcstIkZfBn3yyOPVwp1FHEUSNvtbDL SRIHFPv+kh8gYyvqz130hE37qAVcaNME7lkbDmH1vbxi3D3A8AxKtiHs8oS41ui2 MuSAN9MDb7NjAlFkf2iXlSVxAW5xSek4nHGr4BJKe/13vhLOvRUCTN8h8z+SLORW abxoNIkzuAab0NtfO/Qh0rgoWFC9T69jJPAPsXMDCn5oQ3xh/vhG0vltSSIzHsZ8 pwIDAQAB
// -----END PUBLIC KEY-----";

// https://8gwifi.org/jwkconvertfunctions.jsp
// https://github.com/nov/jose-php/blob/master/src/JOSE/JWK.php
// https://github.com/nov/jose-php/blob/master/test/JOSE/JWK_Test.php

// $z = new JOSE_JWK();
// var_dump(JOSE_JWK::encode($key));
// echo("\n");
// echo($pubkey);
// echo("\n");

$jwk = Helpers::build_jwk($pubkey);

// echo(json_encode($jwk));
// echo("\n");

// TODO: Remove course_navigation
$json_str = <<<JSON
{
    "title": "Tsugi Cloud for Canvas",
    "scopes": [
        "https://purl.imsglobal.org/spec/lti-ags/scope/lineitem",
        "https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly",
        "https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly",
        "https://purl.imsglobal.org/spec/lti-ags/scope/score",
        "https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly"
    ],
    "extensions": [
        {
            "platform": "canvas.instructure.com",
            "settings": {
                "placements": [
                    {
                        "text": "Canvas Tsugi",
                        "placement": "course_navigation",
                        "message_type": "LtiResourceLinkRequest",
                        "target_link_uri": "https://canvas.tsugicloud.org/mod/cats/"
                    },
                    {
                        "text": "Canvas Tsugi",
                        "icon_url": "https://static.tsugi.org/img/logos/tsugi-logo-square.png",
                        "placement": "assignment_selection",
                        "message_type": "LtiDeepLinkingRequest",
                        "target_link_uri": "https://canvas.tsugicloud.org/tsugi/lti/store/?placement=assignment_selection"
                    },
                    {
                        "text": "Canvas Tsugi",
                        "icon_url": "https://static.tsugi.org/img/logos/tsugi-logo-square.png",
                        "placement": "link_selection",
                        "message_type": "LtiDeepLinkingRequest",
                        "target_link_uri": "https://canvas.tsugicloud.org/tsugi/lti/store/?placement=assignment_selection"
                    },
                    {
                        "text": "Canvas Tsugi",
                        "icon_url": "https://static.tsugi.org/img/logos/tsugi-logo-square.png",
                        "placement": "editor_button",
                        "message_type": "LtiDeepLinkingRequest",
                        "target_link_uri": "https://canvas.tsugicloud.org/tsugi/lti/store/?placement=editor_button"
                    }
                ]
            }
        }
    ],
    "public_jwk": {
        "e": "AQAB",
        "n": "qO4FGwu73DwNXVFG6EJKNCnE5ceBAnxi5kOk3exYqx-mSCJNU7J3E88qbZa_jyhSOtSs1ZwtcBoBhROIcbfGznCLGoi3OjZzt223I7cT8WaR1gZlB0XJ6f1XPPo6-IleRZZ7BF1O6SlsIorN00i-K7hF-S9euzdvOHkGLWS6UU537wT19famfvjO-UDzXWTxCVOcdmCnW0oSBVXJeFia-yk9gYMyRuoozKyb6T-s9--OgSVhpvtxNF4fDFc_h26Syve1d7BJwa8Nd0LwKxIniXAtVJi-1Itm3pqwspCE0VJPdPpTx6HRW9wexDn6EtYdUcKjy93l7xLvgnObd3mxfQ",
        "alg": "RS256",
        "kid": "6rW2pCGQblYiEvW_OIDTRBOr6_Pt1NVQaGZ-Z_FF9Ys",
        "kty": "RSA",
        "use": "sig"
    },
    "description": "Tsugi Cloud for Canvas",
    "custom_fields": {},
    "target_link_uri": "https://canvas.tsugicloud.org/tsugi/lti/42_wtf_this_is_silly_when_there_are_placements",
    "oidc_initiation_url": "https://canvas.tsugicloud.org/tsugi/lti/oidc_login"
}
JSON
;

$json = json_decode(trim($json_str));

if ( ! $json ) {
    echo("<pre>\n");
    echo($json_str);
    echo("</pre>\n");
    die('Unable to parse JSON '.json_last_error_msg());
}

header("Content-type: application/json");

$json->title = $CFG->servicename;
if ( $CFG->servicedesc ) {
    $json->description = $CFG->servicedesc;
}
$json->public_jwk = $jwk;

// TODO: Fix this
$json->target_link_uri = $CFG->wwwroot . "/lti/42_wtf_this_is_silly_when_there_are_placements";

$json->oidc_redirect_url = $CFG->wwwroot . "/lti/oidc_launch";
$json->oidc_initiation_url = $CFG->wwwroot . "/lti/oidc_login/".$row['issuer_guid'];
$json->extensions[0]->domain = $domain;
$json->extensions[0]->tool_id = md5($CFG->wwwroot);
$json->extensions[0]->settings->icon_url = $CFG->staticroot . "/img/logos/tsugi-logo-square.png";
for($i=0; $i < count($json->extensions[0]->settings->placements); $i++) {
    $json->extensions[0]->settings->placements[$i]->text = $CFG->servicename;
    $json->extensions[0]->settings->placements[$i]->target_link_uri = $CFG->wwwroot . "/lti/store/?placement=" .
        urlencode($json->extensions[0]->settings->placements[$i]->placement);
    $json->extensions[0]->settings->placements[$i]->icon_url = $CFG->staticroot . "/img/logos/tsugi-logo-square.png";
}

echo(json_encode($json, JSON_PRETTY_PRINT));
