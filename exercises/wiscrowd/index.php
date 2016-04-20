<?php
require_once "../../config.php";

use \Tsugi\Core\LTIX;

// Retrieve required launch data from session
$LAUNCH = LTIX::requireData();
$p = $CFG->dbprefix;

// Add all of your POST handling code here.  Use $LINK->id
// in each of the entries to keep a separate set of guesses for
// each link.

// Add the SQL to retrieve all the guesses for the $LINK->id
// here and leave the list of guesses and average in variables to
// fall through to the view below.

$OUTPUT->header(); // Start the document and begin the <head>
$OUTPUT->bodyStart(); // Finish the </head> and start the <body>
$OUTPUT->flashMessages(); // Print out the $_SESSION['success'] and error messages

// A partial form styled using Twitter Bootstrap
echo('<form method="post">');
echo("Enter guess:\n");
echo('<input type="text" name="guess" value=""> ');
echo('<input type="submit" class="btn btn-primary" name="send" value="Guess"> ');
echo("\n</form>\n");

// Dump out the session information
// This is here for initial debugging only - it should not be part of the final project.
// Note that addSession() is not needed here because PHP autmatically handles
// PHPSESSID on anchor tags and in forms.
if ( $USER->instructor ) {
    echo('<p><a href="debug.php" target="_blank">Debug Print Session Data</a></p>');
    echo("\n");
}

// Finish the body (including loading JavaScript for JQUery and Bootstrap)
// And put out the common footer material

$OUTPUT->footer();
