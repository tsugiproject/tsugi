<?php
/**
 * Bootstrap file for PHPUnit tests
 * Sets up the test environment
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set up paths
$basePath = dirname(__DIR__);
set_include_path(get_include_path() . PATH_SEPARATOR . $basePath);

// Load required files
require_once $basePath . '/parse.php';
require_once $basePath . '/configure_parse.php';

// Mock session if needed
if (!isset($_SESSION)) {
    $_SESSION = array();
}

