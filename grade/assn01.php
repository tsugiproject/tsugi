<?php

require_once "../setup.php";

session_start();

// require_once 'goutte.phar';
// use Goutte\Client;

require_once "includes/vendor/autoload.php";
require_once "includes/Goutte/Client.php";
use Goutte\Client;

require_once 'gradelib.php';
doTop();

$displayname = false;
if ( isset($_SESSION['lti']) ) {
	$lti = $_SESSION['lti'];
	$displayname = $lti['user_displayname'];
}

if ( !isset($_GET['url']) ) {
echo('
<form>
Please enter the URL of your web site to grade:<br/>
<input type="text" name="url" value="http://drchuck.byethost18.com/" size="100"><br/>
<input type="checkbox" name="grade"> Submit for grading<br/>
<input type="submit" value="Grade">
</form>
');
echo("<p>Hello $displayname.  By entering a URL in this field and submitting it for 
grading, you are representing that this is your own work.  Do not submit someone else's
web site for grading.
");
return;
}


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
		}
	} else {
		line_out("Test run only - grade not sent to server");
	}
} else {
    error_out("Did not find Hello World in the h1 tag - assignment not complete!");
}



