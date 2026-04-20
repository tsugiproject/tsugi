<?php

// Load parse.php if not already loaded
if (!function_exists('parse_gift')) {
    require_once __DIR__ . "/../../parse.php";
}

/**
 * Comprehensive test suite for GIFT parser
 * Tests all question types, edge cases, and error handling
 */
class ParserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Helper method to parse GIFT and return questions/errors
     */
    protected function parseGift($gift) {
        $questions = array();
        $errors = array();
        parse_gift($gift, $questions, $errors);
        return array('questions' => $questions, 'errors' => $errors);
    }

    /**
     * Assert that parsing succeeds with no errors
     */
    protected function assertParseSuccess($gift, $expectedQuestionCount = null) {
        $result = $this->parseGift($gift);
        $this->assertEmpty($result['errors'], "Expected no errors but got: " . implode(', ', $result['errors']));
        if ($expectedQuestionCount !== null) {
            $this->assertCount($expectedQuestionCount, $result['questions'], 
                "Expected {$expectedQuestionCount} questions but got " . count($result['questions']));
        }
        return $result['questions'];
    }

    /**
     * Assert that parsing produces expected errors
     */
    protected function assertParseErrors($gift, $expectedErrorPattern = null) {
        $result = $this->parseGift($gift);
        $this->assertNotEmpty($result['errors'], "Expected errors but parsing succeeded");
        if ($expectedErrorPattern !== null) {
            $found = false;
            foreach ($result['errors'] as $error) {
                if (strpos($error, $expectedErrorPattern) !== false) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, "Expected error pattern '{$expectedErrorPattern}' not found in errors: " . implode(', ', $result['errors']));
        }
        return $result;
    }

    // ============================================
    // TRUE/FALSE QUESTION TESTS
    // ============================================

    public function testTrueFalseBasic() {
        $gift = "::Q1:: Is 1+1=2? {T}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertEquals('true_false_question', $q->type);
        $this->assertEquals('Is 1+1=2?', $q->question);
        $this->assertCount(2, $q->parsed_answer); // True and False options
    }

    public function testTrueFalseWithSingleFeedback() {
        $gift = "::Q1:: Is 1+1=2? {T#Correct!}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertEquals('true_false_question', $q->type);
        // Find the True answer
        foreach ($q->parsed_answer as $ans) {
            if ($ans[0] === true && stripos($ans[1], 'true') !== false) {
                $this->assertEquals('Correct!', $ans[2]);
                break;
            }
        }
    }

    public function testTrueFalseWithDoubleFeedback() {
        $gift = "::Q1:: Is 1+1=2? {T#Wrong!#Correct!}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertEquals('true_false_question', $q->type);
        $this->assertCount(2, $q->parsed_answer);
        
        // Check both feedbacks are present
        $feedbacks = array();
        foreach ($q->parsed_answer as $ans) {
            if (strlen($ans[2]) > 0) {
                $feedbacks[] = $ans[2];
            }
        }
        $this->assertContains('Wrong!', $feedbacks, '', true);
        $this->assertContains('Correct!', $feedbacks, '', true);
    }

    public function testFalseAnswer() {
        $gift = "::Q1:: Is 1+1=3? {F}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertEquals('true_false_question', $q->type);
    }

    // ============================================
    // MULTIPLE CHOICE QUESTION TESTS
    // ============================================

    public function testMultipleChoiceBasic() {
        $gift = "::Q1:: What is 2+2? {=4 ~3 ~5 ~6}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertEquals('multiple_choice_question', $q->type);
        $this->assertEquals(1, $q->correct_answers);
        $this->assertCount(4, $q->parsed_answer);
        
        // Verify correct answer
        $hasCorrect = false;
        foreach ($q->parsed_answer as $ans) {
            if ($ans[0] === true && $ans[1] === '4') {
                $hasCorrect = true;
                break;
            }
        }
        $this->assertTrue($hasCorrect, "Correct answer '4' not found");
    }

    public function testMultipleChoiceWithFeedback() {
        $gift = "::Q1:: What is 2+2? {=4#Correct! ~3#Too low ~5#Too high}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertEquals('multiple_choice_question', $q->type);
        
        // Check feedbacks
        foreach ($q->parsed_answer as $ans) {
            if ($ans[1] === '4') {
                $this->assertEquals('Correct!', $ans[2]);
            } elseif ($ans[1] === '3') {
                $this->assertEquals('Too low', $ans[2]);
            } elseif ($ans[1] === '5') {
                $this->assertEquals('Too high', $ans[2]);
            }
        }
    }

    public function testMultipleChoiceWithHexColors() {
        $gift = "::CSS_Q10:: Which color is red? { =#ff0000 ~#00ff00 ~#0000ff ~#ffffff }";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertEquals('multiple_choice_question', $q->type);
        $this->assertCount(4, $q->parsed_answer);
        
        // Verify hex colors are preserved
        $answers = array();
        foreach ($q->parsed_answer as $ans) {
            $answers[] = $ans[1];
        }
        $this->assertContains('#ff0000', $answers, '', true);
        $this->assertContains('#00ff00', $answers, '', true);
    }

    public function testMultipleChoiceWithHexColorsAndFeedback() {
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

    public function testMultipleChoiceWithPercentageWeights() {
        $gift = "::Q1:: Choose one {=Correct%100% ~Wrong ~Also wrong}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        foreach ($q->parsed_answer as $ans) {
            if ($ans[1] === 'Correct') {
                $this->assertEquals(100, $ans[4], "Percentage weight should be 100");
            }
        }
    }

    // ============================================
    // MULTIPLE ANSWER QUESTION TESTS
    // ============================================

    public function testMultipleAnswerBasic() {
        $gift = "::Q1:: Select all even numbers {=2 =4 ~3 ~5}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertEquals('multiple_answers_question', $q->type);
        $this->assertEquals(2, $q->correct_answers);
        $this->assertCount(4, $q->parsed_answer);
    }

    public function testMultipleAnswerWithFeedback() {
        $gift = "::Q1:: Select correct {=Right#Yes =Also right#Yes ~Wrong#No}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertEquals('multiple_answers_question', $q->type);
        $this->assertEquals(2, $q->correct_answers);
    }

    // ============================================
    // SHORT ANSWER QUESTION TESTS
    // ============================================

    public function testShortAnswerBasic() {
        $gift = "::Q1:: Two plus {=two =2} equals four.";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertEquals('short_answer_question', $q->type);
        $this->assertStringContainsString('[_____]', $q->question);
        $this->assertCount(2, $q->parsed_answer);
        $this->assertEquals(2, $q->correct_answers);
    }

    public function testShortAnswerWithFeedback() {
        // Short answer questions only have correct answers (=), no incorrect (~)
        $gift = "::Q1:: Two plus {=two#yes =2#yes} equals four.";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertEquals('short_answer_question', $q->type);
    }

    // ============================================
    // NUMERICAL QUESTION TESTS
    // ============================================

    public function testNumericalWithTolerance() {
        $gift = "::Q1:: What is 3 plus or minus 2? {#3:2}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertEquals('numerical_question', $q->type);
        $this->assertEquals('3:2', $q->parsed_answer[0][1]);
    }

    public function testNumericalWithRange() {
        $gift = "::Q1:: What is a number from 1 to 5? {#1..5}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertEquals('numerical_question', $q->type);
        $this->assertEquals('1..5', $q->parsed_answer[0][1]);
    }

    public function testNumericalExactValue() {
        $gift = "::Q1:: What is 42? {#42}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertEquals('numerical_question', $q->type);
        $this->assertEquals('42', $q->parsed_answer[0][1]);
    }

    public function testNumericalWithFeedback() {
        $gift = "::Q1:: What is 3? {#3#Correct!}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertEquals('Correct!', $q->parsed_answer[0][2]);
    }

    public function testNumericalMultipleAnswersWithPartialCredit() {
        $gift = "::Q1:: When was Ulysses S. Grant born? {# =1822:0 # Correct! Full credit. =%50%1822:2 # Half credit}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertEquals('numerical_question', $q->type);
        $this->assertGreaterThanOrEqual(2, count($q->parsed_answer));
        
        // Check for percentage weight
        $hasPartialCredit = false;
        foreach ($q->parsed_answer as $ans) {
            if (isset($ans[4]) && $ans[4] === 50.0) {
                $hasPartialCredit = true;
                break;
            }
        }
        $this->assertTrue($hasPartialCredit, "Should have partial credit answer with 50% weight");
    }

    // ============================================
    // MATCHING QUESTION TESTS
    // ============================================

    public function testMatchingBasic() {
        $gift = "::Q1:: Match the animals {=cat -> cat food =dog -> dog food}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertEquals('matching_question', $q->type);
        $this->assertGreaterThanOrEqual(2, count($q->parsed_answer));
        
        // Verify matches
        $matches = array();
        foreach ($q->parsed_answer as $ans) {
            $matches[] = $ans[1];
        }
        $this->assertContains('cat -> cat food', $matches, '', true);
        $this->assertContains('dog -> dog food', $matches, '', true);
    }

    public function testMatchingWithFeedback() {
        $gift = "::Q1:: Match {=cat -> cat food#correct =dog -> dog food#also correct}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertEquals('matching_question', $q->type);
    }

    // ============================================
    // ESSAY QUESTION TESTS
    // ============================================

    public function testEssayQuestion() {
        $gift = "::Q1:: How are you? {}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertEquals('essay_question', $q->type);
    }

    // ============================================
    // HTML QUESTION TESTS
    // ============================================

    public function testHtmlQuestion() {
        $gift = "::Q1::[html]This is <b>bold</b> text {=Yes ~No}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertTrue(isset($q->html) && $q->html === true);
        $this->assertStringContainsString('<b>bold</b>', $q->question);
    }

    // ============================================
    // ESCAPE CHARACTER TESTS
    // ============================================

    public function testEscapedCharacters() {
        $gift = "::Q1:: Test escapes {=\\=equals \\~tilde \\#hash \\{brace \\}brace}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        // Verify escaped characters are handled
        $this->assertNotEmpty($q->parsed_answer);
    }

    public function testEscapedBracesInQuestion() {
        $gift = "::Q1:: Question with \\{escaped\\} braces {=Answer}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertNotEmpty($q->question);
    }

    // ============================================
    // MULTIPLE QUESTION TESTS
    // ============================================

    public function testMultipleQuestions() {
        $gift = "::Q1:: First question {T}\n\n::Q2:: Second question {=Yes ~No}";
        $questions = $this->assertParseSuccess($gift, 2);
        
        $this->assertEquals('true_false_question', $questions[0]->type);
        $this->assertEquals('multiple_choice_question', $questions[1]->type);
    }

    public function testCommentsIgnored() {
        $gift = "// This is a comment\n::Q1:: Question {T}\n// Another comment";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $this->assertEquals('true_false_question', $questions[0]->type);
    }

    // ============================================
    // ERROR HANDLING TESTS
    // ============================================

    public function testMalformedQuestion() {
        $gift = "::Q1:: Missing answer braces";
        $result = $this->assertParseErrors($gift);
        $this->assertNotEmpty($result['errors']);
    }

    public function testMissingAnswerBraces() {
        $gift = "::Q1:: Question without braces";
        $result = $this->assertParseErrors($gift);
        $this->assertNotEmpty($result['errors']);
    }

    public function testNoCorrectAnswers() {
        $gift = "::Q1:: Question {~Wrong ~Also wrong}";
        $result = $this->assertParseErrors($gift, "No correct answers");
    }

    // ============================================
    // EDGE CASES
    // ============================================

    public function testQuestionWithNewlines() {
        $gift = "::Q1:: Line one\nLine two\nLine three {T}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertStringContainsString('Line one', $q->question);
        $this->assertStringContainsString('Line two', $q->question);
    }

    public function testMultiLineAnswers() {
        $gift = "::Q1:: Question {\n=Answer one\n~Answer two\n}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertGreaterThanOrEqual(2, count($q->parsed_answer));
    }

    public function testEmptyTitle() {
        $gift = ":::: Question {T}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertEquals('', $q->name);
    }

    public function testSpecialCharactersInAnswer() {
        $gift = "::Q1:: Test {=< > & ' \"}";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertNotEmpty($q->parsed_answer);
    }
}

