<?php

namespace Tsugi\Services\Quiz1;

use Tsugi\Core\LTIX;

/**
 * Load and persist Quiz1 semantic records. Does not know about QTI XML.
 */
class QuizRepository {

    /**
     * @return Quiz[]
     */
    public static function listForContext($context_id) {
        global $CFG, $PDOX;
        LTIX::getConnection();

        $rows = $PDOX->allRowsDie(
            "SELECT Q.quiz_id, Q.title, Q.instructions, Q.created_at, Q.updated_at,
                    (SELECT COUNT(*) FROM {$CFG->dbprefix}quiz1_question QQ WHERE QQ.quiz_id = Q.quiz_id) AS question_count
             FROM {$CFG->dbprefix}quiz1_quiz Q
             WHERE Q.context_id = :CID
             ORDER BY Q.updated_at DESC, Q.quiz_id DESC",
            array(':CID' => $context_id)
        );

        $quizzes = array();
        foreach ( $rows as $row ) {
            $quiz = new Quiz();
            $quiz->id = (int) $row['quiz_id'];
            $quiz->title = $row['title'];
            $quiz->instructions = $row['instructions'] ?? '';
            $quiz->question_count = (int) $row['question_count'];
            $quizzes[] = $quiz;
        }
        return $quizzes;
    }

    /**
     * @return Quiz|null
     */
    public static function load($quiz_id, $context_id) {
        global $CFG, $PDOX;
        LTIX::getConnection();

        $row = $PDOX->rowDie(
            "SELECT quiz_id, context_id, user_id, title, instructions
             FROM {$CFG->dbprefix}quiz1_quiz
             WHERE quiz_id = :QID AND context_id = :CID",
            array(':QID' => $quiz_id, ':CID' => $context_id)
        );
        if ( ! $row ) {
            return null;
        }

        $quiz = new Quiz();
        $quiz->id = (int) $row['quiz_id'];
        $quiz->context_id = (int) $row['context_id'];
        $quiz->user_id = (int) $row['user_id'];
        $quiz->title = $row['title'];
        $quiz->instructions = $row['instructions'] ?? '';
        $quiz->questions = self::loadQuestions((int) $row['quiz_id']);
        return $quiz;
    }

    /**
     * @return Question[]
     */
    public static function loadQuestions($quiz_id) {
        global $CFG, $PDOX;
        LTIX::getConnection();

        $qrows = $PDOX->allRowsDie(
            "SELECT question_id, quiz_id, sequence, qtype, title, prompt, points, feedback, extra
             FROM {$CFG->dbprefix}quiz1_question
             WHERE quiz_id = :QID
             ORDER BY sequence ASC, question_id ASC",
            array(':QID' => $quiz_id)
        );

        $questions = array();
        $ids = array();
        foreach ( $qrows as $row ) {
            $q = self::questionFromRow($row);
            $questions[$q->id] = $q;
            $ids[] = $q->id;
        }

        if ( count($ids) > 0 ) {
            $in = implode(',', array_map('intval', $ids));
            $arows = $PDOX->allRowsDie(
                "SELECT answer_id, question_id, sequence, answer_text, is_correct, feedback
                 FROM {$CFG->dbprefix}quiz1_answer
                 WHERE question_id IN ({$in})
                 ORDER BY sequence ASC, answer_id ASC"
            );
            foreach ( $arows as $arow ) {
                $qid = (int) $arow['question_id'];
                if ( ! isset($questions[$qid]) ) {
                    continue;
                }
                $questions[$qid]->answers[] = Answer::make(
                    $arow['answer_text'],
                    (int) $arow['is_correct'] === 1,
                    (int) $arow['sequence'],
                    (int) $arow['answer_id']
                );
            }
        }

        return array_values($questions);
    }

    /**
     * @return Question|null
     */
    public static function loadQuestion($question_id, $context_id) {
        global $CFG, $PDOX;
        LTIX::getConnection();

        $row = $PDOX->rowDie(
            "SELECT QQ.question_id, QQ.quiz_id, QQ.sequence, QQ.qtype, QQ.title, QQ.prompt,
                    QQ.points, QQ.feedback, QQ.extra
             FROM {$CFG->dbprefix}quiz1_question QQ
             JOIN {$CFG->dbprefix}quiz1_quiz Q ON Q.quiz_id = QQ.quiz_id
             WHERE QQ.question_id = :QID AND Q.context_id = :CID",
            array(':QID' => $question_id, ':CID' => $context_id)
        );
        if ( ! $row ) {
            return null;
        }
        $q = self::questionFromRow($row);
        $arows = $PDOX->allRowsDie(
            "SELECT answer_id, question_id, sequence, answer_text, is_correct, feedback
             FROM {$CFG->dbprefix}quiz1_answer
             WHERE question_id = :QID
             ORDER BY sequence ASC, answer_id ASC",
            array(':QID' => $q->id)
        );
        foreach ( $arows as $arow ) {
            $q->answers[] = Answer::make(
                $arow['answer_text'],
                (int) $arow['is_correct'] === 1,
                (int) $arow['sequence'],
                (int) $arow['answer_id']
            );
        }
        return $q;
    }

