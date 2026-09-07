<?php

namespace Tsugi\Services\Quiz1;

/**
 * One quiz question in the format-neutral Quiz1 model.
 */
class Question {

    /** @var int|null */
    public $id;

    /** @var int|null */
    public $quiz_id;

    /** @var int */
    public $sequence = 1;

    /** @var string */
    public $type = '';

    /** @var string */
    public $title = '';

    /** @var string */
    public $prompt = '';

    /** @var int */
    public $points = 1;

    /** @var string */
    public $feedback = '';

    /** @var bool Pattern match only; FIB is never case-sensitive in CC. */
    public $case_sensitive = false;

    /** @var string Essay sample solution (optional). */
    public $sample_solution = '';

    /** @var Answer[] */
    public $answers = array();

    /**
     * @return string[] Validation error messages.
     */
    public function validate() {
        $errors = array();

        if ( ! QuestionTypes::isValid($this->type) ) {
            $errors[] = 'Each question must have a supported type.';
        }

        if ( self::isBlankHtml($this->prompt) ) {
            $errors[] = 'Each question must have a prompt.';
        }

        $points = (int) $this->points;
        if ( $points < 1 || $points > 99 ) {
            $errors[] = 'Question points must be an integer from 1 to 99.';
        }

        if ( $this->type === QuestionTypes::MULTIPLE_CHOICE ) {
            $errors = array_merge($errors, $this->validateChoice(false));
        } else if ( $this->type === QuestionTypes::MULTIPLE_RESPONSE ) {
            $errors = array_merge($errors, $this->validateChoice(true));
        } else if ( $this->type === QuestionTypes::TRUE_FALSE ) {
            $errors = array_merge($errors, $this->validateTrueFalse());
        } else if ( $this->type === QuestionTypes::ESSAY ) {
            foreach ( $this->answers as $ans ) {
                if ( $ans->correct ) {
                    $errors[] = 'Essay questions cannot have an automatically correct answer.';
                    break;
                }
            }
        } else if ( $this->type === QuestionTypes::FILL_BLANK ) {
            $errors = array_merge($errors, $this->validateAcceptedStrings('Fill in the blank questions need at least one acceptable answer.'));
        } else if ( $this->type === QuestionTypes::PATTERN_MATCH ) {
            $errors = array_merge($errors, $this->validateAcceptedStrings('Pattern match questions need at least one matching pattern.'));
        }

        return $errors;
    }

    /**
     * @return string[]
     */
    private function validateChoice($multiple) {
        $errors = array();
        $choices = $this->nonEmptyAnswers();
        if ( count($choices) < 2 ) {
            $errors[] = $multiple
                ? 'Multiple-answer questions need at least two choices.'
                : 'Multiple-choice questions need at least two choices.';
        }
        $correct = 0;
        foreach ( $choices as $ans ) {
            if ( $ans->correct ) {
                $correct++;
            }
        }
        if ( $multiple ) {
            if ( $correct < 1 ) {
                $errors[] = 'Multiple-answer questions need at least one correct choice.';
            }
        } else if ( $correct !== 1 ) {
            $errors[] = 'Multiple-choice questions need exactly one correct choice.';
        }
        return $errors;
    }

    /**
     * @return string[]
     */
    private function validateTrueFalse() {
        $errors = array();
        $true = null;
        $false = null;
        foreach ( $this->answers as $ans ) {
            $label = strtolower(trim(strip_tags($ans->text)));
            if ( $label === 'true' ) {
                $true = $ans;
            } else if ( $label === 'false' ) {
                $false = $ans;
            }
        }
        if ( $true === null || $false === null ) {
            $errors[] = 'True/False questions need True and False choices.';
            return $errors;
        }
        if ( (bool) $true->correct === (bool) $false->correct ) {
            $errors[] = 'True/False questions need exactly one correct answer.';
        }
        return $errors;
    }

    /**
     * @return string[]
     */
    private function validateAcceptedStrings($empty_message) {
        $errors = array();
        $accepted = $this->nonEmptyAnswers();
        if ( count($accepted) < 1 ) {
            $errors[] = $empty_message;
        }
        return $errors;
    }

    /**
     * @return Answer[]
     */
    public function nonEmptyAnswers() {
        $out = array();
        foreach ( $this->answers as $ans ) {
            if ( ! self::isBlankHtml($ans->text) ) {
                $out[] = $ans;
            }
        }
        return $out;
    }

    public static function isBlankHtml($html) {
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\x{00a0}/u', ' ', $text);
        return trim($text) === '';
    }

    /**
     * Extra JSON stored on the question row.
     *
     * @return array<string,mixed>
     */
    public function extraArray() {
        $extra = array();
        if ( $this->type === QuestionTypes::PATTERN_MATCH && $this->case_sensitive ) {
            $extra['case_sensitive'] = true;
        }
        if ( $this->type === QuestionTypes::ESSAY && trim($this->sample_solution) !== '' ) {
            $extra['sample_solution'] = $this->sample_solution;
        }
        return $extra;
    }

    public function applyExtra($json) {
        if ( is_string($json) && $json !== '' ) {
            $data = json_decode($json, true);
        } else {
            $data = $json;
        }
        if ( ! is_array($data) ) {
            return;
        }
        $this->case_sensitive = ! empty($data['case_sensitive']);
        if ( isset($data['sample_solution']) ) {
            $this->sample_solution = (string) $data['sample_solution'];
        }
    }
}
