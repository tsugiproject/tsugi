<?php

require_once "../../config.php";
require_once "webauto-old.php";
require_once "misc.php";
use Goutte\Client;

line_out("Grading PHP-Intro Assignment 3");

$url = getUrl('http://www.php-intro.com/assn/guess/guess.php');
$grade = 0;

error_log("ASSN03 ".$url);
line_out("Retrieving ".htmlent_utf8($url)."...");
flush();

$client = new Client();

$crawler = $client->request('GET', $url);

// Yes, one gigantic unindented try/catch block
$passed = 5;
$titlepassed = true;
try {

$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);

$retval = webauto_check_title($crawler);
if ( $retval !== true ) {
    error_out($retval);
    $titlepassed = false;
}


line_out("Looking for 'Missing guess parameter'");
if ( stripos($html, 'Missing guess parameter') > 0 ) $passed++;

// Bad guess
$u = $url . "?guess=fred";
$crawler = $client->request('GET', $u);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
line_out("Looking for 'Your guess is not valid'");
if ( stripos($html, 'Your guess is not valid') > 0 ) $passed++;

// Low guess
$u = $url . "?guess=".rand(1,35);
$crawler = $client->request('GET', $u);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
line_out("Looking for 'Your guess is too low'");
if ( stripos($html, 'Your guess is too low') > 0 ) $passed++;

// High guess
$u = $url . "?guess=".rand(45,2000);
$crawler = $client->request('GET', $u);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
line_out("Looking for 'Your guess is too high'");
if ( stripos($html, 'Your guess is too high') > 0 ) $passed++;

// Good guess
$u = $url . "?guess=42";
$crawler = $client->request('GET', $u);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
line_out("Looking for 'Congratulations - You are right'");
if ( stripos($html, 'Congratulations - You are right') > 0 ) $passed++;

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
$score = $passed * (1.0 / $perfect);
if ( $score < 0 ) $score = 0;
if ( $score > 1 ) $score = 1;
$scorestr = "Score = $score ($passed/$perfect)";
if ( $penalty === false ) {
    line_out("Score = $score ($passed/$perfect)");
} else {
    $score = $score * (1.0 - $penalty);
    line_out("Score = $score ($passed/$perfect) penalty=$penalty");
}

if ( ! $titlepassed ) {
    error_out("These pages do not have proper titles so this grade was not sent");
    return;
}

if ( $score > 0.0 ) webauto_test_passed($score, $url);

