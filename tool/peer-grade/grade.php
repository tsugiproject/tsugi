<?php
require_once "../config.php";
use \Tsugi\Blob\BlobUtil;

require_once "peer_util.php";

use \Tsugi\Util\U;
use \Tsugi\Core\Cache;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Result;
use \Tsugi\Grades\GradeUtil;

// Sanity checks
$LAUNCH = LTIX::requireData();
$p = $CFG->dbprefix;

$user_id = false;
$url_goback = 'index';
$url_stay = 'grade';
if ( isset($_GET['user_id']) ) {
    if ( ! $USER->instructor ) die("Only instructors can grade specific students'");
    $user_id = $_GET['user_id'];
    $url_goback = 'student.php?user_id='.$user_id;
    $url_stay = 'grade?user_id='.$user_id;
}

// Model
$row = loadAssignment();
$assn_json = null;
$assn_id = false;
if ( $row !== false ) {
    $assn_json = json_decode(upgradeSubmission($row['json']));
    $assn_id = $row['assn_id'];
}

if ( $assn_id == false ) {
    $_SESSION['error'] = 'This assignment is not yet set up';
    header( 'Location: '.addSession($url_goback) ) ;
    return;
}

// Handle the flag data
if ( isset($_POST['doFlag']) && isset($_POST['submit_id']) ) {

    $submit_id = $_POST['submit_id']+0;
    $stmt = $PDOX->queryDie(
        "INSERT INTO {$p}peer_flag
            (submit_id, user_id, note, created_at, updated_at)
            VALUES ( :SID, :UID, :NOTE, NOW(), NOW())
            ON DUPLICATE KEY UPDATE note = :NOTE, updated_at = NOW()",
        array(
            ':SID' => $submit_id,
            ':UID' => $USER->id,
            ':NOTE' => $_POST['note'])
    );
    $_SESSION['success'] = "Flagged for the instructor to examine, please continue grading.";
    header( 'Location: '.addSession($url_stay) ) ;
    return;
}

// Handle the grade data
if ( isset($_POST['submit_id']) && isset($_POST['user_id']) ) {

    if ( (!isset($_SESSION['peer_submit_id'])) ||
        $_SESSION['peer_submit_id'] != $_POST['submit_id'] ) {

        unset($_SESSION['peer_submit_id']);
        $_SESSION['error'] = 'Error in submission id';
        header( 'Location: '.addSession($url_goback) ) ;
        return;
    }

    $points = null;
    if ( isset($_POST['points']) ) {
        if ( ! is_numeric($_POST['points']) ) {
            $_SESSION['error'] = 'Points must be numeric and between 0 and '.$assn_json->peerpoints;
            header( 'Location: '.addSession($url_stay) ) ;
            return;
        }

        $points = $_POST['points']+0;
        if ( $points < 0 || $points > $assn_json->peerpoints ) {
            $_SESSION['error'] = 'Points must be between 0 and '.$assn_json->peerpoints;
            header( 'Location: '.addSession($url_stay) ) ;
            return;
        }
    }

    // Check to see if user_id is correct for this submit_id
    $user_id = $_POST['user_id']+0;
    $submit_row = loadSubmission($assn_id, $user_id);
    if ( $submit_row === null || $submit_row['submit_id'] != $_POST['submit_id']) {
        $_SESSION['error'] = 'Mis-match between user_id and session_id';
        header( 'Location: '.addSession($url_goback) ) ;
        return;
    }

    $grade_count = loadMyGradeCount($assn_id);
    if ( $grade_count > $assn_json->maxassess && ! $USER->instructor ) {
        $_SESSION['error'] = 'You have already graded more than '.$assn_json->maxassess.' submissions';
        header( 'Location: '.addSession($url_goback) ) ;
        return;
    }

    unset($_SESSION['peer_submit_id']);
    $submit_id = $_POST['submit_id']+0;

    $stmt = $PDOX->queryReturnError(
        "INSERT INTO {$p}peer_grade
            (submit_id, user_id, points, note, created_at, updated_at)
            VALUES ( :SID, :UID, :POINTS, :NOTE, NOW(), NOW())
            ON DUPLICATE KEY UPDATE points = :POINTS, note = :NOTE, updated_at = NOW()",
        array(
            ':SID' => $submit_id,
            ':UID' => $USER->id,
            ':POINTS' => $points,
            ':NOTE' => $_POST['note'])
    );
    Cache::clear('peer_grade');
    if ( ! $stmt->success ) {
        $_SESSION['error'] = $stmt->errorImplode;
        header( 'Location: '.addSession($url_goback) ) ;
        return;
    }

    // Add this to student doing the grading peer_marks count
    // in case a submission is later deleted
    $stmt = $PDOX->queryReturnError(
        "UPDATE {$p}peer_submit
            SET peer_marks = peer_marks + 1
            WHERE assn_id = :AID AND user_id = :UID",
        array(
            ':AID' => $assn_id,
            ':UID' => $USER->id
        )
    );

    // Attempt to update the user's grade, may take a second..
    $grade = computeGrade($assn_id, $assn_json, $user_id);
    $_SESSION['success'] = 'Grade submitted';
    if ( $grade > 0 ) {
        $result = Result::lookupResultBypass($user_id);
        $old_grade = null;
        if ( $result && isset($result['grade']) ) {
            $old_grade = floatval($result['grade']);
        }
        $status = LTIX::gradeSend($grade, $result); // This is the slow bit

        if ( $status === true ) {
            $_SESSION['success'] = 'Grade submitted to server';
            // Send notification to the student whose grade was changed (only if grade actually changed)
            // Use LTI launch_presentation_return_url if available, otherwise fall back to index
            $notification_url = null;
            if ( is_object($LAUNCH) && method_exists($LAUNCH, 'returnUrl') ) {
                $notification_url = $LAUNCH->returnUrl();
            }
            if ( empty($notification_url) ) {
                $notification_url = addSession('index');
            }
            notifyGradeChange($user_id, $grade, $old_grade, $assn_json->title ?? null, $notification_url);
        } else {
            error_log("Problem sending grade ".$status);
        }
    }
    header( 'Location: '.addSession($url_goback) ) ;
    return;
}
unset($_SESSION['peer_submit_id']);

