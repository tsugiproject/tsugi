<?php
// A library for webscraping graders
include_once $CFG->dirroot."/lib/lti_util.php";

require_once $CFG->dirroot."/lib/goutte/vendor/autoload.php";
require_once $CFG->dirroot."/lib/goutte/Goutte/Client.php";

// Check to see if we were launched from LTI, and if so set the 
// displayname varalble for the rest of the code
$displayname = false;
if ( isset($_SESSION['lti']) ) {
    $lti = $_SESSION['lti'];
    $displayname = $lti['user_displayname'];
}

// Check if this has a due date..
$duedate = false;
$duedatestr = false;
$diff = -1;
$penalty = false;
if ( isset($_SESSION['due']) ) {
	date_default_timezone_set('Pacific/Honolulu'); // Lets be generous
	if ( isset($_SESSION['timezone']) ) {
		date_default_timezone_set($_SESSION['timezone']);
	}
    $duedate = strtotime($_SESSION['due']);
	if ( $duedate !== false ) {
		$duedatestr = $_SESSION['due'];
		//  If it is just a date - add nearly an entire day of time...
		if ( strlen($duedatestr) <= 10 ) $duedate = $duedate + 24*60*60 - 1;
		$diff = time() - $duedate;
	}
}

// Should be a percentage off between 0.0 and 1.0
if ( $duedate && $diff > 0 ) {
	$penalty_time = isset($_SESSION['penalty_time']) ? $_SESSION['penalty_time'] + 0 : 24*60*60;
	$penalty_cost = isset($_SESSION['penalty_cost']) ? $_SESSION['penalty_cost'] + 0.0 : 0.2;
	$penalty_exact = $diff / $penalty_time;
	$penalties = intval($penalty_exact) + 1;
    $penalty = $penalties * $penalty_cost;
	if ( $penalty < 0 ) $penalty = 0;
	if ( $penalty > 1 ) $penalty = 1;
	$dayspastdue = $diff / (24*60*60);
	$percent = intval($penalty * 100);
	echo('<p style="color:red">It is currently '.sprintf("%10.2f",$dayspastdue)." days\n");
	echo('past the due date ('.htmlentities($duedatestr).') so your penalty is '.$percent." percent.\n");
	echo("This autograder sends the <em>latest</em> grade <b>not</b> the highest grade. So if you re-send\n");
	echo("a grade after the due date, your score in the LMS might go down.</p>\n");
}

