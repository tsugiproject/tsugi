<?php

 // Setup the migration scripts
if ( ! $CFG->DEVELOPER ) die("Cannot run this script except in developer mode");

require_once $CFG->dirroot."/lib/lms_lib.php";
require_once $CFG->dirroot."/pdo.php";

if ( !isset($maxversion) ) $maxversion = 0;
if ( !isset($maxpath) ) $maxpath = '';

if ( !isset($path) ) {
    $path = getCurrentFile($CURRENT_FILE);
    $path = substr($path,1); // Trim off the initial slash
}

if ( !isset($p) ) $p = $CFG->dbprefix;
if ( !isset($plugins) ) $plugins = "{$p}lms_plugins";

$OUTPUT->header();
$OUTPUT->bodyStart();

