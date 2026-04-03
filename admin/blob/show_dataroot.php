<?php

/**
 * Print $CFG->dataroot to stdout (CLI only). One line; empty if unset/false.
 *
 * Usage: cd to tsugi/admin/blob, then: php show_dataroot.php
 */

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

require_once("../../config.php");

if ( ! U::isCli() ) {
    die("Must be command line\n");
}

LTIX::getConnection();

if ( isset($CFG->dataroot) && $CFG->dataroot ) {
    echo $CFG->dataroot . "\n";
} else {
    echo "\n";
}
