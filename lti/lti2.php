<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../config.php";
require_once $CFG->dirroot."/admin/admin_util.php";
require_once 'tp_messages.php';
require_once 'lti2_util.php';

use \Tsugi\Util\LTI;
use \Tsugi\Core\LTIX;
use \Tsugi\Util\Net;

LTIX::getConnection();

session_start();
header('Content-Type: text/html; charset=utf-8');

if ( ! isset($_SESSION['lti2post']) ) {
    die_with_error_log("Missing LTI 2.0 post data");
}

error_log("Session in lti2 ".session_id());

if ( ! isset($_SESSION['id']) ) {
    die_with_error_log("LTI 2 login failed.");
}

// See if this person is allowed to register a tool
$status = check_lti2_key();

if ( is_string($status) ) {
    $_SESSION['error'] = $status;
    go_home();
    return;
}

// We have a person authorized to use LTI 2.0 on this server
$_POST = $_SESSION['lti2post'];

$lti_message_type = $_POST["lti_message_type"];
$re_register = $lti_message_type == "ToolProxyReregistrationRequest";

// Set up the values for the return URL
$return_url_status = false;
$return_url_tool_guid = false;
$return_url_lti_msg = false;
$return_url_lti_errormsg = false;

function log_return_die($message) {
    global $_POST;

    if ( isset($_POST['launch_presentation_return_url']) ) {
        $launch_presentation_return_url = $_POST['launch_presentation_return_url'];
        if ( strpos($launch_presentation_return_url,'?') > 0 ) {
            $launch_presentation_return_url .= '&';
        } else {
            $launch_presentation_return_url .= '?';
        }
        $launch_presentation_return_url .= "status=failure";
        $launch_presentation_return_url .= "&lti_errormsg=" . urlencode($message);
        echo('<p><a href="'.$launch_presentation_return_url.'">Continue to launch_presentation_url</a></p>'."\n");
    }
    lmsDie($message);
}

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
$tool_proxy_guid = false;
$tool_proxy_guid_from_consumer = isset($_POST['tool_proxy_guid']);
if ( $re_register ) {
        $oauth_consumer_key = $_POST['oauth_consumer_key'];
        $reg_key = $oauth_consumer_key;
        $tool_proxy_guid = $oauth_consumer_key;
        $tool_proxy_guid_from_consumer = true;
        $key_sha256 = lti_sha256($oauth_consumer_key);
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
            log_return_die("Registration key $reg_key cannot be re-registered.");
        }
} else if ( $lti_message_type == "ToolProxyRegistrationRequest" ) {
        $reg_key = $_POST['reg_key'];
        $tool_proxy_guid = isset($_POST['tool_proxy_guid']) ? $_POST['tool_proxy_guid'] : $reg_key;
        $oauth_consumer_key = $tool_proxy_guid;
        $key_sha256 = lti_sha256($tool_proxy_guid);
        echo("key_sha256=".$key_sha256."<br>");
        $oldproxy = $PDOX->rowDie(
            "SELECT user_id
                FROM {$CFG->dbprefix}lti_key
                WHERE key_sha256 = :SHA LIMIT 1",
            array(":SHA" => $key_sha256)
        );
        if ( is_array($oldproxy) && $oldproxy['user_id'] != $_SESSION['id'] ) {
            log_return_die("Registration key $reg_key cannot be registered.");
        }
        $reg_password = $_POST['reg_password'];
} else {
        echo("</pre>");
        log_return_die("lti_message_type not supported ".$lti_message_type);
}

if ( $tool_proxy_guid_from_consumer ) {
    echo("We Received a tool_proxy_guid= $tool_proxy_guid \n");
}

$launch_presentation_return_url = $_POST['launch_presentation_return_url'];

$tc_profile_url = $_POST['tc_profile_url'];

