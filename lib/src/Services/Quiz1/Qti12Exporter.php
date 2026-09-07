<?php

namespace Tsugi\Services\Quiz1;

use Tsugi\Util\CC;

/**
 * Serialize a Quiz1 quiz as Common Cartridge QTI 1.2.1 assessment XML.
 *
 * Target: IMS Common Cartridge 1.2 assessment profile of QTI 1.2.1 — not a
 * generic QTI 1.2 serializer. Generic Setup export stays spec-only.
 * Canvas Setup may add question_type / points_possible item metadata.
 *
 * Mappings (internal type → cc_profile):
 *   multiple_choice   → cc.multiple_choice.v0p1   response_lid Single
 *   multiple_response → cc.multiple_response.v0p1 response_lid Multiple, all-or-nothing
 *   true_false        → cc.true_false.v0p1        response_lid Single
 *   fill_blank        → cc.fib.v0p1               response_str + varequal case="No"
 *   pattern_match     → cc.pattern_match.v0p1     response_str + varsubstring
 *   essay             → cc.essay.v0p1             response_str, not computer-scored
 *
 * QTI ident values are derived from Quiz1 database ids at export time.
 * They are not stored and must not be used as primary keys.
 */
class Qti12Exporter {

    const NS = CC::QTI_NS;
    const SCHEMA = CC::QTI_SCHEMA_LOCATION;
    const RESPONSE_IDENT = 'response';

