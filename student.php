<?php
require_once "../config.php";
use \Tsugi\Blob\BlobUtil;

require_once "peer_util.php";

use \Tsugi\Util\U;
use \Tsugi\Core\Cache;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Result;
use \Tsugi\Core\User;
use \Tsugi\UI\Table;
use \Tsugi\Grades\GradeUtil;

// Set up the GET Params that we want to carry around.
$getparms = $_GET;
unset($getparms['delete']);
unset($getparms['resend']);

if ( !isset($STUDENT_RETURN) ) $STUDENT_RETURN = Table::makeUrl('admin.php', $getparms, Array('user_id'=>false));

// Sanity checks
$LAUNCH = LTIX::requireData();
if ( ! $USER->instructor ) die("Requires instructor role");
$p = $CFG->dbprefix;

if ( !isset($_REQUEST['user_id']) ) die("user_id parameter required");
$user_id = $_REQUEST['user_id'];

// Load the assignment
$row = loadAssignment();
$assn_json = null;
$assn_id = false;
if ( $row !== false ) {
    $assn_json = json_decode(upgradeSubmission($row['json']));
    $assn_id = $row['assn_id'];
}

if ( $assn_id === false ) {
    $_SESSION['error'] = "This assignment is not yet set up.";
    header( 'Location: '.addSession('index') ) ;
    return;
}

$submit_id = false;
$submit_row = loadSubmission($assn_id, $user_id);
if ( $submit_row !== false ) {
    $submit_id = $submit_row['submit_id']+0;
}

// Load user info
$user_row = User::loadUserInfoBypass($user_id);
if ( $user_row == false ) {
    $_SESSION['error'] = "Could not load student data.";
    header( 'Location: '.addSession('index') ) ;
    return;
}

// Handle incoming post to delete the entire submission
if ( isset($_POST['deleteSubmit']) ) {
    if ( $submit_id == false ) {
        $_SESSION['error'] = "Could not load submission.";
        header( 'Location: '.addSession('index') ) ;
        return;
    }

    deleteSubmission($row, $submit_row);

    $note = isset($_POST['deleteNote']) ? $_POST['deleteNote'] : '';
    $retval = mailDeleteSubmit($user_id, $assn_json, $note);
    $stmt = $PDOX->queryDie(
        "DELETE FROM {$p}peer_submit
            WHERE submit_id = :SID",
        array( ':SID' => $submit_id)
    );
    // Since text items are connected to the assignment not submission
    $stmt = $PDOX->queryDie(
        "DELETE FROM {$p}peer_text
            WHERE assn_id = :AID AND user_id = :UID",
        array( ':AID' => $assn_id, ':UID' => $user_id)
    );
    $msg = "Deleted submission for $user_id";
    if ( $retval ) $msg .= ', e-mail notice sent.';
    error_log($msg);
    Cache::clear('peer_grade');
    Cache::clear('peer_submit');
    $msg = "Submission deleted";
    if ( $retval ) $msg .= ', e-mail notice sent.';
    $_SESSION['success'] = $msg;
    header( 'Location: '.addSession($STUDENT_RETURN) ) ;
    return;
}

