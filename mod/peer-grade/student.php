<?php
require_once "../../config.php";
require_once $CFG->dirroot."/db.php";
require_once $CFG->dirroot."/lib/lti_util.php";
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
$row = loadAssignment($db, $LTI);
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
$submit_row = loadSubmission($db, $assn_id, $user_id);
if ( $submit_row !== false ) {
    $submit_id = $submit_row['submit_id']+0;
}

// Handle incoming post to delete the entire submission
if ( isset($_POST['deleteSubmit']) && $submit_id != false ) {
    $stmt = pdoQueryDie($db,
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
$user_row = loadUserInfo($db, $user_id);

// Retrieve our grades...
$our_grades = false;
if ( $submit_id !== false ) {
    $stmt = pdoQueryDie($db,
        "SELECT grade_id, points, note FROM {$p}peer_grade 
            WHERE submit_id = :SID",
        array( ':SID' => $submit_id)
    );
    $our_grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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
    $stmt = pdoQueryDie($db,
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

// View 
headerContent();
?>
</head>
<body>
<?php
flashMessages();

if ( $user_row != false ) {
    echo("<p>".htmlent_utf8($user_row['displayname'])." (".htmlent_utf8($user_row['email']).")</p>\n");
}

if ( $submit_row === false ) {
    echo("<p>This student has not made a submission.</p>\n");
} else {
    $submit_json = json_decode($submit_row['json']);
    showSubmission($assn_json, $submit_json);
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
    echo("\n<tr><th>Points</th><th>Comments</th><th>Action</th></tr>\n");

    foreach ( $our_grades as $grade ) {
        echo("<tr><td>".$grade['points']."</td><td>".htmlent_utf8($grade['note'])."</td>".
        '<td> <form method="post"><input type="hidden" name="grade_id" value="'.$grade['grade_id'].'">'.
        '<input type="hidden" name="user_id" value="'.$user_id.'">'.
        '<input type="submit" name="doDelete" value="delete"></form></td>'.
        "</tr>\n");
    }
    echo("</table>\n");
}

?>
<form method="post">
<br/>
<input type=submit name=doCancel onclick="location='<?php echo(sessionize('admin.php'));?>'; return false;" value="Cancel">
</form>
<?

footerContent();
