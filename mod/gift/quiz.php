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

// Load the gift submission
$submit = isset($_SESSION['gift_submit']) ? $_SESSION['gift_submit'] : array();
$doscore = count($submit) > 0;

$retval['status'] = 'success';
$retval['scored'] = $doscore;
$safe = array();
$count = 1;
$cumulative_score = 0;
$cumulative_total = 0;
// Filter out questions for the user-visible stuff
foreach($questions as $question) {
    $nq = new stdClass();
    if ( ! isset($question->question) ) continue;
    if ( ! isset($question->type) ) continue;
    if ( ! isset($question->code) ) continue;
    $nq->question = $question->question;
    $q_code = $question->code;
    $nq->code = $q_code;
    $t = $question->type;
    $nq->type = $t;
    if ( isset($question->name) ) $nq->name = $question->name;

    // Score the questions that don't have answers
    $score = null;
    $correct = null;
    if ( $doscore && $t == 'short_answer_question' ) {
        if ( isset($submit[$q_code]) ) {
            $nq->value = $submit[$q_code];
            foreach($question->parsed_answer as $answer ) {
                $ans = preg_replace('/\s+/', '', $answer[1]);
                $sub = preg_replace('/\s+/', '', $submit[$q_code]);
                if ( strcasecmp($sub, $ans) == 0 ) {
                    $score = 1;
                    $correct = true;
                    break;
                }
            }
        }
        if ( $score === null ) {
            $score = 0;
            $correct = false;
        }
    } else if ( $doscore && $t == 'true_false_question' ) {
        if ( isset($submit[$q_code]) ) {
            $nq->value = $submit[$q_code];
            $score = ($submit[$q_code] == $question->answer) ? 1 : 0;
            $correct = ($score == 1);
        } else {
            $score = 0;
            $correct = false;
        }
    }

    if ( ( $t == 'multiple_choice_question' || $t == 'multiple_answers_question' ) &&
        isset($question->parsed_answer) && is_array($question->parsed_answer) ) {
        $answers = array();
        $got = 0;
        $need = 0;
        foreach($question->parsed_answer as $answer ) {
            $ans = new stdClass();
            if ( ! is_array($answer) ) continue;
            if ( count($answer) != 4 ) continue;
            $ans->text = $answer[1];
            $a_code = $answer[3];
            $expected = $answer[0];  // An actual boolean
            $actual = isset($submit[$a_code]) ? ($submit[$a_code] == 'true') === $expected : false;
            if ( $actual === $expected ) $got++;
            $need++;
            $ans->code = $a_code;
            if ( $doscore && $t == 'multiple_answers_question' ) {
                $ans->value = $actual;
                $ans->correct = $actual == $expected;
            }
            $answers[] = $ans;
        }
        if ( $doscore ) {
            if ( $t == 'multiple_choice_question' ) {
                $correct = $got == $need;
                $score = $correct ? 1 : 0;
            } else {
                $score = $got / $need;
                $correct = $got == $need;
            }
        }
        $nq->answers = $answers;
    }
    if ( $correct !== null ) $nq->correct = $correct;
    if ( $score !== null ) {
        $nq->score = $score;
        $cumulative_score += $score;
        $cumulative_total += 1;
        // $nq->cumulative_total = $cumulative_total;
        // $nq->cumulative_score = $cumulative_score;
    }
    $nq->count = $count;
    $count++;
    $safe[] = $nq;
}

$retval["questions"] = $safe;
$retval["submit"] = $submit;
if ( $doscore ) {
    $retval["score"] = $cumulative_score / $cumulative_total;
}

echo(json_encode($retval));
