<?php

use \Tsugi\Core\LTIX;

LTIX::getConnection();

 // Setup the migration scripts
if ( ! $CFG->DEVELOPER ) die("Cannot run this script except in developer mode");

\Tsugi\Core\LTIX::getConnection();

if ( !isset($maxversion) ) $maxversion = 0;
if ( !isset($maxpath) ) $maxpath = '';

if ( !isset($path) ) {
    $path = $CFG->getCurrentFile($CURRENT_FILE);
    $path = substr($path,1); // Trim off the initial slash
}

if ( !isset($p) ) $p = $CFG->dbprefix;
if ( !isset($plugins) ) $plugins = "{$p}lms_plugins";

// If we are running a single file - No fancy output
if ( ! isset($CURRENT_FILE) ) {
    $OUTPUT->header();
    $OUTPUT->bodyStart();
}

