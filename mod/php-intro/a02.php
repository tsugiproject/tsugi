<?php

require_once "webauto.php";

use Goutte\Client;

line_out("Grading PHP-Intro Assignment 2");

$url = getUrl('http://csevumich.byethost18.com/howdy.php');
if ( $url === false ) return;
$grade = 0;

error_log("ASSN02 ".$url);
line_out("Retrieving ".htmlent_utf8($url)."...");
flush();

// http://symfony.com/doc/current/components/dom_crawler.html
$client = new Client();
$client->setMaxRedirects(5);

$crawler = $client->request('GET', $url);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);

line_out("Searching for h1 tag...");

$passed = 0;
$titlefound = false;
try {
    $h1 = $crawler->filter('h1')->text();
    line_out("Found h1 tag...");
} catch(Exception $ex) {
    error_out("Did not find h1 tag");
    $h1 = "";
}

if ( stripos($h1, 'Hello') !== false ) {
    success_out("Found 'Hello' in the h1 tag");
    $passed += 1;
} else {
    error_out("Did not find 'Hello' in the h1 tag");
}

if ( $USER->displayname && stripos($h1,$USER->displayname) !== false ) {
    success_out("Found ($USER->displayname) in the h1 tag");
    $passed += 1;
} else if ( $USER->displayname ) {
    error_out("Did not find $USER->displayname in the h1 tag");
    error_out("No score sent");
    return;
}

$perfect = 2;
$score = webauto_compute_effective_score($perfect, $passed, $penalty);

// Send grade
if ( $score > 0.0 ) webauto_test_passed($score, $url);

