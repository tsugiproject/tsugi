<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/core/gradebook/lib.php";


// Sanity checks
$LTI = requireData(array('user_id', 'link_id', 'role','context_id'));
$instructor = isInstructor($LTI);
$user_id = $LTI['user_id'];

if ( ! isset($_POST['code']) ) {
    echo(json_encode(array("error" => "Missing code")));
    return;
}

// Check to see if the code actually changed
$code = $_POST['code'];
updateJSON($pdo, array("code" => $code));
$_SESSION['pythonauto_lastcode'] = $code;

$retval = Array("status" => "success");
echo json_encode($retval);
