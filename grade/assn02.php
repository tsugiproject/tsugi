<?php

require_once "header.php";
use Goutte\Client;

$url = getUrl();
$grade = 0;

line_out("Retrieving ".htmlent_utf8($url)."...");
flush();
$client = new Client();

do_analytics();

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

if ( $displayname && strpos($h1,$displayname) !== false ) {
	success_out("Found ($displayname) in the h1 tag");
} else if ( $displayname ) {
	line_out("Warning: Unable to find $displayname in the h1 tag");
}

if ( strpos($h1, "Dr. Chuck") !== false ) {
    error_out("You need to put your own name in the h1 tag - assignment not complete!");
} else if ( strpos($h1, 'Hello') !== false ) {
    success_out("Found 'Hello' in the h1 tag - assignment correct!");
    if ( isset($_GET['grade']) ) {
		$retval = sendGrade(1.0);
		if ( $retval == true ) {
			success_out("Grade sent to server.");
		} else if ( is_string($retval) ) {
			error_out("Grade not sent: ".$retval);
        } else {
            echo("<pre>\n");
            var_dump($retval);
            echo("</pre>\n");
        }
	} else {
		line_out("Test run only - grade not sent to server");
	}
} else {
    error_out("Did not find 'Hello' in the h1 tag - assignment not complete!");
}

