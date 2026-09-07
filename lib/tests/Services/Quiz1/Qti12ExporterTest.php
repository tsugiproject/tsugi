<?php

use Tsugi\Services\Quiz1\Answer;
use Tsugi\Services\Quiz1\ExportException;
use Tsugi\Services\Quiz1\Qti12Exporter;
use Tsugi\Services\Quiz1\Question;
use Tsugi\Services\Quiz1\QuestionTypes;
use Tsugi\Services\Quiz1\Quiz;
use Tsugi\Services\Quiz1\SampleQuiz;

class Qti12ExporterTest extends \PHPUnit\Framework\TestCase
{
    public function testSampleExportIsWellFormedAndComplete() {
        $quiz = SampleQuiz::build(1);
        $xml = Qti12Exporter::export($quiz);
        $dom = $this->load($xml);
        $xp = $this->xpath($dom);

        $this->assertSame('questestinterop', $dom->documentElement->localName);
        $this->assertSame(Qti12Exporter::NS, $dom->documentElement->namespaceURI);

        $assessment = $xp->query('/q:questestinterop/q:assessment')->item(0);
        $this->assertNotNull($assessment);
        $this->assertSame('Q1_QUIZ_1', $assessment->getAttribute('ident'));
        $this->assertSame('QTI Export Test', $assessment->getAttribute('title'));

        $this->assertSame('cc.exam.v0p1', $this->meta($xp, $assessment, 'cc_profile'));
        $this->assertSame('Examination', $this->meta($xp, $assessment, 'qmd_assessmenttype'));
        $this->assertSame('Percentage', $this->meta($xp, $assessment, 'qmd_scoretype'));
        $this->assertSame(Qti12Exporter::SCHEMA, \Tsugi\Util\CC::QTI_SCHEMA_LOCATION);
        $this->assertStringContainsString('ccv1p2', $xml);
        $this->assertStringNotContainsString('ccv1p1', $xml);
        $this->assertStringNotContainsString('question_type', $xml);
        $this->assertStringNotContainsString('multiple_choice_question', $xml);

        $items = $xp->query('//q:item');
        $this->assertSame(6, $items->length);

        $idents = array();
        $profiles = array();
        foreach ( $items as $item ) {
            $idents[] = $item->getAttribute('ident');
            $profiles[] = $this->meta($xp, $item, 'cc_profile');
        }
        $this->assertSame(
            array('Q1_ITEM_101', 'Q1_ITEM_102', 'Q1_ITEM_103', 'Q1_ITEM_104', 'Q1_ITEM_105', 'Q1_ITEM_106'),
            $idents
        );
        $this->assertSame(
            array(
                'cc.multiple_choice.v0p1',
                'cc.multiple_response.v0p1',
                'cc.true_false.v0p1',
                'cc.essay.v0p1',
                'cc.fib.v0p1',
                'cc.pattern_match.v0p1',
            ),
            $profiles
        );
    }

    public function testMultipleChoiceStructureAndCorrectResponse() {
        $xml = Qti12Exporter::export(SampleQuiz::build(1));
        $xp = $this->xpath($this->load($xml));
        $item = $this->item($xp, 'Q1_ITEM_101');

        $this->assertSame('1', $this->meta($xp, $item, 'cc_weighting'));
        $this->assertSame('Yes', $this->meta($xp, $item, 'qmd_computerscored'));

        $lid = $xp->query('q:presentation/q:response_lid', $item)->item(0);
        $this->assertSame('Single', $lid->getAttribute('rcardinality'));
        $this->assertSame('response', $lid->getAttribute('ident'));

        $labels = $xp->query('q:render_choice/q:response_label', $lid);
        $this->assertSame(4, $labels->length);
        $this->assertSame('Q1_ANS_1011', $labels->item(0)->getAttribute('ident'));
        $this->assertSame('Q1_ANS_1012', $labels->item(1)->getAttribute('ident'));
        $this->assertSame('HTTP', trim($xp->query('.//q:mattext', $labels->item(0))->item(0)->textContent));

        $ve = $xp->query('q:resprocessing/q:respcondition/q:conditionvar/q:varequal', $item)->item(0);
        $this->assertSame('Q1_ANS_1011', trim($ve->textContent));
        $this->assertSame('response', $ve->getAttribute('respident'));
        $setvar = $xp->query('q:resprocessing/q:respcondition/q:setvar', $item)->item(0);
        $this->assertSame('100', trim($setvar->textContent));
        $this->assertSame('Set', $setvar->getAttribute('action'));
    }