if ( strlen($tc_profile_url) > 1 ) {
    // Append lti_version from section 6.1.2 of the spec
    if ( strpos($tc_profile_url,'?') > 0 ) {
        $tc_profile_url .= '&lti_version=LTI-2p0';
    } else {
        $tc_profile_url .= '?lti_version=LTI-2p0';
    }
    echo("Retrieving profile from ".$tc_profile_url."\n");
    $header = "Accept: application/vnd.ims.lti.v2.toolconsumerprofile+json";

    $tc_profile_json = Net::doGet($tc_profile_url, $header);
    echo("Retrieved ".strlen($tc_profile_json)." characters.\n");
    echo("</pre>\n");
    $OUTPUT->togglePre("Retrieved Consumer Profile",$tc_profile_json);
    $tc_profile = json_decode($tc_profile_json);
    if ( $tc_profile == null ) {
        log_return_die("Unable to parse tc_profile error=".json_last_error());
    }
} else {
    log_return_die("We must have a tc_profile_url to continue...");
}

// Figure out the LMS we are dealing with
$ext_lms = false;
try {
    $ext_lms = $tc_profile->product_instance->product_info->product_family->code;
    $ext_lms = strtolower($ext_lms);
    echo("LMS Code=$ext_lms\n");
} catch(Exception $e) {
    $ext_lms = false;
}

// Find the registration URL

echo("<pre>\n");
$tc_services = $tc_profile->service_offered;
echo("Found ".count($tc_services)." services profile..\n");
if ( count($tc_services) < 1 ) log_return_die("At a minimum, we need the service to register ourself - doh!\n");

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

if ( $register_url == false ) log_return_die("Must have an application/vnd.ims.lti.v2.toolproxy+json service available in order to do tool_registration.");

// unset($_SESSION['result_url']);
// if ( $result_url !== false ) $_SESSION['result_url'] = $result_url;

echo("\nFound an application/vnd.ims.lti.v2.toolproxy+json service - nice for us...\n");

// Check for capabilities
$tc_capabilities = $tc_profile->capability_offered;
echo("Found ".count($tc_capabilities)." capabilities..\n");
if ( count($tc_capabilities) < 1 ) log_return_die("No capabilities found!\n");
$cur_base = $CFG->wwwroot;

$tp_profile = json_decode($tool_proxy);
if ( $tp_profile == null ) {
        $OUTPUT->togglePre("Tool Proxy Raw",htmlent_utf8($tool_proxy));
    $body = json_encode($tp_profile);
    $body = LTI::jsonIndent($body);
    $OUTPUT->togglePre("Tool Proxy Parsed",htmlent_utf8($body));
    log_return_die("Unable to parse our own internal Tool Proxy (DOH!) error=".json_last_error()."\n");
}

// Tweak the stock profile
$tp_profile->tool_consumer_profile = $tc_profile_url;
if ( $tool_proxy_guid_from_consumer ) {
    $tp_profile->tool_proxy_guid = $tool_proxy_guid;
}

// Copy over the context
$tp_profile->{'@context'} = $tc_profile->{'@context'};
for($i=0; $i < count($tp_profile->{'@context'}); $i++ ) {
    $ctx = $tp_profile->{'@context'}[$i];
    if ( is_string($ctx) && strpos($ctx,"http://purl.imsglobal.org/ctx/lti/v2/ToolConsumerProfile") !== false ) {
        // $tp_profile->{'@context'}[$i] = "http://www.imsglobal.org/imspurl/lti/v2/ctx/ToolProxy";
        $tp_profile->{'@context'}[$i] = "http://purl.imsglobal.org/ctx/lti/v2/ToolProxy";
    }
}

$tp_profile->tool_profile->message[0]->path = $CFG->wwwroot . '/lti/register';

// A globally unique identifier for the service provider. As a best practice, this value should match an Internet domain name assigned by ICANN, but any globally unique identifier is acceptable.
$instance_guid = isset($CFG->product_instance_guid) ? $CFG->product_instance_guid : "lti2.example.com";
$tp_profile->tool_profile->product_instance->guid = $instance_guid;

$tp_profile->tool_profile->product_instance->support->email = $CFG->owneremail;
$tp_profile->tool_profile->product_instance->service_provider->guid = $CFG->wwwroot;
$tp_profile->tool_profile->product_instance->service_provider->support->email = $CFG->owneremail;
$tp_profile->tool_profile->product_instance->service_provider->service_provider_name->default_value = $CFG->ownername;
$tp_profile->tool_profile->product_instance->service_provider->description->default_value = $CFG->servicename;

