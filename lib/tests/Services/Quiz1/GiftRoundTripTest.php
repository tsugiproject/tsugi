<?php

use Tsugi\Services\Quiz1\ExportException;
use Tsugi\Services\Quiz1\GiftExporter;
use Tsugi\Services\Quiz1\GiftImporter;
use Tsugi\Services\Quiz1\ImportException;
use Tsugi\Services\Quiz1\QuestionTypes;
use Tsugi\Services\Quiz1\SampleQuiz;

class GiftRoundTripTest extends \PHPUnit\Framework\TestCase
{
    public function testSampleExportContainsAllTypes() {
        $gift = GiftExporter::export(SampleQuiz::build(1));
        $this->assertStringContainsString('HTTP protocol', $gift);
        $this->assertStringContainsString('=HTTP', $gift);
        $this->assertStringContainsString('~FTP', $gift);
        $this->assertStringContainsString('~%100%GET', $gift);
        $this->assertStringContainsString('{F}', $gift);
        $this->assertStringContainsString('Explain REST', $gift);
        $this->assertStringContainsString('{}', $gift);
        $this->assertStringContainsString('=80', $gift);
        $this->assertStringContainsString('=eighty', $gift);
        $this->assertStringContainsString('Pattern match', $gift);
        $this->assertStringContainsString('=Script', $gift);
    }

    public function testSampleRoundTripMapsPatternMatchToFillBlank() {
        $gift = GiftExporter::export(SampleQuiz::build(1));
        list($quiz, $warnings) = GiftImporter::import($gift);
        $this->assertSame(array(), $warnings);
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
                QuestionTypes::FILL_BLANK,
            ),
            $types
        );

        $mc = $quiz->questions[0];
        $this->assertSame('HTTP protocol', $mc->title);
        $this->assertStringContainsString('Which protocol', $mc->prompt);
        $this->assertTrue($mc->answers[0]->correct);
        $this->assertSame('HTTP', strip_tags($mc->answers[0]->text));
        $this->assertFalse($mc->answers[1]->correct);

        $mr = $quiz->questions[1];
        $correct = array();
        foreach ( $mr->answers as $ans ) {
            if ( $ans->correct ) {
                $correct[] = strip_tags($ans->text);
            }
        }
        $this->assertSame(array('GET', 'POST'), $correct);

        $tf = $quiz->questions[2];
        $this->assertFalse($tf->answers[0]->correct);
        $this->assertTrue($tf->answers[1]->correct);

        $this->assertSame(QuestionTypes::ESSAY, $quiz->questions[3]->type);
        $this->assertCount(0, $quiz->questions[3]->answers);

        $fib = $quiz->questions[4];
        $this->assertSame(array('80', 'eighty'), array(
            strip_tags($fib->answers[0]->text),
            strip_tags($fib->answers[1]->text),
        ));
    }

    public function testPlainGiftWithoutTitles() {
        $text = "What is 2+2? {=4 ~3 ~5}\n\nIs the sky blue? {T}\n";
        list($quiz, $warnings) = GiftImporter::import($text);
        $this->assertSame(array(), $warnings);
        $this->assertCount(2, $quiz->questions);
        $this->assertSame(QuestionTypes::MULTIPLE_CHOICE, $quiz->questions[0]->type);
        $this->assertSame('4', $quiz->questions[0]->answers[0]->text);
        $this->assertSame(QuestionTypes::TRUE_FALSE, $quiz->questions[1]->type);
        $this->assertTrue($quiz->questions[1]->answers[0]->correct);
    }

    public function testShortAnswerBecomesFillBlank() {
        $text = '::Port:: The default HTTP port is {=80 =eighty}';
        list($quiz, $warnings) = GiftImporter::import($text);
        $this->assertSame(array(), $warnings);
        $this->assertSame(QuestionTypes::FILL_BLANK, $quiz->questions[0]->type);
        $this->assertCount(2, $quiz->questions[0]->answers);
    }

    public function testNumericalIsSkippedWithWarning() {
        $text = "::Num:: How many? {#3:2}\n\n::OK:: Pick {=yes ~no}\n";
        list($quiz, $warnings) = GiftImporter::import($text);
        $this->assertCount(1, $quiz->questions);
        $this->assertNotEmpty($warnings);
        $this->assertStringContainsString('Numerical', $warnings[0]);
    }

    public function testEmptyGiftThrows() {
        $this->expectException(ImportException::class);
        GiftImporter::import('   ');
    }

    public function testEmptyQuizRefusesExport() {
        $quiz = new \Tsugi\Services\Quiz1\Quiz();
        $quiz->title = 'Empty';
        $this->expectException(ExportException::class);
        GiftExporter::export($quiz);
    }

    public function testHtmlAndSpecialCharactersRoundTrip() {
        $gift = GiftExporter::export(SampleQuiz::build(1));
        list($quiz,) = GiftImporter::import($gift);
        $this->assertStringContainsString('<strong>all</strong>', $quiz->questions[1]->prompt);
        $this->assertStringContainsString('2 &lt; 3', $quiz->questions[0]->prompt);
    }
}
