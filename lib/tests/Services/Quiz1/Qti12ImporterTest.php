<?php

use Tsugi\Services\Quiz1\ImportException;
use Tsugi\Services\Quiz1\Qti12Exporter;
use Tsugi\Services\Quiz1\Qti12Importer;
use Tsugi\Services\Quiz1\QuestionTypes;
use Tsugi\Services\Quiz1\SampleQuiz;

class Qti12ImporterTest extends \PHPUnit\Framework\TestCase
{
    public function testGoldenSampleRoundTrip() {
        $xml = file_get_contents(__DIR__ . '/../../fixtures/Quiz1/sample-qti.xml');
        list($quiz, $warnings) = Qti12Importer::import($xml);
        $this->assertSame(array(), $warnings);
        $this->assertSame('QTI Export Test', $quiz->title);
        $this->assertStringContainsString('Sample quiz', $quiz->instructions);
        $this->assertCount(6, $quiz->questions);

        $types = array();
        foreach ( $quiz->orderedQuestions() as $q ) {
            $types[] = $q->type;
        }
        $this->assertSame(
            array(
                QuestionTypes::MULTIPLE_CHOICE,
                QuestionTypes::MULTIPLE_RESPONSE,
                QuestionTypes::TRUE_FALSE,
                QuestionTypes::ESSAY,
                QuestionTypes::FILL_BLANK,
                QuestionTypes::PATTERN_MATCH,
            ),
            $types
        );

        $mc = $quiz->questions[0];
        $this->assertSame('HTTP protocol', $mc->title);
        $this->assertSame(1, $mc->points);
        $this->assertTrue($mc->answers[0]->correct);
        $this->assertSame('HTTP', strip_tags($mc->answers[0]->text));
        $this->assertFalse($mc->answers[1]->correct);

        $mr = $quiz->questions[1];
        $this->assertSame(2, $mr->points);
        $correct = array();
        foreach ( $mr->answers as $ans ) {
            if ( $ans->correct ) {
                $correct[] = strip_tags($ans->text);
            }
        }
        $this->assertSame(array('GET', 'POST'), $correct);

        $tf = $quiz->questions[2];
        $this->assertSame('True', $tf->answers[0]->text);
        $this->assertFalse($tf->answers[0]->correct);
        $this->assertTrue($tf->answers[1]->correct);

        $essay = $quiz->questions[3];
        $this->assertSame(5, $essay->points);
        $this->assertStringContainsString('Representational State Transfer', $essay->sample_solution);

        $fib = $quiz->questions[4];
        $this->assertSame(array('80', 'eighty'), array(
            $fib->answers[0]->text,
            $fib->answers[1]->text,
        ));

        $pm = $quiz->questions[5];
        $this->assertSame('Script', $pm->answers[0]->text);
        $this->assertFalse($pm->case_sensitive);
    }

    public function testExporterImporterRoundTrip() {
        $xml = Qti12Exporter::export(SampleQuiz::build(1));
        list($quiz, $warnings) = Qti12Importer::import($xml);
        $this->assertSame(array(), $warnings);
        $this->assertCount(6, $quiz->questions);
        $this->assertSame(QuestionTypes::PATTERN_MATCH, $quiz->questions[5]->type);
        $this->assertStringContainsString('<strong>all</strong>', $quiz->questions[1]->prompt);
    }

    public function testCanvasQuestionTypeMetadata() {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<questestinterop>
  <assessment ident="a1" title="Canvas">
    <section ident="s1">
      <item ident="i1" title="Pick">
        <itemmetadata>
          <qtimetadata>
            <qtimetadatafield>
              <fieldlabel>question_type</fieldlabel>
              <fieldentry>multiple_choice_question</fieldentry>
            </qtimetadatafield>
            <qtimetadatafield>
              <fieldlabel>points_possible</fieldlabel>
              <fieldentry>3</fieldentry>
            </qtimetadatafield>
          </qtimetadata>
        </itemmetadata>
        <presentation>
          <material><mattext>Choose</mattext></material>
          <response_lid ident="response" rcardinality="Single">
            <render_choice>
              <response_label ident="A"><material><mattext>Yes</mattext></material></response_label>
              <response_label ident="B"><material><mattext>No</mattext></material></response_label>
            </render_choice>
          </response_lid>
        </presentation>
        <resprocessing>
          <respcondition>
            <conditionvar><varequal respident="response">A</varequal></conditionvar>
            <setvar action="Set" varname="SCORE">100</setvar>
          </respcondition>
        </resprocessing>
      </item>
    </section>
  </assessment>
</questestinterop>
XML;
        list($quiz, $warnings) = Qti12Importer::import($xml);
        $this->assertSame(array(), $warnings);
        $this->assertSame(QuestionTypes::MULTIPLE_CHOICE, $quiz->questions[0]->type);
        $this->assertSame(3, $quiz->questions[0]->points);
        $this->assertTrue($quiz->questions[0]->answers[0]->correct);
    }

    public function testZipContainingQti() {
        $xml = Qti12Exporter::export(SampleQuiz::buildMinimal(1));
        $tmp = tempnam(sys_get_temp_dir(), 'q1z');
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('quiz/assessment.xml', $xml);
        $zip->close();
        $bytes = file_get_contents($tmp);
        @unlink($tmp);

        list($quiz, $warnings) = Qti12Importer::import($bytes);
        $this->assertSame(array(), $warnings);
        $this->assertCount(1, $quiz->questions);
        $this->assertSame(QuestionTypes::MULTIPLE_CHOICE, $quiz->questions[0]->type);
    }

    public function testEmptyXmlThrows() {
        $this->expectException(ImportException::class);
        Qti12Importer::import('<questestinterop></questestinterop>');
    }

    public function testInvalidXmlThrows() {
        $this->expectException(ImportException::class);
        Qti12Importer::import('not xml at all');
    }
}