function getUrl($sample) {
	global $displayname;
	if ( isset($_GET['url']) ) return $_GET['url'];

	if ( $displayname ) {
		echo("<p>&nbsp;</p><p><b>Hello $displayname</b> - welcome to the autograder.</p>\n");
	}
	echo('<form>
		Please enter the URL of your web site to grade:<br/>
		<input type="text" name="url" value="'.$sample.'" size="100"><br/>
		<input type="checkbox" name="grade">Send Grade (leave unchecked for a dry run)<br/>
		<input type="submit" value="Evaluate">
		</form>');
	if ( $displayname ) {
		echo("By entering a URL in this field and submitting it for 
		grading, you are representing that this is your own work.  Do not submit someone else's
		web site for grading.
		");
	}

	echo("<p>You can run this autograder as many times as you like and the last submitted
	grade will be recorded.  Make sure to double-check the course Gradebook to verify
	that your grade has been sent.</p>\n");
	exit();
}

function line_out($output) {
	echo(htmlent_utf8($output)."<br/>\n");
    flush();
}

function error_out($output) {
	echo('<span style="color:red"><strong>'.htmlent_utf8($output)."</strong></span><br/>\n");
    flush();
}

function success_out($output) {
	echo('<span style="color:green"><strong>'.htmlent_utf8($output)."</strong></span><br/>\n");
    flush();
}

function togglePre($title, $html) {
    global $div_id;
    $div_id = $div_id + 1;
    echo('<strong>'.htmlent_utf8($title));
    echo(' (<a href="#" onclick="dataToggle('."'".$div_id."'".');return false;">Toggle</a>)</strong>'."\n");
    echo(' ('.strlen($html).' characters)'."\n");
    echo('<pre id="'.$div_id.'" style="display:none; border: solid 1px">'."\n");
    echo(htmlent_utf8($html));
    echo("</pre><br/>\n");
}

function togglePreScript() {
return '<script language="javascript"> 
function dataToggle(divName) {
    var ele = document.getElementById(divName);
    if(ele.style.display == "block") {
        ele.style.display = "none";
    }
    else {
        ele.style.display = "block";
    }
} 
  //]]> 
</script>';
}

function sendGrade($grade) {
    try {
        return sendGradeInternal($grade);
	} catch(Exception $e) {
		$msg = "Grade Exception: ".$e->getMessage();
		error_log($msg);
        return $msg;
    } 
}

function sendGradeInternal($grade) {
	if ( ! isset($_SESSION['lti']) ) {
        return "Session not set up for grade return";
    }
	$lti = $_SESSION['lti'];
	if ( ! ( isset($lti['service']) && isset($lti['sourcedid']) &&
		isset($lti['key_key']) && isset($lti['secret']) ) ) {
		error_log('Session is missing required data');
        ob_start();
        $x = $lti;
        if ( isset($x['secret']) ) $x['secret'] = MD5($x['secret']);
        var_dump($x);
        $result = ob_get_clean();
        error_log($result);
        togglePre('Internal error - please send this to Chuck',$result);
        return "Missing required data";
    }

	$method="POST";
	$content_type = "application/xml";
	$sourcedid = htmlspecialchars($lti['sourcedid']);

	$operation = 'replaceResultRequest';
	$postBody = str_replace(
		array('SOURCEDID', 'GRADE', 'OPERATION','MESSAGE'),
		array($sourcedid, $grade.'', 'replaceResultRequest', uniqid()),
		getPOXGradeRequest());

	line_out('Sending grade to '.$lti['service']);

	togglePre("Grade API Request (debug)",$postBody);
    flush();
	$response = sendOAuthBodyPOST($method, $lti['service'], $lti['key_key'], $lti['secret'], $content_type, $postBody);
	global $LastOAuthBodyBaseString;
	$lbs = $LastOAuthBodyBaseString;
	togglePre("Grade API Response (debug)",$response);
    $status = "Failure to store grade";
	try {
		$retval = parseResponse($response);
		if ( isset($retval['imsx_codeMajor']) && $retval['imsx_codeMajor'] == 'success') {
            $status = true;
		} else if ( isset($retval['imsx_description']) ) {
            $status = $retval['imsx_description'];
        }
	} catch(Exception $e) {
		$status = $e->getMessage();
	}
    $note = $status;
    if ( $note == true ) $note = 'Success';
    error_log('Grade sent '.$grade.' for '.$lti['user_displayname'].' '.$note);
    return $status;
}

function testPassed($grade) {
	global $displayname;

	success_out("Test passed - congratulations");

	if ( $displayname === false || ! isset($_SESSION['lti']) ) {
		line_out('Not setup to return a grade..');
		exit();
	}
	
	if ( ! isset($_GET['grade']) ) {
		line_out('Dry run - grade of ('.intval($grade*100).'%) was not sent.');
		exit();
	}

	$retval = sendGrade($grade);
	if ( $retval == true ) {
		$success = "Grade sent to server (".intval($grade*100)."%)";
	} else if ( is_string($retval) ) {
		$failure = "Grade not sent: ".$retval;
	} else {
		echo("<pre>\n");
		var_dump($retval);
		echo("</pre>\n");
		$failure = "Internal error";
	}

	if ( strlen($success) > 0 ) {
		success_out($success);
		error_log($success);
	} else if ( strlen($failure) > 0 ) {
		error_out($failure);
		error_log($failure);
	} else {
		error_log("No status");
	}
}

function checkTitle($crawler) {
    global $displayname;
    if ( $displayname === false ) return true;

    try {
        $title = $crawler->filter('title')->text();
    } catch(Exception $ex) {
        return "Did not find title tag";
    }
    if ( strpos($title,$displayname) === false ) {
        return "Did not find '$displayname' in title tag";
    }
    return true;
}
