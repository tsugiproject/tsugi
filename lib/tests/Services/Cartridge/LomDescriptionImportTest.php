<?php

require_once "src/Core/I18N.php";
require_once "include/setup_i18n.php";
require_once "src/Config/ConfigInfo.php";

use Tsugi\Services\Cartridge\Importer;
use Tsugi\Services\Cartridge\Matcher;
use Tsugi\Services\Cartridge\Package;
use Tsugi\Services\Cartridge\Session;
use Tsugi\Services\Lessons\LessonsCartridge;

class CartridgeLomDescriptionImportTest extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;
    private $dir;

    protected function setUp(): void
    {
        global $CFG;
        $this->originalCFG = $CFG;
        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost');
        $CFG->apphome = 'http://localhost/app';
        $CFG->wwwroot = 'http://localhost';
        $CFG->fontawesome = 'http://localhost/fontawesome';
        $this->dir = sys_get_temp_dir().'/cc-lom-'.bin2hex(random_bytes(4));
        mkdir($this->dir, 0775, true);
    }

    protected function tearDown(): void
    {
        global $CFG;
        $CFG = $this->originalCFG;
        if ( is_dir($this->dir) ) {
            foreach ( glob($this->dir.'/*') ?: array() as $file ) {
                @unlink($file);
            }
            @rmdir($this->dir);
        }
    }

    public function testImportSetsLessonDescriptionFromItemLom() {
        $html = '<p>Hello <strong>world</strong></p>';
        $path = $this->writeLessonCartridge('Django Models', $html);
        $pkg = Package::open($path);
        try {
            $this->assertCount(1, $pkg->modules);
            $this->assertSame('Django Models', $pkg->modules[0]['title']);
            $this->assertSame($html, $pkg->modules[0]['description']);

            $modules = $this->importModules($pkg);
            $this->assertCount(1, $modules);
            $this->assertSame('Django Models', $modules[0]['title']);
            $this->assertSame($html, $modules[0]['description']);
            $this->assertCount(1, $modules[0]['items']);
            $this->assertSame('Docs', $modules[0]['items'][0]['title']);
        } finally {
            $pkg->close();
        }
    }

    public function testImportLeavesDescriptionEmptyWithoutItemLom() {
        $path = $this->writeLessonCartridge('Week 1', null);
        $pkg = Package::open($path);
        try {
            $this->assertNull($pkg->modules[0]['description']);
            $modules = $this->importModules($pkg);
            $this->assertSame('', $modules[0]['description']);
        } finally {
            $pkg->close();
        }
    }

    public function testImportRestoresWebLinkWindowTarget() {
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
                                'title' => 'PythonAnywhere',
                                'href' => 'https://www.pythonanywhere.com/',
                                'target' => '_blank',
                            ),
                            (object) array(
                                'type' => 'web_link',
                                'subtype' => 'reference',
                                'title' => 'Same page',
                                'href' => 'https://example.com/docs',
                                'target' => '_self',
                            ),
                            (object) array(
                                'type' => 'web_link',
                                'subtype' => 'reference',
                                'title' => 'Modal',
                                'href' => 'https://example.com/modal',
                                'target' => 'modal',
                            ),
                            (object) array(
                                'type' => 'web_link',
                                'subtype' => 'reference',
                                'title' => 'Ordinary',
                                'href' => 'https://example.com/',
                            ),
                        ),
                    ),
                ),
            ),
        );
        $path = $this->dir.'/targets.imscc';
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path, \ZipArchive::CREATE) === true);
        LessonsCartridge::writeZip($l, $zip, array('tsugi_lms' => 'generic'));
        $zip->close();

        $pkg = Package::open($path);
        try {
            $byTitle = array();
            foreach ( $pkg->importableResources() as $res ) {
                $made = $this->materializeWebLink($pkg, $res);
                $byTitle[$made['lesson']['title']] = $made['lesson'];
            }
            $this->assertSame('_blank', $byTitle['PythonAnywhere']['target']);
            $this->assertSame('https://www.pythonanywhere.com/', $byTitle['PythonAnywhere']['href']);
            $this->assertSame('_self', $byTitle['Same page']['target']);
            $this->assertSame('modal', $byTitle['Modal']['target']);
            $this->assertArrayNotHasKey('target', $byTitle['Ordinary']);

            $this->assertSame('_blank', $pkg->modules[0]['items'][0]['target']);
            $modules = $this->importModules($pkg);
            $imported = array();
            foreach ( $modules[0]['items'] as $item ) {
                $imported[$item['title']] = $item;
            }
            $this->assertSame('_blank', $imported['PythonAnywhere']['target']);
            $this->assertSame('_self', $imported['Same page']['target']);
            $this->assertSame('modal', $imported['Modal']['target']);
            $this->assertArrayNotHasKey('target', $imported['Ordinary']);
        } finally {
            $pkg->close();
        }
    }

    public function testImportRestoresIconAndHrefSource() {
        $l = (object) array(
            'lessons' => (object) array(
                'title' => 'Course',
                'modules' => array(
                    (object) array(
                        'title' => 'Week 1',
                        'anchor' => 'w1',
                        'icon' => 'fa-rocket',
                        'items' => array(
                            (object) array(
                                'type' => 'web_link',
                                'subtype' => 'reference',
                                'title' => 'PythonAnywhere',
                                'href' => 'https://www.pythonanywhere.com/',
                                'href_source' => 'course',
                                'icon' => 'fa-globe',
                            ),
                        ),
                    ),
                ),
            ),
        );
        $path = $this->dir.'/icon.imscc';
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path, \ZipArchive::CREATE) === true);
        LessonsCartridge::writeZip($l, $zip, array('tsugi_lms' => 'generic'));
        $zip->close();

        $pkg = Package::open($path);
        try {
            $this->assertSame('fa-rocket', $pkg->modules[0]['icon']);
            $this->assertSame('fa-globe', $pkg->modules[0]['items'][0]['icon']);
            $this->assertSame('course', $pkg->modules[0]['items'][0]['href_source']);
            $modules = $this->importModules($pkg);
            $this->assertSame('fa-rocket', $modules[0]['icon']);
            $this->assertSame('fa-globe', $modules[0]['items'][0]['icon']);
            $this->assertSame('course', $modules[0]['items'][0]['href_source']);
        } finally {
            $pkg->close();
        }
    }

    /**
     * @param string $title
     * @param string|null $description
     * @return string
     */
    private function writeLessonCartridge($title, $description) {
        $module = (object) array(
            'title' => $title,
            'anchor' => 'w1',
            'items' => array(
                (object) array(
                    'type' => 'web_link',
                    'subtype' => 'reference',
                    'title' => 'Docs',
                    'href' => 'https://example.com/',
                ),
            ),
        );
        if ( $description !== null ) {
            $module->description = $description;
        }
        $l = (object) array(
            'lessons' => (object) array(
                'title' => 'Course',
                'modules' => array($module),
            ),
        );
        $path = $this->dir.'/lesson.imscc';
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path, \ZipArchive::CREATE) === true);
        LessonsCartridge::writeZip($l, $zip, array('tsugi_lms' => 'generic'));
        $zip->close();
        return $path;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function importModules(Package $pkg) {
        $actions = array();
        $lessonsByRes = array();
        foreach ( $pkg->importableResources() as $res ) {
            $id = (string) ($res['identifier'] ?? '');
            $actions[$id] = Matcher::NEW;
            $lessonsByRes[$id] = array(
                'type' => 'web_link',
                'title' => (string) ($res['title'] ?? ''),
                'href' => (string) ($res['href'] ?? ''),
            );
        }
        $session = new Session(10, 3);
        $ref = new \ReflectionMethod(Importer::class, 'buildLessonsModules');
        return $ref->invoke(null, $pkg, $session, $actions, $lessonsByRes);
    }

    /**
     * @param array<string, mixed> $res
     * @return array{local_kind:string,local_id:?int,local_key:?string,lesson:?array}
     */
    private function materializeWebLink(Package $pkg, array $res) {
        $ref = new \ReflectionMethod(Importer::class, 'materialize');
        return $ref->invoke(null, $pkg, $res, 'web_link', 0, 0, array(), false);
    }
}
