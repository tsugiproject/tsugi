<?php

// https://github.com/mozilla/openbadges/wiki/Assertions

use \Tsugi\Core\LTIX;
use \Tsugi\UI\Lessons;

if ( !isset($_GET['id']) ) {
    die_with_error_log('Missing id parameter');
}

require_once "../config.php";
require_once "badge-lib.php";

if ( ! isset($CFG->lessons) ) {
    die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
}

// Load the Lesson
$l = new Lessons($CFG->lessons);

$PDOX = LTIX::getConnection();

//echo("<pre>\n");
$encrypted = $_GET['id'];
$x = parse_badge_id($encrypted, $l);
if ( is_string($x) ) {
    die_with_error_log($x);
}
$row = $x[0];
$pieces = $x[2];
$badge = $x[3];

$date = substr($row['login_at'],0,10);
$recepient = 'sha256$' . hash('sha256', $row['email'] . $CFG->badge_assert_salt);
$title = $row['title'];
$code = $pieces[1];
error_log('Assertion:'.$pieces[0].':'.$pieces[1].':'.$pieces[2]);

header('Content-Type: application/json');
?>
{
  "recipient": "<?= $recepient ?>",
  "salt": "<?= $CFG->badge_assert_salt ?>",
  "issued_on": "<?= $date ?>",
  "badge": {
    "version": "1.0.0",
    "name": "<?= $badge->title ?>",
    "image": "<?= $CFG->apphome.'/badges/'.$code.'.png' ?>",
    "description": "Completed <?= $badge->title.' in course '.$title.' at '.$CFG->servicename ?>",
    "criteria": "<?= $CFG->apphome?>",
    "issuer": {
      "origin": "<?= $CFG->apphome?>",
      "name": "<?= $CFG->servicename?>",
      "org": "<?= $CFG->servicename?>"
    }
  }
}

