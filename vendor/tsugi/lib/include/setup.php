<?php
use \Tsugi\Util\U;

if ( ! isset($CFG) ) die("Please configure this product using config.php");

// This is where we change the overall database version to trigger
// upgrade checking - don't change this unless you want to trigger
// database upgrade messages it should be the max of all versions in
// all database.php files.
$CFG->dbversion = 202112011310;

// Just turn this off to avoid security holes due to XML parsing
if ( function_exists ( 'libxml_disable_entity_loader' ) && version_compare(PHP_VERSION, '8.0.0') < 0 ) libxml_disable_entity_loader();

// Only exists in PHP 5 >= 5.5.0
if ( ! function_exists ( 'json_last_error_msg' ) ) {
    function json_last_error_msg() { return ""; }
}

function die_with_error_log($msg, $extra=false, $prefix="DIE:") {
    error_log($prefix.' '.$msg.' '.$extra);
    print_stack_trace();
    die($msg); // with error_log
}

function echo_log($msg) {
    echo(htmlent_utf8($msg));
    error_log(str_replace("\n"," ",$msg));
}

function session_safe_id() {
    $retval = session_id();
    if ( strlen($retval) > 10 ) return '**********'.substr($retval,5);
}

function print_stack_trace() {
    ob_start();
    debug_print_backtrace(0, 10);
    $data = ob_get_clean();
    error_log($data);
}

if ( isset($CFG->upgrading) && $CFG->upgrading === true ) require_once("upgrading.php");

// The vendor include and root
if ( ! isset($CFG->vendorroot) ) $CFG->vendorroot = $CFG->wwwroot."/vendor/tsugi/lib/util";
if ( ! isset($CFG->vendorinclude) ) $CFG->vendorinclude = $CFG->dirroot."/vendor/tsugi/lib/include";
if ( ! isset($CFG->vendorstatic) ) $CFG->vendorstatic = $CFG->dirroot."/vendor/tsugi/lib/static";
if ( ! isset($CFG->launchactivity) ) $CFG->launchactivity = false;
if ( ! isset($CFG->certification) ) $CFG->certification = false;
if ( isset($CFG->staticroot) ) $CFG->staticroot = \Tsugi\Util\U::remove_relative_path($CFG->staticroot);

require_once $CFG->vendorinclude . "/lms_lib.php";

// Check to see if pre_config was included
// TODO: Make this a die() about a year after - Thu Nov 11 19:34:23 EST 2021
if ( !function_exists('_me') ) {
    error_log('config.php out of date, you need to require "pre_config.php" before the autoloader starts - see config-dist.php for the needed command and where to put it in config.php');
    // die('config.php out of date, you need to require "pre_config.php" before the autoloader starts - see config-dist.php for the needed command and where to put it in config.php');
}


// Check if we have been asked to do cookie or cookieless sessions
if ( defined('COOKIE_SESSION') ) {
    // Do nothing - let the session be in a cookie
} else {
    ini_set('session.use_cookies', '0');
    ini_set('session.use_only_cookies',0);
    ini_set('session.use_trans_sid',1);
}

if ( ! isset($CFG->staticroot) ) die_with_error_log('$CFG->staticroot not defined in config.php');
if ( ! isset($CFG->timezone) ) die_with_error_log('$CFG->timezone not defined in config.php');
if ( strpos($CFG->dbprefix, ' ') !== false ) die_with_error_log('$CFG->dbprefix cannot have spaces in it');

if ( !isset($CFG->ownername) ) $CFG->ownername = false;
if ( !isset($CFG->owneremail) ) $CFG->owneremail = false;
if ( !isset($CFG->providekeys) ) $CFG->providekeys = false;
if ( !isset($CFG->unify) ) $CFG->unify = true;

if ( !isset($CFG->apphome) ) $CFG->apphome = $CFG->wwwroot;

if ( !isset($CFG->lang) ) $CFG->lang = false;
if ( !isset($CFG->google_translate) ) $CFG->google_translate = false;

if ( !isset($CFG->noncecheck) ) $CFG->noncecheck = 100;
if ( !isset($CFG->noncetime) ) $CFG->noncetime = 1800;

// By default we don't record events
if ( !isset($CFG->eventcheck) ) $CFG->eventcheck = false;
if ( !isset($CFG->eventtime) ) $CFG->eventtime = 7*24*60*60;

if ( !isset($CFG->git_command) && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ) $CFG->git_command = 'git';

// By default we don't push events
if ( ! isset($CFG->eventpushcount) ) $CFG->eventpushcount = 0;
if ( ! isset($CFG->eventpushtime) ) $CFG->eventpushtime = 2;

// New fontawesome configuration
// If you want to stick with 4.7.0 add this to your config.php
// $CFG->fontawesome = $CFG->staticroot . '/font-awesome-4.7.0'
if ( ! isset($CFG->fontawesome) ) $CFG->fontawesome = $CFG->staticroot . '/fontawesome-free-5.8.2-web';

// Certification hacks
if ( !isset($CFG->prefer_lti1_for_grade_send) ) $CFG->prefer_lti1_for_grade_send = true;

error_reporting(E_ALL & ~E_NOTICE);
error_reporting(E_ALL );
ini_set('display_errors', 1);

if ( isset($CFG->sessionlifetime) ) {
    ini_set('session.gc_maxlifetime', $CFG->sessionlifetime);
} else {
    $CFG->sessionlifetime = ini_get('session.gc_maxlifetime');
}

date_default_timezone_set($CFG->timezone);

function htmlpre_utf8($string) {
    return U::htmlpre_utf8($string);
}

function htmlspec_utf8($string) {
    return U::htmlspec_utf8($string);
}

function htmlent_utf8($string) {
    return U::htmlent_utf8($string);
}

// Makes sure a string is safe as an href
function safe_href($string) {
    return U::safe_href($string);
}

// Convienence method to wrap sha256
function lti_sha256($val) {
    return U::lti_sha256($val);
}

// Convienence method to get the local path if we are doing
function route_get_local_path($dir) {
    return U::route_get_local_path($dir);
}

function get_request_document() {
    return U::get_request_document();
}

function addSession($url) {
    return U::addSession($url);
}

function reconstruct_query($baseurl, $newparms=false) {
    return U::reconstruct_query($baseurl, $newparms);
}

function add_url_parm($url, $key, $val) {
    return U::add_url_parm($url, $key, $val);
}

// Request headers for earlier version of PHP and nginx
// http://www.php.net/manual/en/function.getallheaders.php
if (!function_exists('apache_request_headers')) {
    function apache_request_headers() {
        return U::apache_request_headers();
    }
}

// http://stackoverflow.com/questions/3258634/php-how-to-send-http-response-code
// Backwards compatibility http_response_code
// For 4.3.0 <= PHP <= 5.4.0
if (!function_exists('http_response_code'))
{
    function http_response_code($newcode = NULL)
    {
        return U::http_response_code($newcode);
    }
}

function isCli() {
    return U::isCli();
}

if ( isset($CFG->verifypeer) && $CFG->verifypeer ) \Tsugi\Util\Net::$VERIFY_PEER = true;

// TODO: Create this as well related to OUTPUT.  See Moodle.
// global $PAGE;

// Define these globals later.
global $OUTPUT, $USER, $CONTEXT, $LINK;
$USER = false;
$CONTEXT = false;
$LINK = false;

// No trailer
