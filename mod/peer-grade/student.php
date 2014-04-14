<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once $CFG->dirroot."/core/blob/blob_util.php";
require_once "peer_util.php";

// Sanity checks
$LTI = requireData(array('user_id', 'link_id', 'role','context_id'));
$instructor = isInstructor($LTI);
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
$user_row = loadUserInfo($pdo, $user_id);

// Handle incoming post to delete the entire submission
if ( isset($_POST['deleteSubmit']) && $submit_id != false ) {
    $retval = mailDeleteSubmit($pdo, $user_id, $assn_json);
    $stmt = pdoQueryDie($pdo,
        "DELETE FROM {$p}peer_submit 
            WHERE submit_id = :SID",
        array( ':SID' => $submit_id)
    );
    $msg = "Deleted submission for $user_id";
    if ( $retval ) $msg .= ', e-mail notice sent.';
    error_log($msg);
    cacheClear('peer_grade');
    cacheClear('peer_submit');
    $msg = "Submission deleted";
    if ( $retval ) $msg .= ', e-mail notice sent.';
    $_SESSION['success'] = $msg;
    header( 'Location: '.sessionize('admin.php') ) ;
    return;
}

// Compute grade
$computed_grade = computeGrade($pdo, $assn_id, $assn_json, $user_id);
if ( isset($_POST['resendSubmit']) ) {
    $result = lookupResult($pdo, $LTI, $user_id);
    // Force a resend
    $_SESSION['lti']['grade'] = -1;  // Force a resend
    $result['grade'] = -1;
    $debuglog = array();
    $status = sendGradeDetail($computed_grade, null, null, $debuglog, $pdo, $result); // This is the slow bit
    if ( $status === true ) {
        $_SESSION['success'] = 'Grade submitted to server';
    } else {
        error_log("Problem sending grade ".$status);
        $_SESSION['error'] = 'Error: '.$status;
    }
    $_SESSION['debuglog'] = $debuglog;
    header( 'Location: '.sessionize('student.php?user_id='.$user_id) ) ;
    return;
}

// Retrieve our grades...
$our_grades = retrieveSubmissionGrades($pdo, $submit_id);

// Handle incoming post to delete a grade entry
if ( isset($_POST['grade_id']) && isset($_POST['deleteGrade']) ) {
    // Make sure this is deleting a legit grading entry...
    $found = false;
    if ( $our_grades != false ) foreach ( $our_grades as $grade ) {
        if ($_POST['grade_id'] == $grade['grade_id'] ) $found = true;
    }
    if ( ! $found ) {
        $_SESSION['error'] = "Grade entry not found.";
        header( 'Location: '.sessionize('index.php') ) ;
        return;
    }
    $stmt = pdoQueryDie($pdo,
        "DELETE FROM {$p}peer_grade 
            WHERE grade_id = :GID",
        array( ':GID' => $_POST['grade_id'])
    );
    cacheClear('peer_grade');
    error_log("Instructor deleted grade entry for ".$user_id);
    $_SESSION['success'] = "Grade entry deleted.";
    header( 'Location: '.sessionize('student.php?user_id='.$user_id) ) ;
    return;
}

