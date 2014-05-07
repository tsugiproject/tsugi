<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/core/gradebook/lib.php";

// Sanity checks
$LTI = lti_require_data(array('user_id', 'link_id', 'role','context_id'));
$instructor = is_instructor($LTI);
$user_id = $LTI['user_id'];

if ( ! isset($_POST['code']) ) {
    echo(json_encode(array("error" => "Missing code")));
    return;
}

// Check to see if the code actually changed
$code = $_POST['code'];
update_grade_json($pdo, array("code" => $code));
$_SESSION['pythonauto_lastcode'] = $code;

$retval = Array("status" => "success");
echo json_encode($retval);
