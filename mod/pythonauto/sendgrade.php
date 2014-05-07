<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/core/gradebook/lib.php";

// Sanity checks
$LTI = lti_require_data(array('user_id', 'link_id', 'role','context_id'));
$instructor = is_instructor($LTI);
$user_id = $LTI['user_id'];

$grade = 1.0;

$code = $_POST['code'];
update_grade_json($pdo, array("code" => $code));
$_SESSION['pythonauto_lastcode'] = $code;

$debug_log = array();
$retval = send_grade_detail($grade, $debug_log, $pdo, false);
if ( is_string($retval) ) {
    echo json_encode(Array("status" => "failure", "detail" => $retval, "debug_log" => $debug_log));
    return;
}

$retval = Array("status" => "success", "debug_log" => $debug_log);
echo json_encode($retval);