// Handle incoming post to set the instructor points and update the grade
if ( isset($_POST['instSubmit']) || isset($_POST['instSubmitAdvance']) ) {
    if ( $submit_id == false ) {
        $_SESSION['error'] = "Could not load submission.";
        header( 'Location: '.addSession('index') ) ;
        return;
    }

    // Check the legit options here
    $points = isset($_POST['inst_points']) ? trim($_POST['inst_points']) : null;
    if ( U::isEmpty($points) ) {
        $points = null;
    } else if ( is_numeric($points) ) {
        $points = $points + 0;
    } else {
        $_SESSION['error'] = "Points must either by a number or blank.";
        $studenturl = Table::makeUrl('student.php', $getparms);
        header( 'Location: '.addSession($studenturl) ) ;
        return;
    }

    // Check the range here
    if ( $points < 0 || $points > $assn_json->instructorpoints ) {
        $_SESSION['error'] = "Bad value for instructor point value.";
        $studenturl = Table::makeUrl('student.php', $getparms);
        header( 'Location: '.addSession($studenturl) ) ;
        return;
    }

    $stmt = $PDOX->queryDie(
        "UPDATE {$p}peer_submit SET
            inst_points = :IP, inst_note = :IN
            WHERE submit_id = :SID",
        array( ':IP' => $points,
            ':IN' => $_POST['inst_note'],
            ':SID' => $submit_id)
    );
    Cache::clear('peer_grade');
    Cache::clear('peer_submit');
    $msg = "Submission updated";
    $computed_grade = computeGrade($assn_id, $assn_json, $user_id); // Does not cache
    $result = Result::lookupResultBypass($user_id);
    $old_grade = null;
    if ( $result && isset($result['grade']) && $result['grade'] != -1 ) {
        $old_grade = floatval($result['grade']);
    }
    $result['grade'] = -1; // Force resend
    $debug_log = array();
    $status = LTIX::gradeSend($computed_grade, $result, $debug_log); // This is the slow bit
    if ( $status === true ) {
        $_SESSION['success'] = 'Grade submitted to server';
        // Send notification to the student whose grade was changed (only if grade actually changed)
        // Use LTI launch_presentation_return_url if available, otherwise fall back to index
        $notification_url = null;
        if ( is_object($LAUNCH) && method_exists($LAUNCH, 'returnUrl') ) {
            $notification_url = $LAUNCH->returnUrl();
        }
        if ( empty($notification_url) ) {
            $notification_url = Table::makeUrl('index', $getparms);
            $notification_url = addSession($notification_url);
        }
        notifyGradeChange($user_id, $computed_grade, $old_grade, $assn_json->title ?? null, $notification_url);
    } else {
        error_log("Problem sending grade ".$status);
        $_SESSION['error'] = 'Error sending grade to: '.$status;
        $_SESSION['debug_log'] = $debug_log;
    }
    if ( isset($_POST['instSubmitAdvance']) && isset($_POST['next_user_id_ungraded']) && is_numeric($_POST['next_user_id_ungraded']) ) {
        $next_user_id_ungraded = $_POST['next_user_id_ungraded']+0;
        $studenturl = Table::makeUrl('student.php', $getparms, Array("user_id" => $next_user_id_ungraded));
        header( 'Location: '.addSession($studenturl) ) ;
    } else {
        $studenturl = Table::makeUrl('student.php', $getparms);
        header( 'Location: '.addSession($studenturl) ) ;
    }
    return;
}

// Compute grade
$computed_grade = computeGrade($assn_id, $assn_json, $user_id); // Does not cache
if ( isset($_POST['resendSubmit']) ) {
     $result = Result::lookupResultBypass($user_id); // Does not cache

    // Force a resend
    $_SESSION['lti']['grade'] = -1;  // Force a resend
    $result['grade'] = -1;
    $debug_log = array();
    $status = LTIX::gradeSend($computed_grade, $result, $debug_log); // This is the slow bit
    if ( $status === true ) {
        $_SESSION['success'] = 'Grade submitted to server';
    } else {
        error_log("Problem sending grade ".$status);
        $_SESSION['error'] = 'Error: '.$status;
    }
    $_SESSION['debug_log'] = $debug_log;
    $studenturl = Table::makeUrl('student.php', $getparms);
    header( 'Location: '.addSession($studenturl) ) ;
    return;
}

// Retrieve received grades and grades that have been
$grades_received = retrieveSubmissionGrades($submit_id);
$grades_given = retrieveGradesGiven($assn_id, $user_id);
$peer_marks = retrievePeerMarks($assn_id, $user_id);

