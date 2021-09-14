<?php
use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Util\LTI13;
use \Tsugi\Core\LTIX;

if ( ! isset($user_id) ) {
    echo("<p><b>You are not logged in.</b></p>\n");
    $OUTPUT->footer();
    return;
}
echo("<p><b>Retrieving OpenId configuration from LMS</b><br>\n");
echo(htmlentities($openid_configuration)."\n</p>\n");

$response = Net::doGet($openid_configuration );
$code = Net::getLastHttpResponse();
if ( ! $response || strlen($response) < 1 ) {
    echo("<pre>\n");
    echo("Unable to retrieve:\n".htmlentities($openid_configuration)."\n");
    echo("Error code:".htmlentities($code)."\n");
    echo("</pre>\n");
    return;
}

$OUTPUT->togglePre("LMS-Provided OpenId Configuration", $response);

$platform_configuration = json_decode($response);
if ( ! $platform_configuration || ! is_object($platform_configuration) ) {
    echo("<pre>\n");
    echo("Unable to parse JSON retrieved from:\n".htmlentities($openid_configuration)."\n\n");
    echo(htmlentities($response));
    echo("</pre>\n");
    return;
}

// Parse the response and make sure we have the required values.
try {
  $issuer = $platform_configuration->issuer;
  $authorization_endpoint = $platform_configuration->authorization_endpoint;
  $token_endpoint = $platform_configuration->token_endpoint;
  $jwks_uri = $platform_configuration->jwks_uri;
  $registration_endpoint = $platform_configuration->registration_endpoint;
} catch (Exception $e) {
    echo("<pre>\n");
    echo 'Missing required value: ',  htmlentities($e->getMessage()), "\n";
    echo("</pre>\n");
    return;
}

$authorization_server = isset($platform_configuration->authorization_server) ? $platform_configuration->authorization_server : null;
$title = isset($platform_configuration->title) ? $platform_configuration->title : null;

\Tsugi\Core\LTIX::getConnection();

// Retrieve key for super user
if ( $user_id == 0 ) {
    $row = $PDOX->rowDie(
        "SELECT key_title, K.issuer_id AS issuer_id, key_key, issuer_key, issuer_client,
            lti13_oidc_auth, lti13_keyset_url, lti13_token_url, K.user_id AS user_id
        FROM {$CFG->dbprefix}lti_key AS K
            LEFT JOIN {$CFG->dbprefix}lti_issuer AS I ON
                K.issuer_id = I.issuer_id
            WHERE key_id = :KID",
        array(":KID" => $tsugi_key)
    );
    if ( $row && isset($row['user_id']) ) $user_id = $row['user_id']+0;

// Lets retrieve our key entry if it belongs to us
} else {
    $row = $PDOX->rowDie(
        "SELECT key_title, K.issuer_id AS issuer_id, key_key, issuer_key, issuer_client,
            lti13_oidc_auth, lti13_keyset_url, lti13_token_url, K.user_id AS user_id
        FROM {$CFG->dbprefix}lti_key AS K
            LEFT JOIN {$CFG->dbprefix}lti_issuer AS I ON
                K.issuer_id = I.issuer_id
            WHERE key_id = :KID AND K.user_id = :UID",
        array(":KID" => $tsugi_key, ":UID" => $user_id)
    );
}

if ( ! $row ) {
    echo("<pre>\n");
    echo "Could not load your key\n";
    echo("</pre>\n");
    return;
}

// See the end of the file for some documentation references
$json = new \stdClass();
$tool = new \stdClass();

$json->application_type = "web";
$json->response_types = array("id_token");
$json->grant_types = array("implicit", "client_credentials");
$json->initiate_login_uri = $CFG->wwwroot . '/lti/oidc_login/' . urlencode($tsugi_key);
$json->redirect_uris = array($CFG->wwwroot . '/lti/oidc_launch');
if ( isset($CFG->servicename) && $CFG->servicename ) {
    $json->client_name = $CFG->servicename;
}
$json->jwks_uri = $CFG->wwwroot . '/lti/keyset/';
if ( isset($CFG->privacy_url) && $CFG->privacy_url ) {
    $json->policy_uri = $CFG->privacy_url;
}
if ( isset($CFG->sla_url) && $CFG->sla_url ) {
    $json->tos_uri = $CFG->sla_url;
}
$json->token_endpoint_auth_method = "private_key_jwt";

