<?php
define('COOKIE_SESSION', true);
require_once "../config.php";
require_once "../pdo.php";
require_once $CFG->dirroot.'/lib/lms_lib.php';
require_once 'tp_messages.php';

use \Tsugi\Util\LTI;
use \Tsugi\Util\Net;

session_start();
header('Content-Type: text/html; charset=utf-8');

if ( ! isset($_SESSION['lti2post']) ) {
    die_with_error_log("Missing LTI 2.0 post data");
}

error_log("Session in lti2 ".session_id());

if ( ! isset($_SESSION['id']) ) {
    if ( isset($_REQUEST['login_done']) ) {
        die_with_error_log("LTI 2 login failed.");
    }
    $_SESSION['login_return'] = addSession(getCurrentFileUrl(__FILE__) ."?login_done=true");
    header("Location: ".getLoginUrl());
    return;
}

// See if this person is allowed to register a tool
$row = $PDOX->rowDie(
    "SELECT request_id, user_id, admin, state, lti
        FROM {$CFG->dbprefix}key_request
        WHERE user_id = :UID LIMIT 1",
    array(":UID" => $_SESSION['id'])
);

if ( $row === false ) {
    $_SESSION['error'] = 'You have not requested a key for this service.';
    header('Location: '.$CFG->wwwroot);
    return;
}

if ( $row['state'] == 0 ) {
    $_SESSION['error'] = 'Your key has not yet been approved. '.$row['admin'];
    header('Location: '.$CFG->wwwroot);
    return;
}

if ( $row['state'] != 1 ) {
    $_SESSION['error'] = 'Your key request was not approved. '.$row['admin'];
    header('Location: '.$CFG->wwwroot);
    return;
}

if ( $row['lti'] != 2 ) {
    $_SESSION['error'] = 'Your did not request an LTI 2.0 key. '.$row['admin'];
    header('Location: '.$CFG->wwwroot);
    return;
}

// We have a person authorized to use LTI 2.0 on this server
$_POST = $_SESSION['lti2post'];

$lti_message_type = $_POST["lti_message_type"];
$re_register = $lti_message_type == "ToolProxyReregistrationRequest";

?>
<html>
<head>
  <title>LTI 2.0 Tool Registration</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<script language="javascript">
function dataToggle(divName) {
    var ele = document.getElementById(divName);
    if(ele.style.display == "block") {
        ele.style.display = "none";
    }
    else {
        ele.style.display = "block";
    }
}
  //]]>
</script>
</head>
<body style="font-family:sans-serif; background-color:#add8e6">
<?php
echo("<p><b>LTI 2.0 Registration</b></p>\n");

ksort($_POST);
$output = "";
foreach($_POST as $key => $value ) {
    if (get_magic_quotes_gpc()) $value = stripslashes($value);
    $output = $output . htmlent_utf8($key) . "=" . htmlent_utf8($value) . 
        " (".mb_detect_encoding($value).")\n";
}
$OUTPUT->togglePre("Raw POST Parameters", $output);


$output = "";
ksort($_GET);
foreach($_GET as $key => $value ) {
    if (get_magic_quotes_gpc()) $value = stripslashes($value);
    $output = $output . htmlent_utf8($key) . "=" . htmlent_utf8($value) . 
        " (".mb_detect_encoding($value).")\n";
}
if ( strlen($output) > 0 ) $OUTPUT->togglePre("Raw GET Parameters", $output);

echo("<pre>\n");

// For re-registration the logged in user must own the key
// For registration, the key must not exist and belong to another user
// We double check the registration scenario in a transaction later
if ( $re_register ) {
	$reg_key = $_POST['oauth_consumer_key'];
	$key_sha256 = lti_sha256($reg_key);
	echo("key_sha256=".$key_sha256."<br>");
	$oldproxy = $PDOX->rowDie(
	    "SELECT secret
		FROM {$CFG->dbprefix}lti_key
		WHERE user_id = :UID AND key_sha256 = :SHA LIMIT 1",
	    array(":SHA" => $key_sha256,
		":UID" => $_SESSION['id'])
	);
	$reg_password = $oldproxy['secret'];
	if ( strlen($reg_password) < 1 ) {
            lmsDie("Registration key $reg_key cannot be re-registered.");
	}
} else if ( $lti_message_type == "ToolProxyRegistrationRequest" ) {
	$reg_key = $_POST['reg_key'];
	$key_sha256 = lti_sha256($reg_key);
	echo("key_sha256=".$key_sha256."<br>");
	$oldproxy = $PDOX->rowDie(
	    "SELECT user_id
		FROM {$CFG->dbprefix}lti_key
		WHERE key_sha256 = :SHA LIMIT 1",
	    array(":SHA" => $key_sha256)
	);
	if ( is_array($oldproxy) && $oldproxy['user_id'] != $_SESSION['id'] ) {
            lmsDie("Registration key $reg_key cannot be registered.");
	}
	$reg_password = $_POST['reg_password'];
} else {
	echo("</pre>");
	lmsDie("lti_message_type not supported ".$lti_message_type);
}

