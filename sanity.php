<?php

if ( ! isset($CFG) ) { 
    die('This software is not correctly configured, please copy config-dist.php to 
	config.php and edit config.php according to the installation instructions.');
}

if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);

    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

if ( PHP_VERSION_ID < 50300 ) {
    die("This software requires PHP 5.3.0 or later");
}

if ( strpos(__FILE__,' ') !== false ) {
    die("This software requires that folder and file names have no spaces ".__FILE__);
}

