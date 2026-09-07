<?php

use Tsugi\Services\Quiz1\Answer;
use Tsugi\Services\Quiz1\Grader;
use Tsugi\Services\Quiz1\Question;
use Tsugi\Services\Quiz1\QuestionTypes;
use Tsugi\Services\Quiz1\SampleQuiz;

class GraderTest extends \PHPUnit\Framework\TestCase
{
    public function testSampleQuizPerfectScore() {
        $quiz = SampleQuiz::build(1);
        $post = array(
            'q101' => 1011,
            'q102' => array(1021, 1022),
            'q103' => 1032,
            'q104' => 'REST uses HTTP verbs on resources.',
            'q105' => '80',
            'q106' => 'JavaScript',
        );
        $result = Grader::grade($quiz, $post);
        $this->assertSame(6, $result['earned']);
        $this->assertSame(6, $result['possible']);
        $this->assertSame(5, $result['essay_possible']);
        $this->assertSame(Grader::CORRECT, $result['items'][101]['status']);
        $this->assertSame(Grader::CORRECT, $result['items'][102]['status']);
        $this->assertSame(Grader::CORRECT, $result['items'][103]['status']);
        $this->assertSame(Grader::UNSCORED, $result['items'][104]['status']);
        $this->assertSame(0, $result['items'][104]['earned']);
        $this->assertSame(Grader::CORRECT, $result['items'][105]['status']);
        $this->assertSame(Grader::CORRECT, $result['items'][106]['status']);
    }

    public function testFillBlankIsCaseInsensitiveAndAcceptsEither() {
        $quiz = SampleQuiz::build(1);
        $base = $this->blankPost();
        $base['q105'] = 'EIGHTY';
        $result = Grader::grade($quiz, $base);
        $this->assertSame(Grader::CORRECT, $result['items'][105]['status']);
    }

    public function testPatternMatchContainsAndCaseInsensitive() {
        $quiz = SampleQuiz::build(1);
        $base = $this->blankPost();
        $base['q106'] = 'I prefer TypeScript actually';
        $result = Grader::grade($quiz, $base);
        $this->assertSame(Grader::CORRECT, $result['items'][106]['status']);
    }

    public function testMultipleResponseAllOrNothing() {
        $quiz = SampleQuiz::build(1);
        $base = $this->blankPost();
        $base['q102'] = array(1021);
        $result = Grader::grade($quiz, $base);
        $this->assertSame(Grader::INCORRECT, $result['items'][102]['status']);
        $this->assertSame(0, $result['items'][102]['earned']);

        $base['q102'] = array(1021, 1022, 1023);
        $result = Grader::grade($quiz, $base);
        $this->assertSame(Grader::INCORRECT, $result['items'][102]['status']);
    }

    public function testWrongAndBlankAreIncorrect() {
        $quiz = SampleQuiz::build(1);
        $result = Grader::grade($quiz, array());
        $this->assertSame(0, $result['earned']);
        $this->assertSame(Grader::INCORRECT, $result['items'][101]['status']);
        $this->assertSame(Grader::INCORRECT, $result['items'][105]['status']);
        $this->assertSame(Grader::UNSCORED, $result['items'][104]['status']);
    }

    public function testPatternMatchCaseSensitive() {
        $q = new Question();
        $q->id = 1;
        $q->type = QuestionTypes::PATTERN_MATCH;
        $q->points = 1;
        $q->prompt = 'x';
        $q->case_sensitive = true;
        $q->answers = array(Answer::make('Script', true, 1, 11));
        $this->assertTrue(Grader::gradeQuestion($q, 'JavaScript')['status'] === Grader::CORRECT);
        $this->assertTrue(Grader::gradeQuestion($q, 'javascript')['status'] === Grader::INCORRECT);
    }

    /**
     * @return array<string, mixed>
     */
    private function blankPost() {
        return array(
            'q101' => 1011,
            'q102' => array(1021, 1022),
            'q103' => 1032,
            'q104' => '',
            'q105' => 'nope',
            'q106' => 'Python',
        );
    }
}
