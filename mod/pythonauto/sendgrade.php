<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/core/gradebook/lib.php";

// Sanity checks
$LTI = requireData(array('user_id', 'link_id', 'role','context_id'));
$instructor = isInstructor($LTI);
$user_id = $LTI['user_id'];

$grade = 1.0;

$code = $_POST['code'];
updateGradeJSON($pdo, array("code" => $code));
$_SESSION['pythonauto_lastcode'] = $code;

$debuglog = array();
$retval = sendGradeDetail($grade, $debuglog, $pdo, false);
if ( is_string($retval) ) {
    echo json_encode(Array("status" => "failure", "detail" => $retval, "debuglog" => $debuglog));
    return;
}

$retval = Array("status" => "success", "debuglog" => $debuglog);
echo json_encode($retval);
