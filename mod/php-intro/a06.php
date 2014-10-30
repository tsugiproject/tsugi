<?php

require_once "../../config.php";
require_once "webauto.php";
use Goutte\Client;

line_out("Grading PHP-Intro Assignment 6");

$url = getUrl('http://www.php-intro.com/assn/tracks');
if ( $url === false ) return;
$grade = 0;

error_log("ASSN05 ".$url);
line_out("Retrieving ".htmlent_utf8($url)."...");
flush();

$client = new Client();
$client->setMaxRedirects(5);

// Yes, one gigantic unindented try/catch block
$passed = 0;
$titlefound = true;
try {

$crawler = $client->request('GET', $url);

$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);

$retval = webauto_check_title($crawler);
if ( $retval !== true ) {
    error_out($retval);
    $titlefound = false;
}

line_out("Looking for Add New link in index.php.");
$link = $crawler->selectLink('Add New')->link();
$url = $link->getURI();
line_out("Retrieving ".htmlent_utf8($url)."...");

$crawler = $client->request('GET', $url);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$passed++;

// Add new fail
line_out("Looking for the form with a 'Add New' submit button");
$form = $crawler->selectButton('Add New')->form();
line_out("Setting non-integer values in the plays and rating form fields and leaving title blank");
$form->setValues(array("plays" => "many", "rating" => "awesome"));
$crawler = $client->submit($form);
$passed++;

$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
checkPostRedirect($client);

line_out("Expecting 'Bad value for title, plays, or rating' error in index.php");
if ( strpos(strtolower($html), 'bad value') !== false ) {
    $passed++;
} else {
    error_out("Could not find flash message with 'Bad value for title, plays, or rating'");
}

line_out("Looking for Add New link in index.php.");
$link = $crawler->selectLink('Add New')->link();
$url = $link->getURI();
line_out("Retrieving ".htmlent_utf8($url)."...");

$crawler = $client->request('GET', $url);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$passed++;
line_out("Looking for the form with a 'Add New' submit button");
$form = $crawler->selectButton('Add New')->form();
$title = 'ACDC'.sprintf("%03d",rand(1,100));
$plays = rand(1,100);
$rating = rand(1,100);
line_out("Entering title=$title, plays=$plays, rating=$rating");
$form->setValues(array("title" => $title, "plays" => $plays, "rating" => $rating));
$crawler = $client->submit($form);
$passed++;

$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
checkPostRedirect($client);

line_out("Looking '$title' entry");
$pos = strpos($html, $title);
$pos2 = strpos($html, "edit.php", $pos);
$body = substr($html,$pos,$pos2-$pos);
# echo "body=",htmlentities($body);
line_out("Looking for plays=$plays and rating=$rating");
if ( strpos($body,''.$plays) < 1 || strpos($body,''.$rating) < 1 ) {
    error_out("Could not find plays=$plays and rating=$rating");
} else {
    $passed++;
}

line_out("Looking for edit.php link associated with '$title' entry");
$pos3 = strpos($html, '"', $pos2);
$editlink = substr($html,$pos2,$pos3-$pos2);
line_out("Retrieving ".htmlent_utf8($editlink)."...");

$crawler = $client->request('GET', $editlink);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$passed++;

line_out("Looking for the form with a 'Update' submit button");
$form = $crawler->selectButton('Update')->form();
$plays = rand(1,100);
$rating = rand(1,100);
line_out("Editing title=$title, plays=$plays, rating=$rating");
$form->setValues(array("title" => $title, "plays" => $plays, "rating" => $rating));
$crawler = $client->submit($form);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$passed++;
checkPostRedirect($client);

// Delete...
line_out("Looking '$title' entry");
$pos = strpos($html, $title);
$pos2 = strpos($html, "delete.php", $pos);
$body = substr($html,$pos,$pos2-$pos);
# echo "body=",htmlentities($body);
line_out("Looking for plays=$plays and rating=$rating");
if ( strpos($body,''.$plays) < 1 || strpos($body,''.$rating) < 1 ) {
    error_out("Could not find plays=$plays and rating=$rating");
} else {
    $passed++;
}

line_out("Looking for delete.php link associated with '$title' entry");
$pos3 = strpos($html, '"', $pos2);
$editlink = substr($html,$pos2,$pos3-$pos2);
line_out("Retrieving ".htmlent_utf8($editlink)."...");

