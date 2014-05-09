<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once $CFG->dirroot."/core/gradebook/lib.php";
require_once $CFG->dirroot."/core/blob/blob_util.php";
require_once "peer_util.php";

// Sanity checks
$LTI = lti_require_data(array('user_id', 'link_id', 'role','context_id'));
$instructor = is_instructor($LTI);
if ( ! $instructor ) die("Requires instructor role");
$p = $CFG->dbprefix;

if ( !isset($_REQUEST['user_id']) ) die("user_id parameter required");
$user_id = $_REQUEST['user_id'];

// Load the assignment
$row = loadAssignment($pdo, $LTI);
$assn_json = null;
$assn_id = false;
if ( $row !== false ) {
    $assn_json = json_decode($row['json']);
    $assn_id = $row['assn_id'];
}

if ( $assn_id === false ) {
    $_SESSION['error'] = "This assignment is not yet set up.";
    header( 'Location: '.sessionize('index.php') ) ;
    return;
}

$submit_id = false;
$submit_row = loadSubmission($pdo, $assn_id, $user_id);
if ( $submit_row !== false ) {
    $submit_id = $submit_row['submit_id']+0;
}

// Load user info
$user_row = load_user_info($pdo, $user_id);

// Handle incoming post to delete the entire submission
if ( isset($_POST['deleteSubmit']) ) {
    if ( $submit_id == false ) {
        $_SESSION['error'] = "Could not load submission.";
        header( 'Location: '.sessionize('index.php') ) ;
        return;
    }
    $note = isset($_POST['deleteNote']) ? $_POST['deleteNote'] : '';
    $retval = mailDeleteSubmit($pdo, $user_id, $assn_json, $note);
    $stmt = pdo_query_die($pdo,
        "DELETE FROM {$p}peer_submit
            WHERE submit_id = :SID",
        array( ':SID' => $submit_id)
    );
    $msg = "Deleted submission for $user_id";
    if ( $retval ) $msg .= ', e-mail notice sent.';
    error_log($msg);
    cache_clear('peer_grade');
    cache_clear('peer_submit');
    $msg = "Submission deleted";
    if ( $retval ) $msg .= ', e-mail notice sent.';
    $_SESSION['success'] = $msg;
    header( 'Location: '.sessionize('admin.php') ) ;
    return;
}

// Compute grade
$computed_grade = computeGrade($pdo, $assn_id, $assn_json, $user_id);
if ( isset($_POST['resendSubmit']) ) {
    $result = lookup_result($pdo, $LTI, $user_id);
    // Force a resend
    $_SESSION['lti']['grade'] = -1;  // Force a resend
    $result['grade'] = -1;
    $debug_log = array();
    $status = send_grade_detail($computed_grade, $debug_log, $pdo, $result); // This is the slow bit
    if ( $status === true ) {
        $_SESSION['success'] = 'Grade submitted to server';
    } else {
        error_log("Problem sending grade ".$status);
        $_SESSION['error'] = 'Error: '.$status;
    }
    $_SESSION['debug_log'] = $debug_log;
    header( 'Location: '.sessionize('student.php?user_id='.$user_id) ) ;
    return;
}

// Retrieve our grades...
$grades_received = retrieveSubmissionGrades($pdo, $submit_id);

// Handle incoming post to delete a grade entry
if ( isset($_POST['grade_id']) && isset($_POST['deleteGrade']) ) {
    // Make sure this is deleting a legit grading entry...
    $found = false;
    if ( $grades_received != false ) foreach ( $grades_received as $grade ) {
        if ($_POST['grade_id'] == $grade['grade_id'] ) $found = true;
    }
    if ( ! $found ) {
        $_SESSION['error'] = "Grade entry not found.";
        header( 'Location: '.sessionize('index.php') ) ;
        return;
    }
    $stmt = pdo_query_die($pdo,
        "DELETE FROM {$p}peer_grade
            WHERE grade_id = :GID",
        array( ':GID' => $_POST['grade_id'])
    );
    cache_clear('peer_grade');
    error_log("Instructor deleted grade entry for ".$user_id);
    $_SESSION['success'] = "Grade entry deleted.";
    header( 'Location: '.sessionize('student.php?user_id='.$user_id) ) ;
    return;
}

// Retrieve our flags...
$our_flags = false;
if ( $submit_id !== false ) {
    $stmt = pdo_query_die($pdo,
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
        header( 'Location: '.sessionize('index.php') ) ;
        return;
    }
    $stmt = pdo_query_die($pdo,
        "DELETE FROM {$p}peer_flag
            WHERE flag_id = :FID",
        array( ':FID' => $_POST['flag_id'])
    );
    cache_clear('peer_flag');
    cache_clear('peer_grade');
    error_log("Instructor deleted flag=".$_POST['flag_id']." for ".$user_id);
    $_SESSION['success'] = "Flag entry deleted.";
    header( 'Location: '.sessionize('student.php?user_id='.$user_id) ) ;
    return;
}

// Reteieve the grades that we have given
$grades_given = retrieveGradesGiven($pdo, $assn_id, $user_id);

// View
html_header_content();
html_start_body();
html_flash_messages();

if ( isset($_SESSION['debug_log']) ) {
    echo("<p>Grade send log below:</p>\n");
    dumpGradeDebug($_SESSION['debug_log']);
    unset($_SESSION['debug_log']);
    echo("<p></p>\n");
}

