<?php
require_once "../../config.php";
require_once $CFG->dirroot."/db.php";
require_once $CFG->dirroot."/lib/lti_util.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once "peer_util.php";

session_start();

// Sanity checks
$LTI = requireData(array('user_id', 'link_id', 'role','context_id'));
$instructor = isInstructor($LTI);
$p = $CFG->dbprefix;

// Check to see if we are updating the grade for the current 
// user or another
$user_id = $LTI['user_id'];
if ( isset($_REQUEST['user_id']) ) $user_id = $_REQUEST['user_id'];

// Model 
$row = loadAssignment($db, $LTI);
$assn_json = null;
$assn_id = false;
if ( $row !== false ) {
    $assn_json = json_decode($row['json']);
    $assn_id = $row['assn_id'];
}

if ( $assn_id == false ) {
    json_error('This assignment is not yet set up');
    return;
}

// Compute the user's grade
$grade = computeGrade($db, $assn_id, $assn_json, $user_id);
if ( $grade <= 0 ) {
    json_error('Nothing to grade for this user', $row);
    return;
}

// Lookup the result row if we are grading the non-current user
$result = false;
if ( $user_id != $LTI['user_id'] ) {
    $result = lookupResult($db, $LTI, $user_id);
}

// Send the grade
$status = sendGrade($grade, false, $db, $result);
if ( $status === true ) {
    if ( $user_id != $LTI['user_id'] ) {
        json_output(array("status" => $status, "detail" => $LastPOXGradeResponse));
    } else { 
        json_output(array("status" => $status, "grade" => $grade, "detail" => $LastPOXGradeResponse));
    }
} else { 
    json_error($status, $LastPOXGradeResponse);
}

