<?php

namespace Tsugi\Services\Quiz1;

/**
 * Parse Common Cartridge QTI 1.2.1 (and close cousins) into a Quiz1 quiz.
 *
 * Prefers cc_profile, then Canvas question_type, then presentation shape.
 * Zip / IMSCC payloads are accepted when they contain questestinterop XML.
 */
class Qti12Importer {

    /**
     * @param string $payload XML or zip bytes
     * @return array{0: Quiz, 1: string[]}
     * @throws ImportException
     */
    public static function import($payload) {
        $warnings = array();
        $xml = self::xmlFromPayload((string) $payload);
        $prev = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $ok = $dom->loadXML($xml);
        $errs = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if ( ! $ok ) {
            $msg = 'QTI XML could not be parsed.';
            if ( count($errs) ) {
                $msg .= ' ' . trim($errs[0]->message);
            }
            throw new ImportException($msg);
        }

        $quiz = new Quiz();
        $assessment = self::firstDescendant($dom->documentElement, 'assessment');
        if ( $assessment ) {
            $quiz->title = trim($assessment->getAttribute('title'));
            $quiz->instructions = Html::purify(self::rubricText($assessment));
        }

        $seq = 1;
        foreach ( $dom->getElementsByTagName('item') as $item ) {
            if ( ! $item instanceof \DOMElement ) {
                continue;
            }
            try {
                $question = self::itemToQuestion($item, $seq);
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
            throw new ImportException('No supported QTI questions were found.' . $extra);
        }
        return array($quiz, $warnings);
    }

    /**
     * @throws ImportException
     */
    private static function xmlFromPayload($payload) {
        $payload = self::stripBom($payload);
        if ( strncmp($payload, 'PK', 2) === 0 ) {
            return self::xmlFromZip($payload);
        }
        $trim = ltrim($payload);
        if ( $trim !== '' && $trim[0] === '<' ) {
            return $payload;
        }
        throw new ImportException('Import must be QTI XML or a zip/IMSCC file that contains QTI.');
    }

    /**
     * @throws ImportException
     */
    private static function xmlFromZip($bytes) {
        $tmp = tempnam(sys_get_temp_dir(), 'q1qti');
        if ( $tmp === false ) {
            throw new ImportException('Could not read the QTI zip file.');
        }
        file_put_contents($tmp, $bytes);
        $zip = new \ZipArchive();
        $opened = $zip->open($tmp);
        if ( $opened !== true ) {
            @unlink($tmp);
            throw new ImportException('The zip/IMSCC file could not be opened.');
        }
        $found = null;
        for ( $i = 0; $i < $zip->numFiles; $i++ ) {
            $name = $zip->getNameIndex($i);
            if ( ! is_string($name) || str_ends_with($name, '/') ) {
                continue;
            }
            $stat = $zip->statIndex($i);
            if ( ! is_array($stat) ) {
                continue;
            }
            $size = isset($stat['size']) ? (int) $stat['size'] : 0;
            if ( $size < 20 || $size > 5 * 1024 * 1024 ) {
                continue;
            }
            $content = $zip->getFromIndex($i);
            if ( ! is_string($content) || strlen($content) < 20 || strlen($content) > 5 * 1024 * 1024 ) {
                continue;
            }
            if ( stripos($content, 'questestinterop') !== false ) {
                $found = $content;
                break;
            }
        }
        $zip->close();
        @unlink($tmp);
        if ( $found === null ) {
            throw new ImportException('No QTI assessment XML was found in the zip file.');
        }
        return $found;
    }

    /**
     * @throws ImportException
     */
    private static function itemToQuestion(\DOMElement $item, $seq) {
        $meta = self::itemMeta($item);
        $type = self::typeFromMetaAndItem($meta, $item);
        if ( $type === null ) {
            $title = trim($item->getAttribute('title'));
            throw new ImportException('Unsupported QTI question type: ' . ($title !== '' ? $title : 'item'));
        }

        $q = new Question();
        $q->sequence = $seq;
        $q->type = $type;
        $q->title = trim($item->getAttribute('title'));
        $q->prompt = Html::purify(self::presentationPrompt($item));
        $q->points = self::pointsFromMeta($meta);
        $q->feedback = Html::purify(self::feedbackByIdent($item, 'general_fb'));

        if ( $type === QuestionTypes::ESSAY ) {
            $q->sample_solution = Html::purify(self::solutionText($item));
            return $q;
        }

        if ( QuestionTypes::usesChoiceAnswers($type) ) {
            $q->answers = self::choiceAnswers($item);
            if ( $type === QuestionTypes::TRUE_FALSE ) {
                $q->answers = self::normalizeTrueFalse($q->answers);
            }
            return $q;
        }

        $strings = self::stringAnswers($item);
        $q->answers = $strings['answers'];
        if ( $type === QuestionTypes::PATTERN_MATCH ) {
            $q->case_sensitive = $strings['case_sensitive'];
        }
        return $q;
    }

    /**
     * @return array<string,string>
     */
    private static function itemMeta(\DOMElement $item) {
        $out = array();
        foreach ( $item->getElementsByTagName('qtimetadatafield') as $field ) {
            if ( ! $field instanceof \DOMElement ) {
                continue;
            }
            $label = self::firstDescendant($field, 'fieldlabel');
            $entry = self::firstDescendant($field, 'fieldentry');
            if ( $label && $entry ) {
                $out[trim($label->textContent)] = trim($entry->textContent);
            }
        }
        return $out;
    }

    /**
     * @param array<string,string> $meta
     */
    private static function typeFromMetaAndItem(array $meta, \DOMElement $item) {
        $profiles = array(
            'cc.multiple_choice.v0p1' => QuestionTypes::MULTIPLE_CHOICE,
            'cc.multiple_response.v0p1' => QuestionTypes::MULTIPLE_RESPONSE,
            'cc.true_false.v0p1' => QuestionTypes::TRUE_FALSE,
            'cc.essay.v0p1' => QuestionTypes::ESSAY,
            'cc.fib.v0p1' => QuestionTypes::FILL_BLANK,
            'cc.pattern_match.v0p1' => QuestionTypes::PATTERN_MATCH,
        );
        $profile = $meta['cc_profile'] ?? '';
        if ( isset($profiles[$profile]) ) {
            return $profiles[$profile];
        }

        $canvas = array(
            'multiple_choice_question' => QuestionTypes::MULTIPLE_CHOICE,
            'multiple_answers_question' => QuestionTypes::MULTIPLE_RESPONSE,
            'true_false_question' => QuestionTypes::TRUE_FALSE,
            'essay_question' => QuestionTypes::ESSAY,
            'short_answer_question' => QuestionTypes::FILL_BLANK,
        );
        $qt = $meta['question_type'] ?? '';
        if ( isset($canvas[$qt]) ) {
            return $canvas[$qt];
        }

        return self::inferType($item, $meta);
    }

    /**
     * @param array<string,string> $meta
     */
    private static function inferType(\DOMElement $item, array $meta) {
        $pres = self::firstChild($item, 'presentation');
        if ( ! $pres ) {
            return null;
        }
        $lid = self::firstDescendant($pres, 'response_lid');
        if ( $lid ) {
            $labels = self::choiceLabels($pres);
            if ( self::labelsLookTrueFalse($labels) ) {
                return QuestionTypes::TRUE_FALSE;
            }
            $card = strtolower($lid->getAttribute('rcardinality'));
            return $card === 'multiple' ? QuestionTypes::MULTIPLE_RESPONSE : QuestionTypes::MULTIPLE_CHOICE;
        }
        $str = self::firstDescendant($pres, 'response_str');
        if ( $str ) {
            $computer = strtolower($meta['qmd_computerscored'] ?? '');
            if ( $computer === 'no' || self::firstChild($item, 'resprocessing') === null ) {
                return QuestionTypes::ESSAY;
            }
            if ( $item->getElementsByTagName('varsubstring')->length > 0 ) {
                return QuestionTypes::PATTERN_MATCH;
            }
            return QuestionTypes::FILL_BLANK;
        }
        return null;
    }

    /**
     * @param array<string,string> $meta
     */
    private static function pointsFromMeta(array $meta) {
        $raw = $meta['cc_weighting'] ?? $meta['points_possible'] ?? '1';
        $n = (int) round((float) $raw);
        if ( $n < 1 ) {
            $n = 1;
        }
        if ( $n > 99 ) {
            $n = 99;
        }
        return $n;
    }

    private static function presentationPrompt(\DOMElement $item) {
        $pres = self::firstChild($item, 'presentation');
        if ( ! $pres ) {
            return '';
        }
        $material = self::firstChild($pres, 'material');
        if ( ! $material ) {
            return '';
        }
        $mat = self::firstChild($material, 'mattext');
        return $mat ? $mat->textContent : '';
    }

    /**
     * @return Answer[]
     */
    private static function choiceAnswers(\DOMElement $item) {
        $pres = self::firstChild($item, 'presentation');
        $labels = $pres ? self::choiceLabels($pres) : array();
        $correct = self::correctChoiceIdents($item);
        $answers = array();
        $seq = 1;
        foreach ( $labels as $ident => $text ) {
            $answers[] = Answer::make(
                Html::purify($text),
                isset($correct[$ident]),
                $seq
            );
            $seq++;
        }
        return $answers;
    }

    /**
     * @return array<string,string> ident => text
     */
    private static function choiceLabels(\DOMElement $pres) {
        $out = array();
        foreach ( $pres->getElementsByTagName('response_label') as $label ) {
            if ( ! $label instanceof \DOMElement ) {
                continue;
            }
            $ident = $label->getAttribute('ident');
            if ( $ident === '' ) {
                continue;
            }
            $mat = self::firstDescendant($label, 'mattext');
            $out[$ident] = $mat ? $mat->textContent : $ident;
        }
        return $out;
    }

    /**
     * @param array<string,string> $labels
     */
    private static function labelsLookTrueFalse(array $labels) {
        if ( count($labels) !== 2 ) {
            return false;
        }
        $seen = array();
        foreach ( $labels as $text ) {
            $seen[] = strtolower(trim(strip_tags($text)));
        }
        sort($seen);
        return $seen === array('false', 'true');
    }

    /**
     * @return array<string,true>
     */
    private static function correctChoiceIdents(\DOMElement $item) {
        $correct = array();
        foreach ( self::scoringConditions($item) as $cond ) {
            foreach ( $cond->getElementsByTagName('varequal') as $ve ) {
                if ( ! $ve instanceof \DOMElement ) {
                    continue;
                }
                if ( self::hasAncestorLocal($ve, 'not', $cond) ) {
                    continue;
                }
                $ident = trim($ve->textContent);
                if ( $ident !== '' ) {
                    $correct[$ident] = true;
                }
            }
        }
        return $correct;
    }

    /**
     * @return array{answers: Answer[], case_sensitive: bool}
     */
    private static function stringAnswers(\DOMElement $item) {
        $answers = array();
        $seq = 1;
        $case_sensitive = false;
        foreach ( self::scoringConditions($item) as $cond ) {
            foreach ( $cond->getElementsByTagName('varequal') as $ve ) {
                if ( ! $ve instanceof \DOMElement ) {
                    continue;
                }
                $text = trim($ve->textContent);
                if ( $text === '' ) {
                    continue;
                }
                $answers[] = Answer::make($text, true, $seq);
                $seq++;
            }
            foreach ( $cond->getElementsByTagName('varsubstring') as $vs ) {
                if ( ! $vs instanceof \DOMElement ) {
                    continue;
                }
                $text = trim($vs->textContent);
                if ( $text === '' ) {
                    continue;
                }
                if ( strtolower($vs->getAttribute('case')) === 'yes' ) {
                    $case_sensitive = true;
                }
                $answers[] = Answer::make($text, true, $seq);
                $seq++;
            }
        }
        return array('answers' => $answers, 'case_sensitive' => $case_sensitive);
    }

    /**
     * @return \DOMElement[]
     */
    private static function scoringConditions(\DOMElement $item) {
        $rp = self::firstChild($item, 'resprocessing');
        if ( ! $rp ) {
            return array();
        }
        $all = array();
        foreach ( $rp->childNodes as $child ) {
            if ( $child instanceof \DOMElement && $child->localName === 'respcondition' ) {
                $all[] = $child;
            }
        }
        $scoring = array();
        foreach ( $all as $cond ) {
            $setvars = $cond->getElementsByTagName('setvar');
            if ( $setvars->length > 0 ) {
                if ( self::isPositiveScoring($cond) ) {
                    $scoring[] = $cond;
                }
                continue;
            }
            if ( $cond->getAttribute('continue') === 'No' ) {
                $scoring[] = $cond;
            }
        }
        return $scoring;
    }

    /**
     * True when a respcondition awards a positive SCORE (Set/Add above zero).
     */
    private static function isPositiveScoring(\DOMElement $cond) {
        foreach ( $cond->getElementsByTagName('setvar') as $sv ) {
            if ( ! $sv instanceof \DOMElement ) {
                continue;
            }
            $action = strtolower($sv->getAttribute('action'));
            $n = (float) trim($sv->textContent);
            if ( $n <= 0 ) {
                continue;
            }
            if ( $action === 'set' || $action === 'add' || $action === '' ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Answer[] $answers
     * @return Answer[]
     */
    private static function normalizeTrueFalse(array $answers) {
        $true = null;
        $false = null;
        foreach ( $answers as $ans ) {
            $label = strtolower(trim(strip_tags($ans->text)));
            if ( $label === 'true' ) {
                $true = $ans;
            } else if ( $label === 'false' ) {
                $false = $ans;
            }
        }
        if ( $true && $false ) {
            $true->text = 'True';
            $false->text = 'False';
            $true->sequence = 1;
            $false->sequence = 2;
            return array($true, $false);
        }
        return $answers;
    }

    private static function solutionText(\DOMElement $item) {
        foreach ( $item->getElementsByTagName('itemfeedback') as $fb ) {
            if ( ! $fb instanceof \DOMElement ) {
                continue;
            }
            if ( $fb->getAttribute('ident') === 'solution' || self::firstChild($fb, 'solution') ) {
                $mat = self::firstDescendant($fb, 'mattext');
                return $mat ? $mat->textContent : '';
            }
        }
        return '';
    }

    private static function feedbackByIdent(\DOMElement $item, $ident) {
        foreach ( $item->getElementsByTagName('itemfeedback') as $fb ) {
            if ( $fb instanceof \DOMElement && $fb->getAttribute('ident') === $ident ) {
                $mat = self::firstDescendant($fb, 'mattext');
                return $mat ? $mat->textContent : '';
            }
        }
        return '';
    }

    private static function rubricText(\DOMElement $assessment) {
        $rubric = self::firstChild($assessment, 'rubric');
        if ( ! $rubric ) {
            return '';
        }
        $mat = self::firstDescendant($rubric, 'mattext');
        return $mat ? $mat->textContent : '';
    }

    private static function firstChild(\DOMNode $node, $local) {
        foreach ( $node->childNodes as $child ) {
            if ( $child instanceof \DOMElement && $child->localName === $local ) {
                return $child;
            }
        }
        return null;
    }

    private static function firstDescendant(\DOMNode $node, $local) {
        if ( $node instanceof \DOMDocument ) {
            $node = $node->documentElement;
        }
        if ( ! $node instanceof \DOMElement ) {
            return null;
        }
        $list = $node->getElementsByTagName($local);
        return $list->length ? $list->item(0) : null;
    }

    private static function hasAncestorLocal(\DOMNode $node, $local, \DOMNode $stop) {
        $cur = $node->parentNode;
        while ( $cur && $cur !== $stop ) {
            if ( $cur instanceof \DOMElement && $cur->localName === $local ) {
                return true;
            }
            $cur = $cur->parentNode;
        }
        return false;
    }

    private static function stripBom($text) {
        if ( strncmp($text, "\xEF\xBB\xBF", 3) === 0 ) {
            return substr($text, 3);
        }
        return $text;
    }
}
