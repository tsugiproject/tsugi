<?php

require_once "../../config.php";
require_once "webauto.php";
use Goutte\Client;

line_out("Grading PHP-Intro Assignment 4");

$grade = 0;
$url = getUrl('http://www.php-intro.com/assn/games/rps.php');
if ( $url === false ) return;

error_log("ASSN04 ".$url);

line_out("Initial page ".htmlent_utf8($url)."...");
flush();

// http://symfony.com/doc/current/components/dom_crawler.html
$client = new Client();
$crawler = $client->request('GET', $url);
$html = $crawler->html();
$OUTPUT->togglePre("Show retrieved page",$html);

$names = array('Rock', 'Paper', 'Scissors');

function check($matches) {
    $map = Array('Rock' => 0, 'Paper' => 1, 'Scissors' => 2) ;
    $me = $map[$matches[1]];
    $co = $map[$matches[2]];
    $result = $matches[3];
    $re=2;
    if ( strpos($result,"Win") !== false ) $re=0;
    if ( strpos($result,"Lose") !== false ) $re=1;

    if ( $me == $co && $re == 2 ) return true;
    if ( ( ($co + 1 ) % 3) == $me && $re == 0 ) return true;
    if ( ( ($me + 1 ) % 3) == $co && $re == 1 ) return true;
    return false;
}

$passed = 0;
$title_once = false; // Only send the error once
$titlefound = false;
for ( $i=0; $i<5; $i++) {
    try {
        $form = $crawler->selectButton('Play')->form();
    } catch(Exception $ex) {
        error_out("Did not find form with a 'Play' button");
        break;
    }
    $form['human'] = $i % 3; // Set the drop-down
    line_out("Playing ".$names[$i % 3]);
    $crawler = $client->submit($form);
    $html = $crawler->html();
    $OUTPUT->togglePre('Show retrieved page',$html);

    $retval = webauto_check_title($crawler);
    if ( $retval === true ) {
        $titlefound = true;
    } else {
        if ( ! $title_once ) error_out($retval);
        $title_once = true;
    }

    $matches = Array();
    preg_match('/Your Play=([^ ]*) Computer Play=([^ ]*) Result=(.*)/',$html,$matches);
    if ( count($matches) != 4 ) {
        error_out('Could not find properly formatted line starting with "Your Play="');
        continue;
    }
    line_out('Found:'.$matches[0]);
    if ( check($matches) ) {
        success_out('Correct play');
        $passed++;
    } else {
        error_out('Incorrect play');
    }
}

$perfect = 5;
$score = webauto_compute_effective_score($perfect, $passed, $penalty);

if ( ! $titlefound ) {
    error_out("These pages do not have proper titles so this grade was not sent");
    return;
}

// Send a grade if requested
$grade = 1.0;
if ( $penalty !== false ) $grade = $grade * (1.0 - $penalty);
if ( $grade > 0.0 ) webauto_test_passed($grade, $url);

