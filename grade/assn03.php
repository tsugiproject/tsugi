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
<input type="text" name="url" value="http://csevumich.byethost18.com/howdy.php" size="100"><br/>
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

do_analytics();

$grade = 0;
$url = $_GET['url'];

line_out("Initial page ".htmlent_utf8($url)."...");
flush();

// http://symfony.com/doc/current/components/dom_crawler.html
$client = new Client();
$crawler = $client->request('GET', $url);
$html = $crawler->html();

line_out("Retrieved ".strlen($html)." characters.");
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
    $form = $crawler->selectButton('Play')->form();
    $form['human'] = $i % 3;
	line_out("Playing ".$names[$i % 3]);
    $crawler = $client->submit($form);
    $html = $crawler->html();
    togglePre('Show retrieved page',$html);
    
    $matches = Array();
    preg_match('/Your Play=([^ ]*) Computer Play=([^ ]*) Result=(.*)/',$html,$matches);
	if ( count($matches) != 4 ) {
		line_error('Could not find properly formatted line starting with "Your Play="');
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