$submit_id = false;
$submit_json = null;
if ( $user_id === false ) {
    // Load the the 10 oldest ungraded submissions
    $to_grade = loadUngraded($assn_id);
    if ( count($to_grade) < 1 ) {
        $_SESSION['success'] = 'There are no submissions to grade';
        header( 'Location: '.addSession($url_goback) ) ;
        return;
    }

    // Grab the oldest one
    $to_grade_row = $to_grade[0];
    $user_id = $to_grade_row['user_id'];
}

$submit_row = loadSubmission($assn_id, $user_id);
if ( $submit_row !== null ) {
    $submit_id = $submit_row['submit_id'];
    $submit_json = json_decode($submit_row['json']);
}

if ( $submit_json === null ) {
    $_SESSION['error'] = 'Unable to load submission '.$user_id;
    header( 'Location: '.addSession($url_goback) ) ;
    return;
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
$OUTPUT->flashMessages();

echo("<p><b>Please be careful, you cannot revise grades after you submit them.</b></p>\n");

echo('<div style="border: 1px solid black; padding:3px">');
echo("<p><h4>".$assn_json->title."</h4></p>\n");
echo('<p>'.htmlentities($assn_json->description)."</p>\n");
echo('</div>');
showSubmission($assn_json, $submit_json, $assn_id, $user_id);
echo('<p>'.htmlentities($assn_json->grading)."</p>\n");
?>
<form method="post">
<input type="hidden" value="<?php echo($submit_id); ?>" name="submit_id">
<input type="hidden" value="<?php echo($user_id); ?>" name="user_id">
<?php if ( $assn_json->peerpoints > 0 ) { ?>
<label for="grade_points">Points (<?= $assn_json->peerpoints ?> for full credit)</label>
<input type="number" id="grade_points" min="0" max="<?php echo($assn_json->peerpoints); ?>" name="points"><br/>
<?php } elseif ( $assn_json->rating > 0 ) { ?>
<label for="grade_points">Rating (1-<?= $assn_json->rating ?>)</label>
<input type="number" id="grade_points" min="0" max="<?php echo($assn_json->rating); ?>" name="points"><br/>
<?php } ?>
<label for="grade_note">Comments</label><br/>
<textarea rows="5" cols="60" name="note" id="grade_note"></textarea><br/>
<input type="submit" value="Grade" class="btn btn-primary">
<?php   if ( $assn_json->flag ) { ?>
<button type="button" id="showFlagBtn" onclick="$('#flagform').toggle(); $('#showFlagBtn').attr('aria-expanded', $('#flagform').is(':visible')); return false;" class="btn btn-danger" aria-controls="flagform" aria-expanded="false">Flag</button>
<?php } ?>
<button type="button" onclick="location='<?php echo(addSession($url_goback));?>'; return false;" class="btn btn-default">Cancel</button>
</form>
<?php   if ( $assn_json->flag ) { ?>
<form method="post" id="flagform" style="display:none">
<p>Please be considerate when flagging an item.  Only use
flagging when instructor attention is needed.</p>
<input type="hidden" value="<?php echo($submit_id); ?>" name="submit_id">
<input type="hidden" value="<?php echo($user_id); ?>" name="user_id">
<input type="hidden" value="1" name="doFlag">
<label for="flag_note">Describe why you are flagging this submission for the instructor.</label><br/>
<textarea rows="5" cols="60" name="note" id="flag_note"></textarea><br/>
<input type="submit" name="flagSubmit"
    onclick="return confirm('Are you sure you want to bring this student submission to the attention of the instructor?');"
    value="Submit To Instructor" class="btn btn-primary">
<button type="button" onclick="$('#flagform').toggle(); $('#showFlagBtn').attr('aria-expanded', $('#flagform').is(':visible')); return false;" class="btn btn-default">Cancel Flag</button>
</form>
<?php } ?>
<?php

$_SESSION['peer_submit_id'] = $submit_id;  // Our CSRF touch

$OUTPUT->footerStart();
?>
<script src="<?= U::get_rest_parent() ?>/static/prism.js" type="text/javascript"></script>
<?php
load_htmls();
$OUTPUT->footerEnd();