// Pull out our prototypical resource handler and clear it out
$handler = $tp_profile->tool_profile->resource_handler[0];
$tp_profile->tool_profile->resource_handler = array();
$blank_handler = json_encode($handler);


echo("=================\n");
// echo(LTI::jsonIndent($blank_handler));

// Ask for all the parameter mappings we are interested in
// Canvas rejects us if  we ask for a custom parameter that they did 
// not offer as capability
$requested_parameters = array();
foreach($desired_parameters as $parameter) {
    if ( ! in_array($parameter, $tc_capabilities) ) continue;
    $np = new stdClass();
    $np->variable = $parameter;
    $np->name = strtolower(str_replace(".","_",$parameter));
    $requested_parameters[] = $np;
}
// var_dump($requested_parameters);

// Ask for the kitchen sink...
$enabled_capabilities = array();
$global_enabled_capabilities = array();
$canvas_placement_capabilities = array();
$sakai_contentitem_capabilities = array();
$hmac256 = false;
foreach($tc_capabilities as $capability) {
        if ( "basic-lti-launch-request" == $capability ) continue;
        if ( "ContentItemSelectionRequest" == $capability ) continue;

        if ( "OAuth.hmac-sha256" == $capability ) {
            // This is not fully supported beyond registration so we never accept this
            // $hmac256 = 'HMAC-SHA256';
        }

        // promote these up to the top level capabilities
        if ( "OAuth.splitSecret" == $capability || "OAuth.hmac-sha256" == $capability ) {
            $global_enabled_capabilities[] = $capability;
        }

        // Separate out the Canvas placement capabilities
        if ( strpos($capability,"Canvas.placements.") === 0 ) {
            $canvas_placement_capabilities[] = $capability;
            continue;
        }

	// Separate out the Sakai contentitem capabilities
        if ( strpos($capability,"Sakai.contentitem.") === 0 ) {
            $sakai_contentitem_capabilities[] = $capability;
            continue;
        }
        $enabled_capabilities[] = $capability;
}

$tp_profile->enabled_capability = $global_enabled_capabilities;

// Scan the tools folders for registration settings
echo("Searching for available tools...<br/>\n");
$tools = findFiles("register.php","../");
if ( count($tools) < 1 ) {
    log_return_die("No register.php files found...<br/>\n");
}

// If Canvas sees an LTI 2.0 tool with no placement advice, the default is linkSelection and assignmentSelection

// Note that as of 8-Oct-2015 Canvas LTI 2.0 does not yet provide:
// 'Canvas.placements.contentImport' - be part of the import menu
// 'Canvas.placements.homeworkSubmission' - allow the end-user to pick a file for submission
// 'Canvas.placements.richtextEditor' - Suitable for putting in the text editor
// but we include these in case they appear.  They might end up never appearing or having
// different names - but they are safe to include in our "wish list" below.

// Here are the ones we might get:
// Canvas.placements.linkSelection - Shows up in modules
// Canvas.placements.assignmentSelection - Shows up in assignments dropdown
// Canvas.placements.accountNavigation - Auto installed in the account menu - we don't use this if offered
// Canvas.placements.courseNavigation - Auto installed in the course menu - we don't use this if offered

$message_desired_capabilities = array(
  'launch' => array('Canvas.placements.linkSelection', 'Canvas.placements.assignmentSelection'),
  'launch_result' => array('Canvas.placements.assignmentSelection'),
  'select_file' => array('Sakai.contentitem.selectFile', 'Canvas.placements.homeworkSubmission'),
  'select_import' => array('Sakai.contentitem.selectFile', 'Canvas.placements.contentImport'),
  'select_link' => array('Sakai.contentitem.selectLink', 'Canvas.placements.linkSelection', 'Canvas.placements.richtextEditor'),
  'select_any' => array('Sakai.contentitem.selectAny', 'Sakai.contentitem.selectFile', 'Sakai.contentitem.selectImport', 
       'Canvas.placements.assignmentSelection', 'Canvas.placements.linkSelection', 'Canvas.placements.richtextEditor')
);

