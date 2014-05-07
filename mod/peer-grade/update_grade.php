<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once $CFG->dirroot."/core/gradebook/lib.php";
require_once "peer_util.php";

// Sanity checks
$LTI = lti_require_data(array('user_id', 'link_id', 'role','context_id'));
$instructor = is_instructor($LTI);
$p = $CFG->dbprefix;

// Check to see if we are updating the grade for the current 
// user or another
$user_id = $LTI['user_id'];
if ( isset($_REQUEST['user_id']) ) $user_id = $_REQUEST['user_id'];

// Model 
$row = loadAssignment($pdo, $LTI);
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
$grade = computeGrade($pdo, $assn_id, $assn_json, $user_id);
if ( $grade <= 0 ) {
    json_error('Nothing to grade for this user', $row);
    return;
}

// Lookup the result row if we are grading the non-current user
$result = false;
if ( $user_id != $LTI['user_id'] ) {
    $result = lookup_result($pdo, $LTI, $user_id);
}

// Send the grade
$debug_log = array();
$status = send_grade_detail($grade, $debug_log, $pdo, $result); // This is the slow bit

if ( $status === true ) {
    if ( $user_id != $LTI['user_id'] ) {
        json_output(array("status" => $status, "debug" => $debug_log));
    } else { 
        json_output(array("status" => $status, "grade" => $grade, "debug" => $debug_log));
    }
} else { 
    json_error($status, $debug_log);
}

