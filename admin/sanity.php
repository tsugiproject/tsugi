<?php
if ( ! isset($CFG) ) {
    require_once("config.php");
}

if ( ! isset($CFG) ) {
    die_with_error_log('This software is not correctly configured, please copy tsugi/config-dist.php to
    tsugi/config.php and edit config.php according to the installation instructions.');
}

$min_php = defined('TSUGI_MINIMUM_PHP') ? TSUGI_MINIMUM_PHP : '8.4';
if ( version_compare(PHP_VERSION, $min_php, '<') ) {
    die_with_error_log("This software requires PHP {$min_php} or later (found ".PHP_VERSION.")");
}

if ( strpos(__FILE__,' ') !== false ) {
    die_with_error_log("This software requires that folder and file names have no spaces ".__FILE__);
}
