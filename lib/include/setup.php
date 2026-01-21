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

if ( is_string($CFG->staticroot) ) $CFG->staticroot = \Tsugi\Util\U::remove_relative_path($CFG->staticroot);

// Handle both cases: lib in parent directory structure vs standalone lib
$lms_lib_path = $CFG->dirroot."/lib/include/lms_lib.php";
if ( !file_exists($lms_lib_path) ) {
    $lms_lib_path = $CFG->dirroot."/include/lms_lib.php";
}
require_once $lms_lib_path;

// Check to see if pre_config was included
// TODO: Make this a die() about a year after - Thu Nov 11 19:34:23 EST 2021
if ( !function_exists('_me') ) {
    error_log('config.php out of date, you need to require "pre_config.php" before the autoloader starts - see config-dist.php for the needed command and where to put it in config.php');
    // die('config.php out of date, you need to require "pre_config.php" before the autoloader starts - see config-dist.php for the needed command and where to put it in config.php');
}


// Check if we have been asked to do cookie or cookieless sessions
if ( defined('COOKIE_SESSION') ) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $CFG->DEVELOPER ? '0' : '1');
    // ini_set('session.use_strict_mode', '1');
} else {
    $previous = error_reporting();
    error_reporting(0);
    ini_set('session.use_cookies', '0');
    ini_set('session.use_only_cookies',0);
    ini_set('session.use_trans_sid',1);
    error_reporting($previous);
}

// Check for non-embeddable pages and declare appropriate CSP
// Allow the Dynamic Registration URL to be embedded as it is required
if ( preg_match('/(\/admin\/|\/login)/i', $_SERVER['REQUEST_URI'] ?? "") ) {
    if ( ! preg_match('/(\/admin\/key\/auto.php)/i', $_SERVER['REQUEST_URI']) ) {
        header("Content-Security-Policy: frame-ancestors 'self';");
    }
}

if ( ! isset($CFG->staticroot) ) die_with_error_log('$CFG->staticroot not defined in config.php');
if ( ! isset($CFG->timezone) ) die_with_error_log('$CFG->timezone not defined in config.php');
if ( strpos($CFG->dbprefix, ' ') !== false ) die_with_error_log('$CFG->dbprefix cannot have spaces in it');

if ( !isset($CFG->git_command) && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ) $CFG->git_command = 'git';

if ( ! isset($CFG->fontawesome) || ! is_string($CFG->fontawesome) ) $CFG->fontawesome = $CFG->staticroot . '/fontawesome-free-5.8.2-web';

// Certification hacks
if ( !isset($CFG->prefer_lti1_for_grade_send) ) $CFG->prefer_lti1_for_grade_send = true;

error_reporting(E_ALL & ~E_NOTICE);
error_reporting(E_ALL );
ini_set('display_errors', 1);

if ( isset($CFG->sessionlifetime) && is_numeric($CFG->sessionlifetime)  ) {
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

if (!defined('TSUGI_VERSION')) {
    define('TSUGI_VERSION', '2025.12');   // ← update this occasionally
}

if (!defined('TSUGI_MINIMUM_PHP')) {
    define('TSUGI_MINIMUM_PHP', '8.4');   // ← update this occasionally
}
// No trailer
