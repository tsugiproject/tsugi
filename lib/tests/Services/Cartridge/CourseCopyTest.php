<?php

if ( ! function_exists('__') ) {
    function __($s) {
        return $s;
    }
}

require_once "src/Config/ConfigInfo.php";

use Tsugi\Services\Cartridge\CourseCopy;
use Tsugi\Services\Cartridge\Package;

class CourseCopyTest extends \PHPUnit\Framework\TestCase
{
    /** @var mixed */
    private $originalCFG;
    /** @var mixed */
    private $originalPdox;

    protected function setUp(): void
    {
        global $CFG, $PDOX;
        $this->originalCFG = $CFG;
        $this->originalPdox = $PDOX ?? null;
        $CFG = new \Tsugi\Config\ConfigInfo(dirname(__DIR__, 3), 'http://localhost');
        $CFG->apphome = 'http://localhost/app';
        $CFG->wwwroot = 'http://localhost';
        $CFG->lessons = false;
        $CFG->dbprefix = 'test_';
        $PDOX = null;
    }

    protected function tearDown(): void
    {
        global $CFG, $PDOX;
        $CFG = $this->originalCFG;
        $PDOX = $this->originalPdox;
    }

    public function testParseSourceId()
    {
        $this->assertSame(CourseCopy::FILE_SOURCE, CourseCopy::parseSourceId('file'));
        $this->assertSame(CourseCopy::FILE_SOURCE, CourseCopy::parseSourceId(' file '));
        $this->assertSame(12, CourseCopy::parseSourceId('12'));
        $this->assertSame(3, CourseCopy::parseSourceId(3));
        $this->assertFalse(CourseCopy::parseSourceId('0'));
        $this->assertFalse(CourseCopy::parseSourceId('file2'));
        $this->assertFalse(CourseCopy::parseSourceId(''));
        $this->assertFalse(CourseCopy::parseSourceId(null));
    }

    public function testFileCourseSourceUsesLessonsTitle()
    {
        global $CFG, $PDOX;
        $path = tempnam(sys_get_temp_dir(), 'lessons');
        $this->assertNotFalse($path);
        file_put_contents($path, json_encode(array('title' => 'Django for Everybody', 'modules' => array())));
        $CFG->lessons = $path;
        $PDOX = new class {
            public function allRowsDie($sql, $params) {
                return array();
            }
        };
        try {
            $this->assertTrue(CourseCopy::fileCourseAvailable());
            $sources = CourseCopy::sources(4, 9);
            $this->assertCount(1, $sources);
            $this->assertSame('file', $sources[0]['id']);
            $this->assertSame('file', $sources[0]['kind']);
            $this->assertSame('Django for Everybody', $sources[0]['title']);
        } finally {
            @unlink($path);
        }
    }

    public function testExportFileAndManifestCartridgesListTheModule()
    {
        $l = (object) array(
            'lessons' => (object) array(
                'title' => 'Source Course',
                'modules' => array(
                    (object) array(
                        'title' => 'Week 1',
                        'anchor' => 'w1',
                        'items' => array(
                            (object) array(
                                'type' => 'web_link',
                                'subtype' => 'reference',
                                'title' => 'Notes',
                                'href' => 'https://example.com/notes',
                            ),
                        ),
                    ),
                ),
            ),
        );

        $fileZip = tempnam(sys_get_temp_dir(), 'ccimp');
        $manifestZip = tempnam(sys_get_temp_dir(), 'ccimp');
        $this->assertNotFalse($fileZip);
        $this->assertNotFalse($manifestZip);
        try {
            CourseCopy::writeFileCartridge($l, $fileZip, 'Source Course');
            CourseCopy::writeManifestCartridge($l, $manifestZip, 0);
            foreach ( array($fileZip, $manifestZip) as $path ) {
                $pkg = Package::open($path);
                try {
                    $modules = $pkg->describeModules();
                } finally {
                    $pkg->close();
                }
                $this->assertNotSame(array(), $modules);
                $this->assertSame('Week 1', $modules[0]['title']);
            }
        } finally {
            @unlink($fileZip);
            @unlink($manifestZip);
        }
    }
}