    public static function insertQuiz(Quiz $quiz) {
        global $CFG, $PDOX;
        LTIX::getConnection();

        $PDOX->queryDie(
            "INSERT INTO {$CFG->dbprefix}quiz1_quiz
                (context_id, user_id, title, instructions, created_at, updated_at)
             VALUES (:CID, :UID, :title, :instructions, NOW(), NOW())",
            array(
                ':CID' => $quiz->context_id,
                ':UID' => $quiz->user_id,
                ':title' => $quiz->title,
                ':instructions' => $quiz->instructions !== '' ? $quiz->instructions : null,
            )
        );
        $quiz->id = (int) $PDOX->lastInsertId();
        foreach ( $quiz->questions as $question ) {
            $question->quiz_id = $quiz->id;
            self::insertQuestion($question);
        }
        return $quiz->id;
    }

    public static function updateQuizMeta(Quiz $quiz) {
        global $CFG, $PDOX;
        LTIX::getConnection();

        $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}quiz1_quiz
             SET title = :title, instructions = :instructions, updated_at = NOW()
             WHERE quiz_id = :QID AND context_id = :CID",
            array(
                ':title' => $quiz->title,
                ':instructions' => $quiz->instructions !== '' ? $quiz->instructions : null,
                ':QID' => $quiz->id,
                ':CID' => $quiz->context_id,
            )
        );
    }

    public static function deleteQuiz($quiz_id, $context_id) {
        global $CFG, $PDOX;
        LTIX::getConnection();

        $PDOX->queryDie(
            "DELETE FROM {$CFG->dbprefix}quiz1_quiz WHERE quiz_id = :QID AND context_id = :CID",
            array(':QID' => $quiz_id, ':CID' => $context_id)
        );
    }

    public static function insertQuestion(Question $question) {
        global $CFG, $PDOX;
        LTIX::getConnection();

        if ( $question->sequence < 1 ) {
            $row = $PDOX->rowDie(
                "SELECT COALESCE(MAX(sequence), 0) AS mx FROM {$CFG->dbprefix}quiz1_question WHERE quiz_id = :QID",
                array(':QID' => $question->quiz_id)
            );
            $question->sequence = ((int) $row['mx']) + 1;
        }

        $extra = $question->extraArray();
        $PDOX->queryDie(
            "INSERT INTO {$CFG->dbprefix}quiz1_question
                (quiz_id, sequence, qtype, title, prompt, points, feedback, extra, created_at, updated_at)
             VALUES (:quiz_id, :sequence, :qtype, :title, :prompt, :points, :feedback, :extra, NOW(), NOW())",
            array(
                ':quiz_id' => $question->quiz_id,
                ':sequence' => $question->sequence,
                ':qtype' => $question->type,
                ':title' => $question->title !== '' ? $question->title : null,
                ':prompt' => $question->prompt,
                ':points' => (int) $question->points,
                ':feedback' => $question->feedback !== '' ? $question->feedback : null,
                ':extra' => count($extra) ? json_encode($extra) : null,
            )
        );
        $question->id = (int) $PDOX->lastInsertId();
        self::replaceAnswers($question);
        self::touchQuiz($question->quiz_id);
        return $question->id;
    }

    public static function updateQuestion(Question $question) {
        global $CFG, $PDOX;
        LTIX::getConnection();

        $extra = $question->extraArray();
        $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}quiz1_question
             SET qtype = :qtype, title = :title, prompt = :prompt, points = :points,
                 feedback = :feedback, extra = :extra, updated_at = NOW()
             WHERE question_id = :QID AND quiz_id = :quiz_id",
            array(
                ':qtype' => $question->type,
                ':title' => $question->title !== '' ? $question->title : null,
                ':prompt' => $question->prompt,
                ':points' => (int) $question->points,
                ':feedback' => $question->feedback !== '' ? $question->feedback : null,
                ':extra' => count($extra) ? json_encode($extra) : null,
                ':QID' => $question->id,
                ':quiz_id' => $question->quiz_id,
            )
        );
        self::replaceAnswers($question);
        self::touchQuiz($question->quiz_id);
    }

    public static function deleteQuestion($question_id, $context_id) {
        global $CFG, $PDOX;
        LTIX::getConnection();

        $row = $PDOX->rowDie(
            "SELECT QQ.question_id, QQ.quiz_id
             FROM {$CFG->dbprefix}quiz1_question QQ
             JOIN {$CFG->dbprefix}quiz1_quiz Q ON Q.quiz_id = QQ.quiz_id
             WHERE QQ.question_id = :QID AND Q.context_id = :CID",
            array(':QID' => $question_id, ':CID' => $context_id)
        );
        if ( ! $row ) {
            return false;
        }
        $PDOX->queryDie(
            "DELETE FROM {$CFG->dbprefix}quiz1_question WHERE question_id = :QID",
            array(':QID' => $question_id)
        );
        self::renumber($row['quiz_id']);
        self::touchQuiz($row['quiz_id']);
        return true;
    }

    public static function moveQuestion($question_id, $context_id, $direction) {
        global $CFG, $PDOX;
        LTIX::getConnection();

        $row = $PDOX->rowDie(
            "SELECT QQ.question_id, QQ.quiz_id, QQ.sequence
             FROM {$CFG->dbprefix}quiz1_question QQ
             JOIN {$CFG->dbprefix}quiz1_quiz Q ON Q.quiz_id = QQ.quiz_id
             WHERE QQ.question_id = :QID AND Q.context_id = :CID",
            array(':QID' => $question_id, ':CID' => $context_id)
        );
        if ( ! $row ) {
            return false;
        }

        $quiz_id = (int) $row['quiz_id'];
        $seq = (int) $row['sequence'];
        if ( $direction === 'up' ) {
            $swap = $PDOX->rowDie(
                "SELECT question_id, sequence FROM {$CFG->dbprefix}quiz1_question
                 WHERE quiz_id = :QID AND (sequence < :SEQ OR (sequence = :SEQ AND question_id < :QID2))
                 ORDER BY sequence DESC, question_id DESC LIMIT 1",
                array(':QID' => $quiz_id, ':SEQ' => $seq, ':QID2' => $question_id)
            );
        } else {
            $swap = $PDOX->rowDie(
                "SELECT question_id, sequence FROM {$CFG->dbprefix}quiz1_question
                 WHERE quiz_id = :QID AND (sequence > :SEQ OR (sequence = :SEQ AND question_id > :QID2))
                 ORDER BY sequence ASC, question_id ASC LIMIT 1",
                array(':QID' => $quiz_id, ':SEQ' => $seq, ':QID2' => $question_id)
            );
        }
        if ( ! $swap ) {
            return true;
        }

        $PDOX->beginTransaction();
        try {
            $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}quiz1_question SET sequence = :SEQ WHERE question_id = :QID",
                array(':SEQ' => (int) $swap['sequence'], ':QID' => $question_id)
            );
            $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}quiz1_question SET sequence = :SEQ WHERE question_id = :QID",
                array(':SEQ' => $seq, ':QID' => $swap['question_id'])
            );
            $PDOX->commit();
        } catch ( \Exception $e ) {
            $PDOX->rollBack();
            throw $e;
        }
        self::touchQuiz($quiz_id);
        return true;
    }

    private static function replaceAnswers(Question $question) {
        global $CFG, $PDOX;

        $PDOX->queryDie(
            "DELETE FROM {$CFG->dbprefix}quiz1_answer WHERE question_id = :QID",
            array(':QID' => $question->id)
        );

        $seq = 1;
        foreach ( $question->answers as $ans ) {
            if ( Question::isBlankHtml($ans->text) ) {
                continue;
            }
            $PDOX->queryDie(
                "INSERT INTO {$CFG->dbprefix}quiz1_answer
                    (question_id, sequence, answer_text, is_correct, feedback, created_at)
                 VALUES (:QID, :sequence, :text, :correct, :feedback, NOW())",
                array(
                    ':QID' => $question->id,
                    ':sequence' => $seq,
                    ':text' => $ans->text,
                    ':correct' => $ans->correct ? 1 : 0,
                    ':feedback' => $ans->feedback !== '' ? $ans->feedback : null,
                )
            );
            $seq++;
        }
    }

    private static function renumber($quiz_id) {
        global $CFG, $PDOX;
        $rows = $PDOX->allRowsDie(
            "SELECT question_id FROM {$CFG->dbprefix}quiz1_question
             WHERE quiz_id = :QID ORDER BY sequence ASC, question_id ASC",
            array(':QID' => $quiz_id)
        );
        $n = 1;
        foreach ( $rows as $row ) {
            $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}quiz1_question SET sequence = :SEQ WHERE question_id = :QID",
                array(':SEQ' => $n, ':QID' => $row['question_id'])
            );
            $n++;
        }
    }

    private static function touchQuiz($quiz_id) {
        global $CFG, $PDOX;
        $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}quiz1_quiz SET updated_at = NOW() WHERE quiz_id = :QID",
            array(':QID' => $quiz_id)
        );
    }

    private static function questionFromRow(array $row) {
        $q = new Question();
        $q->id = (int) $row['question_id'];
        $q->quiz_id = (int) $row['quiz_id'];
        $q->sequence = (int) $row['sequence'];
        $q->type = $row['qtype'];
        $q->title = $row['title'] ?? '';
        $q->prompt = $row['prompt'];
        $q->points = (int) $row['points'];
        $q->feedback = $row['feedback'] ?? '';
        $q->applyExtra($row['extra'] ?? null);
        return $q;
    }
}
