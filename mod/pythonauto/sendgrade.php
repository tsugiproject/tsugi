<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/core/gradebook/lib.php";

// Sanity checks
$LTI = ltiRequireData(array('user_id', 'link_id', 'role','context_id'));
$user_id = $USER->id;

$grade = 1.0;

$code = $_POST['code'];
gradeUpdateJson(array("code" => $code));
$_SESSION['pythonauto_lastcode'] = $code;

$debug_log = array();
$retval = gradeSendDetail($grade, $debug_log, false);
if ( is_string($retval) ) {
    echo json_encode(Array("status" => "failure", "detail" => $retval, "debug_log" => $debug_log));
    return;
}

$retval = Array("status" => "success", "debug_log" => $debug_log);
echo json_encode($retval);
