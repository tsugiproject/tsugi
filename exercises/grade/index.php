<?php
require_once "../../config.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Grades\GradeUtil;

// Retrieve the launch data if present
$LAUNCH = LTIX::requireData();
$p = $CFG->dbprefix;
$displayname = $USER->displayname;

if ( isset($_POST['grade']) )  {
    $gradetosend = $_POST['grade'] + 0.0;
    if ( $gradetosend < 0.0 || $gradetosend > 1.0 ) {
        $_SESSION['error'] = "Grade out of range";
        header('Location: '.addSession('index.php'));
        return;
    }

    // TODO: Look in the $LAUNCH->result Variable to find the previous grade
    // to make it so the grade never goes down unless the gradetosend
    // gradetosend is 0.0 - send the 0.0 to reset the grade.
    $prevgrade = 0.5;

    if ( $gradetosend > 0.0 && $gradetosend < $prevgrade ) {
        $_SESSION['error'] = "Grade lower than $prevgrade - not sent";
    } else {
        // Use LTIX to send the grade back to the LMS.
        $debug_log = array();
        $retval = $LAUNCH->result->gradeSend($gradetosend, false, $debug_log);
        $_SESSION['debug_log'] = $debug_log;

        if ( $retval === true ) {
            $_SESSION['success'] = "Grade $gradetosend sent to server.";
        } else if ( is_string($retval) ) {
            $_SESSION['error'] = "Grade not sent: ".$retval;
        } else {
            echo("<pre>\n");
            var_dump($retval);
            echo("</pre>\n");
            die();
        }
    }

    // Redirect to ourself
    header('Location: '.addSession('index.php'));
    return;
}

// Start of the output
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();
// Don't change the line below - change the servicename in the configuration
echo("<h1>Grade Exercise for ".$CFG->servicename."</h1>\n");
$OUTPUT->welcomeUserCourse();

?>
<form method="post">
Enter grade:
<input type="number" name="grade" step="0.01" min="0" max="1.0"><br/>
<input type="submit" name="send" value="Send grade">
</form>
<?php

if ( isset($_SESSION['debug_log']) ) {
    echo("<p>Debug output from grade send:</p>\n");
    $OUTPUT->dumpDebugArray($_SESSION['debug_log']);
    unset($_SESSION['debug_log']);
}

echo("\n<hr>\n<pre>\n");
echo("Global Tsugi Objects:\n");
$LAUNCH->var_dump();
echo("\n</pre>\n");

$OUTPUT->footer();

