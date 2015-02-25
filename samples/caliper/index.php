<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once $CFG->dirroot."/core/gradebook/lib.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;
use \Tsugi\Util\Caliper;

// Retrieve the launch data if present
$LTI = LTIX::requireData();
$p = $CFG->dbprefix;
$displayname = $USER->displayname;

if ( isset($_POST['caliper'])) {
    $caliper = Caliper::sensorCanvasPageView(
        LTIX::sessionGet('user_key'), 
        $CFG->wwwroot,
        "samples/grade/index.php"
    );
    $_SESSION['caliper'] = $caliper;
    $debug_log = array();
    $retval = LTIX::caliperSend($caliper, 'application/json', $debug_log);
/*
echo("<pre>\n");
var_dump($caliper);
echo("</pre>\n");
die();
*/
    if ( $retval ) {
        $_SESSION['success'] = "Caliper sent.";
    } else {
        $_SESSION['error'] = "Caliper attempt failed.";
    }
    $_SESSION['debuglog'] = $debug_log;
    header('Location: '.addSession('index.php'));
    return;
}

// Start of the output
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();
echo("<h1>Caliper Test Harness</h1>\n");
$OUTPUT->welcomeUserCourse();

?>
<form method="post">
<input type="submit" name="caliper" value="Send Caliper Page View">
</form>
<br/>
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