$toolcount = 0;
// If the LMS supports the ContentItem Message, we simply send the store back
// If not, we send back all the tools

// Disable for now
if ( ! $CFG->certification && in_array('ContentItemSelectionRequest', $tc_capabilities) ) {
    echo("This is a ContentItem LMS - Give it back a store URL.\n");
    // Force this to be in wwwroot
    $tp_profile->tool_profile->base_url_choice[0]->secure_base_url = $CFG->wwwroot;
    $tp_profile->tool_profile->base_url_choice[0]->default_base_url = $CFG->wwwroot;
    $newhandler = json_decode($blank_handler);
    $save_message = $newhandler->message[0];
    unset($newhandler->message[0]);

    $message_count = 0;
/*
    $newmsg = clone $save_message;
    $newmsg->path = "/lti/store";
    $newmsg->message_type = "basic-lti-launch-request";
    $newmsg->parameter = $requested_parameters;
    $newmsg->enabled_capability = $tc_capabilities;
    $newhandler->message[$message_count++] = $newmsg;
*/

    $newmsg = clone $save_message;
    $newmsg->message_type = "ContentItemSelectionRequest";
    $newmsg->path = "/lti/store";
    $newmsg->parameter = $requested_parameters;
    $newmsg->enabled_capability = $tc_capabilities; // TODO: Be more selective
    $newhandler->message[$message_count++] = $newmsg;

    $icon_endpoint = $CFG->fontawesome.'/png/shopping-cart.png';

            $icons = array();

            // Sakai likes beautifully scalable FontAwesome icons - but no one else
            if ( $ext_lms == 'sakai' ) {
                $icon_info = new stdClass();
                $icon_style = array();
                $icon_style[] = 'FontAwesome';
                $default_location = new stdClass();
                $default_location->path = 'fa-shopping-cart';
                $icon_info->icon_style = $icon_style;
                $icon_info->default_location = $default_location;
                $icons[] = $icon_info;

            // BUG: Moodle crashes on icons with absolute paths
            // https://tracker.moodle.org/browse/MDL-58216
            // Moodle crashes with "invalidresponse" message (yes no space) on absolute paths
            // Interestingly, Moodle also seems to not handle "IconEndpoint" selectors either
            // So leaving the the icon off completely is the best plan for Moodle currently
            } else if ( $ext_lms == 'moodle' ) {
/*
                $default_location = new stdClass();
                $icon_endpoint = str_replace('fa-','',$fa_icon).'.png';
                $default_location->path = $icon_endpoint;
                $icon_info = new stdClass();
                $icon_info->default_location = $default_location;
                $icons[] = $icon_info;
*/
            // Everyone else (i.e. Canvas) gets a nice CloudFlareable image with an absolute URL.
            } else {
                $default_location = new stdClass();
                $icon_endpoint = $CFG->fontawesome.'/png/shopping-cart.png';
                $default_location->path = $icon_endpoint;
                $icon_info = new stdClass();
                $icon_info->default_location = $default_location;
                $icons[] = $icon_info;
            }

            if ( count($icons) > 0 ) $newhandler->icon_info = $icons;

    $code = str_replace("/","_",$CFG->wwwroot . $newmsg->path);
    $newhandler->resource_type->code = $code;
    $tp_profile->tool_profile->resource_handler[] = $newhandler;
    $toolcount++;
} else foreach($tools as $tool ) {
    $path = str_replace("../","",$tool);
    echo("Checking $path ...<br/>\n");
    unset($REGISTER_LTI2);
    require($tool);
    if ( isset($REGISTER_LTI2) && is_array($REGISTER_LTI2) ) {
        $newhandler = json_decode($blank_handler);
        if ( isset($REGISTER_LTI2['name']) && isset($REGISTER_LTI2['short_name']) && 
            isset($REGISTER_LTI2['description']) ) {
            $newhandler->resource_name->default_value = $REGISTER_LTI2['name'];
            // $newhandler->short_name->default_value = $REGISTER_LTI2['short_name'];
            $newhandler->description->default_value = $REGISTER_LTI2['description'];
        } else {
            log_return_die("Missing required name, short_name, and description in ".$tool);
        }

        $script = isset($REGISTER_LTI2['script']) ? $REGISTER_LTI2['script'] : "index.php";

        $code = isset($CFG->resource_type_prefix) ? $CFG->resource_type_prefix : '';
        if ( isset($REGISTER_LTI2->code) ) {
            $code .= $REGISTER_LTI2->code;
        } else {
            $code .= str_replace("/","_",str_replace("/register.php","",$path));
        }

        if ( isset($REGISTER_LTI2['FontAwesome']) ) {
            $fa_icon = $REGISTER_LTI2['FontAwesome'];
            $icons = array();

            // Sakai likes beautifully scalable FontAwesome icons - but no one else
            if ( $ext_lms == 'sakai' ) {
                $icon_info = new stdClass();
                $icon_style = array();
                $icon_style[] = 'FontAwesome';
                $default_location = new stdClass();
                $default_location->path = $fa_icon;
                $icon_info->icon_style = $icon_style;
                $icon_info->default_location = $default_location;
                $icons[] = $icon_info;

            // BUG: Moodle crashes on icons with absolute paths
            // https://tracker.moodle.org/browse/MDL-58216
            // Moodle crashes with "invalidresponse" message (yes no space) on absolute paths
            // Interestingly, Moodle also seems to not handle "IconEndpoint" selectors either
            // So leaving the the icon off completely is the best plan for Moodle currently
            } else if ( $ext_lms == 'moodle' ) {
/*
                $default_location = new stdClass();
                $icon_endpoint = str_replace('fa-','',$fa_icon).'.png';
                $default_location->path = $icon_endpoint;
                $icon_info = new stdClass();
                $icon_info->default_location = $default_location;
                $icons[] = $icon_info;
*/
            // Everyone else (i.e. Canvas) gets a nice CloudFlareable image with an absolute URL.
            } else {
                $default_location = new stdClass();
                $icon_endpoint = $CFG->fontawesome.'/png/'.str_replace('fa-','',$fa_icon).'.png';
                $default_location->path = $icon_endpoint;
                $icon_info = new stdClass();
                $icon_info->default_location = $default_location;
                $icons[] = $icon_info;
            }

            if ( count($icons) > 0 ) $newhandler->icon_info = $icons;
        }

        $local_capabilities = $enabled_capabilities;

	// Deal with the tools messages - default is tools can launch_result
	$launch = false;
	$select = false;
	$messages = isset($REGISTER_LTI2['messages']) ? $REGISTER_LTI2['messages'] : array("launch");
	foreach($messages as $message ) {
	    $launch = $launch || ( strpos($message,'launch') === 0 );
	    $select = $select || ( strpos($message,'select') === 0 );
	    if ( ! isset($message_desired_capabilities[$message] ) ) continue;
	    foreach ( $message_desired_capabilities[$message] as $desired ) {
		if ( in_array($desired,$local_capabilities) ) continue; // No dups
		if ( in_array($desired,$sakai_contentitem_capabilities) ||
		     in_array($desired,$canvas_placement_capabilities) ) {
			$local_capabilities[] = $desired;
		}
	    }
	}

	$save_message = $newhandler->message[0];
	unset($newhandler->message[0]);
	$message_count = 0;

	if ( $launch && in_array('basic-lti-launch-request', $tc_capabilities) ) {
            $newmsg = $save_message;
            $newmsg->path = "/".str_replace("register.php", $script, $path);
            $newmsg->parameter = $requested_parameters;
            $newmsg->enabled_capability = $local_capabilities;
	    $newhandler->message[$message_count++] = $newmsg;
	}

	// LTI 2.0 on Canvas will tell us when it can handle this -- 06-Oct-15 /Chuck
	if ( $select && in_array('ContentItemSelectionRequest', $tc_capabilities) ) {
            $newmsg = $save_message;
	    $newmsg->message_type = "ContentItemSelectionRequest";
            $newmsg->path = "/".str_replace("register.php", $script, $path);
            $newmsg->parameter = $requested_parameters;
            $newmsg->enabled_capability = $local_capabilities;
	    $newhandler->message[$message_count++] = $newmsg;
	}

	if ( $message_count < 1 ) {
		echo("Could not find a supported message_type in tc_capabilities for: ".$REGISTER_LTI2['name']."\n");
		continue;
	}

        $newhandler->resource_type->code = $code;
        $tp_profile->tool_profile->resource_handler[] = $newhandler;
        $toolcount++;
    }
}

