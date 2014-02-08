<?php
require_once "../../config.php";
require_once $CFG->dirroot."/db.php";
session_start();

// Sanity checks
$LTI = requireData(array('user_id', 'link_id', 'role','context_id'));
$instructor = isInstructor($LTI);
$user_id = $LTI['user_id'];

$grade = 1.0;

$code = $_POST['code'];
$json = json_encode(array("code" => $code));

$retval = sendGradeDetail($grade, null, $json, false, $db, false);
if ( is_string($retval) ) {
    echo json_encode(Array("status" => "failure", "detail" => $retval));
    return;
}

$retval = Array("status" => "success");
if ( isset($_GET["debug"]) ) $retval['debug'] = $debug;
echo json_encode($retval);
?>
