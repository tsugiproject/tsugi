<?php

require_once "../../config.php";

use Tsugi\Core\LTIX;
use Tsugi\Core\Keyset;

LTIX::getConnection();

use Tsugi\Util\U;

$rows = Keyset::getCurrentKeys();
if ( ! $rows || ! is_array($rows) || count($rows) < 1 ) {
    die("Could not load key");
}

$pubkey = $rows[0]['pubkey'];

$pieces = parse_url($CFG->wwwroot);
$domain = isset($pieces['host']) ? $pieces['host'] : false;

$jwk = Keyset::build_jwk($pubkey);

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
$json->target_link_uri = $CFG->wwwroot . "/lti/this_url_is_a_placeholder_when_there_are_placements";

$json->oidc_redirect_url = $CFG->wwwroot . "/lti/oidc_launch";
$json->oidc_initiation_url = $CFG->wwwroot . "/lti/oidc_login".(isset($row['issuer_guid']) ? "/".$row['issuer_guid'] : '');
$json->extensions[0]->domain = $domain;
$json->extensions[0]->tool_id = md5($CFG->wwwroot);
$json->extensions[0]->settings->icon_url = $CFG->staticroot . "/img/logos/tsugi-logo-square.png";
for($i=0; $i < count($json->extensions[0]->settings->placements); $i++) {
    $json->extensions[0]->settings->placements[$i]->text = $CFG->servicename;
    $json->extensions[0]->settings->placements[$i]->target_link_uri = $CFG->wwwroot . "/lti/store/?placement=" .
        urlencode($json->extensions[0]->settings->placements[$i]->placement);
    $json->extensions[0]->settings->placements[$i]->icon_url = $CFG->staticroot . "/img/logos/tsugi-logo-square.png";
}
// removing placements for now.
unset($json->extensions[0]->settings->placements);
$json->extensions[0]->settings->placements = [];
echo(json_encode($json, JSON_PRETTY_PRINT));
