#!/usr/bin/env php
<?php
/**
 * Write isolated Quiz1 Common Cartridge 1.2 fixtures for LMS import tests.
 *
 * Usage (from repo root):
 *   php qa/export-quiz1-fixtures.php [output-dir]
 *
 * Default output: lib/tests/fixtures/Quiz1/generated/
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
require_once $root.'/lib/src/Core/I18N.php';
require_once $root.'/lib/include/setup_i18n.php';

global $CFG;
$CFG = new Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost');
$CFG->apphome = 'http://localhost/app';
$CFG->wwwroot = 'http://localhost';
$CFG->fontawesome = 'http://localhost/fontawesome';

$out = $argv[1] ?? ($root.'/lib/tests/fixtures/Quiz1/generated');
$written = Tsugi\Services\Quiz1\CartridgeFixtures::writeAll($out);
$written['09-mixed-module'] = Tsugi\Services\Quiz1\CartridgeFixtures::writeMixed(
    $out,
    Tsugi\Services\Quiz1\SampleQuiz::buildMinimal(1)
);

$failed = 0;
foreach ( $written as $stem => $path ) {
    $result = Tsugi\Services\Quiz1\CartridgeValidator::validate($path);
    $status = $result['ok'] ? 'PASS' : 'FAIL';
    echo $status.' '.$path."\n";
    if ( ! $result['ok'] ) {
        echo Tsugi\Services\Quiz1\CartridgeValidator::format($result);
        $failed++;
    }
}

echo "Wrote ".count($written)." cartridges to ".$out."\n";
exit($failed === 0 ? 0 : 1);
