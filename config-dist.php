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
$CFG->servicename = 'PHP-Intro';
$CFG->timezone = 'America/New_York';

$CFG->database  = 'tsugi';
$CFG->pdo       = 'mysql:host=127.0.0.1;dbname=tsugi';
$CFG->dbuser    = 'ltiuser';
$CFG->dbpass    = 'ltipassword';
$CFG->dbprefix  = 'tsugi_';
$CFG->dbasekey  = 'something-very-secret';
$CFG->sessionsalt = "something-very-secret";

$CFG->tool_folders = array("core", "mod", "samples");

// Set to false if you do not want analytics
$CFG->analytics_key = false;  // "UA-423997-16";
$CFG->analytics_name = false; // "dr-chuck.com";

require_once $CFG->dirroot."/setup.php";

// No trailing tag to avoid white space
