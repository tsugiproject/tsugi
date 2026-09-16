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
        $this->assertSame(0, $counts['files']);
        $this->assertSame(0, $counts['pages']);
    }

    public function testSummarizeCountsFilesSeparatelyFromResources()
    {
        $sha = str_repeat('a', 64);
        $l = $this->lessonsDoc(array(
            array('type' => 'web_link', 'subtype' => 'reference', 'title' => 'Doc', 'href' => 'https://example.com/'),
            array(
                'type' => 'file',
                'subtype' => 'slides',
                'title' => 'Week One Reading',
                'href' => '/files/download/'.$sha,
                'sha256' => $sha,
                'filename' => 'week-one.pdf',
                'content_type' => 'application/pdf',
            ),
        ));
        $counts = LessonsCartridge::summarize($l);
        $this->assertSame(1, $counts['modules']);
        $this->assertSame(1, $counts['resources']);
        $this->assertSame(1, $counts['files']);
        $this->assertSame(0, $counts['assignments']);
        $this->assertSame(0, $counts['discussions']);
        $this->assertSame(0, $counts['quizzes']);
        $this->assertSame(0, $counts['pages']);
    }

    public function testSummarizeCountsPagesSeparatelyFromResources()
    {
        $l = $this->lessonsDoc(array(
            array('type' => 'web_link', 'subtype' => 'reference', 'title' => 'Doc', 'href' => 'https://example.com/'),
            array(
                'type' => 'html_page',
                'title' => 'About',
                'page_id' => 7,
                'logical_key' => 'about',
                'href' => '/pages/about',
            ),
        ));
        $counts = LessonsCartridge::summarize($l);
        $this->assertSame(1, $counts['modules']);
        $this->assertSame(1, $counts['resources']);
        $this->assertSame(1, $counts['pages']);
        $this->assertSame(0, $counts['files']);
        $this->assertSame(0, $counts['assignments']);
        $this->assertSame(0, $counts['discussions']);
        $this->assertSame(0, $counts['quizzes']);
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
            'tsugi_lms' => 'Canvas',
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
        $this->assertSame('Course_generic.imscc', LessonsCartridge::downloadName($l, ' moodle '));
        $this->assertSame('Course_canvas.imscc', LessonsCartridge::downloadName($l, 'canvas'));
        $this->assertSame('Course_sakai.imscc', LessonsCartridge::downloadName($l, 'sakai'));
        $this->assertSame('generic', LessonsCartridge::exportFlavor(''));
        $this->assertSame('generic', LessonsCartridge::exportFlavor('Moodle'));
        $this->assertSame('canvas', LessonsCartridge::exportFlavor('Canvas'));
        $this->assertSame('sakai', LessonsCartridge::exportFlavor(' SAKAI '));
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

    public function testWriteZipEmbedsFileAsWebcontent()
    {
        $sha = str_repeat('b', 64);
        $bytes = "%PDF-1.4 dummy";
        $l = $this->lessonsDoc(array(
            array(
                'type' => 'file',
                'title' => 'Week One Reading',
                'sha256' => $sha,
                'filename' => 'week-one.pdf',
                'href' => '/files/download/'.$sha,
            ),
        ));
        $path = $this->writeCartridge($l, array(
            'load_file' => function ($item) use ($bytes) {
                $this->assertSame('Week One Reading', $item->title);
                return array(
                    'bytes' => $bytes,
                    'filename' => 'week-one.pdf',
                    'content_type' => 'application/pdf',
                );
            },
        ));
        try {
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $manifest = $zip->getFromName('imsmanifest.xml');
            $this->assertNotFalse($manifest);
            $this->assertStringContainsString('type="'.CC::WEBCONTENT_TYPE.'"', $manifest);
            $this->assertStringContainsString('href="web_resources/week-one.pdf"', $manifest);
            $this->assertStringContainsString('<title>Week One Reading</title>', $manifest);
            $this->assertSame($bytes, $zip->getFromName('web_resources/week-one.pdf'));
            $this->assertStringNotContainsString('imswl_xmlv1p2', $manifest);
            $this->assertStringNotContainsString('/files/download/', $manifest);
            $zip->close();
        } finally {
            @unlink($path);
        }
    }

    public function testWriteZipCanvasMarksFileAsAttachment()
    {
        $sha = str_repeat('c', 64);
        $l = $this->lessonsDoc(array(
            array(
                'type' => 'file',
                'title' => 'Notes',
                'sha256' => $sha,
                'filename' => 'notes.txt',
            ),
        ));
        $path = $this->writeCartridge($l, array(
            'tsugi_lms' => 'canvas',
            'load_file' => function ($item) {
                return array('bytes' => 'hello', 'filename' => 'notes.txt');
            },
        ));
        try {
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $meta = $zip->getFromName('course_settings/module_meta.xml');
            $this->assertNotFalse($meta);
            $this->assertStringContainsString('<content_type>Attachment</content_type>', $meta);
            $this->assertStringContainsString('<title>Notes</title>', $meta);
            $this->assertSame('hello', $zip->getFromName('web_resources/notes.txt'));
            $zip->close();
        } finally {
            @unlink($path);
        }
    }

    public function testWriteZipFailsWhenFileMissing()
    {
        $this->expectException(ExportException::class);
        $this->expectExceptionMessage('could not be loaded');
        $sha = str_repeat('d', 64);
        $l = $this->lessonsDoc(array(
            array(
                'type' => 'file',
                'title' => 'Missing PDF',
                'sha256' => $sha,
                'filename' => 'missing.pdf',
            ),
        ));
        $this->writeCartridge($l, array(
            'load_file' => function ($item) {
                return null;
            },
        ));
    }

    public function testWriteZipEmbedsPageAsWikiContent()
    {
        $html = '<html><head><title>About</title></head><body><p>Hello</p></body></html>';
        $l = $this->lessonsDoc(array(
            array(
                'type' => 'html_page',
                'title' => 'About',
                'page_id' => 7,
                'logical_key' => 'about',
                'href' => '/pages/about',
            ),
        ));
        $path = $this->writeCartridge($l, array(
            'load_page' => function ($item) use ($html) {
                $this->assertSame(7, (int) $item->page_id);
                return array(
                    'title' => 'About',
                    'logical_key' => 'about',
                    'html' => $html,
                );
            },
        ));
        try {
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $manifest = $zip->getFromName('imsmanifest.xml');
            $this->assertNotFalse($manifest);
            $this->assertStringContainsString('type="'.CC::WEBCONTENT_TYPE.'"', $manifest);
            $this->assertStringContainsString('href="wiki_content/about.html"', $manifest);
            $this->assertStringContainsString('<title>About</title>', $manifest);
            $this->assertMatchesRegularExpression('/identifier="(WP_[^"]+_R)" type="'.preg_quote(CC::WEBCONTENT_TYPE, '/').'" href="wiki_content\/about.html"/', $manifest);
            preg_match('/identifier="(WP_[^"]+_R)" type="'.preg_quote(CC::WEBCONTENT_TYPE, '/').'" href="wiki_content\/about.html"/', $manifest, $m);
            $pageHtml = $zip->getFromName('wiki_content/about.html');
            $this->assertNotFalse($pageHtml);
            $this->assertStringContainsString('<title>About</title>', $pageHtml);
            $this->assertStringContainsString('<p>Hello</p>', $pageHtml);
            $this->assertStringContainsString('<meta name="identifier" content="'.$m[1].'"/>', $pageHtml);
            $this->assertStringContainsString('<meta name="editing_roles" content="teachers"/>', $pageHtml);
            $this->assertStringContainsString('<meta name="workflow_state" content="active"/>', $pageHtml);
            $this->assertNotFalse($zip->getFromName('course_settings/canvas_export.txt'));
            $moduleMeta = $zip->getFromName('course_settings/module_meta.xml');
            $this->assertNotFalse($moduleMeta);
            $this->assertStringContainsString('<content_type>WikiPage</content_type>', $moduleMeta);
            $this->assertStringContainsString('<identifierref>'.$m[1].'</identifierref>', $moduleMeta);
            $this->assertStringNotContainsString('imswl_xmlv1p2', $manifest);
            $this->assertStringNotContainsString('/pages/about', $manifest);
            $zip->close();
        } finally {
            @unlink($path);
        }
    }

    public function testWriteZipCanvasMarksPageAsWikiPage()
    {
        $html = '<html><head><title>About</title></head><body><p>Hi</p></body></html>';
        $l = $this->lessonsDoc(array(
            array(
                'type' => 'html_page',
                'title' => 'About',
                'page_id' => 7,
                'logical_key' => 'about',
            ),
        ));
        $path = $this->writeCartridge($l, array(
            'tsugi_lms' => 'canvas',
            'load_page' => function ($item) use ($html) {
                return array('title' => 'About', 'logical_key' => 'about', 'html' => $html);
            },
        ));
        try {
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $meta = $zip->getFromName('course_settings/module_meta.xml');
            $this->assertNotFalse($meta);
            $this->assertStringContainsString('<content_type>WikiPage</content_type>', $meta);
            $this->assertStringContainsString('<title>About</title>', $meta);
            $pageHtml = $zip->getFromName('wiki_content/about.html');
            $this->assertNotFalse($pageHtml);
            $this->assertStringContainsString('<title>About</title>', $pageHtml);
            $this->assertStringContainsString('<p>Hi</p>', $pageHtml);
            $this->assertStringContainsString('<meta name="identifier" content="', $pageHtml);
            $zip->close();
        } finally {
            @unlink($path);
        }
    }

    public function testWriteZipFailsWhenPageMissing()
    {
        $this->expectException(ExportException::class);
        $this->expectExceptionMessage('could not be loaded');
        $l = $this->lessonsDoc(array(
            array(
                'type' => 'html_page',
                'title' => 'Missing',
                'page_id' => 99,
                'logical_key' => 'missing',
            ),
        ));
        $this->writeCartridge($l, array(
            'load_page' => function ($item) {
                return null;
            },
        ));
    }

    public function testWriteZipPageFileLinksUseWebResourcesPath()
    {
        $sha = str_repeat('e', 64);
        $html = '<html><head><title>About</title></head><body><p><a href="$IMS-CC-FILEBASE$files/download/'.$sha.'">img</a></p></body></html>';
        $l = $this->lessonsDoc(array(
            array(
                'type' => 'file',
                'title' => 'Pic',
                'sha256' => $sha,
                'filename' => 'week-one.pdf',
                'path' => 'Student/week-one.pdf',
            ),
            array(
                'type' => 'html_page',
                'title' => 'About',
                'page_id' => 7,
                'logical_key' => 'about',
            ),
        ));
        $path = $this->writeCartridge($l, array(
            'load_file' => function ($item) {
                return array(
                    'bytes' => 'PDF',
                    'filename' => 'week-one.pdf',
                    'path' => 'Student/week-one.pdf',
                );
            },
            'load_page' => function ($item) use ($html) {
                return array('title' => 'About', 'logical_key' => 'about', 'html' => $html);
            },
        ));
        try {
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $this->assertSame('PDF', $zip->getFromName('web_resources/Student/week-one.pdf'));
            $pageHtml = $zip->getFromName('wiki_content/about.html');
            $this->assertNotFalse($pageHtml);
            $this->assertStringContainsString('$IMS-CC-FILEBASE$/Student/week-one.pdf', $pageHtml);
            $this->assertStringNotContainsString('files/download/', $pageHtml);
            $this->assertStringNotContainsString('$IMS-CC-FILEBASE$web_resources/', $pageHtml);
            $zip->close();
        } finally {
            @unlink($path);
        }
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
