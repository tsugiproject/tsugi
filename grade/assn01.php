<?php

require_once "../setup.php";

session_start();

require_once 'goutte.phar';
use Goutte\Client;
require_once 'gradelib.php';
doTop();

$grade = 0;
$url = $_GET['url'];

line_out("Retrieving ".htmlent_utf8($url)."...");
flush();
$client = new Client();

$crawler = $client->request('GET', $url);
$html = $crawler->html();
line_out("Retrieved ".strlen($html)." characters.");
togglePre("Show retrieved page",$html);

line_out("Searching for h1 tag...");

try {
	$h1 = $crawler->filter('h1')->text();
    line_out("Found h1 tag...");
} catch(Exception $ex) {
    error_out("Did not find h1 tag");
	$h1 = "";
}

if ( strpos($h1, "Hello World") !== false ) {
    success_out("Found Hello World in the h1 tag - assignment complete!");
	sendGrade(1.0);
} else {
    error_out("Did not find Hello World in the h1 tag - assignment not complete!");
}



