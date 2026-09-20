<?php

require_once "src/Util/TsugiDOM.php";
require_once "src/Util/CC.php";
require_once "src/Util/CanvasModuleMeta.php";
require_once "src/Util/CanvasAssessmentMeta.php";
require_once "src/UI/LessonsLegacyFiles.php";
require_once "src/Config/ConfigInfo.php";

use Tsugi\UI\LessonsLegacyFiles;
use Tsugi\Util\CC;

class LessonsLegacyFilesTest extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;
    /** @var string */
    private $courseRoot;
    /** @var string */
    private $dirroot;

    protected function setUp(): void
    {
        global $CFG;
        $this->originalCFG = $CFG;
        $this->courseRoot = sys_get_temp_dir().'/tsugi-legacy-cc-'.uniqid('', true);
        $this->dirroot = $this->courseRoot.'/tsugi';
        mkdir($this->dirroot, 0777, true);
        mkdir($this->courseRoot.'/lectures3/pdf', 0777, true);
        mkdir($this->courseRoot.'/html3', 0777, true);
        file_put_contents($this->courseRoot.'/lectures3/Pythonlearn-01-Intro.pptx', 'PPTXBYTES');
        file_put_contents($this->courseRoot.'/lectures3/pdf/Pythonlearn-01-Intro.pdf', 'PDFBYTES');
        file_put_contents($this->courseRoot.'/install.php', '<?php echo "nope";');
        file_put_contents($this->courseRoot.'/html3/01-intro', 'page');

        $CFG = new \Tsugi\Config\ConfigInfo($this->dirroot, 'https://www.example.com/tsugi');
        $CFG->apphome = 'https://www.example.com';
    }

    protected function tearDown(): void
    {
        global $CFG;
        $CFG = $this->originalCFG;
        $this->rmTree($this->courseRoot);
    }

    public function testLooksLikeFileRecognizesOfficeDocuments() {
        $this->assertTrue(LessonsLegacyFiles::looksLikeFile('https://www.example.com/lectures3/foo.pptx'));
        $this->assertTrue(LessonsLegacyFiles::looksLikeFile('lectures3/pdf/foo.PDF'));
        $this->assertTrue(LessonsLegacyFiles::looksLikeFile('https://www.example.com/code/html.zip'));
        $this->assertFalse(LessonsLegacyFiles::looksLikeFile('https://www.example.com/install.php'));
        $this->assertFalse(LessonsLegacyFiles::looksLikeFile('https://www.example.com/assn/spec.md'));
        $this->assertFalse(LessonsLegacyFiles::looksLikeFile('https://www.example.com/html3/01-intro'));
        $this->assertFalse(LessonsLegacyFiles::looksLikeFile('https://www.youtube.com/watch?v=abc'));
    }

    public function testIsSameServerMatchesApphomeNotExternalHost() {
        $this->assertTrue(LessonsLegacyFiles::isSameServer('https://www.example.com/lectures3/foo.pptx'));
        $this->assertFalse(LessonsLegacyFiles::isSameServer('https://www.py4e.com/lectures3/foo.pptx'));
    }

    public function testPayloadReadsSameServerPublicFile() {
        $payload = LessonsLegacyFiles::payloadForUrl(
            'https://www.example.com/lectures3/Pythonlearn-01-Intro.pptx'
        );
        $this->assertNotNull($payload);
        $this->assertSame('PPTXBYTES', $payload['bytes']);
        $this->assertSame('lectures3/Pythonlearn-01-Intro.pptx', $payload['path']);
        $this->assertSame(hash('sha256', 'PPTXBYTES'), $payload['sha256']);
    }

    public function testPayloadLeavesExternalAndPagesAsNull() {
        $this->assertNull(LessonsLegacyFiles::payloadForUrl(
            'https://www.py4e.com/lectures3/Pythonlearn-01-Intro.pptx'
        ));
        $this->assertNull(LessonsLegacyFiles::payloadForUrl(
            'https://www.example.com/html3/01-intro'
        ));
        $this->assertNull(LessonsLegacyFiles::payloadForUrl(
            'https://www.example.com/install.php'
        ));
    }

    public function testPayloadRejectsMissingFileAndTraversal() {
        $this->assertNull(LessonsLegacyFiles::payloadForUrl(
            'https://www.example.com/lectures3/does-not-exist.pdf'
        ));
        $this->assertNull(LessonsLegacyFiles::payloadForUrl(
            'https://www.example.com/lectures3/../../etc/passwd.pdf'
        ));
    }

    public function testAddToModuleEmbedsBytesAndDedupes() {
        $filename = tempnam(sys_get_temp_dir(), 'ccleg');
        unlink($filename);
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($filename, \ZipArchive::CREATE) === true);
        $cc_dom = new CC();
        $cc_dom->set_title('Test');
        $module = $cc_dom->add_module('Week 1', '');
        $helper = new LessonsLegacyFiles(true);

        $url = 'https://www.example.com/lectures3/pdf/Pythonlearn-01-Intro.pdf';
        $this->assertSame('file', $helper->addToModule($zip, $cc_dom, $module, 'Slides: PDF', $url));
        $this->assertSame('listing', $helper->addToModule($zip, $cc_dom, $module, 'Reference: PDF', $url));
        $this->assertSame('url', $helper->addToModule(
            $zip, $cc_dom, $module, 'Chapter', 'https://www.example.com/html3/01-intro'
        ));

        $zip->addFromString('imsmanifest.xml', $cc_dom->saveXML());
        $zip->close();

        try {
            $opened = new \ZipArchive();
            $this->assertTrue($opened->open($filename) === true);
            $path = 'web_resources/lectures3/pdf/Pythonlearn-01-Intro.pdf';
            $this->assertSame('PDFBYTES', $opened->getFromName($path));
            $manifest = $opened->getFromName('imsmanifest.xml');
            $this->assertNotFalse($manifest);
            $this->assertSame(1, substr_count($manifest, 'type="webcontent"'));
            $this->assertSame(1, substr_count($manifest, 'imswl_xmlv1p2'));
            $this->assertGreaterThanOrEqual(2, substr_count($manifest, $path));
            $opened->close();
        } finally {
            @unlink($filename);
        }
    }

    public function testThinCartridgeKeepsSameServerFilesAsLinks() {
        $filename = tempnam(sys_get_temp_dir(), 'ccleg');
        unlink($filename);
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($filename, \ZipArchive::CREATE) === true);
        $cc_dom = new CC();
        $cc_dom->set_title('Test');
        $module = $cc_dom->add_module('Week 1', '');
        $helper = new LessonsLegacyFiles(false);
        $url = 'https://www.example.com/lectures3/pdf/Pythonlearn-01-Intro.pdf';
        $this->assertSame('url', $helper->addToModule($zip, $cc_dom, $module, 'Slides: PDF', $url));
        $zip->addFromString('imsmanifest.xml', $cc_dom->saveXML());
        $zip->close();
        try {
            $opened = new \ZipArchive();
            $this->assertTrue($opened->open($filename) === true);
            $this->assertFalse($opened->getFromName('web_resources/lectures3/pdf/Pythonlearn-01-Intro.pdf'));
            $manifest = $opened->getFromName('imsmanifest.xml');
            $this->assertNotFalse($manifest);
            $this->assertSame(0, substr_count($manifest, 'type="webcontent"'));
            $this->assertSame(1, substr_count($manifest, 'imswl_xmlv1p2'));
            $opened->close();
        } finally {
            @unlink($filename);
        }
    }

    public function testWantsThickCartridgeOnlyForThick() {
        $this->assertFalse(LessonsLegacyFiles::wantsThickCartridge('thin'));
        $this->assertFalse(LessonsLegacyFiles::wantsThickCartridge(''));
        $this->assertFalse(LessonsLegacyFiles::wantsThickCartridge(null));
        $this->assertTrue(LessonsLegacyFiles::wantsThickCartridge('thick'));
        $this->assertTrue(LessonsLegacyFiles::wantsThickCartridge('THICK'));
    }

    public function testSummarizeCountsUniqueFilesAndRemainingLinks() {
        require_once "src/UI/LessonsNormalize.php";
        require_once "src/UI/Lessons.php";
        require_once "src/Util/U.php";
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
                                'subtype' => 'slides',
                                'title' => 'Deck',
                                'href' => 'https://www.example.com/lectures3/Pythonlearn-01-Intro.pptx',
                            ),
                            (object) array(
                                'type' => 'web_link',
                                'subtype' => 'reference',
                                'title' => 'PDF',
                                'href' => 'https://www.example.com/lectures3/pdf/Pythonlearn-01-Intro.pdf',
                            ),
                            (object) array(
                                'type' => 'web_link',
                                'subtype' => 'reference',
                                'title' => 'Same PDF again',
                                'href' => 'https://www.example.com/lectures3/pdf/Pythonlearn-01-Intro.pdf',
                            ),
                            (object) array(
                                'type' => 'web_link',
                                'subtype' => 'reference',
                                'title' => 'Chapter',
                                'href' => 'https://www.example.com/html3/01-intro',
                            ),
                            (object) array(
                                'type' => 'web_link',
                                'subtype' => 'slides',
                                'title' => 'Offsite',
                                'href' => 'https://www.py4e.com/lectures3/Pythonlearn-01-Intro.pptx',
                            ),
                        ),
                    ),
                ),
            ),
        );
        $summary = LessonsLegacyFiles::summarize($l);
        $this->assertSame(5, $summary['scanned']);
        $this->assertSame(2, $summary['files']);
        $this->assertSame(1, $summary['listings']);
        $this->assertSame(2, $summary['links']);
        $this->assertSame(2, $summary['by_module']['w1']['files']);
        $this->assertContains('lectures3/Pythonlearn-01-Intro.pptx', $summary['paths']);
        $this->assertContains('lectures3/pdf/Pythonlearn-01-Intro.pdf', $summary['paths']);
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
