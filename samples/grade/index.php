<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once $CFG->dirroot."/core/gradebook/lib.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;

// Retrieve the launch data if present
$LTI = LTIX::requireData();
$p = $CFG->dbprefix;
$displayname = $USER->displayname;

if ( isset($_POST['caliper'])) {
    $caliper = json_decode('{
          "@context" : "http://purl.imsglobal.org/ctx/caliper/v1/ViewEvent",
          "@type" : "http://purl.imsglobal.org/caliper/v1/ViewEvent",
          "action" : "viewed",
          "startedAtTime" : "Time.now.utc.to_i",
          "duration" : "PT5M30S",
          "actor" : {
                  "@id" : "user_id",
                  "@type" : "http://purl.imsglobal.org/caliper/v1/lis/Person"
          },
          "object" : {
                  "@id" : "context_url",
                  "@type" : "http://www.idpf.org/epub/vocab/structure/#volume",
                  "name" : "Test LTI Tool"
          },
          "edApp" : {
                  "@id" :"context_url",
                  "@type" : "http://purl.imsglobal.org/caliper/v1/SoftwareApplication",
                  "name" : "LTI Tool of All Things",
                  "properties" : {},
                  "lastModifiedTime" : "Time.now.utc.to_i"
          }
  }');
$caliper->startedAtTime = time();
$caliper->actor->{'@id'} = LTIX::sessionGet('user_key');
$caliper->object->{'@id'} = 'https://lti-tools.dr-chuck.com/sample/grade/index.php';
$caliper->edApp->{'@id'} = 'https://lti-tools.dr-chuck.com/';
$_SESSION['caliper'] = $caliper;
$caliper = json_encode($caliper);
/*
echo("<pre>\n");
var_dump($caliper);
echo("</pre>\n");
die();
*/
        $key_key = LTIX::sessionGet('key_key');
        $secret = LTIX::sessionGet('secret');
        $caliperURL = LTIX::postGet('custom_sub_canvas_caliper_url');
        error_log("k=$key_key s=$secret u=$caliperURL");

        $debug_log = array();
        LTI::sendJSONCaliper($caliper, $caliperURL, $key_key, $secret, $debug_log);
    $_SESSION['success'] = "Caliper sent.";
    $_SESSION['debuglog'] = $debug_log;
    header('Location: '.addSession('index.php'));
    return;
}

if ( isset($_POST['grade']) )  {
    $gradetosend = $_POST['grade'] + 0.0;
    if ( $gradetosend < 0.0 || $gradetosend > 1.0 ) {
        $_SESSION['error'] = "Grade out of range";
        header('Location: '.addSession('index.php'));
        return;
    }

    // Use LTIX to send the grade back to the LMS.
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

    // Redirect to ourself
    header('Location: '.addSession('index.php'));
    return;
}

// Start of the output
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();
echo("<h1>Grade Test Harness</h1>\n");
$OUTPUT->welcomeUserCourse();

?>
<form method="post">
Enter grade:
<input type="number" name="grade" step="0.01" min="0" max="1.0"><br/>
<input type="submit" name="send" value="Send grade">
<input type="submit" name="caliper" value="Send Caliper">
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

