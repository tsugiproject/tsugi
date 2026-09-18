<?php
require_once "../config.php";
require_once "peer_util.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Core\Result;
use \Tsugi\Grades\GradeUtil;

// Sanity checks
$LAUNCH = LTIX::requireData();
$p = $CFG->dbprefix;

// Check to see if we are updating the grade for the current
// user or another
$user_id = $USER->id;
if ( isset($_REQUEST['user_id']) ) $user_id = $_REQUEST['user_id'];

// Model
$row = loadAssignment();
$assn_json = null;
$assn_id = false;
if ( $row !== false ) {
    $assn_json = json_decode(upgradeSubmission($row['json']));
    $assn_id = $row['assn_id'];
}

if ( $assn_id == false ) {
    $OUTPUT->jsonError('This assignment is not yet set up');
    return;
}

// Compute the user's grade (0.0-1.0, or -1 if there is no submission)
$grade = computeGrade($assn_id, $assn_json, $user_id);
if ( $grade < 0 ) {
    $OUTPUT->jsonError('Nothing to grade for this user', $row);
    return;
}
// 0 is a real score (submitted, nothing earned yet). Show it; do not pass 0% back to the LMS.
if ( $grade == 0 ) {
    $payload = array("status" => true, "grade" => 0);
    $updated_at = loadResultUpdatedAt($user_id);
    if ( $updated_at ) $payload['updated_at'] = $updated_at;
    $OUTPUT->jsonOutput($payload);
    return;
}

// Lookup the result row if we are grading the non-current user
$result = false;
$old_grade = null;
if ( $user_id != $USER->id ) {
    $result = Result::lookupResultBypass($user_id);
} else {
    // Get the old grade from the current user's result
    $result = Result::lookupResultBypass($user_id);
}
// Get old grade if available
if ( $result && isset($result['grade']) ) {
    $old_grade = floatval($result['grade']);
}

// Send the grade
$debug_log = array();
$status = LTIX::gradeSend($grade, $result, $debug_log); // This is the slow bit

if ( $status === true ) {
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

    $payload = array("status" => $status, "debug" => $debug_log);
    if ( $user_id == $USER->id ) $payload['grade'] = $grade;
    $updated_at = loadResultUpdatedAt($user_id);
    if ( $updated_at ) $payload['updated_at'] = $updated_at;
    $OUTPUT->jsonOutput($payload);
} else {
    $OUTPUT->jsonError($status, $debug_log);
}

