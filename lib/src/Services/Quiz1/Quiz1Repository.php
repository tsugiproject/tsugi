<?php

namespace Tsugi\Services\Quiz1;

use Tsugi\Core\LTIX;
use Tsugi\Grades\GradeUtil;
use Tsugi\Services\Lessons\LessonDue;
use Tsugi\Util\U;

/**
 * Load and persist Quiz1 semantic records. Does not know about QTI XML.
 */
class Quiz1Repository {

    /**
     * @return Quiz1[]
     */
    public static function listForContext($context_id) {
        global $CFG, $PDOX;
        LTIX::getConnection();

        $rows = $PDOX->allRowsDie(
            "SELECT Q.quiz_id, Q.title, Q.instructions, Q.created_at, Q.updated_at, Q.link_id,
                    L.published AS published, L.end_datetime AS end_datetime,
                    (SELECT COUNT(*) FROM {$CFG->dbprefix}quiz1_question QQ WHERE QQ.quiz_id = Q.quiz_id) AS question_count
             FROM {$CFG->dbprefix}quiz1_quiz Q
             LEFT JOIN {$CFG->dbprefix}lti_link L ON L.link_id = Q.link_id
             WHERE Q.context_id = :CID
             ORDER BY Q.updated_at DESC, Q.quiz_id DESC",
            array(':CID' => $context_id)
        );

        $quizzes = array();
        foreach ( $rows as $row ) {
            $quiz = new Quiz1();
            $quiz->id = (int) $row['quiz_id'];
            $quiz->title = $row['title'];
            $quiz->instructions = $row['instructions'] ?? '';
            $quiz->question_count = (int) $row['question_count'];
            $quiz->link_id = isset($row['link_id']) ? (int) $row['link_id'] : null;
            $quiz->published = $quiz->link_id ? (int) $row['published'] : null;
            $quiz->due = $quiz->link_id ? GradeUtil::dueDateInputValue($row['end_datetime'] ?? null) : null;
            $quizzes[] = $quiz;
        }
        return $quizzes;
    }

    /**
     * @return Quiz1|null
     */
    public static function load($quiz_id, $context_id) {
        global $CFG, $PDOX;
        LTIX::getConnection();

        $row = $PDOX->rowDie(
            "SELECT Q.quiz_id, Q.context_id, Q.user_id, Q.title, Q.instructions, Q.link_id,
                    L.link_key AS link_key, L.published AS published
             FROM {$CFG->dbprefix}quiz1_quiz Q
             LEFT JOIN {$CFG->dbprefix}lti_link L ON L.link_id = Q.link_id
             WHERE Q.quiz_id = :QID AND Q.context_id = :CID",
            array(':QID' => $quiz_id, ':CID' => $context_id)
        );
        if ( ! $row ) {
            return null;
        }

        $quiz = new Quiz1();
        $quiz->id = (int) $row['quiz_id'];
        $quiz->context_id = (int) $row['context_id'];
        $quiz->user_id = (int) $row['user_id'];
        $quiz->title = $row['title'];
        $quiz->instructions = $row['instructions'] ?? '';
        $quiz->link_id = isset($row['link_id']) ? (int) $row['link_id'] : null;
        $quiz->link_key = (isset($row['link_key']) && is_string($row['link_key']) && $row['link_key'] !== '')
            ? $row['link_key'] : null;
        $quiz->published = $quiz->link_id ? (int) $row['published'] : null;
        $quiz->questions = self::loadQuestions((int) $row['quiz_id']);
        return $quiz;
    }

