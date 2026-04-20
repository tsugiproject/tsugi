<?php

require_once __DIR__ . "/../../parse.php";
// Mersenne_Twister is only needed for make_quiz, not for parse_gift tests

class FeedbackTest extends \PHPUnit\Framework\TestCase
{
  public function testQuestionWithFeedback() {
    // Check a valid string
    $gift = "::Q1 T/F:: 1+1=2 {T#You got it wrong.#You got it.}\n
    ::Q2 MA:: One of these are right and three are wrong {=Right#Correct ~Wrong#nope ~Incorrect#opposite ~Not right#single word}\n
    ::Q3 MA:: Two of these are right and two are wrong {=Right#correct =Correct#corretc ~Wrong#not this one ~Incorrect#nope}\n
    ::Q4 Short Answer:: Two plus [_____] equals four. {=two#yes, written =2#the numeral, ~4#close}";
    // $gift = "::Q3 MA:: Two of these are right and two are wrong {=Right#correct =Correct ~Wrong ~Incorrect}";

    $questions = array();
    $errors = array();
    parse_gift($gift, $questions, $errors);
    $this->assertEquals($questions[0]->parsed_answer[0][2], "You got it wrong.");
    $this->assertEquals($questions[0]->parsed_answer[1][2], "You got it.");

    // Check a string with single feedback (now valid in Moodle GIFT format)
    $gift = "::Q1 T/F:: 1+1=2 {T#You got it.}";
    $questions = array();
    $errors = array();
    parse_gift($gift, $questions, $errors);
    // Single feedback for T/F is now valid - should parse successfully
    $this->assertEmpty($errors, "Single feedback T/F should parse without errors");
    $this->assertEquals($questions[0]->question, "1+1=2");
    $this->assertEquals($questions[0]->type, "true_false_question");
    
    // Verify feedback is present
    $hasFeedback = false;
    foreach ($questions[0]->parsed_answer as $ans) {
        if ($ans[0] === true && strlen($ans[2]) > 0) {
            $hasFeedback = true;
            $this->assertEquals("You got it.", $ans[2]);
            break;
        }
    }
    $this->assertTrue($hasFeedback, "Should have feedback on correct answer");
  }
}
