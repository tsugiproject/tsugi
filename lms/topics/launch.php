<?php

use \Tsugi\Util\U;
use \Tsugi\Util\LTI;
use \Tsugi\Core\LTIX;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../../config.php";

LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();

$path = U::rest_path();
$redirect_path = U::addSession($path->parent);
if ( $redirect_path == '') $redirect_path = '/';

if ( ! isset($CFG->topics) ) {
    $_SESSION["error"] = __('Cannot find topics.json ($CFG->topics)');
    header('Location: '.$redirect_path);
    exit();
}

// Load the Topic
$l = new \Tsugi\UI\Topics($CFG->topics);
if ( ! $l ) {
    $_SESSION["error"] = __('Cannot load topics.');
    header('Location: '.$redirect_path);
    exit();
}

// Get anchor from URL path
// Check PATH_INFO first (set by Apache rewrite: /topics/launch/{anchor} -> launch.php/{anchor})
$anchor = null;
if ( isset($_SERVER['PATH_INFO']) && U::strlen($_SERVER['PATH_INFO']) > 0 ) {
    $anchor = ltrim($_SERVER['PATH_INFO'], '/');
} else if ( isset($path->action) && U::strlen($path->action) > 0 ) {
    $anchor = $path->action;
} else if ( isset($path->parameters) && count($path->parameters) > 0 ) {
    $anchor = $path->parameters[0];
}

if ( ! $anchor ) {
    $_SESSION["error"] = __('Missing anchor parameter');
    header('Location: '.$redirect_path);
    exit();
}

$topic = $l->getTopicByRlid($anchor);
if ( ! $topic ) {
    $_SESSION["error"] = __('Cannot find topic resource link id');
    header('Location: '.$redirect_path);
    exit();
}

$lti = $l->getLtiByRlid($anchor);
if ( ! $lti ) {
    $_SESSION["error"] = __('Cannot find lti resource link id');
    header('Location: '.$redirect_path);
    exit();
}

// Check that the session has the minimums...
if ( isset($topic->lti) && U::get($_SESSION,'secret') && U::get($_SESSION,'context_key')
    && U::get($_SESSION,'user_key') && U::get($_SESSION,'displayname') && U::get($_SESSION,'email') )
{
    // All good
} else {
    $_SESSION["error"] = __('Missing session data required for launch');
    header('Location: '.$redirect_path);
    exit();
}

$resource_link_title = isset($lti->title) ? $lti->title : $topic->title;
$key = isset($_SESSION['oauth_consumer_key']) ? $_SESSION['oauth_consumer_key'] : false;
$secret = false;
if ( isset($_SESSION['secret']) ) {
    $secret = LTIX::decrypt_secret($_SESSION['secret']);
}

$resource_link_id = $lti->resource_link_id;
$parms = array(
    'lti_message_type' => 'basic-lti-launch-request',
    'resource_link_id' => $resource_link_id,
    'resource_link_title' => $resource_link_title,
    'tool_consumer_info_product_family_code' => 'tsugi',
    'tool_consumer_info_version' => '1.1',
    'context_id' => $_SESSION['context_key'],
    'context_label' => $CFG->context_title,
    'context_title' => $CFG->context_title,
    'user_id' => $_SESSION['user_key'],
    'lis_person_name_full' => $_SESSION['displayname'],
    'lis_person_contact_email_primary' => $_SESSION['email'],
    'roles' => 'Learner'
);
if ( isset($_SESSION['avatar']) ) $parms['user_image'] = $_SESSION['avatar'];

if ( isset($lti->custom) ) {
    foreach($lti->custom as $custom) {
        if ( isset($custom->value) ) {
            $parms['custom_'.$custom->key] = $custom->value;
        }
        if ( isset($custom->json) ) {
            $parms['custom_'.$custom->key] = json_encode($custom->json);
        }
    }
}

// Construct return URL: /lms/topics/{topic_anchor} (not /lms/topics/launch/{topic_anchor})
// We're in /lms/topics/launch.php, so the base path should be /lms/topics
// Get the directory path relative to wwwroot
$script_dir = dirname($_SERVER['SCRIPT_NAME']); // e.g., /py4e/tsugi/lms/topics
// Remove /launch if present, but keep /lms/topics
$base_path = str_replace('/launch', '', $script_dir);
$base_path = rtrim($base_path, '/');
$return_url = $base_path . '/' . $topic->anchor;
$parms['launch_presentation_return_url'] = $return_url;

$sess_key = 'tsugi_top_nav_'.$CFG->wwwroot;
if ( isset($_SESSION[$sess_key]) ) {
    // $parms['ext_tsugi_top_nav'] = $_SESSION[$sess_key];
}

$form_id = "tsugi_form_id_".bin2Hex(openssl_random_pseudo_bytes(4));
$parms['ext_lti_form_id'] = $form_id;

$endpoint = $lti->launch;
$parms = LTI::signParameters($parms, $endpoint, "POST", $key, $secret,
    "Finish Launch", $CFG->wwwroot, $CFG->servicename);

$content = LTI::postLaunchHTML($parms, $endpoint, false /*debug */);
print($content);
