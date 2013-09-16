<?php

require_once 'goutte.phar';
use Goutte\Client;
require_once 'gradelib.php';
doTop();

$grade = 0;
$url = 'http://drchuck.byethost18.com/';

line_out("Retrieving ".htmlent_utf8($url)."...");
flush();
$client = new Client();

$crawler = $client->request('GET', $url);
$html = $crawler->html();
line_out("Retrieved ".strlen($html)." characters.");
if ( !isCli() ) togglePre("Retrieved page",$html);

line_out("Searching for h1 tag...");

$h1 = $crawler->filter('h1')->text();
if ( strlen($h1) > 0 ) {
    line_out("Found h1 tag...");
} 

if ( strpos($h1, "Hello World") !== false ) {
    success_out("Found Hello World in the h1 tag - assignment complete!");
	$grade = 1.0;
} else {
    error_out("Did not find Hello World in the h1 tag - assignment not complete!");
	$grade = 0.0;
}



