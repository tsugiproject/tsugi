<?php

use Tsugi\Services\Quiz1\Answer;
use Tsugi\Services\Quiz1\Question;
use Tsugi\Services\Quiz1\QuestionTypes;
use Tsugi\Services\Quiz1\Quiz;

class QuizValidationTest extends \PHPUnit\Framework\TestCase
{
    public function testValidSampleQuiz() {
        $quiz = \Tsugi\Services\Quiz1\SampleQuiz::build(1);
        $this->assertSame(array(), $quiz->validate());
        $this->assertTrue($quiz->isValid());
        $this->assertCount(6, $quiz->orderedQuestions());
    }

    public function testTitleRequired() {
        $quiz = new Quiz();
        $quiz->title = '   ';
        $errors = $quiz->validate();
        $this->assertNotEmpty($errors);
    }

    public function testMultipleChoiceNeedsTwoChoicesAndOneCorrect() {
        $q = $this->baseQuestion(QuestionTypes::MULTIPLE_CHOICE);
        $q->answers = array(Answer::make('Only', true, 1));
        $this->assertStringContainsString('at least two', implode(' ', $q->validate()));

        $q->answers = array(
            Answer::make('A', false, 1),
            Answer::make('B', false, 2),
        );
        $this->assertStringContainsString('exactly one correct', implode(' ', $q->validate()));

        $q->answers = array(
            Answer::make('A', true, 1),
            Answer::make('B', true, 2),
        );
        $this->assertStringContainsString('exactly one correct', implode(' ', $q->validate()));

        $q->answers = array(
            Answer::make('A', true, 1),
            Answer::make('B', false, 2),
        );
        $this->assertSame(array(), $q->validate());
    }

    public function testMultipleResponseNeedsOneCorrect() {
        $q = $this->baseQuestion(QuestionTypes::MULTIPLE_RESPONSE);
        $q->answers = array(
            Answer::make('A', false, 1),
            Answer::make('B', false, 2),
        );
        $this->assertStringContainsString('at least one correct', implode(' ', $q->validate()));

        $q->answers = array(
            Answer::make('A', true, 1),
            Answer::make('B', true, 2),
        );
        $this->assertSame(array(), $q->validate());
    }

    public function testTrueFalseNeedsBoolean() {
        $q = $this->baseQuestion(QuestionTypes::TRUE_FALSE);
        $q->answers = array(Answer::make('True', true, 1));
        $this->assertNotEmpty($q->validate());

        $q->answers = array(
            Answer::make('True', true, 1),
            Answer::make('False', true, 2),
        );
        $this->assertStringContainsString('exactly one correct', implode(' ', $q->validate()));

        $q->answers = array(
            Answer::make('True', false, 1),
            Answer::make('False', true, 2),
        );
        $this->assertSame(array(), $q->validate());
    }

    public function testEssayRejectsCorrectAnswer() {
        $q = $this->baseQuestion(QuestionTypes::ESSAY);
        $this->assertSame(array(), $q->validate());
        $q->answers = array(Answer::make('Nope', true, 1));
        $this->assertStringContainsString('cannot have an automatically correct', implode(' ', $q->validate()));
    }

    public function testFillBlankNeedsAcceptedAnswer() {
        $q = $this->baseQuestion(QuestionTypes::FILL_BLANK);
        $this->assertNotEmpty($q->validate());
        $q->answers = array(Answer::make('80', true, 1));
        $this->assertSame(array(), $q->validate());
    }

    public function testPatternMatchNeedsPattern() {
        $q = $this->baseQuestion(QuestionTypes::PATTERN_MATCH);
        $this->assertNotEmpty($q->validate());
        $q->answers = array(Answer::make('Script', true, 1));
        $this->assertSame(array(), $q->validate());
    }

    public function testPromptRequiredAndTypeRequired() {
        $q = $this->baseQuestion(QuestionTypes::ESSAY);
        $q->prompt = '<p>   </p>';
        $this->assertStringContainsString('prompt', implode(' ', $q->validate()));

        $q->prompt = '<p>Hello</p>';
        $q->type = 'matching';
        $this->assertStringContainsString('supported type', implode(' ', $q->validate()));
    }

    public function testDuplicateSequenceIsInvalid() {
        $quiz = new Quiz();
        $quiz->title = 'Dup';
        $a = $this->baseQuestion(QuestionTypes::ESSAY);
        $a->sequence = 1;
        $b = $this->baseQuestion(QuestionTypes::ESSAY);
        $b->sequence = 1;
        $quiz->questions = array($a, $b);
        $this->assertStringContainsString('unique', implode(' ', $quiz->validate()));
    }

    public function testOrderingIsDeterministic() {
        $quiz = new Quiz();
        $quiz->title = 'Order';
        $second = $this->baseQuestion(QuestionTypes::ESSAY);
        $second->id = 2;
        $second->sequence = 2;
        $first = $this->baseQuestion(QuestionTypes::ESSAY);
        $first->id = 1;
        $first->sequence = 1;
        $quiz->questions = array($second, $first);
        $ordered = $quiz->orderedQuestions();
        $this->assertSame(1, $ordered[0]->id);
        $this->assertSame(2, $ordered[1]->id);
    }

    private function baseQuestion($type) {
        $q = new Question();
        $q->type = $type;
        $q->prompt = '<p>Prompt</p>';
        $q->points = 1;
        return $q;
    }
}