// Handle incoming post to delete a grade entry
if ( isset($_POST['grade_id']) && isset($_POST['deleteGrade']) ) {
    // Make sure this is deleting a legit grading entry...
    $found = false;
    if ( $grades_given != false ) foreach ( $grades_given as $grade ) {
        if ($_POST['grade_id'] == $grade['grade_id'] ) $found = true;
    }
    if ( ! $found ) {
        $_SESSION['error'] = "Grade entry not found.";
        header( 'Location: '.addSession('index') ) ;
        return;
    }
    $stmt = $PDOX->queryDie(
        "DELETE FROM {$p}peer_grade
            WHERE grade_id = :GID",
        array( ':GID' => $_POST['grade_id'])
    );
    Cache::clear('peer_grade');
    error_log("Instructor deleted grade entry for ".$user_id);
    $_SESSION['success'] = "Grade entry deleted.";
    $studenturl = Table::makeUrl('student.php', $getparms);
    header( 'Location: '.addSession($studenturl) ) ;
    return;
}

// Retrieve our flags...
$our_flags = false;
if ( $submit_id !== false ) {
    $stmt = $PDOX->queryDie(
        "SELECT flag_id, F.user_id AS user_id, grade_id, note, handled, response,
            F.updated_at AS updated_at, displayname, email
        FROM {$p}peer_flag AS F
        JOIN {$p}lti_user as U
            ON F.user_id = U.user_id
        WHERE submit_id = :SID",
        array( ':SID' => $submit_id)
    );
    $our_flags = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle incoming post to delete a flag entry
if ( isset($_POST['flag_id']) && isset($_POST['deleteFlag']) ) {
    // Make sure this is a legit flag entry
    $found = false;
    if ( $our_flags != false ) foreach ( $our_flags as $flag ) {
        if ($_POST['flag_id'] == $flag['flag_id'] ) $found = true;
    }
    if ( ! $found ) {
        $_SESSION['error'] = "Flag entry not found.";
        header( 'Location: '.addSession('index') ) ;
        return;
    }
    $stmt = $PDOX->queryDie(
        "DELETE FROM {$p}peer_flag
            WHERE flag_id = :FID",
        array( ':FID' => $_POST['flag_id'])
    );
    Cache::clear('peer_flag');
    Cache::clear('peer_grade');
    error_log("Instructor deleted flag=".$_POST['flag_id']." for ".$user_id);
    $_SESSION['success'] = "Flag entry deleted.";
    $studenturl = Table::makeUrl('student.php', $getparms);
    header( 'Location: '.addSession($studenturl) ) ;
    return;
}

// Retrieve the next and previous users for paging
$sql = "(SELECT user_id, inst_points FROM {$p}peer_submit
        WHERE user_id < :UID AND assn_id = :AID ORDER BY user_id DESC LIMIT 1)
    UNION (SELECT user_id, inst_points FROM {$p}peer_submit
        WHERE user_id > :UID AND assn_id = :AID ORDER BY user_id ASC LIMIT 1)";
$rows = $PDOX->allRowsDie($sql,
    array(":UID" => $user_id, ":AID" => $assn_id)
);

// Retrieve ungraded rows in a circular manner
$ungraded_rows = false;
if ( $assn_json->instructorpoints > 0 ) {
    $ungraded_sql = "(SELECT user_id, inst_points FROM {$p}peer_submit
        WHERE user_id > :UID AND assn_id = :AID AND inst_points IS NULL ORDER BY user_id ASC LIMIT 1)
    UNION (SELECT user_id, inst_points FROM {$p}peer_submit
        WHERE user_id != :UID AND assn_id = :AID AND inst_points IS NULL ORDER BY user_id ASC LIMIT 1)
    ";
    $ungraded_rows = $PDOX->allRowsDie($ungraded_sql,
        array(":UID" => $user_id, ":AID" => $assn_id)
    );
}


