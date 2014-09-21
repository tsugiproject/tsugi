<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once $CFG->dirroot."/core/gradebook/lib.php";

use \Tsugi\Core\LTIX;

// Retrieve the launch data if present
$LTI = LTIX::requireData(array('user_id', 'result_id', 'role','context_id'));
$p = $CFG->dbprefix;
$displayname = $USER->displayname;

if ( isset($_POST['reset']) ) {
    $sql = "UPDATE {$p}lti_result SET grade = 0.0 WHERE result_id = :RI";
    $stmt = $PDOX->prepare($sql);
    $stmt->execute(array(':RI' => $LINK->result_id));
    $_SESSION['success'] = "Grade reset";
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

if ( isset($_POST['grade']) )  {
    $gradetosend = $_POST['grade'] + 0.0;
    if ( $gradetosend < 0.0 || $gradetosend > 1.0 ) {
        $_SESSION['error'] = "Grade out of range";
        header('Location: '.addSession('index.php'));
        return;
    }

    // TODO: Use a SQL SELECT to retrieve the actual grade from tsugi_lti_result
    // The key for the grade row is in the $LINK->result_id

    $oldgrade = 0.5;   // Replace this with the value from the DB
    if ( $gradetosend < $oldgrade ) {
        $_SESSION['error'] = "Grade lower than $oldgrade - not sent";
    } else {
        // TODO: Decide when *not* to send a grade

        // Call the XML APIs to send the grade back to the LMS.
        $debug_log = array();
        $retval = LTIX::gradeSend($gradetosend, false, $debug_log);
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
$OUTPUT->welcomeUserCourse();

?>
<form method="post">
Enter grade:
<input type="number" name="grade" step="0.01" min="0" max="1.0"><br/>
<input type="submit" name="send" value="Send grade">
<input type="submit" name="reset" value="Reset grade"><br/>
</form>
<?php

if ( isset($_SESSION['debug_log']) ) {
    echo("<p>Debug output from grade send:</p>\n");
    $OUTPUT->dumpDebugArray($_SESSION['debug_log']);
    unset($_SESSION['debug_log']);
}

echo("<pre>Global Tsugi Objects:\n\n");
var_dump($USER);
var_dump($CONTEXT);
var_dump($LINK);

echo("\n<hr/>\n");
echo("Session data (low level):\n");
echo(safe_var_dump($_SESSION));

$OUTPUT->footer();

