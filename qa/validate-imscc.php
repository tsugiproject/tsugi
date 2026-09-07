#!/usr/bin/env php
<?php
/**
 * Validate a Quiz1 / Lessons Common Cartridge 1.2 package.
 *
 * Usage (from repo root):
 *   php qa/validate-imscc.php path/to/course.imscc
 */

$root = dirname(__DIR__);
$autoload = $root.'/lib/vendor/autoload.php';
if ( ! is_readable($autoload) ) {
    $autoload = $root.'/vendor/autoload.php';
}
if ( ! is_readable($autoload) ) {
    fwrite(STDERR, "Cannot find Composer autoload. Run composer install in lib/.\n");
    exit(2);
}
require_once $autoload;

$path = $argv[1] ?? '';
if ( $path === '' || ! is_readable($path) ) {
    fwrite(STDERR, "Usage: php qa/validate-imscc.php file.imscc\n");
    exit(2);
}

$result = Tsugi\Services\Quiz1\CartridgeValidator::validate($path);
echo Tsugi\Services\Quiz1\CartridgeValidator::format($result);
exit($result['ok'] ? 0 : 1);
