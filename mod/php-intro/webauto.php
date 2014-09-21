<?php
// A library for webscraping graders
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once $CFG->dirroot."/core/gradebook/lib.php";

require_once $CFG->dirroot."/lib/goutte/vendor/autoload.php";
require_once $CFG->dirroot."/lib/goutte/Goutte/Client.php";

use \Tsugi\UI\SettingsForm;

// Get any due date information
$dueDate = SettingsForm::getDueDate();
$penalty = $dueDate->penalty;

if ( $dueDate->message ) {
    echo('<p style="color:red;">'.$dueDate->message.'</p>'."\n");
}

function getUrl($sample) {
    global $USER;
    if ( isset($_GET['url']) ) {
        echo('<p><a href="#" onclick="window.location.href = window.location.href; return false;">Re-run this test</a></p>'."\n");
        if ( isset($_SESSION['lti']) ) {
            $retval = gradeUpdateJson(array("url" => $_GET['url']));
        }
        return $_GET['url'];
    }

    echo('<form>
        Please enter the URL of your web site to grade:<br/>
        <input type="text" name="url" value="'.$sample.'" size="100"><br/>
        <input type="submit" class="btn btn-primary" value="Evaluate">
        </form>');
    if ( $USER->displayname ) {
        echo("By entering a URL in this field and submitting it for
        grading, you are representing that this is your own work.  Do not submit someone else's
        web site for grading.
        ");
    }

    echo("<p>You can run this autograder as many times as you like and the last submitted
    grade will be recorded.  Make sure to double-check the course Gradebook to verify
    that your grade has been sent.</p>\n");
    return false;
}

function webauto_test_passed($grade, $url) {
    global $USER, $OUTPUT;

    success_out("Test passed - congratulations");

    if ( $USER->displayname === false || ! isset($_SESSION['lti']) ) {
        line_out('Not setup to return a grade..');
        return false;
    }

    $LTI = $_SESSION['lti'];

    $old_grade = isset($LTI['grade']) ? $LTI['grade'] : 0.0;

    if ( $grade < $old_grade ) {
         line_out('New grade is not higher than your previous grade='.$old_grade);
         line_out('Sending your previous high score');
    }

    gradeUpdateJson(json_encode(array("url" => $url)));
    $debug_log = array();
    $retval = LTIX::gradeSend($grade, false, $debug_log);
    $OUTPUT->dumpDebugArray($debug_log);
    if ( $retval == true ) {
        $success = "Grade sent to server (".$grade.")";
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
    return true;
}

function webauto_check_title($crawler) {
    global $USER;
    if ( $USER->displayname === false ) return true;

    try {
        $title = $crawler->filter('title')->text();
    } catch(Exception $ex) {
        return "Did not find title tag";
    }
    if ( stripos($title,$USER->displayname) === false ) {
        return "Did not find '$USER->displayname' in title tag";
    }
    return true;
}

function webauto_compute_effective_score($perfect, $passed, $penalty) {
    $score = $passed * (1.0 / $perfect);
    if ( $score < 0 ) $score = 0;
    if ( $score > 1 ) $score = 1;
    if ( $penalty == 0 ) {
        $scorestr = "Score = $score ($passed/$perfect)";
    } else {
        $scorestr = "Raw Score = $score ($passed/$perfect) ";
        $score = $score * (1.0 - $penalty);
        $scorestr .= "Effective Score = $score after ".$penalty*100.0." percent late penalty";
    }
    line_out($scorestr);
    return $score;
}

function webauto_check_post_redirect($client) {
    global $passed;
    line_out("Checking to see if there was a POST redirect to a GET");
    $method = $client->getRequest()->getMethod();
    if ( $method == "get" ) {
        $passed++;
    } else {
        error_out('Expecting POST to Redirect to GET - found '.$method);
    }
}
