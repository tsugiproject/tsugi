<?php

use Tsugi\Services\Quiz1\Answer;
use Tsugi\Services\Quiz1\Grader;
use Tsugi\Services\Quiz1\Question;
use Tsugi\Services\Quiz1\QuestionTypes;
use Tsugi\Services\Quiz1\SampleQuiz1;

class GraderTest extends \PHPUnit\Framework\TestCase
{
    public function testSampleQuiz1PerfectScore() {
        $quiz = SampleQuiz1::build(1);
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
        $score = Grader::gradebookScore($result);
        $this->assertEqualsWithDelta(6 / 11, $score['grade'], 0.0001);
        $this->assertTrue($score['pending_manual']);
        $this->assertSame(6, $score['earned']);
        $this->assertSame(11, $score['total']);
    }

    public function testAutoOnlyGradebookScoreIsComplete() {
        $score = Grader::gradebookScore(array(
            'earned' => 3,
            'possible' => 4,
            'essay_possible' => 0,
        ));
        $this->assertEqualsWithDelta(0.75, $score['grade'], 0.0001);
        $this->assertFalse($score['pending_manual']);
        $this->assertSame(4, $score['total']);
    }

    public function testUngradedEssayStaysInTheDenominator() {
        $score = Grader::gradebookScore(array(
            'earned' => 4,
            'possible' => 4,
            'essay_possible' => 1,
        ));
        $this->assertEqualsWithDelta(0.8, $score['grade'], 0.0001);
        $this->assertTrue($score['pending_manual']);
        $this->assertSame(5, $score['total']);
    }

    public function testEssayOnlyGradebookScoreIsZeroUntilGraded() {
        $score = Grader::gradebookScore(array(
            'earned' => 0,
            'possible' => 0,
            'essay_possible' => 5,
        ));
        $this->assertEqualsWithDelta(0.0, $score['grade'], 0.0001);
        $this->assertTrue($score['pending_manual']);
        $this->assertSame(5, $score['total']);
    }

    public function testQuizWithNoPointsHasNoGradebookScore() {
        $this->assertNull(Grader::gradebookScore(array(
            'earned' => 0,
            'possible' => 0,
            'essay_possible' => 0,
        )));
    }

    public function testFillBlankIsCaseInsensitiveAndAcceptsEither() {
        $quiz = SampleQuiz1::build(1);
        $base = $this->blankPost();
        $base['q105'] = 'EIGHTY';
        $result = Grader::grade($quiz, $base);
        $this->assertSame(Grader::CORRECT, $result['items'][105]['status']);
    }

    public function testFillBlankUnicodeCaseInsensitive() {
        $q = new Question();
        $q->id = 1;
        $q->type = QuestionTypes::FILL_BLANK;
        $q->points = 1;
        $q->prompt = 'x';
        $q->answers = array(Answer::make('Café', true, 1, 11));
        $this->assertSame(Grader::CORRECT, Grader::gradeQuestion($q, 'CAFÉ')['status']);
        $this->assertSame(Grader::INCORRECT, Grader::gradeQuestion($q, 'Cafe')['status']);
    }

    public function testPatternMatchContainsAndCaseInsensitive() {
        $quiz = SampleQuiz1::build(1);
        $base = $this->blankPost();
        $base['q106'] = 'I prefer TypeScript actually';
        $result = Grader::grade($quiz, $base);
        $this->assertSame(Grader::CORRECT, $result['items'][106]['status']);
    }

    public function testMultipleResponseAllOrNothing() {
        $quiz = SampleQuiz1::build(1);
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
        $quiz = SampleQuiz1::build(1);
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