    /**
     * @param array{pattern_match_as_fib?:bool,assessment_ident?:string,canvas_item_metadata?:bool} $options
     * @return string UTF-8 XML
     * @throws ExportException
     */
    public static function export(Quiz $quiz, array $options = array()) {
        if ( ! empty($options['pattern_match_as_fib']) ) {
            $quiz = self::withPatternMatchAsFib($quiz);
        }
        $errors = $quiz->validate();
        if ( count($errors) > 0 ) {
            throw new ExportException("Quiz is not valid for QTI export:\n" . implode("\n", $errors));
        }
        if ( count($quiz->questions) < 1 ) {
            throw new ExportException('A quiz must have at least one question to export.');
        }
        if ( $quiz->id === null || (int) $quiz->id < 1 ) {
            throw new ExportException('Quiz must have a stable internal id before export.');
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElementNS(self::NS, 'questestinterop');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $root->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation', self::SCHEMA);
        $dom->appendChild($root);

        $assessment = $dom->createElementNS(self::NS, 'assessment');
        $assessment_ident = isset($options['assessment_ident']) && is_string($options['assessment_ident']) && $options['assessment_ident'] !== ''
            ? $options['assessment_ident']
            : self::quizIdent($quiz);
        $assessment->setAttribute('ident', $assessment_ident);
        $assessment->setAttribute('title', $quiz->title);
        $root->appendChild($assessment);

        $meta = $dom->createElementNS(self::NS, 'qtimetadata');
        self::addMetaField($dom, $meta, 'cc_profile', 'cc.exam.v0p1');
        self::addMetaField($dom, $meta, 'qmd_assessmenttype', 'Examination');
        self::addMetaField($dom, $meta, 'cc_maxattempts', '1');
        self::addMetaField($dom, $meta, 'qmd_scoretype', 'Percentage');
        $assessment->appendChild($meta);

        if ( ! Question::isBlankHtml($quiz->instructions) ) {
            $rubric = $dom->createElementNS(self::NS, 'rubric');
            $material = $dom->createElementNS(self::NS, 'material');
            $material->appendChild(self::mattext($dom, $quiz->instructions));
            $rubric->appendChild($material);
            $assessment->appendChild($rubric);
        }

        $section = $dom->createElementNS(self::NS, 'section');
        $section->setAttribute('ident', 'Q1_SEC_' . (int) $quiz->id);
        $assessment->appendChild($section);

        $canvas_meta = ! empty($options['canvas_item_metadata']);
        foreach ( $quiz->orderedQuestions() as $question ) {
            $section->appendChild(self::item($dom, $question, $canvas_meta));
        }

        return $dom->saveXML();
    }

    /**
     * Canvas does not implement cc.pattern_match.v0p1; it remaps those items
     * to Fill in the Blank and warns. Emit FIB ourselves for Canvas exports.
     * Generic/Sakai keep varsubstring pattern match.
     */
    public static function withPatternMatchAsFib(Quiz $quiz) {
        $copy = clone $quiz;
        $copy->questions = array();
        foreach ( $quiz->questions as $question ) {
            $q = clone $question;
            if ( $q->type === QuestionTypes::PATTERN_MATCH ) {
                $q->type = QuestionTypes::FILL_BLANK;
                $q->case_sensitive = false;
            }
            $copy->questions[] = $q;
        }
        return $copy;
    }

    public static function canvasQuestionType($type) {
        $map = array(
            QuestionTypes::MULTIPLE_CHOICE => 'multiple_choice_question',
            QuestionTypes::MULTIPLE_RESPONSE => 'multiple_answers_question',
            QuestionTypes::TRUE_FALSE => 'true_false_question',
            QuestionTypes::ESSAY => 'essay_question',
            QuestionTypes::FILL_BLANK => 'short_answer_question',
            QuestionTypes::PATTERN_MATCH => 'short_answer_question',
        );
        return $map[$type] ?? 'short_answer_question';
    }

    public static function quizIdent(Quiz $quiz) {
        return 'Q1_QUIZ_' . (int) $quiz->id;
    }

    public static function itemIdent(Question $question) {
        return 'Q1_ITEM_' . (int) $question->id;
    }

    public static function answerIdent(Answer $answer) {
        return 'Q1_ANS_' . (int) $answer->id;
    }

    private static function item(\DOMDocument $dom, Question $question, $canvas_meta = false) {
        if ( $question->id === null || (int) $question->id < 1 ) {
            throw new ExportException('Each question must have a stable internal id before export.');
        }

        $item = $dom->createElementNS(self::NS, 'item');
        $item->setAttribute('ident', self::itemIdent($question));
        $title = trim($question->title);
        if ( $title === '' ) {
            $title = 'Question ' . (int) $question->sequence;
        }
        $item->setAttribute('title', $title);

        $itemmetadata = $dom->createElementNS(self::NS, 'itemmetadata');
        $qtimetadata = $dom->createElementNS(self::NS, 'qtimetadata');
        self::addMetaField($dom, $qtimetadata, 'cc_profile', QuestionTypes::ccProfile($question->type));
        self::addMetaField($dom, $qtimetadata, 'cc_weighting', (string) (int) $question->points);
        if ( $canvas_meta ) {
            self::addMetaField($dom, $qtimetadata, 'question_type', self::canvasQuestionType($question->type));
            self::addMetaField($dom, $qtimetadata, 'points_possible', (string) (int) $question->points);
        }
        self::addMetaField($dom, $qtimetadata, 'qmd_scoringpermitted', 'Yes');
        $computer = $question->type === QuestionTypes::ESSAY ? 'No' : 'Yes';
        self::addMetaField($dom, $qtimetadata, 'qmd_computerscored', $computer);
        $itemmetadata->appendChild($qtimetadata);
        $item->appendChild($itemmetadata);

        $presentation = $dom->createElementNS(self::NS, 'presentation');
        $material = $dom->createElementNS(self::NS, 'material');
        $material->appendChild(self::mattext($dom, $question->prompt));
        $presentation->appendChild($material);

        if ( QuestionTypes::usesChoiceAnswers($question->type) ) {
            self::addChoicePresentation($dom, $presentation, $question);
        } else {
            self::addFibPresentation($dom, $presentation, $question);
        }
        $item->appendChild($presentation);

        if ( $question->type !== QuestionTypes::ESSAY ) {
            $item->appendChild(self::resprocessing($dom, $question));
        } else if ( trim($question->sample_solution) !== '' ) {
            $item->appendChild(self::itemSolutionFeedback($dom, $question->sample_solution));
        }

        if ( ! Question::isBlankHtml($question->feedback) ) {
            $item->appendChild(self::itemFeedback($dom, 'general_fb', $question->feedback));
        }

        foreach ( $question->nonEmptyAnswers() as $ans ) {
            if ( ! Question::isBlankHtml($ans->feedback) && $ans->id ) {
                $item->appendChild(self::itemFeedback($dom, self::answerIdent($ans) . '_fb', $ans->feedback));
            }
        }

        return $item;
    }

    private static function addChoicePresentation(\DOMDocument $dom, \DOMElement $presentation, Question $question) {
        $rcard = $question->type === QuestionTypes::MULTIPLE_RESPONSE ? 'Multiple' : 'Single';
        $response = $dom->createElementNS(self::NS, 'response_lid');
        $response->setAttribute('ident', self::RESPONSE_IDENT);
        $response->setAttribute('rcardinality', $rcard);
        $render = $dom->createElementNS(self::NS, 'render_choice');
        $render->setAttribute('shuffle', 'No');
        foreach ( $question->nonEmptyAnswers() as $ans ) {
            if ( $ans->id === null || (int) $ans->id < 1 ) {
                throw new ExportException('Each answer must have a stable internal id before export.');
            }
            $label = $dom->createElementNS(self::NS, 'response_label');
            $label->setAttribute('ident', self::answerIdent($ans));
            $mat = $dom->createElementNS(self::NS, 'material');
            $mat->appendChild(self::mattext($dom, $ans->text));
            $label->appendChild($mat);
            $render->appendChild($label);
        }
        $response->appendChild($render);
        $presentation->appendChild($response);
    }

    private static function addFibPresentation(\DOMDocument $dom, \DOMElement $presentation, Question $question) {
        $response = $dom->createElementNS(self::NS, 'response_str');
        $response->setAttribute('ident', self::RESPONSE_IDENT);
        $response->setAttribute('rcardinality', 'Single');
        $render = $dom->createElementNS(self::NS, 'render_fib');
        $render->setAttribute('fibtype', 'String');
        $render->setAttribute('rows', '1');
        $label = $dom->createElementNS(self::NS, 'response_label');
        $label->setAttribute('ident', 'Q1_FIB_' . (int) $question->id);
        $render->appendChild($label);
        $response->appendChild($render);
        $presentation->appendChild($response);
    }

    private static function resprocessing(\DOMDocument $dom, Question $question) {
        $rp = $dom->createElementNS(self::NS, 'resprocessing');
        $outcomes = $dom->createElementNS(self::NS, 'outcomes');
        $decvar = $dom->createElementNS(self::NS, 'decvar');
        $decvar->setAttribute('maxvalue', '100');
        $decvar->setAttribute('minvalue', '0');
        $decvar->setAttribute('varname', 'SCORE');
        $decvar->setAttribute('vartype', 'Decimal');
        $outcomes->appendChild($decvar);
        $rp->appendChild($outcomes);

        if ( ! Question::isBlankHtml($question->feedback) ) {
            $gen = $dom->createElementNS(self::NS, 'respcondition');
            $gen->setAttribute('continue', 'Yes');
            $cv = $dom->createElementNS(self::NS, 'conditionvar');
            $cv->appendChild($dom->createElementNS(self::NS, 'other'));
            $gen->appendChild($cv);
            $df = $dom->createElementNS(self::NS, 'displayfeedback');
            $df->setAttribute('feedbacktype', 'Response');
            $df->setAttribute('linkrefid', 'general_fb');
            $gen->appendChild($df);
            $rp->appendChild($gen);
        }

        foreach ( $question->nonEmptyAnswers() as $ans ) {
            if ( Question::isBlankHtml($ans->feedback) || ! $ans->id ) {
                continue;
            }
            if ( ! QuestionTypes::usesChoiceAnswers($question->type) ) {
                continue;
            }
            $per = $dom->createElementNS(self::NS, 'respcondition');
            $per->setAttribute('continue', 'Yes');
            $cv = $dom->createElementNS(self::NS, 'conditionvar');
            $ve = self::textEl($dom, 'varequal', self::answerIdent($ans));
            $ve->setAttribute('respident', self::RESPONSE_IDENT);
            $cv->appendChild($ve);
            $per->appendChild($cv);
            $df = $dom->createElementNS(self::NS, 'displayfeedback');
            $df->setAttribute('feedbacktype', 'Response');
            $df->setAttribute('linkrefid', self::answerIdent($ans) . '_fb');
            $per->appendChild($df);
            $rp->appendChild($per);
        }

        $cond = $dom->createElementNS(self::NS, 'respcondition');
        $cond->setAttribute('continue', 'No');
        $cv = $dom->createElementNS(self::NS, 'conditionvar');
        self::fillCorrectCondition($dom, $cv, $question);
        $cond->appendChild($cv);
        $setvar = self::textEl($dom, 'setvar', '100');
        $setvar->setAttribute('action', 'Set');
        $setvar->setAttribute('varname', 'SCORE');
        $cond->appendChild($setvar);
        $rp->appendChild($cond);

        return $rp;
    }

    private static function fillCorrectCondition(\DOMDocument $dom, \DOMElement $cv, Question $question) {
        $answers = $question->nonEmptyAnswers();

        if ( $question->type === QuestionTypes::MULTIPLE_CHOICE || $question->type === QuestionTypes::TRUE_FALSE ) {
            foreach ( $answers as $ans ) {
                if ( $ans->correct ) {
                    $ve = self::textEl($dom, 'varequal', self::answerIdent($ans));
                    $ve->setAttribute('respident', self::RESPONSE_IDENT);
                    $cv->appendChild($ve);
                    return;
                }
            }
        }

        if ( $question->type === QuestionTypes::MULTIPLE_RESPONSE ) {
            $and = $dom->createElementNS(self::NS, 'and');
            foreach ( $answers as $ans ) {
                $ve = self::textEl($dom, 'varequal', self::answerIdent($ans));
                $ve->setAttribute('respident', self::RESPONSE_IDENT);
                if ( $ans->correct ) {
                    $and->appendChild($ve);
                } else {
                    $not = $dom->createElementNS(self::NS, 'not');
                    $not->appendChild($ve);
                    $and->appendChild($not);
                }
            }
            $cv->appendChild($and);
            return;
        }

        if ( $question->type === QuestionTypes::FILL_BLANK ) {
            $case = 'No';
            if ( count($answers) === 1 ) {
                $ve = self::textEl($dom, 'varequal', $answers[0]->text);
                $ve->setAttribute('respident', self::RESPONSE_IDENT);
                $ve->setAttribute('case', $case);
                $cv->appendChild($ve);
                return;
            }
            $or = $dom->createElementNS(self::NS, 'or');
            foreach ( $answers as $ans ) {
                $ve = self::textEl($dom, 'varequal', $ans->text);
                $ve->setAttribute('respident', self::RESPONSE_IDENT);
                $ve->setAttribute('case', $case);
                $or->appendChild($ve);
            }
            $cv->appendChild($or);
            return;
        }

        if ( $question->type === QuestionTypes::PATTERN_MATCH ) {
            $case = $question->case_sensitive ? 'Yes' : 'No';
            if ( count($answers) === 1 ) {
                $vs = self::textEl($dom, 'varsubstring', $answers[0]->text);
                $vs->setAttribute('respident', self::RESPONSE_IDENT);
                $vs->setAttribute('case', $case);
                $cv->appendChild($vs);
                return;
            }
            $or = $dom->createElementNS(self::NS, 'or');
            foreach ( $answers as $ans ) {
                $vs = self::textEl($dom, 'varsubstring', $ans->text);
                $vs->setAttribute('respident', self::RESPONSE_IDENT);
                $vs->setAttribute('case', $case);
                $or->appendChild($vs);
            }
            $cv->appendChild($or);
        }
    }

    private static function itemFeedback(\DOMDocument $dom, $ident, $html) {
        $fb = $dom->createElementNS(self::NS, 'itemfeedback');
        $fb->setAttribute('ident', $ident);
        $material = $dom->createElementNS(self::NS, 'material');
        $material->appendChild(self::mattext($dom, $html));
        $fb->appendChild($material);
        return $fb;
    }

    /**
     * CC QTI essay sample solution: itemfeedback/solution/solutionmaterial/material/mattext
     */
    private static function itemSolutionFeedback(\DOMDocument $dom, $html) {
        $fb = $dom->createElementNS(self::NS, 'itemfeedback');
        $fb->setAttribute('ident', 'solution');
        $solution = $dom->createElementNS(self::NS, 'solution');
        $solutionmaterial = $dom->createElementNS(self::NS, 'solutionmaterial');
        $material = $dom->createElementNS(self::NS, 'material');
        $material->appendChild(self::mattext($dom, $html));
        $solutionmaterial->appendChild($material);
        $solution->appendChild($solutionmaterial);
        $fb->appendChild($solution);
        return $fb;
    }

    private static function addMetaField(\DOMDocument $dom, \DOMElement $qtimetadata, $label, $entry) {
        $field = $dom->createElementNS(self::NS, 'qtimetadatafield');
        $field->appendChild(self::textEl($dom, 'fieldlabel', $label));
        $field->appendChild(self::textEl($dom, 'fieldentry', $entry));
        $qtimetadata->appendChild($field);
    }

    private static function textEl(\DOMDocument $dom, $name, $text) {
        $el = $dom->createElementNS(self::NS, $name);
        $el->appendChild($dom->createTextNode((string) $text));
        return $el;
    }

    /**
     * CC requires mattext content in CDATA. text/html when markup is present.
     */
    private static function mattext(\DOMDocument $dom, $content) {
        $content = (string) $content;
        $looks_html = $content !== strip_tags($content);
        $el = $dom->createElementNS(self::NS, 'mattext');
        $el->setAttribute('texttype', $looks_html ? 'text/html' : 'text/plain');
        $safe = str_replace(']]>', ']]]]><![CDATA[>', $content);
        $el->appendChild($dom->createCDATASection($safe));
        return $el;
    }
}
