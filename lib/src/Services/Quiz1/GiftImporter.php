<?php

namespace Tsugi\Services\Quiz1;

/**
 * Parse Moodle GIFT into a format-neutral Quiz1 quiz.
 *
 * Supported: multiple choice, multiple response, true/false, essay,
 * and short answer (fill in the blank). Numerical and matching items
 * are skipped with a warning. Pattern match is not a GIFT type.
 */
class GiftImporter {

    /**
     * @param string $text
     * @return array{0: Quiz, 1: string[]}
     * @throws ImportException
     */
    public static function import($text) {
        $warnings = array();
        $text = self::stripBom((string) $text);
        if ( trim($text) === '' ) {
            throw new ImportException('GIFT text is empty.');
        }

        $quiz = new Quiz();
        $seq = 1;
        foreach ( self::blocks($text) as $raw ) {
            if ( preg_match('/^\$CATEGORY:/', trim($raw) ) ) {
                continue;
            }
            try {
                $question = self::parseQuestion($raw, $seq);
            } catch ( ImportException $e ) {
                $warnings[] = $e->getMessage();
                continue;
            }
            $errors = $question->validate();
            if ( count($errors) ) {
                $label = $question->title !== '' ? $question->title : Html::excerpt($question->prompt, 40);
                $warnings[] = ($label !== '' ? $label . ': ' : '') . implode(' ', $errors);
                continue;
            }
            $question->sequence = $seq;
            $quiz->questions[] = $question;
            $seq++;
        }

        if ( count($quiz->questions) < 1 ) {
            $extra = count($warnings) ? ' ' . implode(' ', $warnings) : '';
            throw new ImportException('No supported GIFT questions were found.' . $extra);
        }
        return array($quiz, $warnings);
    }

    /**
     * @return string[]
     */
    private static function blocks($text) {
        $text = str_replace("\r\n", "\n", $text);
        $text = str_replace("\r", "\n", $text);
        $blocks = array();
        $cur = '';
        foreach ( explode("\n", $text) as $line ) {
            $line = rtrim($line);
            if ( strpos($line, '//') === 0 ) {
                continue;
            }
            if ( $line === '' ) {
                if ( trim($cur) !== '' ) {
                    $blocks[] = $cur;
                    $cur = '';
                }
                continue;
            }
            $cur = $cur === '' ? $line : $cur . "\n" . $line;
        }
        if ( trim($cur) !== '' ) {
            $blocks[] = $cur;
        }
        return $blocks;
    }

    /**
     * @throws ImportException
     */
    private static function parseQuestion($raw, $seq) {
        $title = '';
        $rest = $raw;
        if ( preg_match('/^::(.*?)::(.*)$/s', $raw, $m) ) {
            $title = trim(self::unescape($m[1]));
            $rest = $m[2];
        }

        $pair = self::lastBracePair($rest);
        if ( $pair === null ) {
            throw new ImportException('Question is missing {answers}: ' . self::snippet($raw));
        }
        list($spos, $epos) = $pair;
        $prompt_raw = trim(substr($rest, 0, $spos));
        $after = trim(substr($rest, $epos + 1));
        $answer = trim(substr($rest, $spos + 1, $epos - $spos - 1));

        $question = new Question();
        $question->title = $title;
        $question->prompt = self::promptFromGift($prompt_raw);
        $question->points = 1;
        $question->sequence = $seq;

        if ( $after !== '' && strpos($after, '####') === 0 ) {
            $question->feedback = self::promptFromGift(substr($after, 4));
        }

        $answer_one_line = trim(preg_replace('/\s+/', ' ', $answer));
        if ( $answer_one_line === '' ) {
            $question->type = QuestionTypes::ESSAY;
            return $question;
        }

        if ( preg_match('/^(TRUE|FALSE|T|F)\b/i', $answer_one_line) ) {
            return self::trueFalse($question, $answer_one_line);
        }

        if ( isset($answer_one_line[0]) && $answer_one_line[0] === '#' ) {
            throw new ImportException('Numerical GIFT questions are not supported: ' . self::snippet($raw));
        }
        if ( strpos($answer, '->') !== false ) {
            throw new ImportException('Matching GIFT questions are not supported: ' . self::snippet($raw));
        }

        $parsed = self::parseChoiceAnswers($answer);
        if ( count($parsed) < 1 ) {
            throw new ImportException('Could not read GIFT answers: ' . self::snippet($raw));
        }

        $correct = 0;
        $incorrect = 0;
        $seq_a = 1;
        foreach ( $parsed as $row ) {
            $question->answers[] = Answer::make($row['text'], $row['correct'], $seq_a, null, $row['feedback']);
            if ( $row['correct'] ) {
                $correct++;
            } else {
                $incorrect++;
            }
            $seq_a++;
        }

        if ( $correct < 1 ) {
            throw new ImportException('GIFT question has no correct answer: ' . self::snippet($raw));
        }
        if ( $incorrect === 0 ) {
            $question->type = QuestionTypes::FILL_BLANK;
            $tail = trim(substr($rest, $epos + 1));
            if ( $tail !== '' && strpos($tail, '####') !== 0 ) {
                $question->prompt = self::promptFromGift(trim(substr($rest, 0, $spos)) . ' [_____] ' . $tail);
            }
        } else if ( $correct > 1 ) {
            $question->type = QuestionTypes::MULTIPLE_RESPONSE;
        } else {
            $question->type = QuestionTypes::MULTIPLE_CHOICE;
        }
        return $question;
    }

