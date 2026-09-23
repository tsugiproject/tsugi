<?php

require_once "src/Core/I18N.php";
require_once "include/setup_i18n.php";
require_once "src/Config/ConfigInfo.php";

use Tsugi\Services\Cartridge\Fixtures;
use Tsugi\Services\Cartridge\ImportException;
use Tsugi\Services\Cartridge\Matcher;
use Tsugi\Services\Cartridge\Package;
use Tsugi\Services\Cartridge\Session;
use Tsugi\Services\Cartridge\Walker;
use Tsugi\Services\Quiz1\Qti12Importer;

class CartridgeWalkerTest extends \PHPUnit\Framework\TestCase
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
        $this->dir = sys_get_temp_dir().'/cc-walk-'.bin2hex(random_bytes(4));
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

    public function testMixedGenericScanThenRescanIsAllDuplicates() {
        $path = Fixtures::writeMixed($this->dir, 'generic');
        $first = new Session(10, 3);
        $row = Walker::scan($path, $first);
        $this->assertSame(Matcher::STATUS_OK, $row['status']);
        $this->assertSame(0, $row['error_count']);
        $this->assertGreaterThanOrEqual(6, $row['created_count']);
        $this->assertSame($row['created_count'], count($first->objects));
        $this->assertSame(0, $row['duplicate_count']);
        $kinds = array();
        foreach ( $first->objects as $obj ) {
            $kinds[] = $obj['local_kind'];
        }
        $this->assertContains('web_link', $kinds);
        $this->assertContains('file', $kinds);
        $this->assertContains('page', $kinds);
        $this->assertContains('quiz', $kinds);
        $this->assertContains('lti_link', $kinds);
        $this->assertContains('discussion', $kinds);
        $this->assertNotSame('', $row['zip_sha256']);
        $this->assertNotSame('', $row['manifest_identifier']);

        $second = new Session(10, 3, $first->objects);
        $again = Walker::scan($path, $second);
        $this->assertSame(Matcher::STATUS_OK, $again['status']);
        $this->assertSame(0, $again['created_count']);
        $this->assertSame(0, $again['copy_count']);
        $this->assertSame($row['created_count'], $again['duplicate_count']);
        $this->assertCount($row['created_count'], $second->objects);
    }

    public function testCanvasExtrasAreSkipped() {
        $path = Fixtures::writeMixed($this->dir, 'canvas');
        $pkg = Package::open($path);
        $skipped = 0;
        foreach ( $pkg->resources as $res ) {
            if ( ! empty($res['skipped']) ) {
                $skipped++;
            }
        }
        $this->assertGreaterThan(0, $skipped, 'Canvas flavor should list associatedcontent to skip');
        $importable = count($pkg->importableResources());
        $pkg->close();

        $s = new Session(11);
        $row = Walker::scan($path, $s);
        $this->assertSame(Matcher::STATUS_OK, $row['status']);
        $this->assertSame($importable, $row['created_count']);
        foreach ( $s->objects as $obj ) {
            $this->assertFalse(str_starts_with((string) $obj['resource_type'], 'associatedcontent'));
        }
    }

    public function testNestedManifestFolder() {
        $inner = Fixtures::writeMixed($this->dir, 'generic');
        $nested = $this->dir.'/nested.imscc';
        $this->wrapInFolder($inner, $nested, 'CourseExport');

        $s = new Session(12);
        $row = Walker::scan($nested, $s);
        $this->assertSame(Matcher::STATUS_OK, $row['status']);
        $this->assertGreaterThanOrEqual(6, $row['created_count']);
        $this->assertSame(0, $row['error_count']);
    }

    public function testMissingMemberIsErrorNotAbort() {
        $path = $this->dir.'/partial.imscc';
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path, \ZipArchive::CREATE) === true);
        $zip->addFromString('imsmanifest.xml', '<?xml version="1.0"?>
<manifest identifier="m1" xmlns="http://www.imsglobal.org/xsd/imsccv1p2/imscp_v1p1">
  <metadata><schema>IMS Common Cartridge</schema><schemaversion>1.2.0</schemaversion>
    <lomimscc:lom xmlns:lomimscc="http://ltsc.ieee.org/xsd/imsccv1p2/LOM/manifest"><lomimscc:general>
      <lomimscc:title><lomimscc:string>Partial</lomimscc:string></lomimscc:title>
    </lomimscc:general></lomimscc:lom>
  </metadata>
  <organizations><organization identifier="org"><item identifier="root">
    <item identifier="i1" identifierref="r1"><title>Docs</title></item>
    <item identifier="i2" identifierref="r2"><title>Missing</title></item>
  </item></organization></organizations>
  <resources>
    <resource identifier="r1" type="imswl_xmlv1p2"><file href="wl.xml"/></resource>
    <resource identifier="r2" type="webcontent" href="nope.bin"><file href="nope.bin"/></resource>
  </resources>
</manifest>');
        $zip->addFromString('wl.xml', '<?xml version="1.0"?><webLink xmlns="http://www.imsglobal.org/xsd/imsccv1p2/imswl_v1p2"><title>Docs</title><url href="https://www.tsugi.org/"/></webLink>');
        $zip->close();

        $s = new Session(13);
        $row = Walker::scan($path, $s);
        $this->assertSame(Matcher::STATUS_PARTIAL, $row['status']);
        $this->assertSame(1, $row['created_count']);
        $this->assertSame(1, $row['error_count']);
        $this->assertSame('web_link', $s->objects[0]['local_kind']);
    }

    public function testMissingManifestThrows() {
        $path = $this->dir.'/empty.zip';
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path, \ZipArchive::CREATE) === true);
        $zip->addFromString('readme.txt', 'no manifest');
        $zip->close();

        $this->expectException(ImportException::class);
        Walker::scan($path, new Session(14));
    }

    public function testTwoCoursesSameZipAreIndependent() {
        $path = Fixtures::writeMixed($this->dir, 'generic');
        $a = new Session(20);
        Walker::scan($path, $a);
        $b = new Session(21);
        $row = Walker::scan($path, $b);
        $this->assertSame($a->import['created_count'], $row['created_count']);
        $this->assertSame(0, $row['duplicate_count']);
    }

    public function testNewV2CourseCanvasExport() {
        $path = dirname(__DIR__, 2).'/fixtures/Cartridge/New-V2-Course_canvas.imscc';
        $this->assertFileExists($path);

        $pkg = Package::open($path);
        $this->assertSame('New V2 Course import', $pkg->title);
        $this->assertSame('1.2.0', $pkg->schemaversion);
        $this->assertCount(7, $pkg->resources);
        $this->assertCount(5, $pkg->importableResources());
        $this->assertCount(1, $pkg->modules);
        $this->assertSame('Week 1', $pkg->modules[0]['title']);
        $this->assertCount(7, $pkg->modules[0]['items']);
        $headings = 0;
        foreach ( $pkg->modules[0]['items'] as $item ) {
            if ( ! empty($item['heading']) ) {
                $headings++;
            }
        }
        $this->assertSame(2, $headings);
        $qti = $pkg->readHref('Q1_0eaae8c65c7d4c5a/assessment_qti.xml');
        list($quiz, $warnings) = Qti12Importer::import($qti);
        unset($warnings);
        $this->assertNotSame('', $quiz->title);
        $this->assertGreaterThan(0, count($quiz->questions));
        $pkg->close();

        $first = new Session(30);
        $row = Walker::scan($path, $first);
        $this->assertSame(Matcher::STATUS_OK, $row['status']);
        $this->assertSame(5, $row['created_count']);
        $this->assertSame(0, $row['error_count']);

        $byId = array();
        foreach ( $first->objects as $obj ) {
            $byId[$obj['resource_identifier']] = $obj;
        }
        $this->assertSame(array(
            'F_03f2c3fa7683628f_R',
            'WL_b53da9a48762aae8_R',
            'F_4f9a872f28691c67_R',
            'WL_bd8a6ca7eff7b46e_R',
            'Q1_0eaae8c65c7d4c5a_R',
        ), array_keys($byId));
        $this->assertSame('file', $byId['F_03f2c3fa7683628f_R']['local_kind']);
        $this->assertSame('file', $byId['F_4f9a872f28691c67_R']['local_kind']);
        $this->assertSame('web_link', $byId['WL_b53da9a48762aae8_R']['local_kind']);
        $this->assertSame('web_link', $byId['WL_bd8a6ca7eff7b46e_R']['local_kind']);
        $this->assertSame('quiz', $byId['Q1_0eaae8c65c7d4c5a_R']['local_kind']);
        $this->assertSame(
            $byId['WL_b53da9a48762aae8_R']['content_hash'],
            $byId['WL_bd8a6ca7eff7b46e_R']['content_hash']
        );
        $titles = array();
        foreach ( $first->logs as $log ) {
            $titles[] = $log['title'];
        }
        $this->assertSame(
            array(
                'py4e_completion_badge_01.png',
                'Yada 324er5t',
                'badge_03_network.png',
                'Dr. Chuck',
                'QTI Export Test',
            ),
            $titles
        );

        $second = new Session(30, 0, $first->objects);
        $again = Walker::scan($path, $second);
        $this->assertSame(0, $again['created_count']);
        $this->assertSame(5, $again['duplicate_count']);
        $this->assertSame(0, $again['copy_count']);
    }

    public function testDescribeAndRestrictToOneModule() {
        $path = Fixtures::writeTwoModules($this->dir);
        $pkg = Package::open($path);
        $described = $pkg->describeModules();
        $this->assertCount(2, $described);
        $this->assertSame('Week 1', $described[0]['title']);
        $this->assertSame('Week 2', $described[1]['title']);
        $this->assertSame(1, $described[0]['counts']['resources']);
        $this->assertSame(1, $described[1]['counts']['resources']);
        $this->assertCount(2, $pkg->importableResources());

        $firstKey = $described[0]['key'];
        $this->assertNotSame('', $firstKey);
        $pkg->restrictToModules(array($firstKey));
        $this->assertCount(1, $pkg->modules);
        $this->assertSame('Week 1', $pkg->modules[0]['title']);
        $kept = $pkg->importableResources();
        $this->assertCount(1, $kept);
        $this->assertSame($pkg->modules[0]['items'][0]['identifierref'], $kept[0]['identifier']);
        $pkg->close();
    }

    public function testCanvasNewQuizzesWithEmptyOrganizationAreVisible() {
        $fixture = __DIR__.'/../../fixtures/Quiz1/canvas-new-quizzes/objectbank-unfiled.xml.qti';
        $qti = file_get_contents($fixture);
        $this->assertNotFalse($qti);
        $empty = '<?xml version="1.0"?><questestinterop xmlns="http://www.imsglobal.org/xsd/ims_qtiasiv1p2"><assessment ident="shell" title="Quiz: SQL"><section ident="root_section"/></assessment></questestinterop>';
        $manifest = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<manifest identifier="m1" xmlns="http://www.imsglobal.org/xsd/imsccv1p1/imscp_v1p1">
  <metadata>
    <schema>IMS Common Cartridge</schema>
    <schemaversion>1.1.0</schemaversion>
    <lomimscc:lom xmlns:lomimscc="http://ltsc.ieee.org/xsd/imsccv1p1/LOM/manifest">
      <lomimscc:general><lomimscc:title><lomimscc:string>Practice</lomimscc:string></lomimscc:title></lomimscc:general>
    </lomimscc:lom>
  </metadata>
  <organizations>
    <organization identifier="org_1" structure="rooted-hierarchy">
      <item identifier="LearningModules"></item>
    </organization>
  </organizations>
  <resources>
    <resource identifier="settings" type="associatedcontent/imscc_xmlv1p1/learning-application-resource" href="course_settings/canvas_export.txt">
      <file href="course_settings/canvas_export.txt"/>
    </resource>
    <resource identifier="bank1" type="associatedcontent/imscc_xmlv1p1/learning-application-resource" href="non_cc_assessments/bank1.xml.qti">
      <file href="non_cc_assessments/bank1.xml.qti"/>
    </resource>
    <resource identifier="quiz1" type="imsqti_xmlv1p2/imscc_xmlv1p1/assessment">
      <file href="quiz1/assessment_qti.xml"/>
      <dependency identifierref="quiz1meta"/>
    </resource>
    <resource identifier="quiz1meta" type="associatedcontent/imscc_xmlv1p1/learning-application-resource" href="quiz1/assessment_meta.xml">
      <file href="quiz1/assessment_meta.xml"/>
      <file href="non_cc_assessments/quiz1.xml.qti"/>
    </resource>
    <resource identifier="page1" type="webcontent" href="wiki_content/home.html">
      <file href="wiki_content/home.html"/>
    </resource>
  </resources>
</manifest>
XML;
        $path = $this->dir.'/canvas-nq.imscc';
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path, \ZipArchive::CREATE) === true);
        $zip->addFromString('imsmanifest.xml', $manifest);
        $zip->addFromString('course_settings/canvas_export.txt', 'canvas');
        $zip->addFromString('non_cc_assessments/bank1.xml.qti', $qti);
        $zip->addFromString('non_cc_assessments/quiz1.xml.qti', $qti);
        $zip->addFromString('quiz1/assessment_qti.xml', $empty);
        $zip->addFromString('quiz1/assessment_meta.xml', '<quiz/>');
        $zip->addFromString('wiki_content/home.html', '<html><body>Hi</body></html>');
        $zip->close();

        $pkg = Package::open($path);
        $described = $pkg->describeModules();
        $this->assertCount(1, $described);
        $this->assertSame('Imported', $described[0]['title']);
        $this->assertSame(1, $described[0]['counts']['quizzes']);
        $this->assertSame(1, $described[0]['counts']['pages']);
        $this->assertSame(0, $described[0]['counts']['files']);
        $quiz = null;
        foreach ( $pkg->importableResources() as $res ) {
            if ( ($res['identifier'] ?? '') === 'quiz1' ) {
                $quiz = $res;
            }
            $this->assertNotSame('settings', $res['identifier'] ?? '');
            $this->assertNotSame('bank1', $res['identifier'] ?? '');
        }
        $this->assertNotNull($quiz);
        $this->assertSame('non_cc_assessments/quiz1.xml.qti', $quiz['href']);
        $pkg->close();

        $session = new Session(31);
        $row = Walker::scan($path, $session);
        $this->assertSame(0, $row['error_count']);
        $this->assertSame(2, $row['created_count']);
    }

    /**
     * @param string $src
     * @param string $dest
     * @param string $folder
     */
    private function wrapInFolder($src, $dest, $folder) {
        $in = new \ZipArchive();
        $this->assertTrue($in->open($src) === true);
        $out = new \ZipArchive();
        $this->assertTrue($out->open($dest, \ZipArchive::CREATE) === true);
        for ( $i = 0; $i < $in->numFiles; $i++ ) {
            $name = $in->getNameIndex($i);
            $bytes = $in->getFromIndex($i);
            if ( ! is_string($name) || $bytes === false ) {
                continue;
            }
            $out->addFromString($folder.'/'.$name, $bytes);
        }
        $in->close();
        $out->close();
    }
}
