<?php
require_once "../../config.php";
require_once "../../db.php";

session_start();
// Set up global variables from session
require_once $CFG->dirroot."/lib/webauto.php";


if ( isset($_POST['grade']) )  {
	$gradetosend = $_POST['grade'] + 0.0;
	if ( $gradetosend < 0.0 || $gradetosend > 1.0 ) {
		$_SESSION['error'] = "Grade out of range";
		header('Location: testgrade.php?'.session_name().'='.session_id());
		return;
	}

	// TODO: Use a SQL SELECT to retrieve the actual grade from webauto_lti_result
	// The key is in the $_SESSION['lti']['result_id'];
	$oldgrade = 0.5;   // Replace this with the value from the DB
	if ( $gradetosend < $oldgrade ) {
		$_SESSION['error'] = "Grade lower than $oldgrade - not sent";
	} else {
		// TODO: Update the webauto_lti_result table with 
		// the to be sent grade

		// We pass this in session because the sendGrade() function produces output
		$_SESSION['gradetosend'] = $gradetosend;
		$_SESSION['error'] = "Delete this when the grade is coming from the database!";
	}

	// Redirect to ourself with the session ID as a GET parameter.
    header('Location: testgrade.php?'.session_name().'='.session_id());
	return;
}
?>
<html><head><title>Testing of the grade code</title>
<?php echo(togglePreScript());?>
</head>
<body style="background-color:pink;">
<?php
if ( isset($_SESSION['error']) ) {
    echo '<p style="color:red">'.$_SESSION['error']."</p>\n";
    unset($_SESSION['error']);
}
if ( isset($_SESSION['success']) ) {
    echo '<p style="color:green">'.$_SESSION['success']."</p>\n";
    unset($_SESSION['success']);
}

if ( $displayname ) {
    echo("<p>Welcome <strong>\n");
    echo(htmlent_utf8($displayname));
	echo("</strong></p>\n");
}

if ( isset($_SESSION['gradetosend']) ) {
	$gradetosend = $_SESSION['gradetosend'];
	unset($_SESSION['gradetosend']);

	// Produces some output, call the XML APIs to
	// send the grade back to the LMS.
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
}
?>
<form method="post">
Enter grade:
<input type="number" name="grade" step="0.01" min="0" max="1.0"><br/>
<input type="submit" name="send" value="Send grade"><br/>
</form>
<?php

echo('<p>$_SESSION["lti"]["result_id"] is: '.$_SESSION['lti']['result_id']."</p>\n");

echo("<p>Here is the session information:\n<pre>\n");
var_dump($_SESSION);
echo("\n</pre>\n");