$crawler = $client->request('GET', $editlink);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$passed++;

// Do the Delete
line_out("Looking for the form with a 'Delete' submit button");
$form = $crawler->selectButton('Delete')->form();
$crawler = $client->submit($form);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$passed++;
checkPostRedirect($client);

line_out("Making sure '$title' has been deleted");
if ( strpos($html,$title) > 0 ) {
    error_out("Entry '$title' not deleted");
} else {
    $passed++;
}

line_out("Cleaning up old records...");
while (True ) {
    $pos2 = strpos($html, "delete.php");
    if ( $pos2 < 1 ) break;
    $pos3 = strpos($html, '"', $pos2);
    if ( $pos3 < 1 ) break;
    $editlink = substr($html,$pos2,$pos3-$pos2);
    line_out("Retrieving ".htmlent_utf8($editlink)."...");

    $crawler = $client->request('GET', $editlink);
    $html = $crawler->html();
    $OUTPUT->togglePre("Show retrieved page",$html);

    // Do the Delete
    line_out("Looking for the form with a 'Delete' submit button");
    $form = $crawler->selectButton('Delete')->form();
    $crawler = $client->submit($form);
    $html = $crawler->html();
    $OUTPUT->togglePre("Show retrieved page",$html);
    checkPostRedirect($client);
    $passed--; // Since checkPostRedirect() gives a pass
}

line_out("Testing for HTML injection (proper use of htmlentities)...");
line_out("Looking for Add New link in index.php.");
$link = $crawler->selectLink('Add New')->link();
$url = $link->getURI();
line_out("Retrieving ".htmlent_utf8($url)."...");

$crawler = $client->request('GET', $url);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$passed++;

line_out("Looking for the form with a 'Add New' submit button");
$form = $crawler->selectButton('Add New')->form();
$title = 'AC<DC'.sprintf("%03d",rand(1,100));
$plays = rand(1,100);
$rating = rand(1,100);
line_out("Entering title=$title, plays=$plays, rating=$rating");
$form->setValues(array("title" => $title, "plays" => $plays, "rating" => $rating));
$crawler = $client->submit($form);
$passed++;

$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
checkPostRedirect($client);

if ( strpos($html, "AC&lt;DC") > 2 ) {
    $passed+=2;
} else if ( strpos($html, "&amp;lt;") > 2  ) {
    error_out("It looks like you have double-called htmlentities()");
} else {
    error_out("Found HTML Injection");
}

$pos = strpos($html, $title);
$pos2 = strpos($html, "delete.php", $pos);
line_out("Looking for delete.php link associated with '$title' entry");
$pos3 = strpos($html, '"', $pos2);
$editlink = substr($html,$pos2,$pos3-$pos2);
line_out("Retrieving ".htmlent_utf8($editlink)."...");

$crawler = $client->request('GET', $editlink);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$passed++;

if ( strpos($html, "AC&lt;DC") > 2 ) {
    $passed+=2;
} else if ( strpos($html, "&amp;lt;") > 2  ) {
    error_out("It looks like you have double-called htmlentities()");
} else {
    error_out("Found HTML Injection");
}

line_out("Looking for the form with a 'Delete' submit button");
$form = $crawler->selectButton('Delete')->form();
$crawler = $client->submit($form);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);
$passed++;
checkPostRedirect($client);


} catch (Exception $ex) {
    error_out("The autograder did not find something it was looking for in your HTML - test ended.");
    error_log($ex->getMessage());
    error_log($ex->getTraceAsString());
    $detail = "This indicates the source code line where the test stopped.\n" .
        "It may not make any sense without looking at the source code for the test.\n".
        'Caught exception: '.$ex->getMessage()."\n".$ex->getTraceAsString()."\n";
    $OUTPUT->togglePre("Internal error detail.",$detail);
}

// There is a maximum of 26 passes for this test
$perfect = 26;
$score = webauto_compute_effective_score($perfect, $passed, $penalty);

if ( ! $titlefound ) {
    error_out("These pages do not have proper titles so this grade is not official");
    return;
}

if ( $score > 0.0 ) webauto_test_passed($score, $url);
