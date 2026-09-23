<?php

require_once "src/Core/I18N.php";
require_once "include/setup_i18n.php";
require_once "src/Services/Lessons/LessonsService.php";
require_once "src/Services/Lessons/LessonsNormalize.php";
require_once "src/Services/Lessons/LessonsCartridge.php";
require_once "src/Config/ConfigInfo.php";

use Tsugi\Services\Lessons\LessonsCartridge;
use Tsugi\Services\Quiz1\ExportException;
use Tsugi\Services\Quiz1\SampleQuiz1;
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

    public function testSummarizeCountsDiscussionItems() {
        $l = $this->lessonsDoc(array(
            array(
                'type' => 'discussion',
                'title' => 'Welcome',
                'resource_link_id' => 'd1',
            ),
        ));
        $counts = LessonsCartridge::summarize($l);
        $this->assertSame(1, $counts['discussions']);
        $this->assertSame(0, $counts['resources']);
        $this->assertSame(0, $counts['assignments']);
    }

    public function testWriteZipDiscussionDefaultsToLmsTopic() {
        $l = $this->lessonsDoc(array(
            array(
                'type' => 'discussion',
                'title' => 'Welcome',
                'resource_link_id' => 'd1',
            ),
        ));
        $path = $this->writeCartridge($l, array());
        try {
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $manifest = $zip->getFromName('imsmanifest.xml');
            $this->assertNotFalse($manifest);
            $this->assertStringContainsString('type="'.CC::TOPIC_TYPE.'"', $manifest);
            $this->assertStringContainsString('<title>Welcome</title>', $manifest);
            $this->assertStringNotContainsString('type="'.CC::LTI_TYPE.'"', $manifest);
            $this->assertStringNotContainsString('Discussion:', $manifest);
            $topicFile = null;
            for ( $i = 0; $i < $zip->numFiles; $i++ ) {
                $name = $zip->getNameIndex($i);
                if ( is_string($name) && str_starts_with($name, 'xml/TO_') && str_ends_with($name, '.xml') ) {
                    $topicFile = $name;
                    break;
                }
            }
            $this->assertNotNull($topicFile);
            $xml = $zip->getFromName($topicFile);
            $this->assertNotFalse($xml);
            $this->assertStringContainsString(CC::TOPIC_NS, $xml);
            $this->assertStringContainsString('<title>Welcome</title>', $xml);
            $zip->close();
        } finally {
            @unlink($path);
        }
    }

    public function testWriteZipDiscussionNoneOmitsTopics() {
        $l = $this->lessonsDoc(array(
            array(
                'type' => 'discussion',
                'title' => 'Welcome',
                'resource_link_id' => 'd1',
            ),
        ));
        $path = $this->writeCartridge($l, array('topic' => 'none'));
        try {
            $map = $this->zipEntryMap($path);
            $blob = implode("\n", $map);
            $this->assertStringNotContainsString('imsdt_xml', $blob);
            $this->assertStringNotContainsString('<title>Welcome</title>', $blob);
            $this->assertStringNotContainsString('type="'.CC::LTI_TYPE.'"', $blob);
        } finally {
            @unlink($path);
        }
    }

    public function testWriteZipDiscussionLtiIsLaunch() {
        $l = $this->lessonsDoc(array(
            array(
                'type' => 'discussion',
                'title' => 'Welcome',
                'resource_link_id' => 'd1',
            ),
        ));
        $path = $this->writeCartridge($l, array('topic' => 'lti'));
        try {
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $manifest = $zip->getFromName('imsmanifest.xml');
            $this->assertNotFalse($manifest);
            $this->assertStringContainsString('type="'.CC::LTI_TYPE.'"', $manifest);
            $this->assertStringContainsString('<title>Discussion: Welcome</title>', $manifest);
            $this->assertStringNotContainsString('type="'.CC::TOPIC_TYPE.'"', $manifest);
            $zip->close();
        } finally {
            @unlink($path);
        }
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

    public function testWriteZipHonorsAnchors()
    {
        $l = (object) array(
            'lessons' => (object) array(
                'title' => 'Course',
                'modules' => array(
                    (object) array(
                        'title' => 'Week 1',
                        'anchor' => 'w1',
                        'items' => array(
                            (object) array(
                                'type' => 'web_link',
                                'subtype' => 'reference',
                                'title' => 'One',
                                'href' => 'https://example.com/one',
                            ),
                        ),
                    ),
                    (object) array(
                        'title' => 'Week 2',
                        'anchor' => 'w2',
                        'items' => array(
                            (object) array(
                                'type' => 'web_link',
                                'subtype' => 'reference',
                                'title' => 'Two',
                                'href' => 'https://example.com/two',
                            ),
                        ),
                    ),
                ),
            ),
        );
        $path = $this->writeCartridge($l, array(
            'tsugi_lms' => 'canvas',
            'anchors' => array('w1'),
        ));
        try {
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $manifest = $zip->getFromName('imsmanifest.xml');
            $this->assertNotFalse($manifest);
            $this->assertStringContainsString('<title>Week 1</title>', $manifest);
            $this->assertStringContainsString('<title>One</title>', $manifest);
            $this->assertStringNotContainsString('<title>Week 2</title>', $manifest);
            $this->assertStringNotContainsString('<title>Two</title>', $manifest);
            $zip->close();
        } finally {
            @unlink($path);
        }
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

    public function testWriteZipPreservesModuleDescriptionAsItemLom() {
        $l = $this->lessonsDoc(
            array(
                array('type' => 'web_link', 'subtype' => 'reference', 'title' => 'Docs', 'href' => 'https://example.com/'),
                array('type' => 'heading', 'title' => 'Videos'),
            ),
            'Django Models',
            'This lesson introduces the Django ORM.'
        );
        $path = $this->writeCartridge($l, array('tsugi_lms' => 'generic'));
        try {
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $manifest = $zip->getFromName('imsmanifest.xml');
            $zip->close();
            $this->assertNotFalse($manifest);
            $dom = new \DOMDocument();
            $this->assertTrue($dom->loadXML($manifest));
            $moduleItem = $this->moduleItemByTitle($dom, 'Django Models');
            $this->assertSame('', $moduleItem->getAttribute('identifierref'));
            $this->assertSame('This lesson introduces the Django ORM.', CC::lomDescriptionFromItem($moduleItem));
            $names = array();
            foreach ( $moduleItem->childNodes as $child ) {
                if ( $child instanceof \DOMElement ) {
                    $names[] = $child->localName;
                }
            }
            $this->assertSame('title', $names[0]);
            $this->assertSame('metadata', $names[1]);
            $kids = array();
            foreach ( $moduleItem->childNodes as $child ) {
                if ( $child instanceof \DOMElement && $child->localName === 'item' ) {
                    $kids[] = $child;
                }
            }
            $this->assertCount(2, $kids);
            $this->assertSame('Docs', $this->itemTitle($kids[0]));
            $this->assertSame('Videos', $this->itemTitle($kids[1]));
            $this->assertStringNotContainsString('tsugi.org/xsd', $manifest);
        } finally {
            @unlink($path);
        }
    }

    public function testWriteZipOmitsItemMetadataWithoutDescription() {
        $l = $this->lessonsDoc(array(
            array('type' => 'web_link', 'subtype' => 'reference', 'title' => 'Docs', 'href' => 'https://example.com/'),
        ));
        $path = $this->writeCartridge($l, array('tsugi_lms' => 'generic'));
        try {
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $manifest = $zip->getFromName('imsmanifest.xml');
            $zip->close();
            $this->assertNotFalse($manifest);
            $dom = new \DOMDocument();
            $this->assertTrue($dom->loadXML($manifest));
            $moduleItem = $this->moduleItemByTitle($dom, 'Week 1');
            $this->assertNull(CC::lomDescriptionFromItem($moduleItem));
            foreach ( $moduleItem->childNodes as $child ) {
                if ( $child instanceof \DOMElement ) {
                    $this->assertNotSame('metadata', $child->localName);
                }
            }
        } finally {
            @unlink($path);
        }
    }

    public function testWriteZipWebLinkWindowTarget() {
        $l = $this->lessonsDoc(array(
            array(
                'type' => 'web_link',
                'subtype' => 'reference',
                'title' => 'New tab',
                'href' => 'https://example.com/a',
                'target' => '_blank',
            ),
            array(
                'type' => 'web_link',
                'subtype' => 'reference',
                'title' => 'Same page',
                'href' => 'https://example.com/b',
                'target' => '_self',
            ),
            array(
                'type' => 'web_link',
                'subtype' => 'reference',
                'title' => 'Modal',
                'href' => 'https://example.com/c',
                'target' => 'modal',
            ),
            array(
                'type' => 'web_link',
                'subtype' => 'reference',
                'title' => 'Ordinary',
                'href' => 'https://example.com/d',
            ),
        ));
        $path = $this->writeCartridge($l, array('tsugi_lms' => 'generic'));
        try {
            $map = $this->zipEntryMap($path);
            $this->assertStringContainsString('windowTarget="_blank"', $this->webLinkXmlByTitle($map, 'New tab'));
            $this->assertStringContainsString('windowTarget="_self"', $this->webLinkXmlByTitle($map, 'Same page'));
            $this->assertStringContainsString('windowTarget="modal"', $this->webLinkXmlByTitle($map, 'Modal'));
            $ordinary = $this->webLinkXmlByTitle($map, 'Ordinary');
            $this->assertStringContainsString('href="https://example.com/d"', $ordinary);
            $this->assertStringNotContainsString('windowTarget', $ordinary);
            $dom = new \DOMDocument();
            $this->assertTrue($dom->loadXML($map['imsmanifest.xml']));
            $this->assertSame(
                'window',
                CC::lomIdentifiersFromItem($this->itemByTitle($dom, 'New tab'))[CC::LOM_CATALOG_DOCUMENT_TARGET]
            );
            $this->assertSame(
                'iframe',
                CC::lomIdentifiersFromItem($this->itemByTitle($dom, 'Same page'))[CC::LOM_CATALOG_DOCUMENT_TARGET]
            );
            $this->assertSame(
                'modal',
                CC::lomIdentifiersFromItem($this->itemByTitle($dom, 'Modal'))[CC::LOM_CATALOG_DOCUMENT_TARGET]
            );
            $this->assertArrayNotHasKey(
                CC::LOM_CATALOG_DOCUMENT_TARGET,
                CC::lomIdentifiersFromItem($this->itemByTitle($dom, 'Ordinary'))
            );
        } finally {
            @unlink($path);
        }
    }

    public function testWriteZipCanvasNewTabFollowsWindowTarget() {
        $l = $this->lessonsDoc(array(
            array(
                'type' => 'web_link',
                'subtype' => 'reference',
                'title' => 'PythonAnywhere',
                'href' => 'https://www.pythonanywhere.com/',
                'target' => '_blank',
            ),
            array(
                'type' => 'web_link',
                'subtype' => 'reference',
                'title' => 'Inline docs',
                'href' => 'https://example.com/docs',
                'target' => '_self',
            ),
        ));
        $path = $this->writeCartridge($l, array('tsugi_lms' => 'canvas'));
        try {
            $map = $this->zipEntryMap($path);
            $meta = $map['course_settings/module_meta.xml'];
            $this->assertMatchesRegularExpression(
                '/<item identifier="WL_[^"]+">[\s\S]*?<new_tab>true<\/new_tab>[\s\S]*?<title>PythonAnywhere<\/title>[\s\S]*?<\/item>/',
                $meta
            );
            $this->assertMatchesRegularExpression(
                '/<item identifier="WL_[^"]+">[\s\S]*?<new_tab>false<\/new_tab>[\s\S]*?<title>Inline docs<\/title>[\s\S]*?<\/item>/',
                $meta
            );
        } finally {
            @unlink($path);
        }
    }

    public function testWriteZipItemDescriptionAsLomOnWebLink() {
        $l = $this->lessonsDoc(array(
            array(
                'type' => 'web_link',
                'subtype' => 'reference',
                'title' => 'PythonAnywhere',
                'href' => 'https://www.pythonanywhere.com/',
                'description' => 'Host for the Django tutorial.',
            ),
        ));
        $path = $this->writeCartridge($l, array('tsugi_lms' => 'generic'));
        try {
            $map = $this->zipEntryMap($path);
            $dom = new \DOMDocument();
            $this->assertTrue($dom->loadXML($map['imsmanifest.xml']));
            $item = $this->itemByTitle($dom, 'PythonAnywhere');
            $this->assertSame('Host for the Django tutorial.', CC::lomDescriptionFromItem($item));
        } finally {
            @unlink($path);
        }
    }

    public function testWriteZipPreservesIconAndHrefSourceAsLomIdentifiers() {
        $l = $this->lessonsDoc(array(
            array(
                'type' => 'web_link',
                'subtype' => 'reference',
                'title' => 'PythonAnywhere',
                'href' => 'https://www.pythonanywhere.com/',
                'href_source' => 'course',
                'icon' => 'fa-globe',
            ),
        ), 'Installing Django on PythonAnywhere');
        $l->lessons->modules[0]->icon = 'fa-rocket';
        $path = $this->writeCartridge($l, array('tsugi_lms' => 'generic'));
        try {
            $map = $this->zipEntryMap($path);
            $dom = new \DOMDocument();
            $this->assertTrue($dom->loadXML($map['imsmanifest.xml']));
            $moduleItem = $this->moduleItemByTitle($dom, 'Installing Django on PythonAnywhere');
            $this->assertSame('fa-rocket', CC::lomIdentifiersFromItem($moduleItem)[CC::LOM_CATALOG_ICON]);
            $link = $this->itemByTitle($dom, 'PythonAnywhere');
            $ids = CC::lomIdentifiersFromItem($link);
            $this->assertSame('fa-globe', $ids[CC::LOM_CATALOG_ICON]);
            $this->assertSame('course', $ids[CC::LOM_CATALOG_HREF_SOURCE]);
        } finally {
            @unlink($path);
        }
    }

    public function testWriteZipGeneric11DoesNotEmitItemLomDescription() {
        $l = $this->lessonsDoc(
            array(
                array('type' => 'web_link', 'subtype' => 'reference', 'title' => 'Docs', 'href' => 'https://example.com/'),
            ),
            'Django Models',
            'This lesson introduces the Django ORM.'
        );
        $path = $this->writeCartridge($l, array('tsugi_lms' => 'generic11'));
        try {
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $manifest = $zip->getFromName('imsmanifest.xml');
            $zip->close();
            $this->assertNotFalse($manifest);
            $this->assertStringContainsString('<schemaversion>1.1.0</schemaversion>', $manifest);
            $dom = new \DOMDocument();
            $this->assertTrue($dom->loadXML($manifest));
            $moduleItem = $this->moduleItemByTitle($dom, 'Django Models');
            $this->assertNull(CC::lomDescriptionFromItem($moduleItem));
            foreach ( $moduleItem->childNodes as $child ) {
                if ( $child instanceof \DOMElement ) {
                    $this->assertNotSame('metadata', $child->localName);
                }
            }
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
            $welcome = $this->webLinkXmlByTitle($map, 'Video: DJ 01.01 Welcome');
            $this->assertStringContainsString('windowTarget="modal"', $welcome);
            $ai = $this->webLinkXmlByTitle($map, 'Video: DJ 01.02 AI');
            $this->assertStringContainsString('windowTarget="modal"', $ai);
            $dom = new \DOMDocument();
            $this->assertTrue($dom->loadXML($map['imsmanifest.xml']));
            $this->assertSame(
                'modal',
                CC::lomIdentifiersFromItem($this->itemByTitle($dom, 'Video: DJ 01.01 Welcome'))[CC::LOM_CATALOG_DOCUMENT_TARGET]
            );
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
                return SampleQuiz1::build($id);
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

    public function testWriteZipMoodleIsGenericCc11() {
        $l = $this->lessonsDoc(array(
            array('type' => 'quiz', 'title' => 'Week 1 Quiz', 'quiz_id' => 1),
            array('type' => 'web_link', 'subtype' => 'reference', 'title' => 'Doc', 'href' => 'https://example.com/'),
        ));
        $path = $this->writeCartridge($l, array(
            'tsugi_lms' => 'moodle',
            'load_quiz' => function ($id) {
                return SampleQuiz1::build($id);
            },
        ));
        try {
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $manifest = $zip->getFromName('imsmanifest.xml');
            $this->assertNotFalse($manifest);
            $this->assertStringContainsString('<schemaversion>1.1.0</schemaversion>', $manifest);
            $this->assertStringContainsString(CC::CC_11_NS, $manifest);
            $this->assertStringContainsString('type="'.CC::QTI_ASSESSMENT_TYPE_11.'"', $manifest);
            $this->assertStringContainsString('type="'.CC::WEB_LINK_TYPE_11.'"', $manifest);
            $this->assertStringNotContainsString('imsccv1p2', $manifest);
            $this->assertFalse($zip->getFromName('course_settings/module_meta.xml'));
            $this->assertFalse($zip->getFromName('course_settings/canvas_export.txt'));
            $this->assertStringNotContainsString('canvas.instructure.com', $manifest);

            $qtiFile = null;
            $wlFile = null;
            for ( $i = 0; $i < $zip->numFiles; $i++ ) {
                $name = $zip->getNameIndex($i);
                if ( ! is_string($name) ) {
                    continue;
                }
                if ( str_starts_with($name, 'xml/Q1_') && str_ends_with($name, '.xml') ) {
                    $qtiFile = $name;
                }
                if ( str_starts_with($name, 'xml/WL_') && str_ends_with($name, '.xml') ) {
                    $wlFile = $name;
                }
            }
            $this->assertNotNull($qtiFile, 'Moodle cartridge should contain a Q1_ QTI XML file');
            $xml = $zip->getFromName($qtiFile);
            $this->assertNotFalse($xml);
            $this->assertStringContainsString(CC::QTI_SCHEMA_LOCATION_11, $xml);
            $this->assertStringNotContainsString(CC::QTI_SCHEMA_LOCATION, $xml);
            $this->assertNotNull($wlFile, 'Moodle cartridge should contain a web link XML file');
            $wl = $zip->getFromName($wlFile);
            $this->assertNotFalse($wl);
            $this->assertStringContainsString(CC::WL_11_NS, $wl);
            $this->assertStringNotContainsString('imsccv1p2', $wl);
            $zip->close();
        } finally {
            @unlink($path);
        }
    }

    public function testWriteZipGeneric11MatchesMoodle() {
        $l = $this->lessonsDoc(array(
            array('type' => 'quiz', 'title' => 'Week 1 Quiz', 'quiz_id' => 1),
            array('type' => 'web_link', 'subtype' => 'reference', 'title' => 'Doc', 'href' => 'https://example.com/'),
        ));
        $options = array(
            'load_quiz' => function ($id) {
                return SampleQuiz1::build($id);
            },
        );
        $moodlePath = $this->writeCartridge($l, $options + array('tsugi_lms' => 'moodle'));
        $generic11Path = $this->writeCartridge($l, $options + array('tsugi_lms' => 'generic11'));
        try {
            $this->assertSame(
                $this->zipEntryMap($moodlePath),
                $this->zipEntryMap($generic11Path)
            );
        } finally {
            @unlink($moodlePath);
            @unlink($generic11Path);
        }
    }

    public function testWriteZipCanvasKeepsModuleMeta() {
        $l = $this->lessonsDoc(array(
            array('type' => 'quiz', 'title' => 'Week 1 Quiz', 'quiz_id' => 1),
        ));
        $path = $this->writeCartridge($l, array(
            'tsugi_lms' => 'Canvas',
            'load_quiz' => function ($id) {
                return SampleQuiz1::build($id);
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
            $this->assertStringContainsString('non_cc_assessments/', $manifest);
            $this->assertStringContainsString('.xml.qti', $manifest);
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
                return SampleQuiz1::build($id);
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
                return SampleQuiz1::build($id);
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
        $this->assertSame('Course_moodle.imscc', LessonsCartridge::downloadName($l, ' moodle '));
        $this->assertSame('Course_generic11.imscc', LessonsCartridge::downloadName($l, 'generic11'));
        $this->assertSame('Course_canvas.imscc', LessonsCartridge::downloadName($l, 'canvas'));
        $this->assertSame('Course_tsugi.imscc', LessonsCartridge::downloadName($l, 'tsugi'));
        $this->assertSame('Course_sakai.imscc', LessonsCartridge::downloadName($l, 'sakai'));
        $this->assertSame('generic', LessonsCartridge::exportFlavor(''));
        $this->assertSame('generic11', LessonsCartridge::exportFlavor('Generic11'));
        $this->assertSame('moodle', LessonsCartridge::exportFlavor('Moodle'));
        $this->assertSame('canvas', LessonsCartridge::exportFlavor('Canvas'));
        $this->assertSame('tsugi', LessonsCartridge::exportFlavor(' Tsugi '));
        $this->assertSame('sakai', LessonsCartridge::exportFlavor(' SAKAI '));
        $this->assertSame('Generic (CC 1.1)', LessonsCartridge::exportFlavorLabels()['generic11']);
        $this->assertSame('Moodle (CC 1.1)', LessonsCartridge::exportFlavorLabels()['moodle']);
        $this->assertSame('Canvas (CC 1.2)', LessonsCartridge::exportFlavorLabels()['canvas']);
        $this->assertTrue(LessonsCartridge::usesCanvasCartridge('canvas'));
        $this->assertTrue(LessonsCartridge::usesCanvasCartridge('tsugi'));
        $this->assertFalse(LessonsCartridge::usesCanvasCartridge('generic'));
        $this->assertFalse(LessonsCartridge::usesCanvasCartridge('sakai'));
        $this->assertFalse(LessonsCartridge::usesCanvasCartridge('moodle'));
        $this->assertFalse(LessonsCartridge::usesCanvasCartridge('generic11'));
        $this->assertFalse(LessonsCartridge::wantsCanvasExtensions('moodle'));
        $this->assertFalse(LessonsCartridge::wantsCanvasExtensions('generic'));
        $this->assertFalse(LessonsCartridge::wantsCanvasExtensions('generic11'));
        $this->assertSame('lms', LessonsCartridge::exportTopicMode(false));
        $this->assertSame('lms', LessonsCartridge::exportTopicMode(''));
        $this->assertSame('lms', LessonsCartridge::exportTopicMode(' LMS '));
        $this->assertSame('none', LessonsCartridge::exportTopicMode('none'));
        $this->assertSame('lti', LessonsCartridge::exportTopicMode('lti'));
        $this->assertSame('lti_grade', LessonsCartridge::exportTopicMode('lti_grade'));
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
                return SampleQuiz1::build($id);
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
                return SampleQuiz1::build($id);
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
     * @param string $title
     * @param string|null $description
     * @return object
     */
    private function lessonsDoc(array $items, $title = 'Week 1', $description = null) {
        $module = array(
            'title' => $title,
            'anchor' => 'w1',
            'items' => $items,
        );
        if ( $description !== null ) {
            $module['description'] = $description;
        }
        return (object) array(
            'lessons' => (object) array(
                'title' => 'Course',
                'modules' => array(
                    (object) $module,
                ),
            ),
        );
    }

    /**
     * @return \DOMElement
     */
    private function moduleItemByTitle(\DOMDocument $dom, $title) {
        foreach ( $dom->getElementsByTagName('*') as $el ) {
            if ( ! $el instanceof \DOMElement || $el->localName !== 'item' ) {
                continue;
            }
            if ( $el->getAttribute('identifierref') !== '' ) {
                continue;
            }
            if ( $this->itemTitle($el) === $title ) {
                return $el;
            }
        }
        $this->fail('Missing organization item titled '.$title);
    }

    /**
     * @return \DOMElement
     */
    private function itemByTitle(\DOMDocument $dom, $title) {
        foreach ( $dom->getElementsByTagName('*') as $el ) {
            if ( ! $el instanceof \DOMElement || $el->localName !== 'item' ) {
                continue;
            }
            if ( $this->itemTitle($el) === $title ) {
                return $el;
            }
        }
        $this->fail('Missing organization item titled '.$title);
    }

    /**
     * @param array<string, string> $map
     * @param string $title
     * @return string
     */
    private function webLinkXmlByTitle(array $map, $title) {
        $this->assertArrayHasKey('imsmanifest.xml', $map);
        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($map['imsmanifest.xml']));
        $item = $this->itemByTitle($dom, $title);
        $ref = $item->getAttribute('identifierref');
        $this->assertNotSame('', $ref);
        $href = '';
        foreach ( $dom->getElementsByTagName('*') as $el ) {
            if ( ! $el instanceof \DOMElement || $el->localName !== 'resource' ) {
                continue;
            }
            if ( $el->getAttribute('identifier') !== $ref ) {
                continue;
            }
            foreach ( $el->childNodes as $child ) {
                if ( $child instanceof \DOMElement && $child->localName === 'file' ) {
                    $href = $child->getAttribute('href');
                    break 2;
                }
            }
        }
        $this->assertNotSame('', $href);
        $this->assertArrayHasKey($href, $map);
        return $map[$href];
    }

    /**
     * @return string
     */
    private function itemTitle(\DOMElement $item) {
        foreach ( $item->childNodes as $child ) {
            if ( $child instanceof \DOMElement && $child->localName === 'title' ) {
                return $child->textContent;
            }
        }
        return '';
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
