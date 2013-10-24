<?php

require_once "../../config.php";
require_once "header.php";
use Goutte\Client;

line_out("Grading PHP-Intro Assignment 3");

$grade = 0;
$url = getUrl('http://www.php-intro.com/assn/games/rps.php');
error_log("ASSN03 ".$url);

line_out("Initial page ".htmlent_utf8($url)."...");
flush();

// http://symfony.com/doc/current/components/dom_crawler.html
$client = new Client();
$crawler = $client->request('GET', $url);
$html = $crawler->html();
togglePre("Show retrieved page",$html);

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

$success = true;
for ( $i=0; $i<5; $i++) {
    try {
        $form = $crawler->selectButton('Play')->form();
    } catch(Exception $ex) {
		error_out("Did not find form with a 'Play' button");
	    $success = false;
		break;
	}
    $form['human'] = $i % 3; // Set the drop-down
	line_out("Playing ".$names[$i % 3]);
    $crawler = $client->submit($form);
    $html = $crawler->html();
    togglePre('Show retrieved page',$html);
	if ( $displayname !== false ) {
		try {
			$title = $crawler->filter('title')->text();
			line_out("Found title tag...");
		} catch(Exception $ex) {
			error_out("Did not find title tag");
		    $success = false;
			break;
		}
		if ( strpos($title,$displayname) === false ) {
			error_out("Did not find '$displayname' in title tag");
		    $success = false;
			break;
		}
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
	} else {
		error_out('Incorrect play');
		$success = false;
	}
}

if ( ! $success ) {
	error_out('Please fix and re-test.');
    exit();
}

// Send a grade if requested
$grade = 1.0;
if ( $penalty !== false ) $grade = $grade * (1.0 - $penalty);
if ( $grade > 0.0 ) testPassed($grade);

