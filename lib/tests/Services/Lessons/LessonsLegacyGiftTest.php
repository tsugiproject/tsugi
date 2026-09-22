<?php

require_once "src/Util/TsugiDOM.php";
require_once "src/Util/CC.php";
require_once "src/Util/CanvasModuleMeta.php";
require_once "src/Util/CanvasAssessmentMeta.php";
require_once "src/Services/Lessons/LessonsLegacyGift.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/Services/Lessons/LessonsNormalize.php";
require_once "src/Services/Lessons/LessonsService.php";
require_once "src/Util/U.php";

use Tsugi\Services\Lessons\LessonsLegacyGift;
use Tsugi\Util\CC;

class LessonsLegacyGiftTest extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;
    /** @var string */
    private $quizRoot;
    /** @var string */
    private $dirroot;

    protected function setUp(): void
    {
        global $CFG;
        $this->originalCFG = $CFG;
        $this->dirroot = sys_get_temp_dir().'/tsugi-legacy-gift-'.uniqid('', true);
        $this->quizRoot = $this->dirroot.'/quizzes';
        mkdir($this->quizRoot, 0777, true);
        file_put_contents($this->quizRoot.'/.lock', 'secret-password');
        file_put_contents($this->quizRoot.'/DJ-TUT1.txt', "::Q1:: HTTP is a protocol.{T}\n");
        file_put_contents($this->quizRoot.'/numerical.txt', "When was U of M founded?{#1817}\n");
        file_put_contents($this->quizRoot.'/.hidden.txt', "::H:: Hidden.{T}\n");
        mkdir($this->quizRoot.'/nested', 0777, true);
        file_put_contents($this->quizRoot.'/nested/skip.txt', "::S:: Nested.{T}\n");
        file_put_contents($this->quizRoot.'/mixed.txt', "::OK:: HTTP is a protocol.{T}\n\nWhen was U of M founded?{#1817}\n");

        $CFG = new \Tsugi\Config\ConfigInfo($this->dirroot, 'https://www.example.com/tsugi');
        $CFG->apphome = 'https://www.example.com';
        $CFG->giftquizzes = $this->quizRoot;
    }

    protected function tearDown(): void
    {
        global $CFG;
        $CFG = $this->originalCFG;
        $this->rmTree($this->dirroot);
    }

    public function testIsGiftLaunchDetectsOldModAndNewTool() {
        $this->assertTrue(LessonsLegacyGift::isGiftLaunch('mod/gift/'));
        $this->assertTrue(LessonsLegacyGift::isGiftLaunch('mod/gift/?quiz=DJ-TUT1.txt'));
        $this->assertTrue(LessonsLegacyGift::isGiftLaunch('mod/gift/index.php'));
        $this->assertTrue(LessonsLegacyGift::isGiftLaunch('tsugi/tool/gift/?quiz=DJ-TUT1.txt'));
        $this->assertTrue(LessonsLegacyGift::isGiftLaunch('https://www.example.com/tsugi/tool/gift/?quiz=DJ-TUT1.txt'));
        $this->assertTrue(LessonsLegacyGift::isGiftLaunch('https://www.example.com/tsugi/tool/gift'));
        $this->assertTrue(LessonsLegacyGift::isGiftLaunch('http://localhost:8888/tsugi/tool/gift/?quiz=00-Shell.txt'));
        $this->assertTrue(LessonsLegacyGift::isGiftLaunch('tool/gift/?quiz=x'));
    }

    public function testIsGiftLaunchRejectsOtherTools() {
        $this->assertFalse(LessonsLegacyGift::isGiftLaunch('tsugi/tool/tdiscus/'));
        $this->assertFalse(LessonsLegacyGift::isGiftLaunch('mod/pythonauto/?exercise=1'));
        $this->assertFalse(LessonsLegacyGift::isGiftLaunch('https://www.example.com/tools/dj-tutorial/'));
        $this->assertFalse(LessonsLegacyGift::isGiftLaunch('tool/giftcard/?quiz=x'));
        $this->assertFalse(LessonsLegacyGift::isGiftLaunch(''));
        $this->assertFalse(LessonsLegacyGift::isGiftLaunch(null));
    }

    public function testQuizNameFromLaunchQueryAndCustom() {
        $from_query = (object) array(
            'launch' => 'tsugi/tool/gift/?quiz=DJ-TUT1.txt',
            'custom' => array((object) array('key' => 'quiz', 'value' => 'other.txt')),
        );
        $this->assertSame('DJ-TUT1.txt', LessonsLegacyGift::quizNameFromItem($from_query));

        $from_custom = (object) array(
            'launch' => 'mod/gift/',
            'custom' => array((object) array('key' => 'quiz', 'value' => 'DJ-TUT1.txt')),
        );
        $this->assertSame('DJ-TUT1.txt', LessonsLegacyGift::quizNameFromItem($from_custom));

        $assoc = (object) array(
            'launch' => 'tool/gift/',
            'custom' => array('quiz' => 'DJ-TUT1.txt', 'tries' => '3'),
        );
        $this->assertSame('DJ-TUT1.txt', LessonsLegacyGift::quizNameFromItem($assoc));

        $this->assertNull(LessonsLegacyGift::quizNameFromItem((object) array(
            'launch' => 'mod/gift/',
        )));
        $this->assertNull(LessonsLegacyGift::quizNameFromItem((object) array(
            'launch' => 'mod/gift/?quiz=../etc/passwd',
        )));
        $this->assertNull(LessonsLegacyGift::quizNameFromItem((object) array(
            'launch' => 'mod/gift/?quiz=.lock',
        )));
    }

    public function testResolveIgnoresLockAndSkipsDotfiles() {
        $files = LessonsLegacyGift::listQuizFiles($this->quizRoot);
        $this->assertContains('DJ-TUT1.txt', $files);
        $this->assertContains('numerical.txt', $files);
        $this->assertContains('mixed.txt', $files);
        $this->assertNotContains('.lock', $files);
        $this->assertNotContains('.hidden.txt', $files);
        $this->assertNotContains('nested', $files);
        $this->assertNotContains('skip.txt', $files);

        $path = LessonsLegacyGift::resolveGiftFile('DJ-TUT1.txt', $this->quizRoot);
        $this->assertNotNull($path);
        $this->assertSame("::Q1:: HTTP is a protocol.{T}\n", LessonsLegacyGift::readGiftText('DJ-TUT1.txt', $this->quizRoot));
        $this->assertFileExists($this->quizRoot.'/.lock');
    }

    public function testResolveRejectsMissingAndTraversal() {
        $this->assertNull(LessonsLegacyGift::resolveGiftFile('missing.txt', $this->quizRoot));
        $this->assertNull(LessonsLegacyGift::resolveGiftFile('../DJ-TUT1.txt', $this->quizRoot));
        $this->assertNull(LessonsLegacyGift::resolveGiftFile('nested/skip.txt', $this->quizRoot));
        $this->assertNull(LessonsLegacyGift::resolveGiftFile('.lock', $this->quizRoot));
        $this->assertNull(LessonsLegacyGift::readGiftText('.hidden.txt', $this->quizRoot));
    }

    public function testWantsGiftQtiDefaultOn() {
        $this->assertTrue(LessonsLegacyGift::wantsGiftQti('qti'));
        $this->assertTrue(LessonsLegacyGift::wantsGiftQti('QTI'));
        $this->assertTrue(LessonsLegacyGift::wantsGiftQti(''));
        $this->assertTrue(LessonsLegacyGift::wantsGiftQti(null));
        $this->assertTrue(LessonsLegacyGift::wantsGiftQti('yes'));
        $this->assertTrue(LessonsLegacyGift::wantsGiftQti('1'));
        $this->assertFalse(LessonsLegacyGift::wantsGiftQti('lti'));
        $this->assertFalse(LessonsLegacyGift::wantsGiftQti('LTI'));
        $this->assertFalse(LessonsLegacyGift::wantsGiftQti('no'));
        $this->assertFalse(LessonsLegacyGift::wantsGiftQti('0'));
        $this->assertFalse(LessonsLegacyGift::wantsGiftQti(false));
    }

    public function testSummarizeCountsGiftFoundAndLti() {
        $l = $this->lessonsDoc();
        $summary = LessonsLegacyGift::summarize($l, false, $this->quizRoot);
        $this->assertSame(5, $summary['scanned']);
        $this->assertSame(4, $summary['gift']);
        $this->assertSame(2, $summary['found']);
        $this->assertSame(3, $summary['lti']);
        $this->assertContains('DJ-TUT1.txt', $summary['paths']);
        $this->assertContains('mixed.txt', $summary['paths']);
        $this->assertNotContains('numerical.txt', $summary['paths']);
        $this->assertSame(2, $summary['by_module']['w1']['found']);
        $this->assertNotEmpty($summary['warnings']);
    }

    public function testAddToModuleConvertsFoundGiftAndFallsBackToLti() {
        $filename = tempnam(sys_get_temp_dir(), 'ccgift');
        unlink($filename);
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($filename, \ZipArchive::CREATE) === true);
        $cc_dom = new CC();
        $cc_dom->set_title('Test');
        $module_node = $cc_dom->add_module('Week 1', '');
        $helper = new LessonsLegacyGift(true, $this->quizRoot);
        $module = (object) array('title' => 'Week 1');

        $this->assertSame('qti', $helper->addToModule(
            $zip, $cc_dom, $module_node,
            (object) array(
                'title' => 'Quiz: Tutorial 1',
                'launch' => 'tsugi/tool/gift/?quiz=DJ-TUT1.txt',
                'resource_link_id' => 'q1',
            ),
            $module
        ));
        $this->assertSame('lti', $helper->addToModule(
            $zip, $cc_dom, $module_node,
            (object) array(
                'title' => 'Quiz: Missing',
                'launch' => 'mod/gift/?quiz=no-such.txt',
                'resource_link_id' => 'q-missing',
            ),
            $module
        ));
        $this->assertSame('lti', $helper->addToModule(
            $zip, $cc_dom, $module_node,
            (object) array(
                'title' => 'Quiz: Numerical only',
                'launch' => 'mod/gift/?quiz=numerical.txt',
                'resource_link_id' => 'q-num',
            ),
            $module
        ));
        $this->assertSame('lti', $helper->addToModule(
            $zip, $cc_dom, $module_node,
            (object) array(
                'title' => 'Auto-grader: Install',
                'launch' => 'tools/dj-tutorial/',
                'resource_link_id' => 'ag1',
            ),
            $module
        ));
        $this->assertSame('qti', $helper->addToModule(
            $zip, $cc_dom, $module_node,
            (object) array(
                'title' => 'Quiz: Mixed',
                'launch' => 'mod/gift/?quiz=mixed.txt',
                'resource_link_id' => 'q-mix',
            ),
            $module
        ));

        $zip->addFromString('imsmanifest.xml', $cc_dom->saveXML());
        $zip->close();

        try {
            $opened = new \ZipArchive();
            $this->assertTrue($opened->open($filename) === true);
            $manifest = $opened->getFromName('imsmanifest.xml');
            $this->assertNotFalse($manifest);
            $this->assertSame(2, substr_count($manifest, 'imsqti_xmlv1p2'));
            $this->assertGreaterThanOrEqual(3, substr_count($manifest, 'imsbasiclti_xmlv1p0'));
            $qtiCount = 0;
            for ( $i = 0; $i < $opened->numFiles; $i++ ) {
                $name = $opened->getNameIndex($i);
                if ( ! is_string($name) ) {
                    continue;
                }
                $bytes = $opened->getFromName($name);
                if ( is_string($bytes) && str_contains($bytes, 'questestinterop') ) {
                    $qtiCount++;
                    $this->assertStringContainsString('HTTP is a protocol', $bytes);
                }
            }
            $this->assertSame(2, $qtiCount);
            $opened->close();
        } finally {
            @unlink($filename);
        }
    }

    public function testAddToModuleKeepsLtiWhenConversionOff() {
        $filename = tempnam(sys_get_temp_dir(), 'ccgift');
        unlink($filename);
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($filename, \ZipArchive::CREATE) === true);
        $cc_dom = new CC();
        $cc_dom->set_title('Test');
        $module_node = $cc_dom->add_module('Week 1', '');
        $helper = new LessonsLegacyGift(false, $this->quizRoot);
        $this->assertSame('lti', $helper->addToModule(
            $zip, $cc_dom, $module_node,
            (object) array(
                'title' => 'Quiz: Tutorial 1',
                'launch' => 'tsugi/tool/gift/?quiz=DJ-TUT1.txt',
                'resource_link_id' => 'q1',
            ),
            (object) array('title' => 'Week 1')
        ));
        $zip->addFromString('imsmanifest.xml', $cc_dom->saveXML());
        $zip->close();
        try {
            $opened = new \ZipArchive();
            $this->assertTrue($opened->open($filename) === true);
            $manifest = $opened->getFromName('imsmanifest.xml');
            $this->assertNotFalse($manifest);
            $this->assertSame(0, substr_count($manifest, 'imsqti_xmlv1p2'));
            $this->assertGreaterThanOrEqual(1, substr_count($manifest, 'imsbasiclti_xmlv1p0'));
            $opened->close();
        } finally {
            @unlink($filename);
        }
    }

    public function testLegacyModuleLtiArrayIsScanned() {
        $l = (object) array(
            'lessons' => (object) array(
                'title' => 'Course',
                'modules' => array(
                    (object) array(
                        'title' => 'Week 1',
                        'anchor' => 'w1',
                        'lti' => array(
                            (object) array(
                                'title' => 'Quiz: Intro',
                                'launch' => 'mod/gift/?quiz=DJ-TUT1.txt',
                                'resource_link_id' => 'old1',
                            ),
                            (object) array(
                                'title' => 'Autograder',
                                'launch' => 'mod/pythonauto/',
                                'resource_link_id' => 'old2',
                            ),
                        ),
                    ),
                ),
            ),
        );
        $summary = LessonsLegacyGift::summarize($l, false, $this->quizRoot);
        $this->assertSame(2, $summary['scanned']);
        $this->assertSame(1, $summary['gift']);
        $this->assertSame(1, $summary['found']);
        $this->assertSame(1, $summary['lti']);
    }

    /**
     * @return object
     */
    private function lessonsDoc() {
        return (object) array(
            'lessons' => (object) array(
                'title' => 'Course',
                'modules' => array(
                    (object) array(
                        'title' => 'Week 1',
                        'anchor' => 'w1',
                        'items' => array(
                            (object) array(
                                'type' => 'lti',
                                'title' => 'Quiz: Tutorial 1',
                                'launch' => 'tsugi/tool/gift/?quiz=DJ-TUT1.txt',
                                'resource_link_id' => 'q1',
                            ),
                            (object) array(
                                'type' => 'lti',
                                'title' => 'Quiz: Mixed',
                                'launch' => 'mod/gift/?quiz=mixed.txt',
                                'resource_link_id' => 'q2',
                            ),
                            (object) array(
                                'type' => 'lti',
                                'title' => 'Quiz: Numerical',
                                'launch' => 'mod/gift/?quiz=numerical.txt',
                                'resource_link_id' => 'q3',
                            ),
                            (object) array(
                                'type' => 'lti',
                                'title' => 'Quiz: Missing file',
                                'launch' => 'tool/gift/?quiz=no-such.txt',
                                'resource_link_id' => 'q4',
                            ),
                            (object) array(
                                'type' => 'lti',
                                'title' => 'Auto-grader: Install',
                                'launch' => '{apphome}/tools/dj-tutorial/',
                                'resource_link_id' => 'ag1',
                            ),
                        ),
                    ),
                ),
            ),
        );
    }

    private function rmTree($dir) {
        if ( ! is_dir($dir) ) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ( $it as $file ) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        @rmdir($dir);
    }
}