/*
// Add the re-registration message
$newmsg = $tp_profile->tool_profile->message[0];
$newhandler = json_decode($blank_handler);
$newmsg->path = '/tsugi/lti/register.php';
$newmsg->parameter = $requested_parameters;
$newmsg->enabled_capability = $local_capabilities;
unset($newhandler->message[0]);
$newhandler->message[$message_count++] = $newmsg;
$tp_profile->tool_profile->resource_handler[] = $newhandler;
*/

if ( $toolcount < 1 ) {
    log_return_die("No tools to register..");
}

if ( isset($CFG->apphome) ) {
    $tp_profile->tool_profile->base_url_choice[0]->secure_base_url = $CFG->apphome;
    $tp_profile->tool_profile->base_url_choice[0]->default_base_url = $CFG->apphome;
} else {
    $tp_profile->tool_profile->base_url_choice[0]->secure_base_url = $CFG->wwwroot;
    $tp_profile->tool_profile->base_url_choice[0]->default_base_url = $CFG->wwwroot;
}

// BUG: Moodle ignores the 'MessageHandler'
/*
if ( $ext_lms == 'moodle') { 
    $selector = new stdClass();
    $selector->applies_to = array('MessageHandler');
    $tp_profile->tool_profile->base_url_choice[0]->selector = $selector;

    $icon_choice = new stdClass();
    $icon_choice->secure_base_url = $CFG->fontawesome.'/png/';
    $icon_choice->default_base_url = $CFG->fontawesome.'/png/';
    $selector = new stdClass();
    $selector->applies_to = array('IconEndpoint');
    $icon_choice->selector = $selector;
    $tp_profile->tool_profile->base_url_choice[1] = $icon_choice;
}
*/