    private static function trueFalse(Question $question, $answer) {
        $question->type = QuestionTypes::TRUE_FALSE;
        if ( ! preg_match('/^(TRUE|FALSE|T|F)\b\s*(?:#(.*))?$/is', trim($answer), $m) ) {
            throw new ImportException('Could not read true/false answer: ' . $answer);
        }
        $token = strtoupper($m[1]);
        $is_true = ($token === 'T' || $token === 'TRUE');
        $fb = isset($m[2]) ? trim($m[2]) : '';
        $wrong_fb = '';
        $right_fb = '';
        if ( $fb !== '' ) {
            $parts = explode('#', $fb, 2);
            if ( count($parts) === 2 ) {
                $wrong_fb = self::promptFromGift($parts[0]);
                $right_fb = self::promptFromGift($parts[1]);
            } else {
                // Moodle: a single # string is feedback for the incorrect choice.
                $wrong_fb = self::promptFromGift($fb);
            }
        }
        $question->answers = array(
            Answer::make('True', $is_true, 1, null, $is_true ? $right_fb : $wrong_fb),
            Answer::make('False', ! $is_true, 2, null, $is_true ? $wrong_fb : $right_fb),
        );
        return $question;
    }

    /**
     * @return array<int, array{correct:bool,text:string,feedback:string}>
     */
    private static function parseChoiceAnswers($answer) {
        $rows = array();
        $marker = null;
        $buf = '';
        $len = strlen($answer);
        for ( $i = 0; $i <= $len; $i++ ) {
            $at_end = ($i === $len);
            $ch = $at_end ? '' : $answer[$i];
            $escaped = ! $at_end && self::isEscaped($answer, $i);
            if ( $at_end || ( ! $escaped && ($ch === '=' || $ch === '~') ) ) {
                if ( $marker !== null ) {
                    $row = self::oneAnswer($marker, $buf);
                    if ( $row !== null ) {
                        $rows[] = $row;
                    }
                }
                if ( $at_end ) {
                    break;
                }
                $marker = $ch;
                $buf = '';
                continue;
            }
            $buf .= $ch;
        }
        return $rows;
    }

    /**
     * @return array{correct:bool,text:string,feedback:string}|null
     */
    private static function oneAnswer($marker, $raw) {
        $raw = trim($raw);
        if ( $raw === '' ) {
            return null;
        }
        $weight = null;
        if ( preg_match('/^%(-?\d+(?:\.\d+)?)%(.*)$/s', $raw, $m) ) {
            $weight = (float) $m[1];
            $raw = $m[2];
        }
        $text = '';
        $feedback = '';
        $len = strlen($raw);
        for ( $i = 0; $i < $len; $i++ ) {
            $ch = $raw[$i];
            if ( $ch === '#' && ! self::isEscaped($raw, $i) && $i > 0 && trim($text) !== '' ) {
                $feedback = substr($raw, $i + 1);
                break;
            }
            $text .= $ch;
        }
        $text = trim(self::unescape($text));
        $feedback = trim(self::unescape($feedback));
        if ( $text === '' ) {
            return null;
        }
        $correct = ($marker === '=') || ($weight !== null && $weight > 0);
        return array(
            'correct' => $correct,
            'text' => $text,
            'feedback' => $feedback,
        );
    }

    /**
     * @return array{0:int,1:int}|null
     */
    private static function lastBracePair($text) {
        $end = false;
        $len = strlen($text);
        for ( $i = $len - 1; $i >= 0; $i-- ) {
            if ( $text[$i] !== '}' || self::isEscaped($text, $i) ) {
                continue;
            }
            $end = $i;
            $depth = 1;
            for ( $j = $i - 1; $j >= 0; $j-- ) {
                if ( self::isEscaped($text, $j) ) {
                    continue;
                }
                if ( $text[$j] === '}' ) {
                    $depth++;
                } else if ( $text[$j] === '{' ) {
                    $depth--;
                    if ( $depth === 0 ) {
                        return array($j, $end);
                    }
                }
            }
            return null;
        }
        return null;
    }

    private static function promptFromGift($text) {
        $text = trim((string) $text);
        if ( stripos($text, '[html]') === 0 ) {
            return Html::purify(self::unescape(trim(substr($text, 6))));
        }
        if ( preg_match('/^\[(markdown|plain)\]/i', $text) ) {
            $text = preg_replace('/^\[(markdown|plain)\]/i', '', $text);
            $text = trim($text);
        }
        return htmlspecialchars(self::unescape($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function unescape($text) {
        $text = (string) $text;
        $out = '';
        $len = strlen($text);
        for ( $i = 0; $i < $len; $i++ ) {
            if ( $text[$i] === '\\' && $i + 1 < $len ) {
                $out .= $text[$i + 1];
                $i++;
                continue;
            }
            $out .= $text[$i];
        }
        return $out;
    }

    private static function isEscaped($s, $i) {
        $n = 0;
        for ( $j = $i - 1; $j >= 0 && $s[$j] === '\\'; $j-- ) {
            $n++;
        }
        return ($n % 2) === 1;
    }

    private static function snippet($raw) {
        $one = preg_replace('/\s+/', ' ', trim($raw));
        if ( strlen($one) > 80 ) {
            return substr($one, 0, 77) . '...';
        }
        return $one;
    }

    private static function stripBom($text) {
        if ( strncmp($text, "\xEF\xBB\xBF", 3) === 0 ) {
            return substr($text, 3);
        }
        return $text;
    }
}