    public function testMultipleResponseAllOrNothing() {
        $xml = Qti12Exporter::export(SampleQuiz::build(1));
        $xp = $this->xpath($this->load($xml));
        $item = $this->item($xp, 'Q1_ITEM_102');

        $this->assertSame('2', $this->meta($xp, $item, 'cc_weighting'));
        $lid = $xp->query('q:presentation/q:response_lid', $item)->item(0);
        $this->assertSame('Multiple', $lid->getAttribute('rcardinality'));

        $ands = $xp->query('q:resprocessing/q:respcondition/q:conditionvar/q:and', $item);
        $this->assertSame(1, $ands->length);
        $correct = array();
        $incorrect = array();
        foreach ( $xp->query('.//q:varequal', $ands->item(0)) as $ve ) {
            if ( $ve->parentNode->localName === 'not' ) {
                $incorrect[] = trim($ve->textContent);
            } else {
                $correct[] = trim($ve->textContent);
            }
        }
        $this->assertSame(array('Q1_ANS_1021', 'Q1_ANS_1022'), $correct);
        $this->assertSame(array('Q1_ANS_1023', 'Q1_ANS_1024'), $incorrect);
    }

    public function testTrueFalseCorrectIsFalse() {
        $xml = Qti12Exporter::export(SampleQuiz::build(1));
        $xp = $this->xpath($this->load($xml));
        $item = $this->item($xp, 'Q1_ITEM_103');
        $lid = $xp->query('q:presentation/q:response_lid', $item)->item(0);
        $this->assertSame('Single', $lid->getAttribute('rcardinality'));
        $labels = $xp->query('q:render_choice/q:response_label', $lid);
        $this->assertSame('True', trim($xp->query('.//q:mattext', $labels->item(0))->item(0)->textContent));
        $this->assertSame('False', trim($xp->query('.//q:mattext', $labels->item(1))->item(0)->textContent));
        $ve = $xp->query('q:resprocessing/q:respcondition/q:conditionvar/q:varequal', $item)->item(0);
        $this->assertSame('Q1_ANS_1032', trim($ve->textContent));
    }

    public function testEssayIsNotComputerScored() {
        $xml = Qti12Exporter::export(SampleQuiz::build(1));
        $xp = $this->xpath($this->load($xml));
        $item = $this->item($xp, 'Q1_ITEM_104');
        $this->assertSame('No', $this->meta($xp, $item, 'qmd_computerscored'));
        $this->assertSame('5', $this->meta($xp, $item, 'cc_weighting'));
        $this->assertSame(0, $xp->query('q:resprocessing', $item)->length);
        $this->assertSame(1, $xp->query('q:presentation/q:response_str', $item)->length);
        $fb = $xp->query('q:itemfeedback[@ident="solution"]', $item);
        $this->assertSame(1, $fb->length);
        $this->assertSame(1, $xp->query('q:itemfeedback[@ident="solution"]/q:solution/q:solutionmaterial/q:material/q:mattext', $item)->length);
        $this->assertSame(0, $xp->query('q:itemfeedback[@ident="solution"]/q:material', $item)->length);
        $mat = $xp->query('q:itemfeedback[@ident="solution"]//q:mattext', $item)->item(0);
        $this->assertSame('text/html', $mat->getAttribute('texttype'));
        $this->assertStringContainsString('Representational State Transfer', $mat->textContent);
        $this->assertStringContainsString('<p>', $mat->textContent);
    }

    public function testFillBlankLiteralAnswersAreCaseInsensitive() {
        $xml = Qti12Exporter::export(SampleQuiz::build(1));
        $xp = $this->xpath($this->load($xml));
        $item = $this->item($xp, 'Q1_ITEM_105');
        $str = $xp->query('q:presentation/q:response_str', $item)->item(0);
        $this->assertNotNull($str);
        $this->assertSame('Single', $str->getAttribute('rcardinality'));
        $values = array();
        foreach ( $xp->query('.//q:varequal', $item) as $ve ) {
            $this->assertSame('No', $ve->getAttribute('case'));
            $values[] = trim($ve->textContent);
        }
        $this->assertSame(array('80', 'eighty'), $values);
        $this->assertSame(1, $xp->query('q:resprocessing/q:respcondition/q:conditionvar/q:or', $item)->length);
        $this->assertSame(1, $xp->query('q:resprocessing/q:respcondition[@continue="No"]', $item)->length);
    }

    public function testFillBlankSingleAnswerHasNoOr() {
        $xml = Qti12Exporter::export(SampleQuiz::buildFillBlankSingle(1));
        $xp = $this->xpath($this->load($xml));
        $item = $this->item($xp, 'Q1_ITEM_105');
        $this->assertSame(0, $xp->query('.//q:or', $item)->length);
        $ve = $xp->query('.//q:varequal', $item);
        $this->assertSame(1, $ve->length);
        $this->assertSame('80', trim($ve->item(0)->textContent));
        $this->assertSame('No', $ve->item(0)->getAttribute('case'));
    }