$launch_presentation_return_url = $_POST['launch_presentation_return_url'];

$tc_profile_url = $_POST['tc_profile_url'];
if ( strlen($tc_profile_url) > 1 ) {
	echo("Retrieving profile from ".$tc_profile_url."\n");
    $tc_profile_json = Net::doGet($tc_profile_url);
	echo("Retrieved ".strlen($tc_profile_json)." characters.\n");
	echo("</pre>\n");
    $OUTPUT->togglePre("Retrieved Consumer Profile",$tc_profile_json);
    $tc_profile = json_decode($tc_profile_json);
	if ( $tc_profile == null ) {
		lmsDie("Unable to parse tc_profile error=".json_last_error());
	}
} else {
    lmsDie("We must have a tc_profile_url to continue...");
}

// Find the registration URL

echo("<pre>\n");
// $oauth_consumer_key = $tc_profile->guid;
$oauth_consumer_key = $reg_key;
$tc_services = $tc_profile->service_offered;
echo("Found ".count($tc_services)." services profile..\n");
if ( count($tc_services) < 1 ) lmsDie("At a minimum, we need the service to register ourself - doh!\n");

// var_dump($tc_services);
$register_url = false;
$result_url = false;
foreach ($tc_services as $tc_service) {
    $formats = $tc_service->{'format'};
    $actions = $tc_service->{'action'};
    $type = $tc_service->{'@type'};
    $id = $tc_service->{'@id'};
    $actions = $tc_service->action;
    if ( ! (is_array($actions) && in_array('POST', $actions)) ) continue;
    foreach($formats as $format) {
        // Canvas includes two entries - only one with POST
        // The POST entry is the one with a real URL
        if ( ! in_array("POST",$actions) ) continue;
        if ( $format != "application/vnd.ims.lti.v2.toolproxy+json" ) continue;
        $register_url = $tc_service->endpoint;
    }
}

if ( $register_url == false ) lmsDie("Must have an application/vnd.ims.lti.v2.toolproxy+json service available in order to do tool_registration.");

// unset($_SESSION['result_url']);
// if ( $result_url !== false ) $_SESSION['result_url'] = $result_url;

echo("\nFound an application/vnd.ims.lti.v2.toolproxy+json service - nice for us...\n");

// Check for capabilities
$tc_capabilities = $tc_profile->capability_offered;
echo("Found ".count($tc_capabilities)." capabilities..\n");
if ( count($tc_capabilities) < 1 ) lmsDie("No capabilities found!\n");
$cur_base = $CFG->wwwroot;

$tp_profile = json_decode($tool_proxy);
if ( $tp_profile == null ) {
	$OUTPUT->togglePre("Tool Proxy Raw",htmlent_utf8($tool_proxy));
    $body = json_encode($tp_profile);
    $body = jsonIndent($body);
    $OUTPUT->togglePre("Tool Proxy Parsed",htmlent_utf8($body));
    lmsDie("Unable to parse our own internal Tool Proxy (DOH!) error=".json_last_error()."\n");
}

// Tweak the stock profile
$tp_profile->tool_consumer_profile = $tc_profile_url;
$tp_profile->tool_profile->message[0]->path = $CFG->wwwroot;

// A globally unique identifier for the service provider. As a best practice, this value should match an Internet domain name assigned by ICANN, but any globally unique identifier is acceptable.
$instance_guid = isset($CFG->product_instance_guid) ? $CFG->product_instance_guid : "lti2.example.com";
$tp_profile->tool_profile->product_instance->guid = $instance_guid;

$tp_profile->tool_profile->product_instance->support->email = $CFG->owneremail;
$tp_profile->tool_profile->product_instance->service_provider->guid = $CFG->wwwroot;
$tp_profile->tool_profile->product_instance->service_provider->support->email = $CFG->owneremail;
$tp_profile->tool_profile->product_instance->service_provider->provider_name->default_value = $CFG->ownername;
$tp_profile->tool_profile->product_instance->service_provider->description->default_value = $CFG->servicename;


// Pull out our prototypical resource handler and clear it out
$handler = $tp_profile->tool_profile->resource_handler[0];
$tp_profile->tool_profile->resource_handler = array();
$blank_handler = json_encode($handler);
echo("=================\n");
// echo(jsonIndent($blank_handler));

// Scan the tools folders for registration settings
echo("Searching for available tools...<br/>\n");
$tools = findFiles("register.php","../");
if ( count($tools) < 1 ) {
    lmsDie("No register.php files found...<br/>\n");
}

