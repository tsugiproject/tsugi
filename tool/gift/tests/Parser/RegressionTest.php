<?php

require_once __DIR__ . "/ParserTest.php";

// Make sure parse.php is loaded
if (!function_exists('parse_gift')) {
    require_once __DIR__ . "/../../parse.php";
}

/**
 * Regression tests for specific bugs that were found and fixed
 */
class RegressionTest extends ParserTest
{
    /**
     * Test for hex color bug - # in answers should not be treated as feedback separator
     * Bug: Parser was treating #ff0000 as feedback separator
     * Fix: Only treat # as feedback if answer text has already been collected
     */
    public function testHexColorInAnswers() {
        $gift = "::CSS_Q10:: Which of the following color values represents pure red? { =#ff0000 ~#00ff00 ~#0000ff ~#ffffff }";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertEquals('multiple_choice_question', $q->type);
        $this->assertCount(4, $q->parsed_answer);
        
        // Verify all hex colors are preserved as answer text
        $answers = array();
        foreach ($q->parsed_answer as $ans) {
            $answers[] = $ans[1];
            // Hex colors should not have feedback (unless explicitly provided)
            if (strpos($ans[1], '#ff0000') === 0) {
                $this->assertEquals('', $ans[2], "Hex color answer should not have feedback");
            }
        }
        
        $this->assertContains('#ff0000', $answers, '', true);
        $this->assertContains('#00ff00', $answers, '', true);
        $this->assertContains('#0000ff', $answers, '', true);
        $this->assertContains('#ffffff', $answers, '', true);
    }

    /**
     * Test that hex colors WITH feedback still work correctly
     */
    public function testHexColorWithFeedback() {
        $gift = "::Q1:: Test { =#ff0000#This is red ~#00ff00#This is green }";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        foreach ($q->parsed_answer as $ans) {
            if ($ans[1] === '#ff0000') {
                $this->assertEquals('This is red', $ans[2]);
            } elseif ($ans[1] === '#00ff00') {
                $this->assertEquals('This is green', $ans[2]);
            }
        }
    }

    /**
     * Test that brace matching handles escaped braces correctly
     */
    public function testEscapedBracesInQuestionText() {
        $gift = "::Q1:: Question with \\{escaped\\} braces {T}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        // Should parse successfully without treating escaped braces as answer delimiters
        $this->assertEquals('true_false_question', $q->type);
    }

    /**
     * Test that numerical questions with multiple answers parse correctly
     */
    public function testNumericalMultipleAnswers() {
        $gift = "::Q1:: When was Ulysses S. Grant born? {# =1822:0 # Correct! =%50%1822:2 # Half credit}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertEquals('numerical_question', $q->type);
        $this->assertGreaterThanOrEqual(2, count($q->parsed_answer));
        
        // Verify both answers are present
        $hasFullCredit = false;
        $hasHalfCredit = false;
        
        foreach ($q->parsed_answer as $ans) {
            if (strpos($ans[1], '1822:0') !== false) {
                $hasFullCredit = true;
            }
            if (isset($ans[4]) && $ans[4] === 50.0) {
                $hasHalfCredit = true;
            }
        }
        
        $this->assertTrue($hasFullCredit, "Should have full credit answer");
        $this->assertTrue($hasHalfCredit, "Should have half credit answer");
    }

    /**
     * Test that True/False questions with single feedback don't error
     * (Previously might have errored, but Moodle supports this)
     */
    public function testTrueFalseSingleFeedback() {
        $gift = "::Q1:: Is 1+1=2? {T#Correct!}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertEquals('true_false_question', $q->type);
        
        // Should have feedback on the correct answer
        $hasFeedback = false;
        foreach ($q->parsed_answer as $ans) {
            if ($ans[0] === true && strlen($ans[2]) > 0) {
                $hasFeedback = true;
                $this->assertEquals('Correct!', $ans[2]);
                break;
            }
        }
        $this->assertTrue($hasFeedback, "Should have feedback on correct answer");
    }
}