// Construct the half secret or shared secret
$shared_secret = bin2hex( openssl_random_pseudo_bytes( 512/8 ) ) ;
$oauth_splitsecret = in_array('OAuth.splitSecret', $tc_capabilities);
if ( $oauth_splitsecret ) {
    $tp_profile->security_contract->tp_half_shared_secret = $shared_secret;
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
$body = LTI::jsonIndent($body);
// echo($body);die();

echo("Register Endpoint=".$register_url."\n");
echo("Result Endpoint=".$result_url."\n");
echo("Registration Key=".$reg_key."\n");
echo("Registration Secret=".$reg_password."\n");
echo("Consumer Key=".$oauth_consumer_key."\n");

if ( strlen($register_url) < 1 || strlen($reg_key) < 1 || strlen($reg_password) < 1 ) log_return_die("Cannot call register_url - insufficient data...\n");

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

// Time for a pause
if ( !isset($_GET['continue']) ) {
    echo('<a href="lti2?continue=yes">Press Here To Register</a>');
    return;
}

$more_headers = array();
if ( $ack !== false ) {
    $more_headers[] = 'VND-IMS-CONFIRM-URL: '.$CFG->wwwroot.
        '/lti/tp_commit?commit='.urlencode($ack)."&oauth_consumer_key=".$reg_key;
}

$response = LTI::sendOAuthBody("POST", $register_url, $reg_key, $reg_password, "application/vnd.ims.lti.v2.toolproxy+json", $body, $more_headers, $hmac256);

$response_code = Net::getLastHttpResponse();

global $LastOAuthBodyBaseString;
$OUTPUT->togglePre("Registration Request Headers",htmlent_utf8(Net::getBodySentDebug()));
$OUTPUT->togglePre("Registration Request Base String",$LastOAuthBodyBaseString);
echo("<p>Http Response code = $response_code</p>\n");
$OUTPUT->togglePre("Registration Response Headers",htmlent_utf8(Net::getBodyReceivedDebug()));
$OUTPUT->togglePre("Registration Response",htmlent_utf8(LTI::jsonIndent($response)));

if ( $response_code != 201 ) {
    log_return_die("Did not get 201 response code=".$response_code);
}

if ( strlen($response) < 1 ) {
    log_return_die("Expecting JSON response - unable to proceed");
}

// Parse the JSON
$responseObject = json_decode($response);
if ( $responseObject == null ) {
    log_return_die("Unable to parse returned JSON - unable to proceed");
}

// Parse the response object and update the shared_secret if needed
$tc_tool_proxy_guid = $responseObject->tool_proxy_guid;
if ( $tc_tool_proxy_guid ) {
    $oauth_consumer_key = $tc_tool_proxy_guid;
    echo('<p>Tool consumer returned tool_proxy_guid='.$tc_tool_proxy_guid." (using as oauth_consumer_key)</p>\n");
    if ( $tool_proxy_guid && $tool_proxy_guid != $tc_tool_proxy_guid ) {
        echo('<p style="color: orange;">Note: Returned tool_proxy_guid did not match launch oauth_consumer_key/tool_proxy_guid='.$tool_proxy_guid."</p>\n");
    }
} else {
    echo('<p style="color: red;">Error: Tool Consumer did not include tool_proxy_guid in its response.</p>'."\n");
}

if ( $oauth_splitsecret && $shared_secret ) {
    if ( ! isset($responseObject->tc_half_shared_secret) ) {
        log_return_die("<p>Error: Tool Consumer did not provide tc_half_shared_secret</p>\n");
    } else {
        $tc_half_shared_secret = $responseObject->tc_half_shared_secret;
        $shared_secret = $tc_half_shared_secret . $shared_secret;
        echo("<p>Split Secret: ".$shared_secret."</p>\n");
    }
}

// A big issue here is that TCs choose the proxy guid, and we need for 
// the proxy guids (i.e. oauth_consumer_key) to be unique.  So we mark
// these with user_id and do not let a second TC slip in and take over
// an existing key.   So the next few lines of code are really critical.
// And we can neither use INSERT / UPDATE because we cannot add the user_id 
// to the unique constraint.

if ( $re_register ) {
    $key_sha256 = lti_sha256($oauth_consumer_key);
    $retval = $PDOX->queryDie(
        "UPDATE {$CFG->dbprefix}lti_key SET updated_at = NOW(), ack = :ACK,
            new_secret = :SECRET, new_consumer_profile = :PROFILE
            WHERE key_sha256 = :SHA and user_id = :UID",
        array(":SECRET" => $shared_secret, ":PROFILE" => $tc_profile_json,
            ":UID" => $_SESSION['id'], ":SHA" => $key_sha256, 
            ":ACK" => $ack)
    );

    if ( ! $retval->success ) {
        log_return_die("Unable to UPDATE Registration key $oauth_consumer_key ".$retval->errorImplode);
    }

    $return_url_lti_message = "LTI2 Key $oauth_consumer_key updated";

// If we do not have a key, insert one, checking carefully for a failed insert
// due to a unique constraint violation.  If this insert fails, it is likely
// a race condition between competing INSERTs for the same key_id
} else {
    $key_sha256 = lti_sha256($oauth_consumer_key);
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
        log_return_die("Unable to INSERT Registration key $oauth_consumer_key ".$retval->errorImplode);
    }
    $return_url_lti_message = "LTI2 Key $oauth_consumer_key inserted";
}

echo_log("$return_url_lti_message \n");


if ( $last_http_response == 201 || $last_http_response == 200 ) {

    if ( strpos($launch_presentation_return_url,'?') > 0 ) {
        $launch_presentation_return_url .= '&';
    } else {
        $launch_presentation_return_url .= '?';
    }
    $launch_presentation_return_url .= "status=success";
    $launch_presentation_return_url .= "&lti_message=" . urlencode($return_url_lti_message);
    $launch_presentation_return_url .= "&tool_proxy_guid=" . urlencode($tc_tool_proxy_guid);


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
