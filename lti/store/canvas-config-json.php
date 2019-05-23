<?php

require_once "../../config.php";

use Tsugi\Util\U;
use phpseclib\Crypt\RSA;
use Tsugi\Core\LTIX;

LTIX::getConnection();

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
    $rows = $PDOX->rowDie(
        "SELECT lti13_pubkey FROM {$CFG->dbprefix}lti_issuer
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
$key = new RSA();
$key->setPublicKey($pubkey);
// var_dump(JOSE_JWK::encode($key));
// echo("\n");
// echo($pubkey);
// echo("\n");

$jwk = array(
                    'kty' => 'RSA',
                    'e' => JOSE_URLSafeBase64::encode($key->publicExponent->toBytes()),
                    'n' => JOSE_URLSafeBase64::encode($key->modulus->toBytes()),
                    'kid' => hash('sha256', trim($pubkey)),
                );
                if ($key->exponent != $key->publicExponent) {
                    $components = array_merge($components, array(
                        'd' => JOSE_URLSafeBase64::encode($key->exponent->toBytes())
                    ));
                }

// echo(json_encode($jwk));
// echo("\n");

$json_str = <<<JSON
{
    "title":"LTI 1.3 twoa",
    "description":"1.3 Test Tool",
    "scopes":[
        "https://purl.imsglobal.org/spec/lti-ags/scope/lineitem",
        "https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly",
        "https://purl.imsglobal.org/spec/lti-ags/scope/score",
        "https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly"
    ],
    "extensions":[
    {
      "domain":"domain",
      "tool_id":"toolid",
      "platform": "canvas.instructure.com",
      "settings":{
           "privacy_level":"public",
           "text":"Tsugi",
           "use_1_3":true,
           "icon_url":"icon_url",
           "selection_width":500,
           "selection_height":500,
           "placements": [
            {
                "text": "LTI 1.3 0d516e5f",
                "enabled": true,
                "icon_url": "https://static.thenounproject.com/png/131630-200.png",
                "placement": "link_selection",
                "placement_comment": "Modules",
                "message_type": "LtiDeepLinkingRequest",
                "target_link_uri": "?placement=assignment_selection",
                "canvas_icon_class": "icon-lti"
            },
            {
                "text": "LTI 1.3 0d516e5f",
                "enabled": true,
                "icon_url": "https://static.thenounproject.com/png/131630-200.png",
                "placement": "assignment_selection",
                "placement_comment": "External tool submission type",
                "message_type": "LtiDeepLinkingRequest",
                "target_link_uri": "?placement=assignment_selection",
                "canvas_icon_class": "icon-lti"
            },
            {
                "text": "LTI 1.3 0d516e5f",
                "enabled": true,
                "icon_url": "https://static.thenounproject.com/png/131630-200.png",
                "placement": "editor_button",
                "message_type": "LtiDeepLinkingRequest",
                "target_link_uri": "?placement=editor_button",
                "canvas_icon_class": "icon-lti"
            }
            ]
        }
      }
    ],
    "public_jwk":{
        "e":"AQAB",
        "n":"3yRcWT20SD9jrdPVwDj0pu3P4mh_VXldVQBHF9ggs3j2PM_j5NSRQP5jUovTbFP_-dpx_M_6llKCud8lFq6IOLuVq9Q5qar64cbc4ryQ1HGdLzBqJ39xZ5Pr5lvsQ6ppTrapkwNfN_iWNWxS1mYkdclu6AReVDAJB95uveMEqXB9fZQlBHU4vxwCvA9liTw6Msd0VGxr3KE5oxlKI1bxphieGG4t4Aqlda8oRamJPoRfzOQuedtY-5RmCrQ4eOB6ju2IzuQAjLGn_kTwyMQmin2moMnzZCwk2oLjImFopTnhmNvAUqzyRmgjSCLtXKR_vd3oaxKiTHZrvfh6kPqfuw",
        "alg":"RS256",
        "kid":"nHdYfFK3k2BzKRCzMyZGnr9JFtWKeKXS6R_FHiFgUnE",
        "kty":"RSA",
        "use":"sig"
    },
    "target_link_uri":"replace",
    "oidc_login_uri": "replace"
    "oidc_redirect_uri": "replace"
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
$json->description = $CFG->servicedesc;
$json->public_jwk = $jwk;

// TODO: Fix this
$json->target_link_uri = $CFG->wwwroot . "/lti/42_wtf";

$json->oidc_redirect_uri = $CFG->wwwroot . "/lti/oidc_launch";
$json->oidc_login_uri = $CFG->wwwroot . "/lti/oidc_login";
$json->extensions[0]->domain = $domain;
$json->extensions[0]->tool_id = md5($CFG->wwwroot);
$json->extensions[0]->settings->icon_url = $CFG->staticroot . "/img/logos/tsugi-logo-square.png";
for($i=0; $i < count($json->extensions[0]->settings->placements); $i++) {
    $json->extensions[0]->settings->placements[$i]->text = $CFG->servicename;
    $json->extensions[0]->settings->placements[$i]->target_link_uri = $CFG->wwwroot . "/lti/store/" . 
        $json->extensions[0]->settings->placements[$i]->target_link_uri;
    $json->extensions[0]->settings->placements[$i]->icon_url = $CFG->staticroot . "/img/logos/tsugi-logo-square.png";
}

echo(json_encode($json, JSON_PRETTY_PRINT));
