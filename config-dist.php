<?php // Configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

// No trailing slash
$CFG->wwwroot = 'http://localhost/~csev/tsugi';
$CFG->staticroot = $CFG->wwwroot;
// Bootstrap recommended CDN
// $CFG->bootstrap = "//netdna.bootstrapcdn.com/bootstrap/3.0.2";
$CFG->bootstrap = $CFG->staticroot . "/static/bootstrap";
$CFG->dirroot = realpath(dirname(__FILE__));
// If you don't set dataroot it will be in temp space (dev test only)
// $CFG->dataroot = $CFG->dirroot . '/_files/a';
$CFG->servicename = 'TSUGI (dev)';
$CFG->timezone = 'America/New_York';

$CFG->DEVELOPER = false;  // Change to true to enable testing screens

$CFG->database  = 'tsugi';
$CFG->pdo       = 'mysql:host=127.0.0.1;dbname=tsugi';
$CFG->dbuser    = 'ltiuser';
$CFG->dbpass    = 'ltipassword';
$CFG->dbprefix  = 'tsugi_';
$CFG->dbasekey  = 'something-very-secret';
$CFG->sessionsalt = "something-very-secret";

$CFG->adminpw = 'something-super-secret!';  // Change this!

$CFG->OFFLINE = false;  // Set to true if you have no network connection at all

// This supports the auto-login via long-term cookie 
$CFG->cookiesecret = '2f518066blahblahlongstring5fd09d757a289b543';
$CFG->cookiename = 'autochuckonline';
$CFG->cookiepad = '390b246ea9';

$CFG->tool_folders = array("core", "mod", "samples");

// Set to false if you do not want analytics
$CFG->analytics_key = false;  // "UA-423997-16";
$CFG->analytics_name = false; // "dr-chuck.com";

require_once $CFG->dirroot."/setup.php";

// No trailing tag to avoid white space
