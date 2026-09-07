<?php

namespace Tsugi\Services\Quiz1;

/**
 * Semantic question types for Quiz1 (not QTI element names).
 *
 * These six types are the Common Cartridge QTI 1.2.1 assessment profile.
 */
class QuestionTypes {

    const MULTIPLE_CHOICE = 'multiple_choice';
    const MULTIPLE_RESPONSE = 'multiple_response';
    const TRUE_FALSE = 'true_false';
    const ESSAY = 'essay';
    const FILL_BLANK = 'fill_blank';
    const PATTERN_MATCH = 'pattern_match';

    /**
     * @return string[]
     */
    public static function all() {
        return array(
            self::MULTIPLE_CHOICE,
            self::MULTIPLE_RESPONSE,
            self::TRUE_FALSE,
            self::ESSAY,
            self::FILL_BLANK,
            self::PATTERN_MATCH,
        );
    }

    /**
     * Author-facing labels (not QTI terminology).
     *
     * @return array<string,string>
     */
    public static function labels() {
        return array(
            self::MULTIPLE_CHOICE => 'Multiple choice (one answer)',
            self::MULTIPLE_RESPONSE => 'Multiple choice (several answers)',
            self::TRUE_FALSE => 'True / False',
            self::ESSAY => 'Essay',
            self::FILL_BLANK => 'Fill in the blank',
            self::PATTERN_MATCH => 'Pattern match',
        );
    }

    public static function label($type) {
        $labels = self::labels();
        return $labels[$type] ?? $type;
    }

    public static function isValid($type) {
        return in_array($type, self::all(), true);
    }

    /**
     * Common Cartridge cc_profile fieldentry for an item.
     */
    public static function ccProfile($type) {
        $map = array(
            self::MULTIPLE_CHOICE => 'cc.multiple_choice.v0p1',
            self::MULTIPLE_RESPONSE => 'cc.multiple_response.v0p1',
            self::TRUE_FALSE => 'cc.true_false.v0p1',
            self::ESSAY => 'cc.essay.v0p1',
            self::FILL_BLANK => 'cc.fib.v0p1',
            self::PATTERN_MATCH => 'cc.pattern_match.v0p1',
        );
        return $map[$type] ?? null;
    }

    public static function usesChoiceAnswers($type) {
        return $type === self::MULTIPLE_CHOICE
            || $type === self::MULTIPLE_RESPONSE
            || $type === self::TRUE_FALSE;
    }

    public static function usesAcceptedStrings($type) {
        return $type === self::FILL_BLANK || $type === self::PATTERN_MATCH;
    }
}
