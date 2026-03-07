<?php
/**
 * PHPUnit bootstrap - load Composer autoloader before any tests.
 * This prevents "Cannot redeclare class" when tests mix manual requires
 * with autoloader (e.g. setup.php loads autoloader, then another test
 * manually requires a class already loaded).
 */
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}
