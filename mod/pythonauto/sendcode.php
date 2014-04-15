<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";

// Sanity checks
$LTI = requireData(array('user_id', 'link_id', 'role','context_id'));
$instructor = isInstructor($LTI);
$user_id = $LTI['user_id'];

if ( ! isset($_POST['code']) ) {
    echo(json_encode(array("error" => "Missing code")));
    return;
}
$code = $_POST['code'];

// Check to see if the code actually changed
if ( (!isset($_SESSION['pythonauto_lastcode'])) || $code != $_SESSION['pythonauto_lastcode'] ) {
    $json = json_encode(array("code" => $code));

    $retval = updateGradeJSON($pdo, $json);
    $_SESSION['pythonauto_lastcode'] = $code;
}

$retval = Array("status" => "success");
echo json_encode($retval);
