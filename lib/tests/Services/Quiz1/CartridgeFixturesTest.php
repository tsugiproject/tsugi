<?php

require_once "src/Core/I18N.php";
require_once "include/setup_i18n.php";
require_once "src/UI/Lessons.php";
require_once "src/UI/LessonsNormalize.php";
require_once "src/UI/LessonsCartridge.php";
require_once "src/Config/ConfigInfo.php";

use Tsugi\Services\Quiz1\CartridgeFixtures;
use Tsugi\Services\Quiz1\CartridgeValidator;
use Tsugi\Services\Quiz1\SampleQuiz;
use Tsugi\Util\CC;

class CartridgeFixturesTest extends \PHPUnit\Framework\TestCase
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
        $this->dir = sys_get_temp_dir().'/quiz1-cc12-'.bin2hex(random_bytes(4));
        mkdir($this->dir, 0775, true);
    }

    protected function tearDown(): void
    {
        global $CFG;
        $CFG = $this->originalCFG;
        if ( is_dir($this->dir) ) {
            foreach ( glob($this->dir.'/*') as $file ) {
                @unlink($file);
            }
            @rmdir($this->dir);
        }
    }

    public function testAllFixturesValidateAsCc12() {
        $written = CartridgeFixtures::writeAll($this->dir);
        $this->assertCount(9, $written);
        foreach ( $written as $stem => $path ) {
            $this->assertFileExists($path, $stem);
            $result = CartridgeValidator::validate($path);
            $this->assertTrue($result['ok'], $stem."\n".CartridgeValidator::format($result));
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $this->assertFalse($zip->getFromName('course_settings/module_meta.xml'), $stem.' generic should omit module_meta');
            $this->assertFalse($zip->getFromName('course_settings/canvas_export.txt'), $stem.' generic should omit canvas_export');
            $manifest = $zip->getFromName('imsmanifest.xml');
            $this->assertStringNotContainsString('canvas.instructure.com', $manifest);
            $this->assertStringNotContainsString('associatedcontent/', $manifest);
            $zip->close();
        }
    }

    public function testMixedModulePositionsAreSequentialAndUnique() {
        $path = CartridgeFixtures::writeMixed($this->dir, SampleQuiz::buildMinimal(1));
        $result = CartridgeValidator::validate($path);
        $this->assertTrue($result['ok'], CartridgeValidator::format($result));

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $meta = $zip->getFromName('course_settings/module_meta.xml');
        $manifest = $zip->getFromName('imsmanifest.xml');
        $has_qti = false;
        $has_assessment_meta = false;
        $has_non_cc = false;
        for ( $i = 0; $i < $zip->numFiles; $i++ ) {
            $name = $zip->getNameIndex($i);
            if ( ! is_string($name) ) {
                continue;
            }
            if ( str_ends_with($name, '/assessment_qti.xml') ) {
                $has_qti = true;
            }
            if ( str_ends_with($name, '/assessment_meta.xml') ) {
                $has_assessment_meta = true;
            }
            if ( str_starts_with($name, 'non_cc_assessments/') && str_ends_with($name, '.xml.qti') ) {
                $has_non_cc = true;
            }
        }
        $this->assertTrue($has_qti);
        $this->assertTrue($has_assessment_meta);
        $this->assertTrue($has_non_cc);
        $zip->close();

        $this->assertNotFalse($meta);
        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($meta));
        $xp = new \DOMXPath($dom);
        $xp->registerNamespace('m', 'http://canvas.instructure.com/xsd/cccv1p0');
        $items = $xp->query('//m:item');
        $this->assertSame(3, $items->length);
        $types = array();
        $i = 1;
        foreach ( $items as $item ) {
            $pos = $xp->query('m:position', $item);
            $this->assertSame(1, $pos->length);
            $this->assertSame((string) $i, trim($pos->item(0)->textContent));
            $types[] = trim($xp->query('m:content_type', $item)->item(0)->textContent);
            $i++;
        }
        $this->assertSame(
            array('ContextModuleSubHeader', 'ExternalUrl', 'Quizzes::Quiz'),
            $types
        );

        $this->assertStringContainsString('<schemaversion>'.CC::VERSION.'</schemaversion>', $manifest);
        $this->assertStringContainsString(CC::QTI_ASSESSMENT_TYPE, $manifest);
        $this->assertStringContainsString('assessment_qti.xml', $manifest);
        $this->assertStringContainsString('assessment_meta.xml', $manifest);
        $this->assertStringContainsString('<dependency identifierref="', $manifest);
        $this->assertStringNotContainsString('imsccv1p1', $manifest);
        $this->assertStringNotContainsString('imscc_xmlv1p1', $manifest);
    }
}
