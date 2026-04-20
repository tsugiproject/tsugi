<?php

require_once __DIR__ . "/ParserTest.php";

/**
 * Integration tests - test complete GIFT files and real-world scenarios
 */
class IntegrationTest extends ParserTest
{
    /**
     * Test loading and parsing a complete GIFT file
     */
    public function testLoadGiftFile() {
        $giftFile = __DIR__ . "/../../tests/data/valid/example_multiple_choice.gift";
        if (file_exists($giftFile)) {
            $gift = file_get_contents($giftFile);
            $questions = $this->assertParseSuccess($gift);
            $this->assertGreaterThan(0, count($questions), "Should parse at least one question from file");
        } else {
            $this->markTestSkipped("Test data file not found: {$giftFile}");
        }
    }

    /**
     * Test parsing multiple question types in one file
     */
    public function testMixedQuestionTypes() {
        $gift = <<<GIFT
// Mixed question types
::Q1 T/F:: Is 1+1=2? {T}

::Q2 MC:: What is 2+2? {=4 ~3 ~5}

::Q3 MA:: Select even numbers {=2 =4 ~3 ~5}

::Q4 SA:: Two plus {=two =2} equals four.

::Q5 Num:: What is 3? {#3}

::Q6 Match:: Match {=a -> 1 =b -> 2}

::Q7 Essay:: How are you? {}
GIFT;
        
        $questions = $this->assertParseSuccess($gift, 7);
        
        $types = array();
        foreach ($questions as $q) {
            $types[] = $q->type;
        }
        
        $this->assertContains('true_false_question', $types, '', true);
        $this->assertContains('multiple_choice_question', $types, '', true);
        $this->assertContains('multiple_answers_question', $types, '', true);
        $this->assertContains('short_answer_question', $types, '', true);
        $this->assertContains('numerical_question', $types, '', true);
        $this->assertContains('matching_question', $types, '', true);
        $this->assertContains('essay_question', $types, '', true);
    }

    /**
     * Test real-world CSS question with hex colors
     */
    public function testRealWorldCSSQuestion() {
        $gift = "::CSS_Q10:: Which of the following color values represents pure red? { =#ff0000 ~#00ff00 ~#0000ff ~#ffffff }";
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertEquals('multiple_choice_question', $q->type);
        $this->assertEquals('CSS_Q10', $q->name);
        $this->assertCount(4, $q->parsed_answer);
    }

    /**
     * Test that check_gift function works correctly
     */
    public function testCheckGiftFunction() {
        // Mock session
        $_SESSION = array();
        
        $validGift = "::Q1:: Test {T}";
        $this->assertTrue(check_gift($validGift), "Valid GIFT should pass check_gift");
        
        $invalidGift = "::Q1:: Missing braces";
        $this->assertFalse(check_gift($invalidGift), "Invalid GIFT should fail check_gift");
    }
}

