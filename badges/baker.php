<?php

use \Tsugi\Util\U;
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
    if ( strpos($x, '<h2>Badge Configuration Required</h2>') !== false ) {
        // Configuration error - display as HTML
        header('Content-Type: text/html; charset=utf-8');
        echo($x);
        exit();
    }
    die_with_error_log($x);
}
$row = $x[0];
$png = $x[1];
$pieces = $x[2];
$badge = $x[3];

$date = U::iso8601($row['login_at']);
$email = $row['email'];
$title = $row['title'];
$code = $pieces[1];
error_log('Assertion:'.$pieces[0].':'.$pieces[1].':'.$pieces[2]);
$image = $CFG->badge_url.'/'.$code.'.png';

$text = get_assertion($encrypted, $date, $code, $badge, $title, $email);
if ( is_string($text) && strpos($text, '<h2>Badge Configuration Required</h2>') !== false ) {
    // Configuration error - display as HTML
    header('Content-Type: text/html; charset=utf-8');
    echo($text);
    exit();
}

// https://www.imsglobal.org/sites/default/files/Badges/OBv2p0Final/baking/index.html
$png2 = Png::addOrReplaceTextInPng($png,"openbadges",$text, 'iTXt');

header('Content-Type: image/png');
header('Content-Length: ' . strlen($png2));

echo($png2);
