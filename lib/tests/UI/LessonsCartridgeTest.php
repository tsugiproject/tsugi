<?php

require_once "src/Core/I18N.php";
require_once "include/setup_i18n.php";
require_once "src/UI/Lessons.php";
require_once "src/UI/LessonsNormalize.php";
require_once "src/UI/LessonsCartridge.php";
require_once "src/Config/ConfigInfo.php";

use Tsugi\UI\LessonsCartridge;
use Tsugi\Services\Quiz1\ExportException;
use Tsugi\Services\Quiz1\SampleQuiz;
use Tsugi\Util\CC;

class LessonsCartridgeTest extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;

    protected function setUp(): void
    {
        global $CFG;
        $this->originalCFG = $CFG;
        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost');
        $CFG->apphome = 'http://localhost/app';
        $CFG->wwwroot = 'http://localhost';
        $CFG->fontawesome = 'http://localhost/fontawesome';
    }

    protected function tearDown(): void
    {
        global $CFG;
        $CFG = $this->originalCFG;
    }

    public function testSummarizeCountsNativeQuizzesSeparatelyFromLti() {
        $l = $this->lessonsDoc(array(
            array('type' => 'quiz', 'title' => 'Native', 'quiz_id' => 1),
            array(
                'type' => 'lti',
                'subtype' => 'quiz',
                'title' => 'Gift',
                'launch' => 'mod/gift/',
                'resource_link_id' => 'g1',
            ),
            array('type' => 'web_link', 'subtype' => 'reference', 'title' => 'Doc', 'href' => 'https://example.com/'),
        ));
        $counts = LessonsCartridge::summarize($l);
        $this->assertSame(1, $counts['modules']);
        $this->assertSame(1, $counts['quizzes']);
        $this->assertSame(1, $counts['assignments']);
        $this->assertSame(1, $counts['resources']);
        $this->assertSame(0, $counts['discussions']);
    }

    public function testWriteZipIncludesQtiForLessonQuiz() {
        $l = $this->lessonsDoc(array(
            array('type' => 'quiz', 'title' => 'Week 1 Quiz', 'quiz_id' => 1),
        ));
        $path = $this->writeCartridge($l, array(
            'load_quiz' => function ($id) {
                $this->assertSame(1, $id);
                return SampleQuiz::build($id);
            },
        ));
        try {
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $manifest = $zip->getFromName('imsmanifest.xml');
            $this->assertNotFalse($manifest);
            $this->assertStringContainsString('type="'.CC::QTI_ASSESSMENT_TYPE.'"', $manifest);
            $this->assertStringContainsString('<title>Week 1 Quiz</title>', $manifest);
            $this->assertStringContainsString('<schemaversion>'.CC::VERSION.'</schemaversion>', $manifest);
            $this->assertFalse($zip->getFromName('course_settings/module_meta.xml'));
            $this->assertFalse($zip->getFromName('course_settings/canvas_export.txt'));
            $this->assertStringNotContainsString('canvas.instructure.com', $manifest);
            $this->assertStringNotContainsString('associatedcontent/', $manifest);
            $this->assertStringNotContainsString('assessment_meta.xml', $manifest);
            $this->assertStringNotContainsString('non_cc_assessments', $manifest);

            $qtiFile = null;
            $has_non_cc = false;
            for ( $i = 0; $i < $zip->numFiles; $i++ ) {
                $name = $zip->getNameIndex($i);
                if ( ! is_string($name) ) {
                    continue;
                }
                if ( str_starts_with($name, 'xml/Q1_') && str_ends_with($name, '.xml') ) {
                    $qtiFile = $name;
                }
                if ( str_starts_with($name, 'non_cc_assessments/') ) {
                    $has_non_cc = true;
                }
            }
            $this->assertFalse($has_non_cc);
            $this->assertNotNull($qtiFile, 'Cartridge should contain a Q1_ QTI XML file');
            $xml = $zip->getFromName($qtiFile);
            $this->assertNotFalse($xml);
            $this->assertStringNotContainsString('question_type', $xml);
            $this->assertStringContainsString('questestinterop', $xml);
            $this->assertStringContainsString('Q1_QUIZ_1', $xml);
            $this->assertStringContainsString('cc.multiple_choice.v0p1', $xml);
            $this->assertStringContainsString('cc.pattern_match.v0p1', $xml);
            $zip->close();
        } finally {
            @unlink($path);
        }
    }

    public function testWriteZipCanvasKeepsModuleMeta() {
        $l = $this->lessonsDoc(array(
            array('type' => 'quiz', 'title' => 'Week 1 Quiz', 'quiz_id' => 1),
        ));
        $path = $this->writeCartridge($l, array(
            'tsugi_lms' => 'canvas',
            'load_quiz' => function ($id) {
                return SampleQuiz::build($id);
            },
        ));
        try {
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $meta = $zip->getFromName('course_settings/module_meta.xml');
            $this->assertNotFalse($meta);
            $this->assertStringContainsString('<content_type>Quizzes::Quiz</content_type>', $meta);
            $this->assertNotFalse($zip->getFromName('course_settings/canvas_export.txt'));
            $manifest = $zip->getFromName('imsmanifest.xml');
            $this->assertStringContainsString('<dependency identifierref="', $manifest);
            $this->assertStringContainsString('assessment_meta.xml', $manifest);
            $this->assertStringContainsString('assessment_qti.xml', $manifest);

            $qti = null;
            $metaXml = null;
            $nonCc = null;
            for ( $i = 0; $i < $zip->numFiles; $i++ ) {
                $name = $zip->getNameIndex($i);
                if ( ! is_string($name) ) {
                    continue;
                }
                if ( str_ends_with($name, '/assessment_qti.xml') ) {
                    $qti = $zip->getFromName($name);
                }
                if ( str_ends_with($name, '/assessment_meta.xml') ) {
                    $metaXml = $zip->getFromName($name);
                }
                if ( str_starts_with($name, 'non_cc_assessments/') && str_ends_with($name, '.xml.qti') ) {
                    $nonCc = $zip->getFromName($name);
                }
            }
            $this->assertNotFalse($qti);
            $this->assertNotFalse($metaXml);
            $this->assertNotFalse($nonCc);
            $this->assertSame($qti, $nonCc);
            $this->assertStringNotContainsString('cc.pattern_match.v0p1', $qti);
            $this->assertStringNotContainsString('varsubstring', $qti);
            $this->assertStringContainsString('cc.fib.v0p1', $qti);
            $this->assertStringContainsString('<fieldlabel>question_type</fieldlabel>', $qti);
            $this->assertStringContainsString('multiple_choice_question', $qti);
            $this->assertStringContainsString('<quiz_type>assignment</quiz_type>', $metaXml);
            $this->assertStringContainsString('<points_possible>11</points_possible>', $metaXml);
            $this->assertStringContainsString('online_quiz', $metaXml);
            $this->assertSame(1, preg_match('/<assessment ident="(Q1_[^"]+)"/', $qti, $identMatch));
            $quizIdent = $identMatch[1];
            $this->assertStringContainsString('<identifierref>'.$quizIdent.'</identifierref>', $meta);
            $this->assertStringNotContainsString('<identifierref>'.$quizIdent.'_R</identifierref>', $meta);
            $this->assertStringContainsString('<quiz_identifierref>'.$quizIdent.'</quiz_identifierref>', $metaXml);
            $this->assertFalse($zip->getFromName('xml/Q1_dummy.xml'));
            $names = array();
            for ( $i = 0; $i < $zip->numFiles; $i++ ) {
                $names[] = $zip->getNameIndex($i);
            }
            $xmlQ1 = array_filter($names, function ($n) {
                return is_string($n) && str_starts_with($n, 'xml/Q1_');
            });
            $this->assertCount(0, $xmlQ1, 'Canvas flavor uses assessment_qti.xml, not xml/Q1_*.xml');
            $zip->close();
        } finally {
            @unlink($path);
        }
    }

    public function testWriteZipSakaiHasModuleMetaButNoQuizWrapper() {
        $l = $this->lessonsDoc(array(
            array('type' => 'quiz', 'title' => 'Week 1 Quiz', 'quiz_id' => 1),
        ));
        $path = $this->writeCartridge($l, array(
            'tsugi_lms' => 'sakai',
            'load_quiz' => function ($id) {
                return SampleQuiz::build($id);
            },
        ));
        try {
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $this->assertNotFalse($zip->getFromName('course_settings/module_meta.xml'));
            $has_non_cc = false;
            $has_assessment_meta = false;
            for ( $i = 0; $i < $zip->numFiles; $i++ ) {
                $name = $zip->getNameIndex($i);
                if ( is_string($name) && str_starts_with($name, 'non_cc_assessments/') ) {
                    $has_non_cc = true;
                }
                if ( is_string($name) && str_ends_with($name, 'assessment_meta.xml') ) {
                    $has_assessment_meta = true;
                }
            }
            $this->assertFalse($has_non_cc);
            $this->assertFalse($has_assessment_meta);
            $qtiFile = null;
            for ( $i = 0; $i < $zip->numFiles; $i++ ) {
                $name = $zip->getNameIndex($i);
                if ( is_string($name) && str_starts_with($name, 'xml/Q1_') && str_ends_with($name, '.xml') ) {
                    $qtiFile = $name;
                    break;
                }
            }
            $this->assertNotNull($qtiFile);
            $qti = $zip->getFromName($qtiFile);
            $this->assertStringContainsString('cc.pattern_match.v0p1', $qti);
            $this->assertStringNotContainsString('question_type', $qti);
            $zip->close();
        } finally {
            @unlink($path);
        }
    }

    public function testDownloadNameIncludesExportFlavor() {
        $l = $this->lessonsDoc(array());
        $this->assertSame('Course_generic.imscc', LessonsCartridge::downloadName($l));
        $this->assertSame('Course_generic.imscc', LessonsCartridge::downloadName($l, 'generic'));
        $this->assertSame('Course_generic.imscc', LessonsCartridge::downloadName($l, false));
        $this->assertSame('Course_canvas.imscc', LessonsCartridge::downloadName($l, 'canvas'));
        $this->assertSame('Course_sakai.imscc', LessonsCartridge::downloadName($l, 'sakai'));
        $this->assertSame('generic', LessonsCartridge::exportFlavor(''));
        $this->assertSame('canvas', LessonsCartridge::exportFlavor('Canvas'));
    }

    public function testWriteZipDoesNotCallLoadQuizForLtiQuiz() {
        $called = false;
        $l = $this->lessonsDoc(array(
            array(
                'type' => 'lti',
                'subtype' => 'quiz',
                'title' => 'Gift',
                'launch' => 'https://example.com/mod/gift/',
                'resource_link_id' => 'g1',
            ),
        ));
        $path = $this->writeCartridge($l, array(
            'load_quiz' => function ($id) use (&$called) {
                $called = true;
                return SampleQuiz::build($id);
            },
        ));
        @unlink($path);
        $this->assertFalse($called);
    }

    public function testWriteZipFailsWhenQuizIdMissing() {
        $this->expectException(ExportException::class);
        $this->expectExceptionMessage('missing quiz_id');
        $l = $this->lessonsDoc(array(
            array('type' => 'quiz', 'title' => 'Broken'),
        ));
        $this->writeCartridge($l, array(
            'load_quiz' => function ($id) {
                return SampleQuiz::build($id);
            },
        ));
    }

    public function testWriteZipFailsWhenQuizNotFound() {
        $this->expectException(ExportException::class);
        $this->expectExceptionMessage('was not found');
        $l = $this->lessonsDoc(array(
            array('type' => 'quiz', 'title' => 'Missing', 'quiz_id' => 99),
        ));
        $this->writeCartridge($l, array(
            'load_quiz' => function ($id) {
                return null;
            },
        ));
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return object
     */
    private function lessonsDoc(array $items) {
        return (object) array(
            'lessons' => (object) array(
                'title' => 'Course',
                'modules' => array(
                    (object) array(
                        'title' => 'Week 1',
                        'anchor' => 'w1',
                        'items' => $items,
                    ),
                ),
            ),
        );
    }

    /**
     * @param object $l
     * @param array<string, mixed> $options
     * @return string Zip path
     */
    private function writeCartridge($l, array $options) {
        $filename = tempnam(sys_get_temp_dir(), 'ccq1');
        unlink($filename);
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($filename, \ZipArchive::CREATE) === true);
        try {
            LessonsCartridge::writeZip($l, $zip, $options);
        } catch ( \Exception $e ) {
            $zip->close();
            @unlink($filename);
            throw $e;
        }
        $zip->close();
        return $filename;
    }
}
