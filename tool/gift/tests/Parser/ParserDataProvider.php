<?php

/**
 * Data provider for GIFT parser tests
 * Provides test cases in a structured format for easy maintenance
 */
class ParserDataProvider
{
    /**
     * Get valid GIFT test cases
     * Returns array of [description, gift_string, expected_type, expected_answer_count]
     */
    public static function getValidTestCases() {
        return array(
            // True/False
            array('True/False basic', "::Q1:: Is 1+1=2? {T}", 'true_false_question', 2),
            array('True/False with feedback', "::Q1:: Is 1+1=2? {T#Correct!}", 'true_false_question', 2),
            array('True/False double feedback', "::Q1:: Is 1+1=2? {T#Wrong!#Correct!}", 'true_false_question', 2),
            array('False answer', "::Q1:: Is 1+1=3? {F}", 'true_false_question', 2),
            
            // Multiple Choice
            array('Multiple choice basic', "::Q1:: What is 2+2? {=4 ~3 ~5}", 'multiple_choice_question', 3),
            array('Multiple choice with feedback', "::Q1:: Test {=Yes#Correct ~No#Wrong}", 'multiple_choice_question', 2),
            array('Multiple choice hex colors', "::Q1:: Color {=#ff0000 ~#00ff00}", 'multiple_choice_question', 2),
            
            // Multiple Answer
            array('Multiple answer basic', "::Q1:: Select {=A =B ~C}", 'multiple_answers_question', 3),
            array('Multiple answer with feedback', "::Q1:: Select {=A#Yes =B#Yes ~C#No}", 'multiple_answers_question', 3),
            
            // Short Answer
            array('Short answer basic', "::Q1:: Two plus {=two =2} equals four.", 'short_answer_question', 2),
            array('Short answer with feedback', "::Q1:: Test {=answer#yes =answer2#yes}", 'short_answer_question', 2),
            
            // Numerical
            array('Numerical tolerance', "::Q1:: Value {#3:2}", 'numerical_question', 1),
            array('Numerical range', "::Q1:: Range {#1..5}", 'numerical_question', 1),
            array('Numerical exact', "::Q1:: Exact {#42}", 'numerical_question', 1),
            array('Numerical with feedback', "::Q1:: Value {#3#Correct!}", 'numerical_question', 1),
            
            // Matching
            array('Matching basic', "::Q1:: Match {=cat -> food =dog -> food}", 'matching_question', 2),
            array('Matching with feedback', "::Q1:: Match {=a -> b#correct}", 'matching_question', 1),
            
            // Essay
            array('Essay question', "::Q1:: How are you? {}", 'essay_question', 0),
            
            // HTML
            array('HTML question', "::Q1::[html]<b>Bold</b> {=Yes ~No}", 'multiple_choice_question', 1),
        );
    }

    /**
     * Get invalid GIFT test cases (should produce errors)
     * Returns array of [description, gift_string, expected_error_pattern]
     */
    public static function getInvalidTestCases() {
        return array(
            array('Missing braces', "::Q1:: No answer", 'Could not find answer'),
            array('No correct answers', "::Q1:: Question {~Wrong ~Also wrong}", 'No correct answers'),
            array('Malformed question', "::Q1 Missing colons Question {T}", 'Mal-formed question'),
        );
    }

    /**
     * Get edge case test cases
     * Returns array of [description, gift_string]
     */
    public static function getEdgeCases() {
        return array(
            array('Empty title', ":::: Question {T}"),
            array('Multi-line question', "::Q1:: Line 1\nLine 2\nLine 3 {T}"),
            array('Multi-line answers', "::Q1:: Question {\n=Answer 1\n~Answer 2\n}"),
            array('Special characters', "::Q1:: Test {=< > & ' \"}"),
            array('Escaped braces', "::Q1:: Test \\{escaped\\} {T}"),
            array('Comments', "// Comment\n::Q1:: Question {T}\n// Another"),
        );
    }
}