if ( isset($CFG->owneremail) && $CFG->owneremail ) {
    $json->contacts = array($CFG->owneremail);
    $contact = new \stdClass();
    $contact->email = $CFG->owneremail;
    if ( isset($CFG->ownername) && $CFG->ownername ) $contact->display_name = $CFG->ownername;
    $tool->better_contacts = array($contact);
}

// Yes, this *is* an array of strings serialized into a string with a space separator
$json->scope = "https://purl.imsglobal.org/spec/lti-ags/scope/score https://purl.imsglobal.org/spec/lti-ags/scope/lineitem https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly";

$tool->product_family_code = "tsugi.org";
$tool->target_link_uri = $CFG->wwwroot . '/lti/store/';

$pieces = parse_url($CFG->apphome);
if ( U::get($pieces, 'host') ) $tool->domain = U::get($pieces, 'host');

if ( isset($CFG->servicedesc) && $CFG->servicedesc ) {
    $tool->description = $CFG->servicedesc;
}

$tool->claims = array( "iss", "sub", "name", "given_name", "family_name", "email" );

// TODO: Issue #53 - Define placements...
// TODO: Issue #59 - Message parsing order - Sakai takes first, Moodle takes last
$tool->messages = array(
    // TODO: ContextPlacementLaunch
    // Issue #59 - For LMS's that take the "first"
    array(
        "type" => "LtiDeepLinkingRequest",
        "label" => $json->client_name,
        "target_link_uri" => $CFG->wwwroot . '/lti/store/',
    ),
    array(
        "type" => "DataPrivacyLaunchRequest",
        "label" => $json->client_name,
        "target_link_uri" => $CFG->wwwroot,
    ),
    array(
        "type" => "MartinImportRequest",
        "label" => $json->client_name,
        "target_link_uri" => __("Import from") . " ". $CFG->wwwroot . '/cc/export/',
    ),
    array(
        "type" => "LtiDeepLinkingRequest",
        "label" => __("Import from") . " ".$json->client_name,
        "target_link_uri" => $CFG->wwwroot . '/cc/export',
        "placements" => array( "migration_selection")
    ),
    array(
        "type" => "LtiDeepLinkingRequest",
        "label" => $json->client_name,
        "target_link_uri" => $CFG->wwwroot . '/lti/store/?type=link_selection',
        "placements" => array( "link_selection")
    ),
    array(
        "type" => "LtiDeepLinkingRequest",
        "label" => $json->client_name,
        "target_link_uri" => $CFG->wwwroot . '/lti/store/?type=editor_button',
        "placements" => array( "editor_button")
    ),
    array(
        "type" => "LtiDeepLinkingRequest",
        "label" => $json->client_name,
        "target_link_uri" => $CFG->wwwroot . '/lti/store/?type=assignment_selection',
        "placements" => array( "assignment_selection")
    ),
    // TODO: Make this not needed
    // Issue #59 - For LMS's that take the "last" (i.e. like Moodle)
    array(
        "type" => "LtiDeepLinkingRequest",
        "label" => $json->client_name,
        "target_link_uri" => $CFG->wwwroot . '/lti/store/',
    ),
);

$json->{"https://purl.imsglobal.org/spec/lti-tool-configuration"} = $tool;

$body = json_encode($json, JSON_PRETTY_PRINT);

$method = "POST";
$header = "Content-type: application/json;\n" .
            "Authorization: Bearer ".$registration_token;
$url = $registration_endpoint;

echo("\n<p><b>Sending registration to LMS</b><br>\n");
echo(htmlentities($url)."\n</p>\n");
$OUTPUT->togglePre("Registration data sent to LMS", $body);

$response = Net::bodyCurl($url, $method, $body, $header);

$retval = Net::getLastBODYDebug();
$retval['body_url'] = $url;
$retval['body_sent'] = $body;
$retval['body_received'] = $response;

$response_code = Net::getLastHttpResponse();

if ( $response_code != 200 ) {
    echo("\nDID NOT GET 1200 :(\n");
    echo("</pre>\n");
    return;
}

$OUTPUT->togglePre("Registration response from LMS", $response);
$resp = json_decode($response);
if ( ! $resp || ! is_object($resp) ) {
    echo("Unable to parse JSON retrieved from:\n".htmlentities($registration_endpoint)."\n\n");
    echo(htmlentities($response));
    echo("</pre>\n");
    return;
}