$toolcount = 0;
foreach($tools as $tool ) {
    $path = str_replace("../","",$tool);
    echo("Checking $path ...<br/>\n");
    unset($REGISTER_LTI2);
    require($tool);
    if ( isset($REGISTER_LTI2) && is_array($REGISTER_LTI2) ) {
        $newhandler = json_decode($blank_handler);
        if ( isset($REGISTER_LTI2['name']) && isset($REGISTER_LTI2['short_name']) && 
            isset($REGISTER_LTI2['description']) ) {
            $newhandler->resource_name->default_value = $REGISTER_LTI2['name'];
            $newhandler->short_name->default_value = $REGISTER_LTI2['short_name'];
            $newhandler->description->default_value = $REGISTER_LTI2['description'];
        } else {
            lmsDie("Missing required name, short_name, and description in ".$tool);
        }

        $script = isset($REGISTER_LTI2['script']) ? $REGISTER_LTI2['script'] : "index.php";

        $code = isset($CFG->resource_type_prefix) ? $CFG->resource_type_prefix : '';
        if ( isset($REGISTER_LTI2->code) ) {
            $code .= $REGISTER_LTI2->code;
        } else {
            $code .= str_replace("/","_",str_replace("/register.php","",$path));
        }
        $newhandler->resource_type->code = $code;
        $newhandler->message[0]->path = "/".str_replace("register.php", $script, $path);
        $tp_profile->tool_profile->resource_handler[] = $newhandler;
        $toolcount++;
    }
}

if ( $toolcount < 1 ) {
    lmsDie("No tools to register..");
}

// Only ask for parameters we are allowed to ask for 
// Canvas rejects us if  we ask for a custom parameter that they did 
// not offer as capability
$parameters = $tp_profile->tool_profile->resource_handler[0]->message[0]->parameter;
$newparms = array();
foreach($parameters as $parameter) {
    if ( isset($parameter->variable) ) {
        if ( ! in_array($parameter->variable, $tc_capabilities) ) continue;
    }
    $newparms[] = $parameter;
}
// var_dump($newparms);
$tp_profile->tool_profile->resource_handler[0]->message[0]->parameter = $newparms;

// Ask for the kitchen sink...
foreach($tc_capabilities as $capability) {
	if ( "basic-lti-launch-request" == $capability ) continue;
	if ( in_array($capability, $tp_profile->tool_profile->resource_handler[0]->message[0]->enabled_capability) ) continue;
	$tp_profile->tool_profile->resource_handler[0]->message[0]->enabled_capability[] = $capability;
}

// Cause an error on registration
// $tp_profile->tool_profile->resource_handler[0]->message[0]->enabled_capability[] = "Give.me.the.database.password";

$tp_profile->tool_profile->base_url_choice[0]->secure_base_url = $CFG->wwwroot;
$tp_profile->tool_profile->base_url_choice[0]->default_base_url = $CFG->wwwroot;

// Construct the half secret or shared secret
$shared_secret = bin2hex( openssl_random_pseudo_bytes( 512/8 ) ) ;
$oauth_splitsecret = in_array('OAuth.splitSecret', $tc_capabilities);
if ( $oauth_splitsecret ) {
    $tp_profile->security_contract->tp_oauth_half_secret = $shared_secret;
    echo("Provider Half Secret:\n".$shared_secret."\n");
} else {
    $tp_profile->security_contract->shared_secret = $shared_secret;
    echo("Shared secret:\n".$shared_secret."\n");
}

$tp_services = array();
foreach($tc_services as $tc_service) {
	// var_dump($tc_service);
	$tp_service = new stdClass;
	$tp_service->{'@type'} = 'RestServiceProfile';
	$tp_service->action = $tc_service->action;
	$tp_service->service = $tc_service->{'@id'};
	$tp_services[] = $tp_service;
}
// var_dump($tp_services);
$tp_profile->security_contract->tool_service = $tp_services;
// print_r($tp_profile);

$body = json_encode($tp_profile);
$body = jsonIndent($body);

echo("Register Endpoint=".$register_url."\n");
echo("Result Endpoint=".$result_url."\n");
echo("Registration Key=".$reg_key."\n");
echo("Registration Secret=".$reg_password."\n");
echo("Consumer Key=".$oauth_consumer_key."\n");

if ( strlen($register_url) < 1 || strlen($reg_key) < 1 || strlen($reg_password) < 1 ) lmsDie("Cannot call register_url - insufficient data...\n");

unset($_SESSION['reg_key']);
unset($_SESSION['reg_password']);
$_SESSION['reg_key'] = $reg_key;
$_SESSION['reg_password'] = $reg_password;

// Now we are ready to send the registration.  Time to set up the key for the 
// our side of the security contract.

$key_sha256 = lti_sha256($oauth_consumer_key);
echo("key_sha256=".$key_sha256."<br>");

