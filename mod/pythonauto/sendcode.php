<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";

// Sanity checks
$LTI = requireData(array('user_id', 'link_id', 'role','context_id'));
$instructor = isInstructor($LTI);
$user_id = $LTI['user_id'];

$grade = 1.0;

if ( ! isset($_POST['code']) ) {
    echo(json_encode(array("error" => "Missing code")));
    return;
}
$code = $_POST['code'];
$json = json_encode(array("code" => $code));

$retval = updateGradeJSON($pdo, $json);

$retval = Array("status" => "success");
echo json_encode($retval);
