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

    public function testWriteZipCanvasIncludesHeadings()
    {
        $l = $this->lessonsDoc(array(
            array('type' => 'heading', 'title' => 'Start here'),
            array('type' => 'header', 'text' => 'Videos'),
            array('type' => 'web_link', 'subtype' => 'reference', 'title' => 'Docs', 'href' => 'https://example.com/'),
        ));
        $path = $this->writeCartridge($l, array('tsugi_lms' => 'canvas'));
        try {
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $manifest = $zip->getFromName('imsmanifest.xml');
            $this->assertNotFalse($manifest);
            $this->assertMatchesRegularExpression(
                '/<item identifier="H_[^"]+">\s*<title>Start here<\/title>\s*<\/item>/',
                $manifest
            );
            $this->assertMatchesRegularExpression(
                '/<item identifier="H_[^"]+">\s*<title>Videos<\/title>\s*<\/item>/',
                $manifest
            );
            $meta = $zip->getFromName('course_settings/module_meta.xml');
            $this->assertNotFalse($meta);
            $this->assertSame(2, substr_count($meta, '<content_type>ContextModuleSubHeader</content_type>'));
            $this->assertStringContainsString('<title>Start here</title>', $meta);
            $this->assertStringContainsString('<title>Videos</title>', $meta);
            $zip->close();
        } finally {
            @unlink($path);
        }
    }

    public function testAddHeadingItemIgnoresNonHeadings() {
        $cc_dom = new CC();
        $cc_dom->set_title('Course');
        $module = $cc_dom->add_module('Week 1');
        $this->assertFalse(LessonsCartridge::addHeadingItem(
            $cc_dom,
            $module,
            (object) array('type' => 'web_link', 'title' => 'Docs', 'href' => 'https://example.com/')
        ));
        $this->assertTrue(LessonsCartridge::addHeadingItem(
            $cc_dom,
            $module,
            (object) array('type' => 'heading', 'title' => 'Start here')
        ));
        $this->assertTrue(LessonsCartridge::addHeadingItem(
            $cc_dom,
            $module,
            (object) array('type' => 'header', 'text' => 'Videos')
        ));
        $save = $cc_dom->saveXML();
        $this->assertStringContainsString('<title>Start here</title>', $save);
        $this->assertStringContainsString('<title>Videos</title>', $save);
    }

    public function testWriteZipPrefersKalturaOverYoutubeHref() {
        global $CFG;
        $CFG->setExtension(
            'kaltura_embed',
            'https://cdnapisec.kaltura.com/p/1038472/embedPlaykitJs/uiconf_id/58045402?iframeembed=true&entry_id={id}'
        );
        $l = $this->lessonsDoc(array(
            array(
                'type' => 'web_link',
                'subtype' => 'video',
                'title' => 'DJ 01.01 Welcome',
                'youtube' => 'oxJQB4f2MMs',
                'kaltura_id' => '1_rivimz4s',
                'href' => 'https://www.youtube.com/watch?v=oxJQB4f2MMs',
            ),
            array(
                'type' => 'video',
                'title' => 'DJ 01.02 AI',
                'youtube' => '1u-gQ-d5Lv8',
                'kaltura_id' => '1_xf35oqja',
            ),
        ));
        $path = $this->writeCartridge($l, array('tsugi_lms' => 'canvas'));
        try {
            $map = $this->zipEntryMap($path);
            $blob = implode("\n", $map);
            $this->assertStringContainsString('entry_id=1_rivimz4s', $blob);
            $this->assertStringContainsString('entry_id=1_xf35oqja', $blob);
            $this->assertStringNotContainsString('youtube.com/watch', $blob);
            $this->assertStringNotContainsString('oxJQB4f2MMs', $blob);
            $this->assertArrayHasKey('course_settings/module_meta.xml', $map);
            $this->assertStringContainsString('<new_tab>false</new_tab>', $map['course_settings/module_meta.xml']);
            $this->assertStringContainsString('cdnapisec.kaltura.com', $map['course_settings/module_meta.xml']);
        } finally {
            @unlink($path);
        }
    }

    public function testWriteZipVideoFallsBackToYoutubeWithoutKalturaConfig() {
        $l = $this->lessonsDoc(array(
            array(
                'type' => 'video',
                'title' => 'Welcome',
                'youtube' => 'oxJQB4f2MMs',
                'kaltura_id' => '1_rivimz4s',
            ),
        ));
        $path = $this->writeCartridge($l, array());
        try {
            $map = $this->zipEntryMap($path);
            $blob = implode("\n", $map);
            $this->assertStringContainsString('youtube.com/watch?v=oxJQB4f2MMs', $blob);
            $this->assertStringNotContainsString('kaltura.com', $blob);
            $this->assertStringNotContainsString('1_rivimz4s', $blob);
        } finally {
            @unlink($path);
        }
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

    public function testWriteZipTsugiCartridgeMatchesCanvas()
    {
        $html = '<html><head><title>About</title></head><body><p>Hi</p></body></html>';
        $l = $this->lessonsDoc(array(
            array('type' => 'quiz', 'title' => 'Week 1 Quiz', 'quiz_id' => 1),
            array(
                'type' => 'html_page',
                'title' => 'About',
                'page_id' => 7,
                'logical_key' => 'about',
            ),
        ));
        $options = array(
            'load_quiz' => function ($id) {
                return SampleQuiz::build($id);
            },
            'load_page' => function ($item) use ($html) {
                return array('title' => 'About', 'logical_key' => 'about', 'html' => $html);
            },
        );
        $canvasPath = $this->writeCartridge($l, $options + array('tsugi_lms' => 'canvas'));
        $tsugiPath = $this->writeCartridge($l, $options + array('tsugi_lms' => 'tsugi'));
        try {
            $this->assertSame(
                $this->zipEntryMap($canvasPath),
                $this->zipEntryMap($tsugiPath)
            );
        } finally {
            @unlink($canvasPath);
            @unlink($tsugiPath);
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
        $this->assertSame('Course_tsugi.imscc', LessonsCartridge::downloadName($l, 'tsugi'));
        $this->assertSame('Course_sakai.imscc', LessonsCartridge::downloadName($l, 'sakai'));
        $this->assertSame('generic', LessonsCartridge::exportFlavor(''));
        $this->assertSame('generic', LessonsCartridge::exportFlavor('Moodle'));
        $this->assertSame('canvas', LessonsCartridge::exportFlavor('Canvas'));
        $this->assertSame('tsugi', LessonsCartridge::exportFlavor(' Tsugi '));
        $this->assertSame('sakai', LessonsCartridge::exportFlavor(' SAKAI '));
        $this->assertTrue(LessonsCartridge::usesCanvasCartridge('canvas'));
        $this->assertTrue(LessonsCartridge::usesCanvasCartridge('tsugi'));
        $this->assertFalse(LessonsCartridge::usesCanvasCartridge('generic'));
        $this->assertFalse(LessonsCartridge::usesCanvasCartridge('sakai'));
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

    public function testWriteZipGenericEmbedsPageAsWebResource()
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
            $this->assertStringContainsString('href="web_resources/pages/about.html"', $manifest);
            $this->assertStringContainsString('<title>About</title>', $manifest);
            $this->assertStringNotContainsString('wiki_content/', $manifest);
            $this->assertFalse($zip->getFromName('wiki_content/about.html'));
            $this->assertFalse($zip->getFromName('course_settings/canvas_export.txt'));
            $this->assertFalse($zip->getFromName('course_settings/module_meta.xml'));
            $pageHtml = $zip->getFromName('web_resources/pages/about.html');
            $this->assertNotFalse($pageHtml);
            $this->assertStringContainsString('<title>About</title>', $pageHtml);
            $this->assertStringContainsString('<p>Hello</p>', $pageHtml);
            $this->assertStringNotContainsString('name="identifier"', $pageHtml);
            $this->assertStringNotContainsString('editing_roles', $pageHtml);
            $this->assertStringNotContainsString('imswl_xmlv1p2', $manifest);
            $this->assertStringNotContainsString('href="/pages/about"', $manifest);
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
            $this->assertNotFalse($zip->getFromName('course_settings/canvas_export.txt'));
            $zip->close();
        } finally {
            @unlink($path);
        }
    }

    public function testWriteZipTwoListingsOfSameFileShareOneResource()
    {
        $sha = str_repeat('c', 64);
        $l = $this->lessonsDoc(array(
            array(
                'type' => 'file',
                'title' => 'Reading',
                'sha256' => $sha,
                'filename' => 'week-one.pdf',
                'path' => 'Student/week-one.pdf',
            ),
            array(
                'type' => 'file',
                'title' => 'Same PDF later',
                'sha256' => $sha,
                'filename' => 'week-one.pdf',
                'path' => 'Student/week-one.pdf',
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
        ));
        try {
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $this->assertSame('PDF', $zip->getFromName('web_resources/Student/week-one.pdf'));
            $manifest = $zip->getFromName('imsmanifest.xml');
            $this->assertNotFalse($manifest);
            $this->assertSame(2, substr_count($manifest, 'href="web_resources/Student/week-one.pdf"'));
            $this->assertStringContainsString('<title>Reading</title>', $manifest);
            $this->assertStringContainsString('<title>Same PDF later</title>', $manifest);
            preg_match_all('/<item identifier="(F_[^"]+)" identifierref="(F_[^"]+_R)"/', $manifest, $items);
            $this->assertCount(2, $items[1]);
            $this->assertNotSame($items[1][0], $items[1][1]);
            $this->assertSame($items[2][0], $items[2][1]);
            $this->assertSame(1, substr_count($manifest, 'identifier="'.$items[2][0].'" type="'.CC::WEBCONTENT_TYPE.'"'));
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

    public function testWriteZipGenericPageFileLinksKeepWebResourcesInFileBase()
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
            $pageHtml = $zip->getFromName('web_resources/pages/about.html');
            $this->assertNotFalse($pageHtml);
            $this->assertStringContainsString('$IMS-CC-FILEBASE$/web_resources/Student/week-one.pdf', $pageHtml);
            $this->assertStringNotContainsString('files/download/', $pageHtml);
            $this->assertFalse($zip->getFromName('wiki_content/about.html'));
            $zip->close();
        } finally {
            @unlink($path);
        }
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
            'tsugi_lms' => 'canvas',
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

    public function testWriteZipPageFileLinksKeepSpacesAndCommasForCanvas()
    {
        $sha = str_repeat('f', 64);
        $name = 'ChatGPT Image Sep 15, 2026, 11_59_21 AM.png';
        $pathName = 'Student/'.$name;
        $encoded = '$IMS-CC-FILEBASE$files/'.rawurlencode('Student').'/'.rawurlencode($name);
        $html = '<html><head><title>About</title></head><body>'
            .'<p><img src="'.$encoded.'">'
            .'<a href="https://local.dj4e.com/courses/12/files/'.rawurlencode('Student').'/'.rawurlencode($name).'">pic</a></p>'
            .'</body></html>';
        $l = $this->lessonsDoc(array(
            array(
                'type' => 'file',
                'title' => 'Pic',
                'sha256' => $sha,
                'filename' => $name,
                'path' => $pathName,
            ),
            array(
                'type' => 'html_page',
                'title' => 'About',
                'page_id' => 7,
                'logical_key' => 'about',
            ),
        ));
        $path = $this->writeCartridge($l, array(
            'tsugi_lms' => 'canvas',
            'load_file' => function ($item) use ($name, $pathName) {
                return array(
                    'bytes' => 'PNG',
                    'filename' => $name,
                    'path' => $pathName,
                );
            },
            'load_page' => function ($item) use ($html) {
                return array('title' => 'About', 'logical_key' => 'about', 'html' => $html);
            },
        ));
        try {
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $this->assertSame('PNG', $zip->getFromName('web_resources/'.$pathName));
            $pageHtml = $zip->getFromName('wiki_content/about.html');
            $this->assertNotFalse($pageHtml);
            $this->assertStringContainsString('$IMS-CC-FILEBASE$/'.$pathName, $pageHtml);
            $this->assertStringNotContainsString('%20', $pageHtml);
            $this->assertStringNotContainsString('%2C', $pageHtml);
            $this->assertStringNotContainsString('$IMS-CC-FILEBASE$files/', $pageHtml);
            $zip->close();
        } finally {
            @unlink($path);
        }
    }

    public function testWriteZipPageToPageLinksUseWikiReference()
    {
        $about = '<html><head><title>About</title></head><body>'
            .'<p><a href="$IMS-CC-FILEBASE$pages/one">One</a>'
            .'<a href="https://local.dj4e.com/courses/12/pages/one">One again</a></p>'
            .'</body></html>';
        $one = '<html><head><title>One</title></head><body><p>Hi</p></body></html>';
        $l = $this->lessonsDoc(array(
            array(
                'type' => 'html_page',
                'title' => 'About',
                'page_id' => 7,
                'logical_key' => 'about',
            ),
            array(
                'type' => 'html_page',
                'title' => 'One',
                'page_id' => 8,
                'logical_key' => 'one',
            ),
        ));
        $path = $this->writeCartridge($l, array(
            'tsugi_lms' => 'canvas',
            'load_page' => function ($item) use ($about, $one) {
                $key = isset($item->logical_key) ? $item->logical_key : '';
                if ( $key === 'one' ) {
                    return array('title' => 'One', 'logical_key' => 'one', 'html' => $one);
                }
                return array('title' => 'About', 'logical_key' => 'about', 'html' => $about);
            },
        ));
        try {
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $ref = \Tsugi\Util\CCIdentifier::wikiMigrationId('one');
            $aboutHtml = $zip->getFromName('wiki_content/about.html');
            $oneHtml = $zip->getFromName('wiki_content/one.html');
            $this->assertNotFalse($aboutHtml);
            $this->assertNotFalse($oneHtml);
            $this->assertStringContainsString('$WIKI_REFERENCE$/pages/'.$ref, $aboutHtml);
            $this->assertStringNotContainsString('$IMS-CC-FILEBASE$pages/', $aboutHtml);
            $this->assertStringContainsString('<meta name="identifier" content="'.$ref.'"/>', $oneHtml);
            $zip->close();
        } finally {
            @unlink($path);
        }
    }

    public function testWriteZipGenericPageToPageLinksUseFileBase()
    {
        $about = '<html><head><title>About</title></head><body>'
            .'<p><a href="$IMS-CC-FILEBASE$pages/one">One</a>'
            .'<a href="https://local.dj4e.com/courses/12/pages/one">One again</a></p>'
            .'</body></html>';
        $one = '<html><head><title>One</title></head><body><p>Hi</p></body></html>';
        $l = $this->lessonsDoc(array(
            array(
                'type' => 'html_page',
                'title' => 'About',
                'page_id' => 7,
                'logical_key' => 'about',
            ),
            array(
                'type' => 'html_page',
                'title' => 'One',
                'page_id' => 8,
                'logical_key' => 'one',
            ),
        ));
        $path = $this->writeCartridge($l, array(
            'load_page' => function ($item) use ($about, $one) {
                $key = isset($item->logical_key) ? $item->logical_key : '';
                if ( $key === 'one' ) {
                    return array('title' => 'One', 'logical_key' => 'one', 'html' => $one);
                }
                return array('title' => 'About', 'logical_key' => 'about', 'html' => $about);
            },
        ));
        try {
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $aboutHtml = $zip->getFromName('web_resources/pages/about.html');
            $oneHtml = $zip->getFromName('web_resources/pages/one.html');
            $this->assertNotFalse($aboutHtml);
            $this->assertNotFalse($oneHtml);
            $this->assertStringContainsString('$IMS-CC-FILEBASE$/web_resources/pages/one.html', $aboutHtml);
            $this->assertStringNotContainsString('$WIKI_REFERENCE$', $aboutHtml);
            $this->assertFalse($zip->getFromName('wiki_content/about.html'));
            $this->assertFalse($zip->getFromName('course_settings/canvas_export.txt'));
            $zip->close();
        } finally {
            @unlink($path);
        }
    }

    /**
     * @param string $path
     * @return array<string, string>
     */
    private function zipEntryMap($path) {
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $map = array();
        for ( $i = 0; $i < $zip->numFiles; $i++ ) {
            $name = $zip->getNameIndex($i);
            if ( ! is_string($name) ) {
                continue;
            }
            $map[$name] = (string) $zip->getFromName($name);
        }
        $zip->close();
        ksort($map);
        return $map;
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
