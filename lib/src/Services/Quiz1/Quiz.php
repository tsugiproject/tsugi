<?php

namespace Tsugi\Services\Quiz1;

/**
 * Format-neutral quiz: title, instructions, ordered questions.
 *
 * QTI and GIFT are interchange formats only. This object is what Tsugi
 * edits, validates, and later imports into.
 */
class Quiz {

    /** @var int|null */
    public $id;

    /** @var int */
    public $context_id = 0;

    /** @var int */
    public $user_id = 0;

    /** @var string */
    public $title = '';

    /** @var string */
    public $instructions = '';

    /** @var Question[] */
    public $questions = array();

    /** @var int|null Set on list views without loading questions. */
    public $question_count;

    /**
     * @return string[] Validation error messages (empty = valid).
     */
    public function validate() {
        $errors = array();

        if ( trim(strip_tags($this->title)) === '' ) {
            $errors[] = 'Quiz title is required.';
        }

        $seen = array();
        foreach ( $this->questions as $i => $question ) {
            if ( ! $question instanceof Question ) {
                $errors[] = 'Question ' . ($i + 1) . ' is invalid.';
                continue;
            }
            $prefix = 'Question ' . ($i + 1) . ': ';
            foreach ( $question->validate() as $err ) {
                $errors[] = $prefix . $err;
            }
            $seq = (int) $question->sequence;
            if ( isset($seen[$seq]) ) {
                $errors[] = 'Question ordering must be unique; duplicate sequence ' . $seq . '.';
            }
            $seen[$seq] = true;
        }

        return $errors;
    }

    public function isValid() {
        return count($this->validate()) === 0;
    }

    /**
     * Questions in deterministic display order.
     *
     * @return Question[]
     */
    public function orderedQuestions() {
        $list = $this->questions;
        usort($list, function($a, $b) {
            $sa = (int) $a->sequence;
            $sb = (int) $b->sequence;
            if ( $sa === $sb ) {
                return ((int) $a->id) <=> ((int) $b->id);
            }
            return $sa <=> $sb;
        });
        return $list;
    }
}