// Retrieve our flags...
$our_flags = false;
if ( $submit_id !== false ) {
    $stmt = pdoQueryDie($pdo,
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
    $stmt = pdoQueryDie($pdo,
        "DELETE FROM {$p}peer_flag 
            WHERE flag_id = :FID",
        array( ':FID' => $_POST['flag_id'])
    );
    cacheClear('peer_flag');
    cacheClear('peer_grade');
    error_log("Instructor deleted flag=".$_POST['flag_id']." for ".$user_id);
    $_SESSION['success'] = "Flag entry deleted.";
    header( 'Location: '.sessionize('student.php?user_id='.$user_id) ) ;
    return;
}

// View 
headerContent();
startBody();
flashMessages();

if ( isset($_SESSION['debuglog']) ) {
    echo("<p>Grade send log below:</p>\n");
    dumpGradeDebug($_SESSION['debuglog']);
    unset($_SESSION['debuglog']);
    echo("<p></p>\n");
}

if ( $user_row != false ) {
    echo("<p>".htmlent_utf8($user_row['displayname'])." (".htmlent_utf8($user_row['email']).")</p>\n");
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
        <input type="submit" name="deleteSubmit" value="Complete Delete">
        <input type="submit" name="doCancel" value="Cancel Delete"
            onclick="location=\''.sessionize('student.php?user_id='.$user_id).'\'; return false;">
        </form>');
} else {
    echo('<p><a href="student.php?delete=yes&user_id='.$user_id.'">Delete this 
        submission and grades (allows student to resubmit)</a></p>'."\n");
}

echo("<p>Computed grade: ".$computed_grade."<br/>\n");
echo("Stored grade: ".$LTI['grade']."</p>\n");

if ( isset($_GET['resend']) ) {
    echo('<form method="post">
        <input type="hidden" name="user_id" value="'.$user_id.'">
        <input type="submit" name="resendSubmit" value="Resend the Grade">
        <input type="submit" name="doCancel" value="Cancel Resend"
            onclick="location=\''.sessionize('student.php?user_id='.$user_id).'\'; return false;">
        </form>');
} else {
    echo('<p><a href="student.php?resend=yes&user_id='.$user_id.'">
        Resend computed grade to the LMS</a></p>');
}

if ( count($our_grades) < 1 ) {
    echo("<p>No one has graded this submission yet.</p>");
} else {
    echo("<p>Grading activity:</p>");
    echo('<table border="1" style="margin:3px">');
    echo("\n<tr><th>User</th><th>Email</th><th>Points</th>
        <th>Comments</th><th>Grade Id</th><th>Action</th></tr>\n");

    foreach ( $our_grades as $grade ) {
        echo("<tr>
        <td>".htmlent_utf8($grade['displayname'])."</td>
        <td>".htmlent_utf8($grade['email'])."</td>
        <td>".$grade['points']."</td><td>".htmlent_utf8($grade['note'])."</td>
        <td>".htmlent_utf8($grade['grade_id'])."</td>".
        '<td> <form method="post"><input type="hidden" 
            name="grade_id" value="'.$grade['grade_id'].'">
        <input type="hidden" name="user_id" value="'.$user_id.'">
        <input type="submit" name="deleteGrade" value="delete"></form></td>'.
        "\n</tr>\n");
    }
    echo("</table>\n");
}

if ( $our_flags !== false && count($our_flags) > 0 ) {
    echo('<p style="color:red">This entry has the following flags:<br/>'."\n");
    echo('<table border="1"><tr>');
    echo("\n<th>Name</th><th>Email</th><th>Flag_Id</th><th>Comment</th><th>Time</th><th>Action</th></tr>");
    foreach ( $our_flags as $flag ) {
        echo("\n<tr>");
        echo("<td>".htmlent_utf8($flag['displayname'])."</td>\n");
        echo("<td>".htmlent_utf8($flag['email'])."</td>\n");
        echo("<td>".htmlent_utf8($flag['flag_id'])."</td>\n");
        echo("<td>".htmlent_utf8($flag['note'])."</td>\n");
        echo("<td>".htmlent_utf8($flag['updated_at'])."</td>\n");
        echo('<td> <form method="post"><input type="hidden" 
            name="flag_id" value="'.$flag['flag_id'].'">
        <input type="submit" name="deleteFlag" value="delete"></form></td>');
        echo("</tr>\n");
    }
    echo("</table>\n");
    echo("</p>\n");
}
?>
<form method="post">
<br/>
<input type="submit" name="doDone" onclick="location='<?php echo(sessionize('admin.php'));?>'; return false;" value="Done">
</form>
<?php

footerContent();
