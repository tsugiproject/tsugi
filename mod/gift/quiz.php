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

$retval = array("status" => "failure", "errors" => $errors);
if ( count($questions) < 1 ) {
    $retval["message"] = "No questions found";
    echo ( json_encode($retval));
    return;
}

$retval['status'] = 'success';
$safe = array();
// Filter out questions for the user-visible stuff
foreach($questions as $question) {
    $nq = new stdClass();
    if ( ! isset($question->question) ) continue;
    if ( ! isset($question->type) ) continue;
    $nq->question = $question->question;
    $t = $question->type;
    $nq->type = $t;
    if ( isset($question->name) ) $nq->name = $question->name;
    if ( ( $t == 'multiple_choice_question' || $t == 'multiple_answers_question' ) &&
        isset($question->parsed_answer) && is_array($question->parsed_answer) ) {
        $answers = array();
        foreach($question->parsed_answer as $answer ) {
            if ( ! is_array($answer) ) continue;
            if ( count($answer) != 3 ) continue;
            $answers[] = $answer[1];
        }
        $nq->answers = $answers;
    }
    $safe[] = $nq;
}

$retval["questions"] = $safe;

echo(json_encode($retval));