$user_display = false;
if ( $user_row != false ) {
    $user_display = htmlent_utf8($user_row['displayname'])." (".htmlent_utf8($user_row['email']).")";
    echo("<p>".$user_display."</p>\n");
}

if ( $submit_row === false ) {
    echo("<p>This student has not made a submission.</p>\n");
} else {
    $submit_json = json_decode($submit_row['json']);
    showSubmission($LTI, $assn_json, $submit_json);
}

echo('<p><a href="grade.php?user_id='.$user_id.'">Grade this student</a></p>'."\n");
if ( isset($_GET['delete']) ) {
    echo('<form method="post">
        <input type="hidden" name="user_id" value="'.$user_id.'">
        <label for="deleteNote">Enter an optional note to send to the student</label><br/>
        <textarea name="deleteNote" id="deleteNote" style="width:60%" rows="5">
        </textarea><br/>
        <input type="submit" name="deleteSubmit" value="Complete Delete" class="btn btn-danger">
        <input type="submit" name="doCancel" value="Cancel Delete" class="btn btn-normal"
            onclick="location=\''.sessionize('student.php?user_id='.$user_id).'\'; return false;">
        </form>');
} else {
    echo('<p><a href="student.php?delete=yes&user_id='.$user_id.'">Delete this
        submission and grades (allows student to resubmit)</a></p>'."\n");
}

echo("<p>Computed grade: ".$computed_grade."<br/>\n");

if ( isset($_GET['resend']) ) {
    echo('<form method="post">
        <input type="hidden" name="user_id" value="'.$user_id.'">
        <input type="submit" name="resendSubmit" value="Resend the Grade" class="btn btn-warning">
        <input type="submit" name="doCancel" value="Cancel Resend" class="btn btn-normal"
            onclick="location=\''.sessionize('student.php?user_id='.$user_id).'\'; return false;">
        </form>');
} else {
    echo('<p><a href="student.php?resend=yes&user_id='.$user_id.'">
        Resend computed grade to the LMS</a></p>');
}

if ( $user_display !== false ) $user_display = " by ".$user_display;

if ( $our_flags !== false && count($our_flags) > 0 ) {
    echo("<p style=\"color:red\">This entry $user_display has the following flags:</p>\n");
    echo('<div style="margin:3px;">');
    echo('<table border="1" class="table table-hover table-condensed table-responsive"><tr>');
    echo("\n<th>Flagged By</th><th>Email</th><th>Comment</th><th>Time</th><th>Action</th></tr>");
    foreach ( $our_flags as $flag ) {
        echo("\n<tr>");
        echo("<td>".htmlent_utf8($flag['displayname'])."</td>\n");
        echo("<td>".htmlent_utf8($flag['email'])."</td>\n");
        echo("<td>".htmlent_utf8($flag['note'])."</td>\n");
        echo("<td>".htmlent_utf8($flag['updated_at'])."</td>\n");
        echo('<td> <form method="post"><input type="hidden"
            name="flag_id" value="'.$flag['flag_id'].'">
        <input type="submit" name="deleteFlag" value="delete" class="btn btn-danger"></form></td>');
        echo("</tr>\n");
    }
    echo("</table>\n");
    echo("</div>\n");
}

if ( $grades_received === false || count($grades_received) < 1 ) {
    echo("<p>No one has graded this submission $user_display.</p>");
} else {
    echo("<p>Grades Received$user_display:</p>");
    echo('<div style="margin:3px;">');
    echo('<table border="1" class="table table-hover table-condensed table-responsive">');
    echo("\n<tr><th>User</th><th>Email</th><th>Points</th>
        <th>Comments</th><th>Action</th></tr>\n");

    foreach ( $grades_received as $grade ) {
        echo("<tr>
        <td>".htmlent_utf8($grade['displayname'])."</td>
        <td>".htmlent_utf8($grade['email'])."</td>
        <td>".$grade['points']."</td><td>".htmlent_utf8($grade['note'])."</td>".
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
    echo("<p>Nothing has been graded $user_display yet.</p>");
} else {
    echo("<p>Grades Given$user_display:</p>");
    echo('<div style="margin:3px;">');
    echo('<table border="1" class="table table-hover table-condensed table-responsive">');
    echo("\n<tr><th>User</th><th>Email</th><th>Points</th>
        <th>Comments</th><th>Action</th></tr>\n");

    foreach ( $grades_given as $grade ) {
        echo("<tr>
        <td>".htmlent_utf8($grade['displayname'])."</td>
        <td>".htmlent_utf8($grade['email'])."</td>
        <td>".$grade['points']."</td><td>".htmlent_utf8($grade['note'])."</td>".
        '<td> <form method="post"><input type="hidden"
            name="grade_id" value="'.$grade['grade_id'].'">
        <input type="hidden" name="user_id" value="'.$user_id.'">
        <input type="submit" name="deleteGrade" value="delete" class="btn btn-danger"></form></td>'.
        "\n</tr>\n");
    }
    echo("</table>\n");
    echo("</div>\n");
}

?>
<form method="post">
<br/>
<input type="submit" name="doDone" class="btn btn-success"
onclick="location='<?php echo(sessionize('admin.php'));?>'; return false;" value="Done">
</form>
<?php

html_footer_content();
