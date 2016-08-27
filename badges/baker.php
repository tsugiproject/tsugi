<?php

use \Tsugi\Core\LTIX;
use \Tsugi\UI\Lessons;
use \Tsugi\Image\Png;

require_once "../config.php";
require_once "badge-util.php";

if ( ! isset($CFG->lessons) ) {
    die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
}

$PDOX = LTIX::getConnection();
// Load the Lesson
$l = new Lessons($CFG->lessons);

$url = $_SERVER['REQUEST_URI'];
$pieces = explode('/',$url);
$encrypted = basename($pieces[count($pieces)-1],'.png');

$x = parse_badge_id($encrypted, $l);
if ( is_string($x) ) {
    die_with_error_log($x);
}
$row = $x[0];
$png = $x[1];

$png2 = Png::addOrReplaceTextInPng($png,"openbadges",$CFG->wwwroot."/badges/assert.php?id=".$encrypted);

header('Content-Type: image/png');
header('Content-Length: ' . strlen($png2));

echo($png2);
