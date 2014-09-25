<?php

require_once "../../config.php";
require_once "webauto.php";
use Goutte\Client;

line_out("Grading PHP-Intro Assignment 3");

$url = getUrl('http://www.php-intro.com/assn/guess/guess.php');
if ( $url === false ) return;
$grade = 0;

error_log("ASSN03 ".$url);
line_out("Retrieving ".htmlent_utf8($url)."...");
flush();

$client = new Client();

$crawler = $client->request('GET', $url);

// Yes, one gigantic unindented try/catch block
$passed = 5;
$titlefound = false;
try {

$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);

$retval = webauto_check_title($crawler);
if ( $retval === true ) {
    $titlefound = true;
} else {
    error_out($retval);
}

line_out("Looking for 'Missing guess parameter'");
if ( stripos($html, 'Missing guess parameter') > 0 ) $passed++;
else error_out("Not found");

// Bad guess
$u = $url . "?guess=fred";
line_out("Retrieving ".htmlent_utf8($u));
$crawler = $client->request('GET', $u);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
line_out("Looking for 'Your guess is not valid'");
if ( stripos($html, 'Your guess is not valid') > 0 ) $passed++;
else error_out("Not found");

// Low guess
$u = $url . "?guess=".rand(1,35);
line_out("Retrieving ".htmlent_utf8($u));
$crawler = $client->request('GET', $u);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
line_out("Looking for 'Your guess is too low'");
if ( stripos($html, 'Your guess is too low') > 0 ) $passed++;
else error_out("Not found");

// High guess
$u = $url . "?guess=".rand(45,2000);
line_out("Retrieving ".htmlent_utf8($u));
$crawler = $client->request('GET', $u);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
line_out("Looking for 'Your guess is too high'");
if ( stripos($html, 'Your guess is too high') > 0 ) $passed++;
else error_out("Not found");

// Good guess
$u = $url . "?guess=42";
line_out("Retrieving ".htmlent_utf8($u));
$crawler = $client->request('GET', $u);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
line_out("Looking for 'Congratulations - You are right'");
if ( stripos($html, 'congratulations') > 0 ) $passed++;
else error_out("Not found");

} catch (Exception $ex) {
    error_out("The autograder did not find something it was looking for in your HTML - test ended.");
    error_log($ex->getMessage());
    error_log($ex->getTraceAsString());
    $detail = "This indicates the source code line where the test stopped.\n" .
        "It may not make any sense without looking at the source code for the test.\n".
        'Caught exception: '.$ex->getMessage()."\n".$ex->getTraceAsString()."\n";
    $OUTPUT->togglePre("Internal error detail.",$detail);
}

$perfect = 10;
$score = webauto_compute_effective_score($perfect, $passed, $penalty);

if ( ! $titlefound ) {
    error_out("These pages do not have proper titles so this grade was not sent");
    return;
}

if ( $score > 0.0 ) webauto_test_passed($score, $url);

