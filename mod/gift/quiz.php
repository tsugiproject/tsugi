<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once "parse.php";

use \Tsugi\Core\LTIX;

$LTI = LTIX::requireData();
$gift = $LINK->getJson();

$OUTPUT->headerJson();

// Check if we got any GIFT
if ( $gift === false || strlen($gift) < 1 ) {
    echo ( json_encode(array("status" => "failure", "message" => "This quiz has not yet been configured")));
    return;
}

// parse the GIFT questions
$questions = array();
$errors = array();
parse_gift($gift, $questions, $errors);

// Both reduce the visible bits and score the quiz if a submission is present
$submit = isset($_SESSION['gift_submit']) ? $_SESSION['gift_submit'] : array();
$retval = make_quiz($submit, $questions, $errors);

echo(json_encode($retval));