// View
$OUTPUT->header();
?>
<link href="<?= U::get_rest_parent() ?>/static/prism.css" rel="stylesheet"/>
<script>
let html_loads = [];
</script>
<?php
$OUTPUT->bodyStart();
$OUTPUT->topNav();

$prev_user_id = false;
$next_user_id = false;
foreach ($rows as $row ) {
    if ( $row['user_id'] < $user_id ) $prev_user_id = $row['user_id'];
    if ( $row['user_id'] > $user_id && $next_user_id === false ) $next_user_id = $row['user_id'];
}

$next_user_id_ungraded = false;
if ( $assn_json->instructorpoints > 0 && count($ungraded_rows) > 0 ) {
    $next_user_id_ungraded = $ungraded_rows[0]['user_id'];
}

$user_display = false;
echo('<div style="float:right">');
if ( $prev_user_id !== false ) {
    $studenturl = Table::makeUrl('student.php', $getparms, Array("user_id" => $prev_user_id));
    echo('<button class="btn btn-normal"
        title="Students are ordered by user_id"
        onclick="location=\''.addSession($studenturl).'\';
        return false">Previous Student</button> ');
} else {
    echo('<button class="btn btn-normal"
        title="Students are ordered by user_id"
        disabled="disabled">Previous Student</button> ');
}

if ( $next_user_id !== false ) {
    $studenturl = Table::makeUrl('student.php', $getparms, Array("user_id" => $next_user_id));
    echo('<button class="btn btn-normal"
        title="Students are ordered by user_id"
        onclick="location=\''.addSession($studenturl).'\';
        return false">Next Student</button> ');
} else {
    echo('<button class="btn btn-normal"
        title="Students are ordered by user_id"
        disabled="disabled">Next Student</button> ');
}

if ( $next_user_id_ungraded !== false ) {
    $studenturl = Table::makeUrl('student.php', $getparms, Array("user_id" => $next_user_id_ungraded));
    echo('<button class="btn btn-normal"
        title="At the end of ungraded students this goes back to the first ungraded student."
        onclick="location=\''.addSession($studenturl).'\';
        return false">Next Ungraded Student</button> ');
} else if ( $assn_json->instructorpoints > 0 ) {
    echo('<button class="btn btn-normal"
        title="All students have instructor grades"
        disabled="disabled">Next Ungraded Student</button> ');
}
echo('<button type="button" class="btn btn-success"
    onclick="location=\''.addSession($STUDENT_RETURN).'\'; return false;" aria-label="Exit to student list">Exit</button>');
echo("</div>");

if ( $user_row != false ) {
    $user_display = htmlentities($user_row['displayname'] ?? '')." (".htmlentities($user_row['email'] ?? '').")";
    echo("<p><b>Grade record for: ".htmlentities($user_row['displayname'] ?? '')."</b></p>\n");
}
echo('<br clear="all"/>');

// Delay flash messages
$OUTPUT->flashMessages();

if ( $submit_row === false ) {
    echo("<p>This student has not made a submission.</p>\n");
} else {
    $submit_json = json_decode($submit_row['json']);
    showSubmission($assn_json, $submit_json, $assn_id, $user_id);
    if ( $submit_json && isset($submit_json->peer_exempt) ) {
        echo("<p>This student has requested an peer grading exemption due to accessibility issues.</p>\n");
    }

}

echo('<form method="post">
      <input type="hidden" name="user_id" value="'.$user_id.'">');

if ( $next_user_id_ungraded !== false ) {
      echo('<input type="hidden" name="next_user_id_ungraded" value="'.$next_user_id_ungraded.'">');
}

if ( $assn_json->instructorpoints > 0 ) {
    echo('<label for="inst_points">Instructor Points</label>
          <input type="number" name="inst_points" id="inst_points" min="0" ');
    echo('max="'. $assn_json->instructorpoints.'" value="'.($submit_row["inst_points"]).'">');
    echo(" (Maximum ". $assn_json->instructorpoints.' points)<br/>');
}

echo('<label for="inst_note">Instructor Note To Student</label><br/>
      <textarea name="inst_note" id="inst_note" style="width:60%" rows="5">');
echo(htmlentities($submit_row['inst_note'] ?? ''));
echo('</textarea><br/>
      <input type="submit" name="instSubmit" value="Update" class="btn btn-primary">');

if ( $next_user_id_ungraded !== false ) {
    echo(' <input type="submit" name="instSubmitAdvance" value="Update and Go To Next Ungraded Student" class="btn btn-primary">');
}
echo('</form>');

if ( $assn_json->maxassess > 0 ) {
    $gradeurl = Table::makeUrl('grade', $getparms);
    echo('<p><a href="'.$gradeurl.'">Peer grade this student</a></p>'."\n");
}

if ( $assn_json->rating > 0 ) {
    $rateadmin = Table::makeUrl('rate-admin.php', $getparms);
    echo('<p><a href="'.$rateadmin.'">Rate this student</a></p>'."\n");
}

if ( isset($_GET['delete']) ) {
    $studenturl = Table::makeUrl('student.php', $getparms);
    echo('<form method="post">
        <input type="hidden" name="user_id" value="'.$user_id.'">
        <label for="deleteNote">Enter an optional note to send to the student</label><br/>
        <textarea name="deleteNote" id="deleteNote" style="width:60%" rows="5">
        </textarea><br/>
        <input type="submit" name="deleteSubmit" value="Complete Delete" class="btn btn-danger">
        <input type="submit" name="doCancel" value="Cancel Delete" class="btn btn-normal"
            onclick="location=\''.addSession($studenturl).'\'; return false;">
        </form>');
} else {
    $studenturl = Table::makeUrl('student.php', $getparms, Array("delete" => "yes"));
    echo('<p><a href="'.$studenturl.'">Delete this
        submission and grades (allows student to resubmit)</a></p>'."\n");
}

if ( $assn_json->totalpoints == 0 ) {
    echo("<p>This is an ungraded assignment</p>\n");
} else {
    echo("<p>Computed grade: ".$computed_grade."<br/>\n");

    if ( isset($_GET['resend']) ) {
        $studenturl = Table::makeUrl('student.php', $getparms);
        echo('<form method="post">
            <input type="hidden" name="user_id" value="'.$user_id.'">
            <input type="submit" name="resendSubmit" value="Resend the Grade" class="btn btn-warning">
            <input type="submit" name="doCancel" value="Cancel Resend" class="btn btn-normal"
                onclick="location=\''.addSession($studenturl).'\'; return false;">
            </form>');
    } else {
        $studenturl = Table::makeUrl('student.php', $getparms, Array("resend" => "yes"));
        echo('<p><a href="'.$studenturl.'">
            Resend computed grade to the LMS</a></p>');
    }
}

if ( $user_display !== false ) $user_display = " by ".$user_display;

if ( $our_flags !== false && count($our_flags) > 0 ) {
    echo("<p style=\"color:red\">This entry $user_display has the following flags:</p>\n");
    echo('<div style="margin:3px;">');
    echo('<table border="1" class="table table-hover table-condensed table-responsive"><tr>');
    echo("\n<th>Flagged By</th><th>Email</th><th>Comment</th><th>Time</th><th>Action</th></tr>");
    foreach ( $our_flags as $flag ) {
        echo("\n<tr>");
        echo("<td>".htmlentities($flag['displayname'] ?? '')."</td>\n");
        echo("<td>".htmlentities($flag['email'] ?? '')."</td>\n");
        echo("<td>".htmlentities($flag['note'] ?? '')."</td>\n");
        echo("<td>".htmlentities($flag['updated_at'] ?? '')."</td>\n");
        echo('<td> <form method="post"><input type="hidden"
            name="flag_id" value="'.$flag['flag_id'].'">
        <input type="submit" name="deleteFlag" value="delete" class="btn btn-danger"></form></td>');
        echo("</tr>\n");
    }
    echo("</table>\n");
    echo("</div>\n");
}

if ( $grades_received === false || count($grades_received) < 1 ) {
    echo("<p>No peer has graded this submission $user_display.</p>");
} else {
    echo("<p>Grades Received$user_display:</p>");
    echo('<div style="margin:3px;">');
    echo('<table border="1" class="table table-hover table-condensed table-responsive">');
    echo("\n<tr><th>User</th><th>Email</th>");
    if ( $assn_json->peerpoints > 0 ) echo("<th>Points</th>");
    if ( $assn_json->rating > 0 ) echo("<th>Rating</th>");
    echo("<th>Comments</th><th>Action</th></tr>\n");

    foreach ( $grades_received as $grade ) {
        echo("<tr>
        <td>".htmlentities($grade['displayname'] ?? '')."</td>
        <td>".htmlentities($grade['email'] ?? '')."</td>");
        if ( $assn_json->peerpoints > 0 ) echo("<td>".$grade['points']."</td>");
        if ( $assn_json->rating > 0 ) echo("<td>".$grade['rating']."</td>");
        echo("<td>".htmlentities($grade['note'] ?? '')."</td>".
        '<td> <form method="post"><input type="hidden"
            name="grade_id" value="'.$grade['grade_id'].'">
        <input type="hidden" name="user_id" value="'.$user_id.'">
        <input type="submit" name="deleteGrade" value="delete" class="btn btn-danger"></form></td>'.
        "\n</tr>\n");
    }
    echo("</table>\n");
    echo("</div>\n");
}

if ( $grades_given === false || count($grades_given) < 1 ) {
    if ( $peer_marks > 0 ) {
        echo("<p>$peer_marks peer_marks given $user_display.</p>");
    } else {
        echo("<p>Nothing has been graded $user_display yet.</p>");
    }
} else {
    echo("<p>Grades Given$user_display:</p>");
    echo('<div style="margin:3px;">');
    echo('<table border="1" class="table table-hover table-condensed table-responsive">');
    echo("\n<tr><th>User</th><th>Email</th>");
    if ( $assn_json->peerpoints > 0 ) echo("<th>Points</th>");
    if ( $assn_json->rating > 0 ) echo("<th>Rating</th>");
    echo("<th>Comments</th><th>Action</th></tr>\n");

    foreach ( $grades_given as $grade ) {
        echo("<tr>
        <td>".htmlentities($grade['displayname'] ?? '')."</td>
        <td>".htmlentities($grade['email'] ?? '')."</td>");
        if ( $assn_json->peerpoints > 0 ) echo("<td>".$grade['points']."</td>");
        if ( $assn_json->rating > 0 ) echo("<td>".$grade['rating']."</td>");
        echo("<td>".htmlentities($grade['note'] ?? '')."</td>".
        '<td> <form method="post"><input type="hidden"
            name="grade_id" value="'.$grade['grade_id'].'">
        <input type="hidden" name="user_id" value="'.$user_id.'">
        <input type="submit" name="deleteGrade" value="delete" class="btn btn-danger"></form></td>'.
        "\n</tr>\n");
    }
    echo("</table>\n");
    echo("</div>\n");
}

// Delay the debug output to the bottom
if ( isset($_SESSION['debug_log']) ) {
    echo("<p>Grade send log below:</p>\n");
    $OUTPUT->dumpDebugArray($_SESSION['debug_log']);
    unset($_SESSION['debug_log']);
    echo("<p></p>\n");
}

$OUTPUT->footerStart();
?>
<script src="<?= U::get_rest_parent() ?>/static/prism.js" type="text/javascript"></script>
<?php
load_htmls();
$OUTPUT->footerEnd();
