<?php
require_once "../config.php";

use \Tsugi\Core\LTIX;

header('Content-Type: application/json');

$LAUNCH = LTIX::requireData();

/*
 * Tools must set a budget before the browser can call this endpoint, e.g.:
 * $_SESSION['RECORD_ATTEMPT_GSRF'] = 50;
 */
if (!isset($_SESSION['RECORD_ATTEMPT_GSRF']) || !is_numeric($_SESSION['RECORD_ATTEMPT_GSRF']) || $_SESSION['RECORD_ATTEMPT_GSRF'] < 1) {
    echo json_encode(array("status" => "failure", "detail" => "Missing RECORD_ATTEMPT_GSRF token"));
    return;
}

$_SESSION['RECORD_ATTEMPT_GSRF'] = $_SESSION['RECORD_ATTEMPT_GSRF'] - 1;

global $RESULT;
if (!isset($RESULT) || !is_object($RESULT) || empty($RESULT->id)) {
    echo json_encode(array("status" => "failure", "detail" => "No LTI result in session"));
    return;
}

$RESULT->recordAttempt();
echo json_encode(array("status" => "success"));