if ( !isset($resp->client_id) ) {
    echo("Did not find client_id in response\n");
    print_r($resp);
    echo("</pre>\n");
    return;
}

$client_id = $resp->client_id;

$tc_key = "https://purl.imsglobal.org/spec/lti-tool-configuration";
$tool_configuration = isset($resp->{$tc_key}) ? $resp->{$tc_key} : null;
$deployment_id = null;
if ( $tool_configuration ) {
    $deployment_id = isset($tool_configuration->deployment_id) ? $tool_configuration->deployment_id : null;
}

// One day will be obsolete...
$issuer_sha256 = hash('sha256', trim($issuer));
$guid = U::createGUID();

// Retrieve the issuer
$issuer_row = $PDOX->rowDie(
    "SELECT * FROM {$CFG->dbprefix}lti_issuer
        WHERE issuer_key = :ISS AND issuer_client = :CLI",
    array(":ISS" => $issuer, ":CLI" => $client_id)
);


$success = false;
// Simple case - no issuer - lets make one!
if ( ! $issuer_row ) {

    // Default (for now) is insert issuer data into the key
    // TODO: Just remove this option
    if ( isset($CFG->autoissuer) && $CFG->autoissuer ) {
        $sql = "INSERT INTO {$CFG->dbprefix}lti_issuer
            (issuer_title, issuer_sha256, issuer_guid, issuer_key, issuer_client, user_id, lti13_oidc_auth,
                lti13_keyset_url, lti13_token_url, lti13_token_audience)
            VALUES
            (:title, :sha256, :guid, :key, :client, :user_id, :oidc_auth,
                :keyset_url, :token_url, :token_audience)
        ";
        $values = array(
            ":title" => $title,
            ":sha256" => $issuer_sha256,
            ":guid" => $guid,
            ":key" => $issuer,
            ":client" => $client_id,
            ":user_id" => $user_id,
            ":oidc_auth" => $authorization_endpoint,
            ":keyset_url" => $jwks_uri,
            ":token_url" => $token_endpoint,
            ":token_audience" => $authorization_server,
        );

        $stmt = $PDOX->queryReturnError($sql, $values);

        if ( ! $stmt->success ) {
            echo("Unable to insert issuer\n");
            return;
        }

        $issuer_id = $PDOX->lastInsertId();

        echo("<p>Created new issuer = $issuer_id</p>\n");

        $stmt = $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}lti_key SET issuer_id = :IID
                WHERE key_id = :KID AND user_id = :UID",
            array(":IID" => $issuer_id, ":KID" => $tsugi_key, ":UID" => $user_id)
        );

        if ( ! $stmt->success ) {
            echo("Unable to update key entry to connect to the issuer\n");
            return;
        }

    // TODO: "this is the way" when you are ready for it
    } else { // Store LMS data in the row

        $sql = "UPDATE {$CFG->dbprefix}lti_key SET
                issuer_id = NULL,
                lms_issuer = :lms_issuer,
                lms_issuer_sha256 = :lms_issuer_sha256,
                lms_client = :lms_client,
                lms_oidc_auth = :lms_oidc_auth,
                lms_keyset_url = :lms_keyset_url,
                lms_token_url = :lms_token_url,
                lms_token_audience = :lms_token_audience
            WHERE key_id = :ID
        ";

        $values = array(
            ":ID" => $tsugi_key,
            ":lms_issuer" => $issuer,
            ":lms_issuer_sha256" => $issuer_sha256,
            ":lms_client" => $client_id,
            ":lms_oidc_auth" => $authorization_endpoint,
            ":lms_keyset_url" => $jwks_uri,
            ":lms_token_url" => $token_endpoint,
            ":lms_token_audience" => $authorization_server,
        );

        $PDOX->queryDie($sql, $values);

        error_log("Stored issuer $issuer / $client_id in lti_key table");

    } // End Store LMS Data in the row

    if ( $deployment_id ) {
        $deployment_sha256 = hash('sha256', trim($deployment_id));
        $stmt = $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}lti_key SET deploy_key = :DID, deploy_sha256 = :D256
                WHERE key_id = :KID AND (user_id = :UID OR :UID = 0 )",
            array(":DID" => $deployment_id, ":D256" => $deployment_sha256, ":KID" => $tsugi_key, ":UID" => $user_id)
        );

        if ( ! $stmt->success ) {
            echo("Unable to update key entry to set deployment_id\n");
            return;
        }
    }

    $success = true;

} else {
    $old_issuer_id = $issuer_row['issuer_id'];
    $old_oidc_auth = $issuer_row['lti13_oidc_auth'];
    $old_keyset_url = $issuer_row['lti13_keyset_url'];
    $old_token_url = $issuer_row['lti13_token_url'];
    $old_token_audience = $issuer_row['lti13_token_audience'];

    $current_issuer_id = $row['issuer_id'];

    // Existing issuer is good...
    if ( $authorization_endpoint == $old_oidc_auth &&
        $jwks_uri == $old_keyset_url &&
        $token_endpoint == $old_token_url &&
        $authorization_server == $old_token_audience ) {
        if ( $current_issuer_id == $old_issuer_id ) {
            $success = true;
        } else {
            echo("<p>Updated the key to point at an existing issuer/client_id</p>\n");
            $stmt = $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}lti_key SET issuer_id = :IID
                    WHERE key_id = :KID AND user_id = :UID",
                array(":IID" => $old_issuer_id, ":KID" => $tsugi_key, ":UID" => $user_id)
            );
            if ( $deployment_id ) {
                $deployment_sha256 = hash('sha256', trim($deployment_id));
                $stmt = $PDOX->queryDie(
                    "UPDATE {$CFG->dbprefix}lti_key SET deploy_key = :DID, deploy_sha256 = :D256
                        WHERE key_id = :KID AND user_id = :UID",
                    array(":DID" => $deployment_id, ":D256" => $deployment_sha256, ":KID" => $tsugi_key, ":UID" => $user_id)
                );

                if ( ! $stmt->success ) {
                    echo("Unable to update key entry to set deployment_id\n");
                    return;
                }
            }
            $success = true;
        }
    } else {
        echo("You are not allower to redefine the issuer=".htmlentities($issuer)." / client=".htmlentities($client_id). "\n");
        $success = false;
    }
}

