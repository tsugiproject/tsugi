<?php

use \Tsugi\UI\Lessons;

// In the top frame, we use cookies for session.
define('COOKIE_SESSION', true);
require_once("config.php");

$l = new Lessons('../lessons.json');

header('Content-Type: text/html; charset=utf-8');
session_start();

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();

if ( isset( $l->lessons->other_courses) ) {
    echo("<p>You can take this course / earn badges for this material from the following:</p>\n");
    echo("<ul>\n");
    foreach($l->lessons->other_courses as $badge) {
        echo('<li><a href="'.$badge->href.'" target="_blank">'.$badge->title);
        echo('</a></li>'."\n");
    }
    echo("</ul>\n");
}
if ( isset( $l->lessons->badges) ) {
    echo("<p>The following Open Badges will be awarded for this course when the badge feature is completed on this system:</p>\n");
    echo("<ul>\n");
    foreach($l->lessons->badges as $badge) {
        echo('<li>'.$badge->title.'</li>'."\n");
    }
    echo("</ul>\n");
echo("<p>This feature to award Open Badges is in progress. You can work on the course and when the badge feature is complete all of your progress will be retroactively credited towards your badge(s).</p>\n");
}
echo('<p><a href="https://www.imsglobal.org/initiative/enabling-better-digital-credentialing" target="blank">IMS Digital Credentials initiative</a></p>'."\n");

$OUTPUT->footer();
