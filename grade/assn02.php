<?php

require_once "../setup.php";

session_start();

require_once "../lib/goutte/vendor/autoload.php";
require_once "../lib/goutte/Goutte/Client.php";
use Goutte\Client;

require_once '../lib/gradelib.php';
doTop();

$displayname = false;
if ( isset($_SESSION['lti']) ) {
	$lti = $_SESSION['lti'];
	$displayname = $lti['user_displayname'];
}

if ( !isset($_GET['url']) ) {
if ( $displayname ) {
    echo("<p>&nbsp;</p><p><b>Hello $displayname</b> - welcome to the autograder.</p>\n");
}
echo('
<form>
Please enter the URL of your web site to grade:<br/>
<input type="text" name="url" value="http://drchuck.byethost18.com/" size="100"><br/>
<input type="checkbox" name="grade" checked="yes">Send Grade (uncheck for a dry run)<br/>
<input type="submit" value="Grade">
</form>
');
if ( $displayname ) {
echo("By entering a URL in this field and submitting it for 
grading, you are representing that this is your own work.  Do not submit someone else's
web site for grading.
");
}
echo("<p>You can run this autograder as many times as you like and the last submitted
grade will be recorded.  Make sure to double-check the course Gradebook to verify
that your grade has been sent.</p>\n");
return;
}


$grade = 0;
$url = $_GET['url'];

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
} else if ( strpos($h1, "Hello World") !== false ) {
    success_out("Found Hello World in the h1 tag - assignment correct!");
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
    error_out("Did not find Hello World in the h1 tag - assignment not complete!");
}

