<?php

require_once "../../config.php";
require_once "header.php";
use Goutte\Client;

line_out("Grading PHP-Intro Assignment 2");

$url = getUrl('http://csevumich.byethost18.com/howdy.php');
$grade = 0;

error_log("ASSN02 ".$url);
line_out("Retrieving ".htmlent_utf8($url)."...");
flush();

// http://symfony.com/doc/current/components/dom_crawler.html
$client = new Client();

$crawler = $client->request('GET', $url);
$html = $crawler->html();
togglePre("Show retrieved page",$html);

line_out("Searching for h1 tag...");

try {
	$h1 = $crawler->filter('h1')->text();
    line_out("Found h1 tag...");
} catch(Exception $ex) {
    error_out("Did not find h1 tag");
	$h1 = "";
}

if ( $displayname && strpos($h1,$displayname) !== false ) {
	success_out("Found ($displayname) in the h1 tag");
} else if ( $displayname ) {
	line_out("Warning: Unable to find $displayname in the h1 tag");
}

$success = "";
$failure = "";
$grade = 0.0;

if ( strpos($h1, "Dr. Chuck") !== false ) {
    $failure = "You need to put your own name in the h1 tag - assignment not complete!";
} else if ( strpos($h1, 'Hello') !== false ) {
    $success = "Found 'Hello' in the h1 tag - assignment correct!";
	$grade = 1.0;
} else {
    $failure = "Did not find 'Hello' in the h1 tag - assignment not complete!";
}

if ( strlen($success) > 0 ) {
    success_out($success);
    error_log($success);
} else if ( strlen($failure) > 0 ) {
    error_out($failure);
    error_log($failure);
    exit();
} else {
    error_log("No status");
    exit();
}

// Send grade
if ( $penalty !== false ) $grade = $grade * (1.0 - $penalty);
if ( $grade > 0.0 ) testPassed($grade);
