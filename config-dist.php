<?php // Configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

// No trailing slash
$CFG->wwwroot = 'http://localhost/~csev/webauto';
$CFG->dirroot = realpath(dirname(__FILE__));
$CFG->servicename = 'PHP-Intro';


$CFG->database  = 'webauto';
$CFG->pdo       = 'mysql:host=127.0.0.1;dbname=webauto';
$CFG->dbuser    = 'ltiuser';
$CFG->dbpass    = 'ltipassword';
$CFG->dbprefix  = 'webauto_';
$CFG->dbaeskey	= 'something-very-secret';
$CFG->sessionsalt = "something-very-secret";

// Set to false if you do not want analytics
$CFG->analytics_key = "UA-423997-16";
$CFG->analytics_name = "dr-chuck.com";

// No trailing tag to avoid white space