?>
<button onclick="(window.opener || window.parent).postMessage({subject:'org.imsglobal.lti.close'}, '*')">Continue Registration in the LMS</button>
<?php

$OUTPUT->footer();

/*

POST /connect/register HTTP/1.1
Content-Type: application/json
Accept: application/json
Host: server.example.com
Authorization: Bearer eyJhbGciOiJSUzI1NiJ9.eyJ .

{
    "application_type": "web",
    "response_types": ["id_token"],
    "grant_types": ["implict", "client_credentials"],
    "initiate_login_uri": "https://client.example.org/lti",
    "redirect_uris":
      ["https://client.example.org/callback",
       "https://client.example.org/callback2"],
    "client_name": "Virtual Garden",
    "client_name#ja": "バーチャルガーデン",
    "jwks_uri": "https://client.example.org/.well-known/jwks.json",
    "logo_uri": "https://client.example.org/logo.png",
    "policy_uri": "https://client.example.org/privacy",
    "policy_uri#ja": "https://client.example.org/privacy?lang=ja",
    "tos_uri": "https://client.example.org/tos",
    "tos_uri#ja": "https://client.example.org/tos?lang=ja",
    "token_endpoint_auth_method": "private_key_jwt",
    "contacts": ["ve7jtb@example.org", "mary@example.org"],
    "scope": "https://purl.imsglobal.org/spec/lti-ags/scope/score https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly",
    "https://purl.imsglobal.org/spec/lti-tool-configuration": {
        "domain": "client.example.org",
        "description": "Learn Botany by tending to your little (virtual) garden.",
        "description#ja": "小さな（仮想）庭に行くことで植物学を学びましょう。",
        "target_link_uri": "https://client.example.org/lti",
        "custom_parameters": {
            "context_history": "$Context.id.history"
        },
        "claims": ["iss", "sub", "name", "given_name", "family_name"],
        "messages": [
            {
                "type": "LtiDeepLinkingRequest",
                "target_link_uri": "https://client.example.org/lti/dl",
                "label": "Add a virtual garden",
                "label#ja": "バーチャルガーデンを追加する",
            }
        ]
    }
}
 */
