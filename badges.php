<?php
// In the top frame, we use cookies for session.
define('COOKIE_SESSION', true);
require_once("config.php");

header('Content-Type: text/html; charset=utf-8');
session_start();

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();

echo("<p>This feature to award Open Badges is in progress. You can work on the course and when the badge feature is complete all of your progress will be credited towards your badge(s).</p>\n");
echo('<p><a href="https://www.imsglobal.org/initiative/enabling-better-digital-credentialing" target="blank">IMS Digital Credentials initiative</a></p>'."\n");

$OUTPUT->footer();
