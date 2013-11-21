<?php

if ( ! defined('COOKIE_SESSION') ) {
    ini_set('session.use_cookies', '0');
    ini_set('session.use_only_cookies',0);
    ini_set('session.use_trans_sid',1); 
}

if ( ! isset($CFG) ) die("Please configure this product using config.php");
if ( ! isset($CFG->staticroot) ) die('$CFG->staticroot not defined see https://github.com/csev/webauto/issues/2');
if ( ! isset($CFG->bootstrap) ) die('$CFG->bootstrap not defined in config.php');
if ( ! isset($CFG->timezone) ) die('$CFG->timezone not defined in config.php');

error_reporting(E_ALL & ~E_NOTICE);
error_reporting(E_ALL );
ini_set('display_errors', 1);

date_default_timezone_set($CFG->timezone);

// No trailer
