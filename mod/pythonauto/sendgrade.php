<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/core/gradebook/lib.php";

use \Tsugi\Core\LTIX;

// Sanity checks
$LTI = LTIX::requireData();
$user_id = $USER->id;

$grade = 1.0;

$code = $_POST['code'];
gradeUpdateJson(array("code" => $code));
$_SESSION['pythonauto_lastcode'] = $code;

$debug_log = array();
$retval = LTIX::gradeSend($grade, false, $debug_log);
if ( is_string($retval) ) {
    echo json_encode(Array("status" => "failure", "detail" => $retval, "debug_log" => $debug_log));
    return;
}

$retval = Array("status" => "success", "debug_log" => $debug_log);
echo json_encode($retval);
