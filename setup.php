<?php

// This is where we change the overall database version to trigger
// upgrade checking - don't change this unless you want to trigger
// database upgrade messages it should be the max of all versions in
// all database.php files.
$CFG->dbversion = 2014050500;

function dieWithErrorLog($msg, $extra=false, $prefix="DIE:") {
    error_log($prefix.' '.$msg.' '.$extra);
    printStackTrace();
    die($msg); // with error_log
}

function echoLog($msg) {
    echo($msg);
    error_log(str_replace("\n"," ",$msg));
}

function safeSessionId() {
    $retval = session_id();
    if ( strlen($retval) > 10 ) return '**********'.substr($retval,5);
}

function printStackTrace() {
    ob_start();
    debug_print_backtrace();
    $data = ob_get_clean();
    error_log($data);
}

if ( isset($CFG->upgrading) && $CFG->upgrading === true ) require_once("upgrading.php");

require_once $CFG->dirroot."/lib/lms_lib.php";  // During transition

// Check if we have been asked to do cookie or cookieless sessions
if ( defined('COOKIE_SESSION') ) {
    // Do nothing - let the session be in a cookie
} else {
    ini_set('session.use_cookies', '0');
    ini_set('session.use_only_cookies',0);
    ini_set('session.use_trans_sid',1);
}

if ( ! isset($CFG) ) dieWithErrorLog("Please configure this product using config.php");
if ( ! isset($CFG->staticroot) ) dieWithErrorLog('$CFG->staticroot not defined in config.php');
if ( ! isset($CFG->timezone) ) dieWithErrorLog('$CFG->timezone not defined in config.php');
if ( strpos($CFG->dbprefix, ' ') !== false ) dieWithErrorLog('$CFG->dbprefix cannot have spaces in it');

if ( !isset($CFG->ownername) ) $CFG->ownername = false; 
if ( !isset($CFG->owneremail) ) $CFG->owneremail = false; 
if ( !isset($CFG->providekeys) ) $CFG->providekeys = false;

// Set this to the temporary folder if not set - dev only
if ( ! isset($CFG->dataroot) ) {
    $tmp = sys_get_temp_dir();
    if (strlen($tmp) > 1 && substr($tmp, -1) == '/') $tmp = substr($tmp,0,-1);
    $CFG->dataroot = $tmp;
}

error_reporting(E_ALL & ~E_NOTICE);
error_reporting(E_ALL );
ini_set('display_errors', 1);

if ( isset($CFG->sessionlifetime) ) {
    ini_set('session.gc_maxlifetime', $CFG->sessionlifetime);
} else {
    $CFG->sessionlifetime = ini_get('session.gc_maxlifetime');
}

date_default_timezone_set($CFG->timezone);

function htmlspec_utf8($string) {
    return htmlspecialchars($string,ENT_QUOTES,$encoding = 'UTF-8');
}

function htmlent_utf8($string) {
    return htmlentities($string,ENT_QUOTES,$encoding = 'UTF-8');
}

// Makes sure a string is safe as an href
function safe_href($string) {
    return str_replace(array('"', '<'),
        array('&quot;',''), $string);
}

// Convienence method to wrap sha256
function lti_sha256($val) {
    return hash('sha256', $val);
}

function sessionize($url) {
    if ( ini_get('session.use_cookies') != '0' ) return $url;
    $parameter = session_name().'='.session_id();
    if ( strpos($url, $parameter) !== false ) return $url;
    $url = $url . (strpos($url,'?') > 0 ? "&" : "?");
    $url = $url . $parameter;
    return $url;
}

function reConstructQuery($baseurl, $newparms=false) {
    foreach ( $_GET as $k => $v ) {
        if ( $k == session_name() ) continue;
        if ( is_array($newparms) && array_key_exists($k, $newparms) ) continue;
        $baseurl = addUrlParm($baseurl, $k, $v);
    }
    if ( is_array($newparms) ) foreach ( $newparms as $k => $v ) {
        $baseurl = addUrlParm($baseurl, $k, $v);
    }

    return $baseurl;
}

function addUrlParm($url, $key, $val) {
    $url .= strpos($url,'?') === false ? '?' : '&';
    $url .= urlencode($key) . '=' . urlencode($val);
    return $url;
}

// Request headers for earlier version of PHP and nginx
// http://www.php.net/manual/en/function.getallheaders.php
if (!function_exists('apache_request_headers')) {
    function apache_request_headers() {
        foreach($_SERVER as $key=>$value) {
            if (substr($key,0,5)=="HTTP_") {
                $key=str_replace(" ","-",ucwords(strtolower(str_replace("_"," ",substr($key,5)))));
                $out[$key]=$value;
            } else {
                $out[$key]=$value;
            }
        }
        return $out;
    }
}

// No trailer
