<?php

require_once __DIR__ . "/ParserTest.php";
require_once __DIR__ . "/ParserDataProvider.php";

// Make sure base path is set
if (!class_exists('ParserTest')) {
    require_once __DIR__ . "/../../parse.php";
}

/**
 * Data-driven tests for GIFT parser
 * Uses data providers for easy test case management
 */
class ParserDataDrivenTest extends ParserTest
{
    /**
     * @dataProvider validGiftProvider
     */
    public function testValidGiftFormats($description, $gift, $expectedType, $expectedAnswerCount) {
        $questions = $this->assertParseSuccess($gift, 1);
        
        $q = $questions[0];
        $this->assertEquals($expectedType, $q->type, "Failed test: {$description}");
        
        if ($expectedAnswerCount > 0) {
            $this->assertGreaterThanOrEqual(
                $expectedAnswerCount, 
                count($q->parsed_answer),
                "Failed test: {$description} - Expected at least {$expectedAnswerCount} answers"
            );
        }
    }

    /**
     * @dataProvider invalidGiftProvider
     */
    public function testInvalidGiftFormats($description, $gift, $expectedErrorPattern) {
        $result = $this->assertParseErrors($gift, $expectedErrorPattern);
        // Test passes if assertParseErrors doesn't throw
    }

    /**
     * @dataProvider edgeCaseProvider
     */
    public function testEdgeCases($description, $gift) {
        // Edge cases should parse without errors (or with expected errors)
        $result = $this->parseGift($gift);
        // Just verify it doesn't crash - some edge cases might have errors, some might not
        $this->assertTrue(
            is_array($result['questions']) && is_array($result['errors']),
            "Failed test: {$description} - Parser should return arrays"
        );
    }

    public function validGiftProvider() {
        return ParserDataProvider::getValidTestCases();
    }

    public function invalidGiftProvider() {
        return ParserDataProvider::getInvalidTestCases();
    }

    public function edgeCaseProvider() {
        return ParserDataProvider::getEdgeCases();
    }
}

