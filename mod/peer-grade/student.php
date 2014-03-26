<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once $CFG->dirroot."/core/blob/blob_util.php";
require_once "peer_util.php";

session_start();

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

// Handle incoming post to delete the entire submission
if ( isset($_POST['deleteSubmit']) && $submit_id != false ) {
    $stmt = pdoQueryDie($pdo,
        "DELETE FROM {$p}peer_submit 
            WHERE submit_id = :SID",
        array( ':SID' => $submit_id)
    );
    error_log("Instructor deleted submission for ".$user_id);
    cacheClear('peer_grade');
    cacheClear('peer_submit');
    $_SESSION['success'] = "Submit entry deleted.";
    header( 'Location: '.sessionize('admin.php') ) ;
    return;
}

// Load user info
$user_row = loadUserInfo($pdo, $user_id);

// Retrieve our grades...
$our_grades = retrieveSubmissionGrades($pdo, $submit_id);

// Handle incoming post to delete a grade entry
if ( isset($_POST['grade_id']) && isset($_POST['doDelete']) ) {
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

// View 
headerContent();
startBody();
flashMessages();

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
        <input type="submit" name="doDelete" value="delete"></form></td>'.
        "\n</tr>\n");
    }
    echo("</table>\n");
}

if ( $our_flags !== false && count($our_flags) > 0 ) {
    echo('<p style="color:red">This entry has the following flags:<br/>'."\n");
    echo('<table border="1"><tr>');
    echo("\n<th>Name</th><th>Email</th><th>Grade_Id</th><th>Comment</th><th>Time</th></tr>");
    foreach ( $our_flags as $flag ) {
        echo("\n<tr>");
        echo("<td>".htmlent_utf8($flag['displayname'])."</td>\n");
        echo("<td>".htmlent_utf8($flag['email'])."</td>\n");
        echo("<td>".htmlent_utf8($flag['grade_id'])."</td>\n");
        echo("<td>".htmlent_utf8($flag['note'])."</td>\n");
        echo("<td>".htmlent_utf8($flag['updated_at'])."</td>\n");
        echo("</tr>\n");
    }
    echo("</table>\n");
    echo("</p>\n");
}

?>
<form method="post">
<br/>
<input type=submit name=doCancel onclick="location='<?php echo(sessionize('admin.php'));?>'; return false;" value="Cancel">
</form>
<?php

footerContent();
