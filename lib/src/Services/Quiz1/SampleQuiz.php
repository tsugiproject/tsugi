<?php

namespace Tsugi\Services\Quiz1;

/**
 * Representative quiz exercising every Common Cartridge QTI question type.
 *
 * Used by automated export tests and by the instructor "Create sample quiz"
 * action for manual Canvas/Sakai import experiments.
 */
class SampleQuiz {

    public static function title() {
        return 'QTI Export Test';
    }

    /**
     * In-memory quiz with stable fake ids so export is deterministic.
     */
    public static function build($quiz_id = 1) {
        $quiz = new Quiz();
        $quiz->id = (int) $quiz_id;
        $quiz->title = self::title();
        $quiz->instructions = '<p>Sample quiz for Common Cartridge QTI 1.2.1 export. Characters: & < > " \'</p>';

        $quiz->questions[] = self::mc($quiz_id);
        $quiz->questions[] = self::mr($quiz_id);
        $quiz->questions[] = self::tf($quiz_id);
        $quiz->questions[] = self::essay($quiz_id);
        $quiz->questions[] = self::fib($quiz_id);
        $quiz->questions[] = self::pattern($quiz_id);

        return $quiz;
    }

    private static function mc($quiz_id) {
        $q = new Question();
        $q->id = $quiz_id * 100 + 1;
        $q->quiz_id = $quiz_id;
        $q->sequence = 1;
        $q->type = QuestionTypes::MULTIPLE_CHOICE;
        $q->title = 'HTTP protocol';
        $q->prompt = '<p>Which protocol is used for the web? Special chars: 2 < 3 & 4 > 1</p>';
        $q->points = 1;
        $q->answers = array(
            Answer::make('HTTP', true, 1, $q->id * 10 + 1),
            Answer::make('FTP', false, 2, $q->id * 10 + 2),
            Answer::make('SMTP', false, 3, $q->id * 10 + 3),
            Answer::make('SSH', false, 4, $q->id * 10 + 4),
        );
        return $q;
    }

    private static function mr($quiz_id) {
        $q = new Question();
        $q->id = $quiz_id * 100 + 2;
        $q->quiz_id = $quiz_id;
        $q->sequence = 2;
        $q->type = QuestionTypes::MULTIPLE_RESPONSE;
        $q->title = 'HTTP methods';
        $q->prompt = '<p>Select <strong>all</strong> HTTP methods.</p>';
        $q->points = 2;
        $q->answers = array(
            Answer::make('GET', true, 1, $q->id * 10 + 1),
            Answer::make('POST', true, 2, $q->id * 10 + 2),
            Answer::make('HTML', false, 3, $q->id * 10 + 3),
            Answer::make('FTP', false, 4, $q->id * 10 + 4),
        );
        return $q;
    }

    private static function tf($quiz_id) {
        $q = new Question();
        $q->id = $quiz_id * 100 + 3;
        $q->quiz_id = $quiz_id;
        $q->sequence = 3;
        $q->type = QuestionTypes::TRUE_FALSE;
        $q->title = 'HTML language';
        $q->prompt = '<p>HTML is a programming language.</p>';
        $q->points = 1;
        $q->answers = array(
            Answer::make('True', false, 1, $q->id * 10 + 1),
            Answer::make('False', true, 2, $q->id * 10 + 2),
        );
        return $q;
    }

    private static function essay($quiz_id) {
        $q = new Question();
        $q->id = $quiz_id * 100 + 4;
        $q->quiz_id = $quiz_id;
        $q->sequence = 4;
        $q->type = QuestionTypes::ESSAY;
        $q->title = 'REST';
        $q->prompt = '<p>Explain REST in your own words.</p>';
        $q->points = 5;
        $q->sample_solution = '<p>Representational State Transfer uses HTTP verbs on resources.</p>';
        return $q;
    }

    private static function fib($quiz_id) {
        $q = new Question();
        $q->id = $quiz_id * 100 + 5;
        $q->quiz_id = $quiz_id;
        $q->sequence = 5;
        $q->type = QuestionTypes::FILL_BLANK;
        $q->title = 'HTTP port';
        $q->prompt = '<p>The default HTTP port is ____.</p>';
        $q->points = 1;
        $q->answers = array(
            Answer::make('80', true, 1, $q->id * 10 + 1),
            Answer::make('eighty', true, 2, $q->id * 10 + 2),
        );
        return $q;
    }

    private static function pattern($quiz_id) {
        $q = new Question();
        $q->id = $quiz_id * 100 + 6;
        $q->quiz_id = $quiz_id;
        $q->sequence = 6;
        $q->type = QuestionTypes::PATTERN_MATCH;
        $q->title = 'Script languages';
        $q->prompt = '<p>Name a language whose name contains "Script".</p>';
        $q->points = 1;
        $q->case_sensitive = false;
        $q->answers = array(
            Answer::make('Script', true, 1, $q->id * 10 + 1),
        );
        return $q;
    }
}
