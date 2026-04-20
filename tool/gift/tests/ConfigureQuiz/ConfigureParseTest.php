<?php

require_once "configure_parse.php";
require_once "parse.php";

class ConfigureParseTest extends \PHPUnit\Framework\TestCase
{
  public function setUp(): void {
    // create a test post
    $_POST = Array
    (
        'PHPSESSID' => 'ca80ad1e7a9c9d9d71f742d563a99145',
        'title_question1' => 'Q1 T/F',
        'text_question1' => '1+1=2',
        'type_question1' => 'true_false_question',
        'answer1_question1' => 'true',
        'title_question2' => 'Q2 MA',
        'text_question2' => 'One of these are right and three are wrong',
        'type_question2' => 'multiple_choice_question',
        'answer1_question2' => 'Right',
        'answer1_iscorrect_question2' => 'on',
        'answer2_question2' => 'Wrong',
        'answer3_question2' => 'Incorrect',
        'answer4_question2' => 'Not right',
        'answer5_question2' => NULL,
        'title_question3' => 'Q3 MA',
        'text_question3' => 'Two of these are right and two are wrong',
        'type_question3' => 'multiple_answers_question',
        'answer1_question3' => 'Right',
        'answer1_iscorrect_question3' => 'on',
        'answer2_question3' => 'Correct',
        'answer2_iscorrect_question3' => 'on',
        'answer3_question3' => 'Wrong',
        'answer4_question3' => 'Incorrect',
        'answer5_question3' => NULL,
        'title_question4' => 'Q4 Short Answer',
        'text_question4' => 'Two plus [_____] equals four.',
        'type_question4' => 'short_answer_question',
        'answer1_question4' => 'two',
        'answer2_question4' => '2',
        'answer3_question4' => NULL
    );
  }

  public function testCreateGiftFormat() {
    // True/False questions
    $question = array();
    $question['title'] = 'Q1';
    $question['text'] = 'Test Question';
    $question['type'] = 'true_false_question';
    $question['answer'][1] = array('text' => 'true', 'iscorrect' => false);

    $this->assertEquals(create_gift_format($question), "::Q1:: Test Question {T}");
    // Check the same question, but with the answer as false
    $question['answer'][1] = array('text' => 'false', 'iscorrect' => false);
    $this->assertEquals(create_gift_format($question), "::Q1:: Test Question {F}");

    // Multiple Choice questions
    $question['title'] = 'Q2 - MC';
    $question['text'] = 'Multiple Choice Test Question';
    $question['type'] = 'multiple_choice_question';
    $question['answer'][1] = array('text' => 'Right', 'iscorrect' => true);
    $question['answer'][2] = array('text' => 'Wrong', 'iscorrect' => false);
    $question['answer'][3] = array('text' => 'Incorrect', 'iscorrect' => false);
    $question['answer'][4] = array('text' => 'Not right', 'iscorrect' => false);

    $this->assertEquals(create_gift_format($question), "::Q2 - MC:: Multiple Choice Test Question {=Right ~Wrong ~Incorrect ~Not right}");

    // Multiple answer questions
    $question['title'] = 'Q3 - MA';
    $question['text'] = 'Multiple Answer Test Question';
    $question['type'] = 'multiple_answers_question';
    $question['answer'][3] = array('text' => 'Correct', 'iscorrect' => true);

    $this->assertEquals(create_gift_format($question), "::Q3 - MA:: Multiple Answer Test Question {=Right ~Wrong =Correct ~Not right}");

    // Short Answer Questions
    $question['title'] = 'Q4 - SA';
    $question['text'] = 'Short Answer Test Question';
    $question['type'] = 'short_answer_question';
    $question['answer'] = array();
    $question['answer'][1] = array('text' => 'Option 1', 'iscorrect' => false);
    $question['answer'][2] = array('text' => 'Option 2', 'iscorrect' => false);

    $this->assertEquals(create_gift_format($question), "::Q4 - SA:: Short Answer Test Question {=Option 1 =Option 2}");
  }

  public function testParse() {
    $gift_output = "::Q1 T/F:: 1+1=2 {T}
::Q2 MA:: One of these are right and three are wrong {=Right ~Wrong ~Incorrect ~Not right}
::Q3 MA:: Two of these are right and two are wrong {=Right =Correct ~Wrong ~Incorrect}
::Q4 Short Answer:: Two plus [_____] equals four. {=two =2}";

    // $this->assertEquals(parse_configure_post(), $gift_output);

    $this->assertTrue(check_gift(parse_configure_post()));
  }
}