    public function testCanvasItemMetadataAndAssessmentIdent() {
        $xml = Qti12Exporter::export(SampleQuiz::build(1), array(
            'pattern_match_as_fib' => true,
            'canvas_item_metadata' => true,
            'assessment_ident' => 'Q1_deadbeef',
        ));
        $xp = $this->xpath($this->load($xml));
        $this->assertSame('Q1_deadbeef', $xp->query('/q:questestinterop/q:assessment')->item(0)->getAttribute('ident'));
        $mc = $this->item($xp, 'Q1_ITEM_101');
        $this->assertSame('multiple_choice_question', $this->meta($xp, $mc, 'question_type'));
        $this->assertSame('1', $this->meta($xp, $mc, 'points_possible'));
        $this->assertSame('cc.multiple_choice.v0p1', $this->meta($xp, $mc, 'cc_profile'));
    }

    public function testCanvasExportMapsPatternMatchToFillBlank() {
        $xml = Qti12Exporter::export(SampleQuiz::build(1), array('pattern_match_as_fib' => true));
        $xp = $this->xpath($this->load($xml));
        $this->assertSame(0, $xp->query('//q:fieldentry[text()="cc.pattern_match.v0p1"]')->length);
        $this->assertSame(0, $xp->query('//q:varsubstring')->length);
        $item = $this->item($xp, 'Q1_ITEM_106');
        $this->assertSame('cc.fib.v0p1', $this->meta($xp, $item, 'cc_profile'));
        $ve = $xp->query('.//q:varequal', $item);
        $this->assertSame(1, $ve->length);
        $this->assertSame('Script', trim($ve->item(0)->textContent));
        $this->assertSame('No', $ve->item(0)->getAttribute('case'));
    }

    public function testPatternMatchUsesVarsubstring() {
        $xml = Qti12Exporter::export(SampleQuiz::build(1));
        $xp = $this->xpath($this->load($xml));
        $item = $this->item($xp, 'Q1_ITEM_106');
        $vs = $xp->query('.//q:varsubstring', $item);
        $this->assertSame(1, $vs->length);
        $this->assertSame('Script', trim($vs->item(0)->textContent));
        $this->assertSame('No', $vs->item(0)->getAttribute('case'));
        $this->assertSame(0, $xp->query('.//q:varequal', $item)->length);
    }

    public function testSpecialCharactersAreEscapedAndHtmlSurvives() {
        $xml = Qti12Exporter::export(SampleQuiz::build(1));
        $this->assertStringContainsString('<![CDATA[', $xml);

        $xp = $this->xpath($this->load($xml));
        $item = $this->item($xp, 'Q1_ITEM_101');
        $prompt = $xp->query('q:presentation/q:material/q:mattext', $item)->item(0);
        $this->assertSame('text/html', $prompt->getAttribute('texttype'));
        $this->assertStringContainsString('2 < 3', $prompt->textContent);
        $this->assertStringContainsString('&', $prompt->textContent);

        $item2 = $this->item($xp, 'Q1_ITEM_102');
        $html = $xp->query('q:presentation/q:material/q:mattext', $item2)->item(0)->textContent;
        $this->assertStringContainsString('<strong>all</strong>', $html);

        $quiz = new Quiz();
        $quiz->id = 8;
        $quiz->title = 'Quote " & <test>';
        $q = new Question();
        $q->id = 80;
        $q->sequence = 1;
        $q->type = QuestionTypes::FILL_BLANK;
        $q->prompt = '<p>Type "AT&T" if x < y</p>';
        $q->points = 1;
        $q->answers = array(Answer::make('AT&T <ok>', true, 1, 801));
        $quiz->questions[] = $q;
        $xml2 = Qti12Exporter::export($quiz);
        $this->assertNotFalse($this->load($xml2));
        $xp2 = $this->xpath($this->load($xml2));
        $this->assertSame('Quote " & <test>', $xp2->query('//q:assessment')->item(0)->getAttribute('title'));
        $ve = $xp2->query('//q:varequal')->item(0);
        $this->assertSame('AT&T <ok>', trim($ve->textContent));
    }

    public function testGoldenSampleRegression() {
        $xml = Qti12Exporter::export(SampleQuiz::build(1));
        $golden = __DIR__ . '/../../fixtures/Quiz1/sample-qti.xml';
        $this->assertFileExists($golden);
        $expected = file_get_contents($golden);
        $this->assertXmlStringEqualsXmlString($expected, $xml);
    }

    public function testInvalidQuizRefusesExport() {
        $quiz = new Quiz();
        $quiz->id = 9;
        $quiz->title = 'Bad';
        $q = new Question();
        $q->id = 1;
        $q->type = QuestionTypes::MULTIPLE_CHOICE;
        $q->prompt = '<p>No choices</p>';
        $q->points = 1;
        $quiz->questions[] = $q;
        $this->expectException(ExportException::class);
        Qti12Exporter::export($quiz);
    }

