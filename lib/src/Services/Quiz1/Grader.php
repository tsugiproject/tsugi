<?php

namespace Tsugi\Services\Quiz1;

/**
 * Score a Quiz1 quiz from submitted student responses.
 *
 * Matches Common Cartridge QTI 1.2.1 rules used by Qti12Exporter:
 * multiple choice / true-false = one correct choice; multiple response =
 * all-or-nothing; fill-in-the-blank = case-insensitive exact; pattern match
 * = substring (optional case). Essays are not computer-scored.
 *
 * Does not persist attempts.
 */
class Grader {

    const CORRECT = 'correct';
    const INCORRECT = 'incorrect';
    const UNSCORED = 'unscored';

    /**
     * @param array<string, mixed> $post Request POST (q{id} / q{id}[])
     * @return array{earned:int,possible:int,essay_possible:int,items:array<int,array<string,mixed>>}
     */
    public static function grade(Quiz $quiz, array $post) {
        $earned = 0;
        $possible = 0;
        $essay_possible = 0;
        $items = array();

        foreach ( $quiz->orderedQuestions() as $question ) {
            $qid = (int) $question->id;
            $submitted = self::submittedFor($question, $post);
            $row = self::gradeQuestion($question, $submitted);
            $items[$qid] = $row;
            if ( $question->type === QuestionTypes::ESSAY ) {
                $essay_possible += (int) $question->points;
            } else {
                $possible += (int) $question->points;
                $earned += (int) $row['earned'];
            }
        }

        return array(
            'earned' => $earned,
            'possible' => $possible,
            'essay_possible' => $essay_possible,
            'items' => $items,
        );
    }

    /**
     * @param mixed $submitted
     * @return array{status:string,earned:int,possible:int,submitted:mixed}
     */
    public static function gradeQuestion(Question $question, $submitted) {
        $possible = (int) $question->points;
        if ( $question->type === QuestionTypes::ESSAY ) {
            return array(
                'status' => self::UNSCORED,
                'earned' => 0,
                'possible' => $possible,
                'submitted' => is_string($submitted) ? $submitted : '',
            );
        }

        $ok = false;
        if ( $question->type === QuestionTypes::MULTIPLE_CHOICE || $question->type === QuestionTypes::TRUE_FALSE ) {
            $ok = self::gradeSingleChoice($question, $submitted);
        } else if ( $question->type === QuestionTypes::MULTIPLE_RESPONSE ) {
            $ok = self::gradeMultipleResponse($question, $submitted);
        } else if ( $question->type === QuestionTypes::FILL_BLANK ) {
            $ok = self::gradeFillBlank($question, is_string($submitted) ? $submitted : '');
        } else if ( $question->type === QuestionTypes::PATTERN_MATCH ) {
            $ok = self::gradePatternMatch($question, is_string($submitted) ? $submitted : '');
        }

        return array(
            'status' => $ok ? self::CORRECT : self::INCORRECT,
            'earned' => $ok ? $possible : 0,
            'possible' => $possible,
            'submitted' => $submitted,
        );
    }

    /**
     * @param array<string, mixed> $post
     * @return mixed
     */
    public static function submittedFor(Question $question, array $post) {
        $key = 'q'.$question->id;
        if ( $question->type === QuestionTypes::MULTIPLE_RESPONSE ) {
            $raw = isset($post[$key]) ? $post[$key] : array();
            if ( ! is_array($raw) ) {
                $raw = $raw === '' || $raw === null ? array() : array($raw);
            }
            $ids = array();
            foreach ( $raw as $v ) {
                if ( is_numeric($v) ) {
                    $ids[] = (int) $v;
                }
            }
            return $ids;
        }
        if ( $question->type === QuestionTypes::MULTIPLE_CHOICE || $question->type === QuestionTypes::TRUE_FALSE ) {
            $raw = isset($post[$key]) ? $post[$key] : '';
            return is_numeric($raw) ? (int) $raw : 0;
        }
        $raw = isset($post[$key]) ? $post[$key] : '';
        return is_string($raw) ? $raw : (string) $raw;
    }

    private static function gradeSingleChoice(Question $question, $submitted) {
        $id = (int) $submitted;
        if ( $id < 1 ) {
            return false;
        }
        foreach ( $question->nonEmptyAnswers() as $ans ) {
            if ( (int) $ans->id === $id ) {
                return (bool) $ans->correct;
            }
        }
        return false;
    }

    private static function gradeMultipleResponse(Question $question, $submitted) {
        $picked = array();
        if ( is_array($submitted) ) {
            foreach ( $submitted as $v ) {
                $picked[(int) $v] = true;
            }
        }
        foreach ( $question->nonEmptyAnswers() as $ans ) {
            $id = (int) $ans->id;
            $selected = isset($picked[$id]);
            if ( $ans->correct && ! $selected ) {
                return false;
            }
            if ( ! $ans->correct && $selected ) {
                return false;
            }
        }
        foreach ( $picked as $id => $unused ) {
            $found = false;
            foreach ( $question->nonEmptyAnswers() as $ans ) {
                if ( (int) $ans->id === (int) $id ) {
                    $found = true;
                    break;
                }
            }
            if ( ! $found ) {
                return false;
            }
        }
        return true;
    }

    private static function gradeFillBlank(Question $question, $submitted) {
        $got = self::plain($submitted);
        if ( $got === '' ) {
            return false;
        }
        $got = mb_strtolower($got, 'UTF-8');
        foreach ( $question->nonEmptyAnswers() as $ans ) {
            if ( $got === mb_strtolower(self::plain($ans->text), 'UTF-8') ) {
                return true;
            }
        }
        return false;
    }

    private static function gradePatternMatch(Question $question, $submitted) {
        $got = self::plain($submitted);
        if ( $got === '' ) {
            return false;
        }
        foreach ( $question->nonEmptyAnswers() as $ans ) {
            $needle = self::plain($ans->text);
            if ( $needle === '' ) {
                continue;
            }
            if ( $question->case_sensitive ) {
                if ( mb_strpos($got, $needle) !== false ) {
                    return true;
                }
            } else if ( mb_stripos($got, $needle) !== false ) {
                return true;
            }
        }
        return false;
    }

    public static function plain($html) {
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\x{00a0}/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim((string) $text);
    }
}
