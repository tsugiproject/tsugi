<?php

require_once "../../config.php";
require_once $CFG->dirroot."/lib/header.php";
use Goutte\Client;

echo("<p>&nbsp;</p><h4>This is only for fun - everyone gets 100% on this test</h4>\n");

$grade = 0;
$url = getUrl('http://www.php-intro.com/assn/games/rps.php');

line_out("Retrieving ".htmlent_utf8($url)."...");
flush();
$client = new Client();

$crawler = $client->request('GET', $url);
$html = $crawler->html();
line_out("Retrieved ".strlen($html)." characters.");
togglePre("Show retrieved page",$html);

if ( $displayname ) {
    echo("<p>This is your name as known in the LMS:<br/><strong>\n");
    echo(htmlent_utf8($displayname));
    echo("</strong><br/>\nYou will want to use this name in your web pages to get 
        full credit on actual assignments.</p>\n");
}

// Everyone gets 100%!
$retval = sendGrade(1.0);
if ( $retval === true ) {
    success_out("Grade sent to server.");
} else if ( is_string($retval) ) {
    error_out("Grade not sent: ".$retval);
} else {
    echo("<pre>\n");
    var_dump($retval);
    echo("</pre>\n");
}
flush();
