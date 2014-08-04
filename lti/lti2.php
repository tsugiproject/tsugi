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

error_log("Sesssion in lti2 ".session_id());

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

if ( $lti_message_type == "ToolProxyReregistrationRequest" ) {
	$reg_key = $_POST['oauth_consumer_key'];
	$reg_password = "secret";
} else if ( $lti_message_type == "ToolProxyRegistrationRequest" ) {
	$reg_key = $_POST['reg_key'];
	$reg_password = $_POST['reg_password'];
} else {
	echo("</pre>");
	die("lti_message_type not supported ".$lti_message_type);
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
		die("Unable to parse tc_profile error=".json_last_error());
	}
} else {
    die("We must have a tc_profile_url to continue...");
}

// Find the registration URL

echo("<pre>\n");
$tc_services = $tc_profile->service_offered;
echo("Found ".count($tc_services)." services profile..\n");
if ( count($tc_services) < 1 ) die("At a minimum, we need the service to register ourself - doh!\n");

// var_dump($tc_services);
$register_url = false;
$result_url = false;
foreach ($tc_services as $tc_service) {
    $formats = $tc_service->{'format'};
    $type = $tc_service->{'@type'};
    $id = $tc_service->{'@id'};
    $actions = $tc_service->action;
    if ( ! (is_array($actions) && in_array('POST', $actions)) ) continue;
    foreach($formats as $format) {
        echo("Service: ".$format." id=".$id."\n");
        if ( $format != "application/vnd.ims.lti.v2.toolproxy+json" ) continue;
        // var_dump($tc_service);
        $register_url = $tc_service->endpoint;
    }
}

if ( $register_url == false ) die("Must have an application/vnd.ims.lti.v2.toolproxy+json service available in order to do tool_registration.");

// unset($_SESSION['result_url']);
// if ( $result_url !== false ) $_SESSION['result_url'] = $result_url;

echo("\nFound an application/vnd.ims.lti.v2.toolproxy+json service - nice for us...\n");

// Check for capabilities
$tc_capabilities = $tc_profile->capability_offered;
echo("Found ".count($tc_capabilities)." capabilities..\n");
if ( count($tc_capabilities) < 1 ) die("No capabilities found!\n");

$cur_base = $CFG->wwwroot;

$tp_profile = json_decode($tool_proxy);
if ( $tp_profile == null ) {
	$OUTPUT->togglePre("Tool Proxy Raw",htmlent_utf8($tool_proxy));
    $body = json_encode($tp_profile);
    $body = jsonIndent($body);
    $OUTPUT->togglePre("Tool Proxy Parsed",htmlent_utf8($body));
    die("Unable to parse our own internal Tool Proxy (DOH!) error=".json_last_error()."\n");
}

// Tweak the stock profile
$tp_profile->tool_consumer_profile = $tc_profile_url;

// I want this *not* to be unique per instance
$tp_profile->tool_profile->product_instance->guid = "urn:sakaiproject:unit-test";
$tp_profile->tool_profile->product_instance->service_provider->guid = $CFG->wwwroot;

// Re-register
$tp_profile->tool_profile->message[0]->path = $CFG->wwwroot;
$tp_profile->tool_profile->product_instance->product_info->product_family->vendor->website = $cur_base;
$tp_profile->tool_profile->product_instance->product_info->product_family->vendor->timestamp = "2013-07-13T09:08:16-04:00";

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
    die("No register.php files found...<br/>\n");
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
            $newhandler->name = $REGISTER_LTI2['name'];
            $newhandler->short_name = $REGISTER_LTI2['short_name'];
            $newhandler->description = $REGISTER_LTI2['description'];
        } else {
            die("Missing required name, short_name, and description in ".$tool);
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
    die("No tools to register..");
}


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

$tp_profile->security_contract->shared_secret = 'secret';
$tp_services = array();
foreach($tc_services as $tc_service) {
	// var_dump($tc_service);
	$tp_service = new stdClass;
	$tp_service->{'@id'} = $tc_service->{'@id'};
	$tp_service->{'@type'} = $tc_service->{'@type'};
	$tp_service->format = $tc_service->format;
	$tp_service->action = $tc_service->action;
	$tp_service->service = $tc_service->endpoint;
	$tp_services[] = $tp_service;
}
// var_dump($tp_services);
$tp_profile->security_contract->tool_service = $tp_services;
// print_r($tp_profile);

$body = json_encode($tp_profile);
$body = jsonIndent($body);

echo("Registering....\n");
echo("Register Endpoint=".$register_url."\n");
echo("Result Endpoint=".$result_url."\n");
echo("Key=".$reg_key."\n");
echo("Secret=".$reg_password."\n");
echo("</pre>\n");

if ( strlen($register_url) < 1 || strlen($reg_key) < 1 || strlen($reg_password) < 1 ) die("Cannot call register_url - insufficient data...\n");

unset($_SESSION['reg_key']);
unset($_SESSION['reg_password']);
$_SESSION['reg_key'] = $reg_key;
$_SESSION['reg_password'] = $reg_password;

$OUTPUT->togglePre("Registration Request",htmlent_utf8($body));

$response = LTI::sendOAuthBodyPOST($register_url, $reg_key, $reg_password, "application/vnd.ims.lti.v2.toolproxy+json", $body);

$OUTPUT->togglePre("Registration Request Headers",htmlent_utf8(Net::getBodySentDebug()));

global $LastOAuthBodyBaseString;
$OUTPUT->togglePre("Registration Request Base String",$LastOAuthBodyBaseString);

$OUTPUT->togglePre("Registration Response Headers",htmlent_utf8(Net::getBodyReceivedDebug()));

$OUTPUT->togglePre("Registration Response",htmlent_utf8(jsonIndent($response)));

if ( $last_http_response == 201 || $last_http_response == 200 ) {
  echo('<p><a href="'.$launch_presentation_return_url.'">Continue to launch_presentation_url</a></p>'."\n");
  exit();
}

echo("Registration failed, http code=".$last_http_response."\n");

// Check to see if they slid us the base string...
$responseObject = json_decode($response);
if ( $responseObject != null && isset($responseObject->base_string) ) {
	$base_string = $responseObject->base_string;
	if ( strlen($base_string) > 0 && strlen($LastOAuthBodyBaseString) > 0 && $base_string != $LastOAuthBodyBaseString ) {
		$compare = LTI::compareBaseStrings($LastOAuthBodyBaseString, $base_string);
		$OUTPUT->togglePre("Compare Base Strings (ours first)",htmlent_utf8($compare));
	}
}

?>
