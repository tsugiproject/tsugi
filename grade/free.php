<?php

require_once "../setup.php";

session_start();

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
<input type="submit" value="Grade">
</form>
');
return;
}

$grade = 0;
$url = $_GET['url'];

echo("<p>&nbsp;</p><h4>This is not really grading $url - everyone gets 100% on this test</h4>\n");

if ( $displayname ) {
    echo("<p>This is your name as known in the LMS:<br/><strong>\n");
    echo(htmlent_utf8($displayname));
    echo("</strong><br/>\nYou will want to use this name in your web pages to get 
        full credit on actual assignments.</p>\n");
}

flush();
$retval = sendGrade(1.0);
if ( $retval == true ) {
    success_out("Grade sent to server.");
} else if ( is_string($retval) ) {
    error_out("Grade not sent: ".$retval);
}
flush();
