<?php // Configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

// No trailing slash
$CFG->wwwroot = 'http://localhost/~csev/webauto';

$CFG->dirroot = realpath(dirname(__FILE__));

$CFG->database  = 'webauto';
$CFG->pdo       = 'mysql:host=127.0.0.1;dbname=webauto';
$CFG->dbuser    = 'ltiuser';
$CFG->dbpass    = 'ltipassword';
$CFG->dbprefix  = 'webauto_';
$CFG->dbaeskey	= 'ltiaes';
$CFG->sessionsalt = "lkjdslkjdslj";

// No trailing tag to avoid white space
