<?php

require_once $CFG->dirroot."/lib/lms_lib.php";  // During transition

if ( ! defined('COOKIE_SESSION') ) {
    ini_set('session.use_cookies', '0');
    ini_set('session.use_only_cookies',0);
    ini_set('session.use_trans_sid',1); 
}

if ( ! isset($CFG) ) die("Please configure this product using config.php");
if ( ! isset($CFG->staticroot) ) die('$CFG->staticroot not defined see https://github.com/csev/webauto/issues/2');
if ( ! isset($CFG->bootstrap) ) die('$CFG->bootstrap not defined in config.php');
if ( ! isset($CFG->timezone) ) die('$CFG->timezone not defined in config.php');

// Set this to the temporary folder if not set - dev only 
if ( ! isset($CFG->dataroot) ) {
	$tmp = sys_get_temp_dir();
    if (strlen($tmp) > 1 && substr($tmp, -1) == '/') $tmp = substr($tmp,0,-1);
	$CFG->dataroot = $tmp;
}


error_reporting(E_ALL & ~E_NOTICE);
error_reporting(E_ALL );
ini_set('display_errors', 1);

date_default_timezone_set($CFG->timezone);

// No trailer