    public function testEmptyQuizRefusesExport() {
        $quiz = new Quiz();
        $quiz->id = 9;
        $quiz->title = 'Empty';
        $this->expectException(ExportException::class);
        $this->expectExceptionMessage('at least one question');
        Qti12Exporter::export($quiz);
    }

    public function testAnswerOrderPreserved() {
        $quiz = new Quiz();
        $quiz->id = 7;
        $quiz->title = 'Order';
        $q = new Question();
        $q->id = 70;
        $q->sequence = 1;
        $q->type = QuestionTypes::MULTIPLE_CHOICE;
        $q->prompt = '<p>Pick</p>';
        $q->points = 1;
        $q->answers = array(
            Answer::make('First', false, 1, 701),
            Answer::make('Second', true, 2, 702),
            Answer::make('Third', false, 3, 703),
        );
        $quiz->questions[] = $q;
        $xp = $this->xpath($this->load(Qti12Exporter::export($quiz)));
        $labels = $xp->query('//q:response_label');
        $this->assertSame('Q1_ANS_701', $labels->item(0)->getAttribute('ident'));
        $this->assertSame('Q1_ANS_702', $labels->item(1)->getAttribute('ident'));
        $this->assertSame('Q1_ANS_703', $labels->item(2)->getAttribute('ident'));
        $this->assertSame('First', trim($xp->query('.//q:mattext', $labels->item(0))->item(0)->textContent));
    }

    public function testHtmlInQuestionAnswerAndEssaySolution() {
        $quiz = new Quiz();
        $quiz->id = 3;
        $quiz->title = 'HTML';
        $q = new Question();
        $q->id = 31;
        $q->sequence = 1;
        $q->type = QuestionTypes::MULTIPLE_CHOICE;
        $q->prompt = '<p>This is <strong>important</strong>.</p>';
        $q->points = 1;
        $q->answers = array(
            Answer::make('<em>Yes</em>', true, 1, 311),
            Answer::make('No', false, 2, 312),
        );
        $quiz->questions[] = $q;
        $e = new Question();
        $e->id = 32;
        $e->sequence = 2;
        $e->type = QuestionTypes::ESSAY;
        $e->prompt = '<p>Explain <code>REST</code>.</p>';
        $e->points = 1;
        $e->sample_solution = '<p>This is <strong>important</strong>.</p>';
        $quiz->questions[] = $e;

        $xml = Qti12Exporter::export($quiz);
        $this->assertStringNotContainsString('&lt;p&gt;', $xml);
        $xp = $this->xpath($this->load($xml));
        $mc = $this->item($xp, 'Q1_ITEM_31');
        $prompt = $xp->query('q:presentation/q:material/q:mattext', $mc)->item(0);
        $this->assertSame('text/html', $prompt->getAttribute('texttype'));
        $this->assertStringContainsString('<strong>important</strong>', $prompt->textContent);
        $ans = $xp->query('q:presentation//q:response_label[@ident="Q1_ANS_311"]//q:mattext', $mc)->item(0);
        $this->assertSame('text/html', $ans->getAttribute('texttype'));
        $this->assertStringContainsString('<em>Yes</em>', $ans->textContent);
        $essay = $this->item($xp, 'Q1_ITEM_32');
        $sol = $xp->query('q:itemfeedback[@ident="solution"]/q:solution/q:solutionmaterial/q:material/q:mattext', $essay)->item(0);
        $this->assertNotNull($sol);
        $this->assertStringContainsString('<strong>important</strong>', $sol->textContent);
    }

    private function load($xml) {
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $ok = $dom->loadXML($xml);
        $this->assertTrue($ok, 'XML should be well formed');
        return $dom;
    }

    private function xpath(\DOMDocument $dom) {
        $xp = new \DOMXPath($dom);
        $xp->registerNamespace('q', Qti12Exporter::NS);
        return $xp;
    }

    private function item(\DOMXPath $xp, $ident) {
        $nodes = $xp->query('//q:item[@ident="'.$ident.'"]');
        $this->assertSame(1, $nodes->length, 'Expected item '.$ident);
        return $nodes->item(0);
    }

    private function meta(\DOMXPath $xp, \DOMNode $ctx, $label) {
        $fields = $xp->query('.//q:qtimetadatafield', $ctx);
        foreach ( $fields as $field ) {
            $lab = $xp->query('q:fieldlabel', $field)->item(0);
            if ( $lab && trim($lab->textContent) === $label ) {
                return trim($xp->query('q:fieldentry', $field)->item(0)->textContent);
            }
        }
        $this->fail('Missing metadata '.$label);
    }
}
