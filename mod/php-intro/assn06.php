<?php

require_once "../../config.php";
require_once "header.php";
require_once "misc.php";
use Goutte\Client;

line_out("Grading PHP-Intro Assignment 6");

$url = getUrl('http://www.php-intro.com/assn/tracks');
$grade = 0;

error_log("ASSN06 ".$url);
line_out("-- The intial URL shuold be the url of lms.php");
line_out("Retrieving ".htmlent_utf8($url)."...");
flush();

$client = new Client();

$crawler = $client->request('GET', $url);

// Yes, one gigantic unindented try/catch block
$passed = 0;
$titlepassed = true;
try {

$html = $crawler->html();
togglePre("Show retrieved page",$html);

line_out("Looking for the form with a 'Debug Launch' submit button");
$form = $crawler->selectButton('Debug Launch')->form();
$crawler = $client->submit($form);
$passed++;

$html = $crawler->html();
togglePre("Show retrieved page",$html);

// Thankfully our primitive web browser ignores target=
line_out("Looking for the form with a 'Finish Launch' submit button");
$form = $crawler->selectButton('Finish Launch')->form();
$crawler = $client->submit($form);
$passed++;

$html = $crawler->html();
togglePre("Show retrieved page",$html);

line_out("Looking for the form with a 'Send grade' submit button (note case)");
$form = $crawler->selectButton('Send grade')->form();
line_out('Looking for a field named "grade" in the form');
$form->setValues(array("grade" => "0.4"));
$crawler = $client->submit($form);
$passed++;

$html = $crawler->html();
togglePre("Show retrieved page",$html);


die("This is only a partial autograder - it needs more work");

} catch (Exception $ex) {
    error_out("The autograder did not find something it was looking for in your HTML - test ended.");
    error_log($ex->getMessage());
    error_log($ex->getTraceAsString());
    $detail = "This indicates the source code line where the test stopped.\n" .
        "It may not make any sense without looking at the source code for the test.\n".
        'Caught exception: '.$ex->getMessage()."\n".$ex->getTraceAsString()."\n";
    togglePre("Internal error detail.",$detail);
}

$perfect = 26;
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
    error_out("These pages do not have proper titles so this grade is not official");
    return;
}

if ( $score > 0.0 ) testPassed($score);

