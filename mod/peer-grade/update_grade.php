<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
use \Tsugi\Grades\GradeUtil;
require_once "peer_util.php";

use \Tsugi\Core\LTIX;

// Sanity checks
$LTI = LTIX::requireData();
$p = $CFG->dbprefix;

// Check to see if we are updating the grade for the current
// user or another
$user_id = $USER->id;
if ( isset($_REQUEST['user_id']) ) $user_id = $_REQUEST['user_id'];

// Model
$row = loadAssignment($LTI);
$assn_json = null;
$assn_id = false;
if ( $row !== false ) {
    $assn_json = json_decode($row['json']);
    $assn_id = $row['assn_id'];
}

if ( $assn_id == false ) {
    jsonError('This assignment is not yet set up');
    return;
}

// Compute the user's grade
$grade = computeGrade($assn_id, $assn_json, $user_id);
if ( $grade <= 0 ) {
    jsonError('Nothing to grade for this user', $row);
    return;
}

// Lookup the result row if we are grading the non-current user
$result = false;
if ( $user_id != $USER->id ) {
    $result = lookupResult($LTI, $user_id);
}

// Send the grade
$debug_log = array();
$status = LTIX::gradeSend($grade, $result, $debug_log); // This is the slow bit

if ( $status === true ) {
    if ( $user_id != $USER->id ) {
        jsonOutput(array("status" => $status, "debug" => $debug_log));
    } else {
        jsonOutput(array("status" => $status, "grade" => $grade, "debug" => $debug_log));
    }
} else {
    jsonError($status, $debug_log);
}

