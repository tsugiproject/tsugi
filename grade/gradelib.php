<?php

include_once "../lib/lti_util.php";

function line_out($output) {
	echo(htmlent_utf8($output)."<br/>\n");
}

function error_out($output) {
	echo('<span style="color:red"><strong>'.htmlent_utf8($output)."</strong></span><br/>\n");
}

function success_out($output) {
	echo('<span style="color:green"><strong>'.htmlent_utf8($output)."</strong></span><br/>\n");
}

function doTop() {
echo('<html>
<head>
  <title>Automatic Web Grading Tool</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<script language="javascript"> 
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
</script>
</head>
<body style="font-family:sans-serif; background-color:#add8e6">
');
}

function togglePre($title, $html) {
    global $div_id;
    $div_id = $div_id + 1;
    echo('<strong>'.htmlent_utf8($title));
    echo(' (<a href="#" onclick="dataToggle('."'".$div_id."'".');return false;">Toggle</a>)</strong>'."\n");
    echo('<pre id="'.$div_id.'" style="display:none; border: solid 1px">'."\n");
    echo(htmlent_utf8($html));
    echo("</pre><br/>\n");
}

function sendGrade($grade) {
	if ( ! isset($_SESSION['lti']) ) return;
	$lti = $_SESSION['lti'];
	if ( ! ( isset($lti['service']) && isset($lti['sourcedid']) &&
		isset($lti['key_key']) && isset($lti['secret']) ) ) return;

	$method="POST";
	$content_type = "application/xml";
	$sourcedid = htmlspecialchars($lti['sourcedid']);

	$operation = 'replaceResultRequest';
	$postBody = str_replace(
		array('SOURCEDID', 'GRADE', 'OPERATION','MESSAGE'),
		array($sourcedid, $grade.'', 'replaceResultRequest', uniqid()),
		getPOXGradeRequest());

	line_out('Sending grade to '.$lti['service']);

	togglePre("Grade API Request",$postBody);
	$response = sendOAuthBodyPOST($method, $lti['service'], $lti['key_key'], $lti['secret'], $content_type, $postBody);
	global $LastOAuthBodyBaseString;
	$lbs = $LastOAuthBodyBaseString;
	togglePre("Grade API Response",$response);
	try {
		$retval = parseResponse($response);
		if ( isset($retval['imsx_codeMajor']) && $retval['imsx_codeMajor'] == 'success') return true;
		if ( isset($retval['imsx_description']) ) return $retval['imsx_description'];
		return "Failure to store grade";
	} catch(Exception $e) {
		return $e->getMessage();
	}
}