echo("</pre>\n");

// Get the ack value
$ack = false;
if ( $re_register ) {
    $ack = bin2hex(openssl_random_pseudo_bytes(10));
}

// Lets register!
$OUTPUT->togglePre("Registration Request",htmlent_utf8($body));

$more_headers = array();
if ( $ack !== false ) {
    $more_headers[] = 'VND-IMS-ACKNOWLEDGE-URL: '.$CFG->wwwroot.
	'/lti/tp_commit.php?commit='.urlencode($ack);
}

$response = LTI::sendOAuthBody("POST", $register_url, $reg_key, $reg_password, "application/vnd.ims.lti.v2.toolproxy+json", $body, $more_headers);

$OUTPUT->togglePre("Registration Request Headers",htmlent_utf8(Net::getBodySentDebug()));

global $LastOAuthBodyBaseString;
$OUTPUT->togglePre("Registration Request Base String",$LastOAuthBodyBaseString);

$OUTPUT->togglePre("Registration Response Headers",htmlent_utf8(Net::getBodyReceivedDebug()));

$OUTPUT->togglePre("Registration Response",htmlent_utf8(jsonIndent($response)));

// Parse the response object and update the shared_secret if needed
$responseObject = json_decode($response);
if ( $responseObject != null ) {
    if ( $oauth_splitsecret && $shared_secret ) {
        if ( ! isset($responseObject->tc_oauth_half_secret) ) {
            die_with_error_log("<p>Error: Tool Consumer did not provide oauth_splitsecret</p>\n");
        } else {
            $tc_oauth_half_secret = $responseObject->tc_oauth_half_secret;
            $shared_secret = $tc_oauth_half_secret . $shared_secret;
            echo("<p>Split Secret: ".$shared_secret."</p>\n");
        }
    }
}

// A big issue here is that TCs choose the proxy guid, and we need for 
// the proxy guids (i.e. oauth_consumer_key) to be unique.  So we mark
// these with user_id and do not let a second TC slip in and take over
// an existing key.   So the next few lines of code are really critical.
// And we can neither use INSERT / UPDATE because we cannot add the user_id 
// to the unique constraint.

if ( $re_register ) {
    $retval = $PDOX->queryDie(
        "UPDATE {$CFG->dbprefix}lti_key SET updated_at = NOW(), ack = :ACK,
            new_secret = :SECRET, new_consumer_profile = :PROFILE
            WHERE key_sha256 = :SHA and user_id = :UID",
        array(":SECRET" => $shared_secret, ":PROFILE" => $tc_profile_json,
            ":UID" => $_SESSION['id'], ":SHA" => $key_sha256, 
	    ":ACK" => $ack)
    );

    if ( ! $retval->success ) {
        lmsDie("Unable to UPDATE Registration key $oauth_consumer_key ".$retval->errorImplode);
    }

    echo_log("LTI2 Key $oauth_consumer_key updated.\n");

// If we do not have a key, insert one, checking carefully for a failed insert
// due to a unique constraint violation.  If this insert fails, it is likely
// a race condition between competing INSERTs for the same key_id
} else {
    $retval = $PDOX->queryDie(
        "INSERT INTO {$CFG->dbprefix}lti_key 
            (key_sha256, key_key, user_id, secret, consumer_profile)
        VALUES
            (:SHA, :KEY, :UID, :SECRET, :PROFILE)
        ON DUPLICATE KEY
            UPDATE secret = :SECRET, consumer_profile = :PROFILE
        ",
        array(":SHA" => $key_sha256, ":KEY" => $oauth_consumer_key, 
            ":UID" => $_SESSION['id'], ":SECRET" => $shared_secret,
            ":PROFILE" => $tc_profile_json)
    );
    if ( ! $retval->success ) {
        lmsDie("Unable to INSERT Registration key $oauth_consumer_key ".$retval->errorImplode);
    }
    echo_log("LTI2 Key $oauth_consumer_key inserted.\n");
}


if ( $last_http_response == 201 || $last_http_response == 200 ) {
  echo('<p><a href="'.$launch_presentation_return_url.'">Continue to launch_presentation_url</a></p>'."\n");
  exit();
}

echo("Registration failed, http code=".$last_http_response."\n");

// Check to see if they slid us the base string...
if ( $responseObject != null && isset($responseObject->base_string) ) {
	$base_string = $responseObject->base_string;
	if ( strlen($base_string) > 0 && strlen($LastOAuthBodyBaseString) > 0 && $base_string != $LastOAuthBodyBaseString ) {
		$compare = LTI::compareBaseStrings($LastOAuthBodyBaseString, $base_string);
		$OUTPUT->togglePre("Compare Base Strings (ours first)",htmlent_utf8($compare));
	}
}

?>
