<?php

// https://github.com/mozilla/openbadges/wiki/Assertions

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\Lessons;

require_once "../config.php";
require_once "badge-util.php";

if ( ! isset($CFG->lessons) ) {
    die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
}

// Load the Lesson
$l = new Lessons($CFG->lessons);

$PDOX = LTIX::getConnection();

// Issuer is now installation-wide, so id parameter is optional (for backward compatibility)
if (isset($_GET['id'])) {
    // Legacy encrypted ID approach (for backward compatibility)
    $encrypted = $_GET['id'];
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
    $pieces = $x[2];
    $badge = $x[3];
    $title = $row['title'];
    $code = $pieces[1];
    error_log('Assertion:'.$pieces[0].':'.$pieces[1].':'.$pieces[2]);
    $text = get_issuer($encrypted, $code, $badge, $title);
    if ( is_string($text) && strpos($text, '<h2>Badge Configuration Required</h2>') !== false ) {
        // Configuration error - display as HTML
        header('Content-Type: text/html; charset=utf-8');
        echo($text);
        exit();
    }
} else {
    // New installation-wide issuer (no id needed)
    $title = isset($CFG->servicename) ? $CFG->servicename : 'Course';
    $text = get_issuer(null, null, null, $title);
    if ( is_string($text) && strpos($text, '<h2>Badge Configuration Required</h2>') !== false ) {
        // Configuration error - display as HTML
        header('Content-Type: text/html; charset=utf-8');
        echo($text);
        exit();
    }
}
header('Content-Type: application/json');
echo($text);

