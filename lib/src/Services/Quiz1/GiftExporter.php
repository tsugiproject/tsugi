<?php

namespace Tsugi\Services\Quiz1;

/**
 * Serialize a Quiz1 quiz as Moodle GIFT text.
 *
 * GIFT has no pattern-match profile. Those questions are written as short
 * answer (fill in the blank) and come back as fill_blank on import.
 */
class GiftExporter {

    /**
     * @return string UTF-8 GIFT
     * @throws ExportException
     */
    public static function export(Quiz $quiz) {
        $errors = $quiz->validate();
        if ( count($errors) > 0 ) {
            throw new ExportException("Quiz is not valid for GIFT export:\n" . implode("\n", $errors));
        }
        if ( count($quiz->questions) < 1 ) {
            throw new ExportException('A quiz must have at least one question to export.');
        }

        $out = array();
        $title = trim(strip_tags($quiz->title));
        if ( $title !== '' ) {
            $out[] = '// ' . self::commentLine($title);
        }
        $out[] = '// Exported from Tsugi Quiz1';
        $out[] = '';

        foreach ( $quiz->orderedQuestions() as $question ) {
            $out[] = self::question($question);
            $out[] = '';
        }

        return implode("\n", $out);
    }

    private static function question(Question $question) {
        $lines = array();
        if ( $question->type === QuestionTypes::PATTERN_MATCH ) {
            $lines[] = '// Pattern match (exported as GIFT short answer)';
        }

        $head = '';
        $title = trim($question->title);
        if ( $title !== '' ) {
            $head .= '::' . self::escape($title) . '::';
        }
        $head .= self::promptText($question->prompt);

        $body = $head . self::answerBlock($question);
        if ( ! Question::isBlankHtml($question->feedback) ) {
            $body .= '####' . self::promptText($question->feedback);
        }
        $lines[] = $body;
        return implode("\n", $lines);
    }

    private static function promptText($prompt) {
        $prompt = (string) $prompt;
        if ( $prompt !== strip_tags($prompt) ) {
            return '[html]' . self::escape($prompt);
        }
        return self::escape($prompt);
    }

    private static function answerBlock(Question $question) {
        if ( $question->type === QuestionTypes::ESSAY ) {
            return '{}';
        }
        if ( $question->type === QuestionTypes::TRUE_FALSE ) {
            $true = true;
            foreach ( $question->nonEmptyAnswers() as $ans ) {
                $label = strtolower(trim(strip_tags($ans->text)));
                if ( $label === 'true' ) {
                    $true = $ans->correct ? true : false;
                    break;
                }
            }
            return $true ? '{T}' : '{F}';
        }

        $rows = array();
        foreach ( $question->nonEmptyAnswers() as $ans ) {
            $text = self::escape(self::plainOrHtml($ans->text));
            $fb = '';
            if ( ! Question::isBlankHtml($ans->feedback) ) {
                $fb = '#' . self::escape(self::plainOrHtml($ans->feedback));
            }
            if ( $question->type === QuestionTypes::MULTIPLE_RESPONSE ) {
                if ( $ans->correct ) {
                    $rows[] = '~%100%' . $text . $fb;
                } else {
                    $rows[] = '~' . $text . $fb;
                }
            } else if ( QuestionTypes::usesAcceptedStrings($question->type) ) {
                $rows[] = '=' . $text . $fb;
            } else {
                $rows[] = ($ans->correct ? '=' : '~') . $text . $fb;
            }
        }
        return "{\n" . implode("\n", $rows) . "\n}";
    }

    private static function plainOrHtml($html) {
        $html = (string) $html;
        if ( $html !== strip_tags($html) ) {
            return $html;
        }
        return html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Escape GIFT control characters.
     */
    public static function escape($text) {
        $text = (string) $text;
        $text = str_replace('\\', '\\\\', $text);
        $map = array(
            '~' => '\\~',
            '=' => '\\=',
            '#' => '\\#',
            '{' => '\\{',
            '}' => '\\}',
            ':' => '\\:',
        );
        return str_replace(array_keys($map), array_values($map), $text);
    }

    private static function commentLine($text) {
        return str_replace(array("\r", "\n"), ' ', $text);
    }
}
