<?php

namespace Tsugi\Services\Quiz1;

/**
 * One answer alternative or accepted response. Semantic, not GIFT/QTI syntax.
 */
class Answer {

    /** @var int|null */
    public $id;

    /** @var int */
    public $sequence = 1;

    /** @var string */
    public $text = '';

    /** @var bool */
    public $correct = false;

    /** @var string */
    public $feedback = '';

    public static function make($text, $correct = false, $sequence = 1, $id = null, $feedback = '') {
        $a = new self();
        $a->id = $id;
        $a->text = (string) $text;
        $a->correct = (bool) $correct;
        $a->sequence = (int) $sequence;
        $a->feedback = (string) $feedback;
        return $a;
    }
}