    /**
     * The quiz whose launch link is $link_id in this course.
     *
     * @return Quiz1|null
     */
    public static function loadByLink($link_id, $context_id) {
        global $CFG, $PDOX;
        LTIX::getConnection();

        $row = $PDOX->rowDie(
            "SELECT Q.quiz_id
             FROM {$CFG->dbprefix}quiz1_quiz Q
             WHERE Q.link_id = :LID AND Q.context_id = :CID",
            array(':LID' => (int) $link_id, ':CID' => (int) $context_id)
        );
        if ( ! $row ) {
            return null;
        }
        return self::load((int) $row['quiz_id'], (int) $context_id);
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
                    (int) $arow['answer_id'],
                    $arow['feedback'] ?? ''
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
                (int) $arow['answer_id'],
                $arow['feedback'] ?? ''
            );
        }
        return $q;
    }

    /**
     * @param string|null $linkKey Cartridge resource link id to store on the new link.
     *        quiz1:42 is that id even when this row's quiz_id is not 42.
     *        Null mints a key that is not the primary key.
     */
    public static function insertQuiz(Quiz1 $quiz, $linkKey = null) {
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
        self::ensureLink($quiz->id, (int) $quiz->context_id, is_string($linkKey) ? $linkKey : null);
        return $quiz->id;
    }

    public static function updateQuizMeta(Quiz1 $quiz) {
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

    /**
     * Append questions to an existing quiz (sequence continues after the last item).
     *
     * Best-effort: a failure on one question is recorded and the rest still
     * save. A partial quiz is the intended outcome, not a rollback.
     *
     * @param Question[] $questions
     * @return array{0: int, 1: string[]} Inserted count and per-question errors
     */
    public static function appendQuestions($quiz_id, array $questions) {
        $n = 0;
        $errors = array();
        // Do not wrap this loop in a transaction. Import is liberal: keep
        // every question that saves, record the ones that do not, and leave
        // a partial quiz. Rolling back the whole file because one item failed
        // would discard work the instructor can already use and edit.
        foreach ( $questions as $i => $question ) {
            if ( ! $question instanceof Question ) {
                continue;
            }
            $question->id = null;
            $question->quiz_id = (int) $quiz_id;
            $question->sequence = 0;
            foreach ( $question->answers as $ans ) {
                $ans->id = null;
            }
            try {
                self::insertQuestion($question);
                $n++;
            } catch ( \Exception $e ) {
                $label = $question->title !== '' ? $question->title : Html::excerpt($question->prompt, 40);
                if ( $label === '' ) {
                    $label = 'Question ' . ((int) $i + 1);
                }
                $errors[] = $label . ': ' . $e->getMessage();
            }
        }
        return array($n, $errors);
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

    /**
     * Legacy link key quiz1:{quiz_id}. Rows already stored this way keep it.
     * A quiz with no link yet is still addressed this way until ensureLink runs.
     */
    public static function linkKey($quiz_id) {
        return 'quiz1:' . (int) $quiz_id;
    }

    /**
     * True for the legacy quiz1:{quiz_id} form. A newer key is quiz1_k plus hex.
     *
     * @param mixed $key
     */
    public static function isLegacyLinkKey($key) {
        return is_string($key) && preg_match('/^quiz1:\d+$/', $key) === 1;
    }

    /**
     * True when this resource link id belongs to a quiz, including a legacy
     * quiz1:{id} and a key minted as quiz1_k plus hex.
     *
     * @param mixed $key
     */
    public static function isQuizLinkKey($key) {
        return is_string($key) && (str_starts_with($key, 'quiz1:') || str_starts_with($key, 'quiz1_'));
    }

    /**
     * Link key for a quiz created from now on. Not the primary key, and no ":",
     * so the cartridge item identifier can be this string.
     */
    public static function allocateLinkKey() {
        return 'quiz1_k'.bin2hex(random_bytes(8));
    }

    /**
     * Cartridge item identifier for this resource link id.
     * ":" is written as _colon_ because an XML identifier cannot contain ":".
     * A key that already contains the letters _colon_ is stored as rlhex_ plus
     * hex, so import does not turn that text into a colon.
     * Other underscores stay underscores. A key that is already a safe identifier,
     * such as quiz1_k… or Q1_ca934192ea441d46, is kept.
     *
     * @param mixed $linkKey
     */
    public static function cartridgeIdentifierForLinkKey($linkKey) {
        $linkKey = is_string($linkKey) ? trim($linkKey) : '';
        if ( ! self::acceptableLinkKey($linkKey) ) {
            return '';
        }
        if ( self::needsHexCartridgeId($linkKey) ) {
            $identifier = 'rlhex_'.bin2hex($linkKey);
        } else {
            $identifier = str_replace(':', '_colon_', $linkKey);
        }
        if ( preg_match('/^[A-Za-z_][A-Za-z0-9_.-]*$/', $identifier) !== 1 ) {
            return '';
        }
        return $identifier;
    }

    /**
     * A key that contains _colon_, or that already looks like an rlhex_ encoding,
     * is stored as hex so the two forms cannot import as each other.
     *
     * @param string $linkKey
     */
    private static function needsHexCartridgeId($linkKey) {
        if ( str_contains($linkKey, '_colon_') ) {
            return true;
        }
        if ( ! str_starts_with($linkKey, 'rlhex_') ) {
            return false;
        }
        $hex = substr($linkKey, strlen('rlhex_'));
        return $hex !== '' && strlen($hex) % 2 === 0 && ctype_xdigit($hex);
    }

    /**
     * Resource link id carried by a quiz item or resource identifier.
     * rlhex_ is the exact key, decoded before _colon_ becomes ":".
     * A plain underscore is left alone, so quiz1_k… and Q1_ca934192ea441d46 stay those strings.
     * A resource identifier's trailing _R is the cartridge suffix, not part of the id.
     *
     * @param mixed $identifier
     */
    public static function linkKeyFromCartridgeIdentifier($identifier) {
        $identifier = is_string($identifier) ? trim($identifier) : '';
        if ( $identifier === '' ) {
            return '';
        }
        if ( str_ends_with($identifier, '_R') && strlen($identifier) > 2 ) {
            $bare = substr($identifier, 0, -2);
            if ( $bare !== '' && ! str_ends_with($bare, '_') ) {
                $identifier = $bare;
            }
        }
        if ( str_starts_with($identifier, 'rlhex_') ) {
            $hex = substr($identifier, strlen('rlhex_'));
            if ( $hex !== '' && strlen($hex) % 2 === 0 && ctype_xdigit($hex) ) {
                $raw = hex2bin($hex);
                if ( is_string($raw) && self::acceptableLinkKey($raw)
                    && self::cartridgeIdentifierForLinkKey($raw) === $identifier ) {
                    return $raw;
                }
            }
        }
        $identifier = str_replace('_colon_', ':', $identifier);
        if ( ! self::acceptableLinkKey($identifier) ) {
            return '';
        }
        return $identifier;
    }

    /**
     * @param mixed $key
     */
    public static function acceptableLinkKey($key) {
        if ( ! is_string($key) ) {
            return false;
        }
        $key = trim($key);
        if ( $key === '' || strlen($key) > 256 ) {
            return false;
        }
        return preg_match('/[\x00-\x1F\x7F]/', $key) !== 1;
    }

    /**
     * link_key stored on this quiz's link, or '' when it has no link.
     *
     * @param int|string $quiz_id
     * @param int|string $context_id
     */
    public static function storedLinkKey($quiz_id, $context_id) {
        global $CFG, $PDOX;
        $quiz_id = (int) $quiz_id;
        $context_id = (int) $context_id;
        if ( $quiz_id < 1 || $context_id < 1 ) {
            return '';
        }
        if ( ! isset($PDOX) || ! is_object($PDOX) || ! isset($CFG) ) {
            return '';
        }
        LTIX::getConnection();
        $row = $PDOX->rowDie(
            "SELECT L.link_key
             FROM {$CFG->dbprefix}quiz1_quiz Q
             JOIN {$CFG->dbprefix}lti_link L ON L.link_id = Q.link_id
             WHERE Q.quiz_id = :QID AND Q.context_id = :CID
               AND (L.deleted IS NULL OR L.deleted = 0)",
            array(':QID' => $quiz_id, ':CID' => $context_id)
        );
        if ( ! is_array($row) || ! isset($row['link_key']) || ! is_string($row['link_key']) ) {
            return '';
        }
        return trim($row['link_key']);
    }

    /**
     * Resource link id lessons and the grade book use for this quiz.
     * The stored key wins. A quiz with no link yet still uses quiz1:{id},
     * unless that legacy key already belongs to a different quiz.
     *
     * @param int|string $quiz_id
     * @param int|string $context_id
     */
    public static function resourceLinkId($quiz_id, $context_id) {
        $quiz_id = (int) $quiz_id;
        $context_id = (int) $context_id;
        if ( $quiz_id < 1 ) {
            return '';
        }
        $stored = self::storedLinkKey($quiz_id, $context_id);
        if ( $stored !== '' ) {
            return $stored;
        }
        $legacy = self::linkKey($quiz_id);
        $owner = self::quizIdByLinkKey($context_id, $legacy);
        if ( $owner > 0 && $owner !== $quiz_id ) {
            return '';
        }
        return $legacy;
    }

    /**
     * Quiz in this course whose link uses $key, or 0.
     *
     * @param int|string $context_id
     * @param mixed $key
     */
    public static function quizIdByLinkKey($context_id, $key) {
        global $CFG, $PDOX;
        $context_id = (int) $context_id;
        if ( $context_id < 1 || ! self::acceptableLinkKey($key) ) {
            return 0;
        }
        if ( ! isset($PDOX) || ! is_object($PDOX) || ! isset($CFG) ) {
            return 0;
        }
        LTIX::getConnection();
        $key = trim((string) $key);
        $row = $PDOX->rowDie(
            "SELECT Q.quiz_id
             FROM {$CFG->dbprefix}quiz1_quiz Q
             JOIN {$CFG->dbprefix}lti_link L ON L.link_id = Q.link_id
             WHERE Q.context_id = :CID AND L.context_id = :CID AND L.link_sha256 = :SHA
               AND (L.deleted IS NULL OR L.deleted = 0)
             LIMIT 1",
            array(
                ':CID' => $context_id,
                ':SHA' => U::lti_sha256($key),
            )
        );
        if ( ! is_array($row) || empty($row['quiz_id']) ) {
            return 0;
        }
        return (int) $row['quiz_id'];
    }

    /**
     * Point this quiz's link at $key when that key is free in the course.
     * Import uses this so the new course keeps the source course's resource link id.
     * A taken key is left alone. Returns the key stored afterward.
     *
     * @param int|string $quiz_id
     * @param int|string $context_id
     * @param mixed $key
     */
    public static function adoptLinkKey($quiz_id, $context_id, $key) {
        global $CFG, $PDOX;
        $quiz_id = (int) $quiz_id;
        $context_id = (int) $context_id;
        $current = self::storedLinkKey($quiz_id, $context_id);
        if ( ! self::acceptableLinkKey($key) ) {
            return $current;
        }
        $key = trim((string) $key);
        if ( $current === $key ) {
            return $key;
        }
        if ( $quiz_id < 1 || $context_id < 1 || ! isset($PDOX) || ! is_object($PDOX) || ! isset($CFG) ) {
            return $current;
        }
        LTIX::getConnection();
        $owner = self::quizIdByLinkKey($context_id, $key);
        if ( $owner > 0 && $owner !== $quiz_id ) {
            return $current;
        }
        $taken = self::linkIdByKey($context_id, $key);
        $row = $PDOX->rowDie(
            "SELECT link_id FROM {$CFG->dbprefix}quiz1_quiz
             WHERE quiz_id = :QID AND context_id = :CID",
            array(':QID' => $quiz_id, ':CID' => $context_id)
        );
        $link_id = (is_array($row) && ! empty($row['link_id'])) ? (int) $row['link_id'] : 0;
        if ( $link_id < 1 ) {
            if ( $taken > 0 ) {
                return $current;
            }
            $created = self::ensureLink($quiz_id, $context_id, $key);
            return $created ? self::storedLinkKey($quiz_id, $context_id) : $current;
        }
        if ( $taken > 0 && $taken !== $link_id ) {
            return $current;
        }
        $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}lti_link
             SET link_key = :LK, link_sha256 = :SHA, updated_at = NOW()
             WHERE link_id = :LID AND context_id = :CID",
            array(
                ':LK' => $key,
                ':SHA' => U::lti_sha256($key),
                ':LID' => $link_id,
                ':CID' => $context_id,
            )
        );
        GradeUtil::invalidateDueDatesCache($context_id);
        return $key;
    }

    /**
     * The quiz's one lti_link. A new quiz gets this row unpublished.
     * The new row's key is not the quiz id. An existing row is left as stored.
     *
     * @param string|null $preferredKey Import: the source course's resource link id, when it is free
     * @return int|null link_id, or null when the quiz is not in this context
     */
    public static function ensureLink($quiz_id, $context_id, $preferredKey = null) {
        global $CFG, $PDOX;
        LTIX::getConnection();

        $quiz_id = (int) $quiz_id;
        $context_id = (int) $context_id;
        if ( $quiz_id < 1 || $context_id < 1 ) {
            return null;
        }
        $row = $PDOX->rowDie(
            "SELECT quiz_id, title, link_id
             FROM {$CFG->dbprefix}quiz1_quiz
             WHERE quiz_id = :QID AND context_id = :CID",
            array(':QID' => $quiz_id, ':CID' => $context_id)
        );
        if ( ! $row ) {
            return null;
        }

        $link_id = isset($row['link_id']) ? (int) $row['link_id'] : 0;
        if ( $link_id > 0 ) {
            return $link_id;
        }

        $link_key = self::newLinkKey($context_id, $preferredKey);
        $PDOX->queryDie(
            "INSERT INTO {$CFG->dbprefix}lti_link
                (link_key, link_sha256, title, context_id, path, published, created_at, updated_at)
             VALUES
                (:link_key, :link_sha256, :title, :context_id, :path, 0, NOW(), NOW())",
            array(
                ':link_key' => $link_key,
                ':link_sha256' => U::lti_sha256($link_key),
                ':title' => $row['title'],
                ':context_id' => $context_id,
                ':path' => 'quiz1/' . $quiz_id,
            )
        );
        $link_id = (int) $PDOX->lastInsertId();
        $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}quiz1_quiz
             SET link_id = :LID, updated_at = NOW()
             WHERE quiz_id = :QID AND context_id = :CID",
            array(':LID' => $link_id, ':QID' => $quiz_id, ':CID' => $context_id)
        );
        LessonDue::copyOntoLink($context_id, $link_id);
        GradeUtil::invalidateDueDatesCache($context_id);
        return $link_id;
    }

    /**
     * Preferred key when it is free in this course, otherwise a new allocated key.
     *
     * @param mixed $preferredKey
     */
    private static function newLinkKey($context_id, $preferredKey) {
        if ( self::acceptableLinkKey($preferredKey) ) {
            $preferredKey = trim((string) $preferredKey);
            if ( self::linkIdByKey($context_id, $preferredKey) < 1 ) {
                return $preferredKey;
            }
        }
        return self::allocateLinkKey();
    }

    /**
     * @param int $context_id
     * @param string $link_key
     */
    private static function linkIdByKey($context_id, $link_key) {
        global $CFG, $PDOX;
        $sha = U::lti_sha256($link_key);
        if ( ! is_string($sha) ) {
            return 0;
        }
        $row = $PDOX->rowDie(
            "SELECT link_id FROM {$CFG->dbprefix}lti_link
             WHERE context_id = :CID AND link_sha256 = :SHA
             LIMIT 1",
            array(':CID' => (int) $context_id, ':SHA' => $sha)
        );
        return (is_array($row) && ! empty($row['link_id'])) ? (int) $row['link_id'] : 0;
    }

    /**
     * Mark the quiz's link published. The link already exists for a quiz created now.
     *
     * @return int|null link_id, or null when the quiz is not in this context
     */
    public static function publish($quiz_id, $context_id) {
        global $CFG, $PDOX;
        $quiz_id = (int) $quiz_id;
        $context_id = (int) $context_id;
        $link_id = self::ensureLink($quiz_id, $context_id);
        if ( ! $link_id ) {
            return null;
        }

        LTIX::getConnection();
        $row = $PDOX->rowDie(
            "SELECT title FROM {$CFG->dbprefix}quiz1_quiz
             WHERE quiz_id = :QID AND context_id = :CID",
            array(':QID' => $quiz_id, ':CID' => $context_id)
        );
        $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}lti_link
             SET published = 1, title = :title, updated_at = NOW()
             WHERE link_id = :LID AND context_id = :CID",
            array(
                ':title' => $row ? $row['title'] : '',
                ':LID' => $link_id,
                ':CID' => $context_id,
            )
        );
        LessonDue::copyOntoLink($context_id, $link_id, true);
        return $link_id;
    }

    /**
     * Soft-unpublish. The link row and quiz1_quiz.link_id stay.
     *
     * @return bool False when the quiz has never been published or is not in this context.
     */
    public static function unpublish($quiz_id, $context_id) {
        global $CFG, $PDOX;
        LTIX::getConnection();

        $quiz_id = (int) $quiz_id;
        $context_id = (int) $context_id;
        $row = $PDOX->rowDie(
            "SELECT link_id
             FROM {$CFG->dbprefix}quiz1_quiz
             WHERE quiz_id = :QID AND context_id = :CID",
            array(':QID' => $quiz_id, ':CID' => $context_id)
        );
        if ( ! $row || ! isset($row['link_id']) ) {
            return false;
        }

        $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}lti_link
             SET published = 0, updated_at = NOW()
             WHERE link_id = :LID AND context_id = :CID",
            array(':LID' => (int) $row['link_id'], ':CID' => $context_id)
        );
        return true;
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
