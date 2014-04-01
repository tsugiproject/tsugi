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
$p = $CFG->dbprefix;

$user_id = false;
$url_goback = 'index.php';
$url_stay = 'grade.php';
if ( isset($_GET['user_id']) ) {
    if ( ! $instructor ) die("Only instructors can grade specific students'");
    $user_id = $_GET['user_id'];
    $url_goback = 'student.php?user_id='.$user_id;
    $url_stay = 'grade.php?user_id='.$user_id;
}

// Model 
$row = loadAssignment($pdo, $LTI);
$assn_json = null;
$assn_id = false;
if ( $row !== false ) {
    $assn_json = json_decode($row['json']);
    $assn_id = $row['assn_id'];
}

if ( $assn_id == false ) {
    $_SESSION['error'] = 'This assignment is not yet set up';
    header( 'Location: '.sessionize($url_goback) ) ;
    return;
}

// Handle the flag data
if ( isset($_POST['doFlag']) && isset($_POST['submit_id']) ) {

    $submit_id = $_POST['submit_id']+0; 
    $stmt = pdoQueryDie($pdo,
        "INSERT INTO {$p}peer_flag 
            (submit_id, user_id, note, created_at, updated_at) 
            VALUES ( :SID, :UID, :NOTE, NOW(), NOW()) 
            ON DUPLICATE KEY UPDATE note = :NOTE, updated_at = NOW()",
        array(
            ':SID' => $submit_id,
            ':UID' => $LTI['user_id'],
            ':NOTE' => $_POST['note'])
    );
    $_SESSION['success'] = "Flagged for the instructor to examine";
    header( 'Location: '.sessionize($url_stay) ) ;
    return;
}

// Handle the grade data
if ( isset($_POST['points']) && isset($_POST['submit_id']) &&
    isset($_SESSION['peer_submit_id']) && isset($_POST['user_id']) ) {

    if ( $_SESSION['peer_submit_id'] != $_POST['submit_id'] ) {
        unset($_SESSION['peer_submit_id']);
        $_SESSION['error'] = 'Error in submission id';
        header( 'Location: '.sessionize($url_goback) ) ;
        return;
    }

    if ( strlen($_POST['points']) < 1 ) {
        $_SESSION['error'] = 'Points are required';
        header( 'Location: '.sessionize($url_stay) ) ;
        return;
    }
    
    $points = $_POST['points']+0;
    if ( $points < 0 || $points > $assn_json->maxpoints ) {
        $_SESSION['error'] = 'Points must be between 0 and '.$assn_json->maxpoints;
        header( 'Location: '.sessionize($url_stay) ) ;
        return;
    }

    // Check to see if user_id is correct for this submit_id
    $user_id = $_POST['user_id']+0; 
    $submit_row = loadSubmission($pdo, $assn_id, $user_id);
    if ( $submit_row === null || $submit_row['submit_id'] != $_POST['submit_id']) {
        $_SESSION['error'] = 'Mis-match between user_id and session_id';
        header( 'Location: '.sessionize($url_goback) ) ;
        return;
    }

    $grade_count = loadMyGradeCount($pdo, $LTI, $assn_id);
    if ( $grade_count > $assn_json->maxassess ) {
        $_SESSION['error'] = 'You have already graded more than '.$assn_json->maxassess.' submissions';
        header( 'Location: '.sessionize($url_goback) ) ;
        return;
    }

    unset($_SESSION['peer_submit_id']);
    $submit_id = $_POST['submit_id']+0; 

    $stmt = pdoQuery($pdo,
        "INSERT INTO {$p}peer_grade 
            (submit_id, user_id, points, note, created_at, updated_at) 
            VALUES ( :SID, :UID, :POINTS, :NOTE, NOW(), NOW()) 
            ON DUPLICATE KEY UPDATE points = :POINTS, note = :NOTE, updated_at = NOW()",
        array(
            ':SID' => $submit_id,
            ':UID' => $LTI['user_id'],
            ':POINTS' => $points,
            ':NOTE' => $_POST['note'])
    );
    cacheClear('peer_grade');
    if ( ! $stmt->success ) {
        $_SESSION['error'] = $stmt->errorImplode;
        header( 'Location: '.sessionize($url_goback) ) ;
        return;
    }

    // Attempt to update the user's grade, may take a second..
    $grade = computeGrade($pdo, $assn_id, $assn_json, $user_id);
    $_SESSION['success'] = 'Grade submitted';
    if ( $grade > 0 ) {
        $result = lookupResult($pdo, $LTI, $user_id);
        // $status = sendGrade($grade, false, $pdo, $result); // This is the slow bit
        $debuglog = array();
        $status = sendGradeDetail($computed_grade, null, null, $debuglog, $pdo, $result); // This is the slow bit

        if ( $status === true ) {
            $_SESSION['success'] = 'Grade submitted to server';
        } else {
            error_log("Problem sending grade ".$status);
        }
    }
    header( 'Location: '.sessionize($url_goback) ) ;
    return;
}
unset($_SESSION['peer_submit_id']);
 
$submit_id = false;
$submit_json = null;
if ( $user_id === false ) {
    // Load the the 10 oldest ungraded submissions
    $to_grade = loadUngraded($pdo, $LTI, $assn_id);
    if ( count($to_grade) < 1 ) {
        $_SESSION['success'] = 'There are no submissions to grade';
        header( 'Location: '.sessionize($url_goback) ) ;
        return;
    }

    // Grab the oldest one
    $to_grade_row = $to_grade[0];
    $user_id = $to_grade_row['user_id'];
}

$submit_row = loadSubmission($pdo, $assn_id, $user_id);
if ( $submit_row !== null ) {
    $submit_id = $submit_row['submit_id'];
    $submit_json = json_decode($submit_row['json']);
}

if ( $submit_json === null ) {
    $_SESSION['error'] = 'Unable to load submission '.$user_id;
    header( 'Location: '.sessionize($url_goback) ) ;
    return;
}

// View 
headerContent();
startBody();
flashMessages();

echo("<p><b>Please be careful, you cannot revise grades after you submit them.</b></p>\n");

showSubmission($LTI, $assn_json, $submit_json);
echo('<p>'.htmlent_utf8($assn_json->grading)."</p>\n");
?>
<form method="post">
<input type="hidden" value="<?php echo($submit_id); ?>" name="submit_id">
<input type="hidden" value="<?php echo($user_id); ?>" name="user_id">
<input type="number" min="0" max="<?php echo($assn_json->maxpoints); ?>" name="points">
(<?php echo($assn_json->maxpoints); ?> maximum points)<br/>
Comments:<br/>
<textarea rows="5" cols="60" name="note"></textarea><br/>
<input type="submit" value="Grade">
<input type="submit" name="showFlag" onclick="$('#flagform').toggle(); return false;" value="Flag">
<input type="submit" name="doCancel" onclick="location='<?php echo(sessionize($url_goback));?>'; return false;" value="Cancel">
</form>
<form method="post" id="flagform" style="display:none">
<p>Please be considerate when flagging an item.  It does not mean
that something is inappropriate - it simply brings the item to the 
attention of the instructor.</p>
<input type="hidden" value="<?php echo($submit_id); ?>" name="submit_id">
<input type="hidden" value="<?php echo($user_id); ?>" name="user_id">
<textarea rows="5" cols="60" name="note"></textarea><br/>
<input type="submit" name="doFlag" value="Submit To Instructor">
<input type="submit" name="doCancel" onclick="$('#flagform').toggle(); return false;" value="Cancel Flag">
</form>
<?php

$_SESSION['peer_submit_id'] = $submit_id;  // Our CSRF touch
footerContent();
