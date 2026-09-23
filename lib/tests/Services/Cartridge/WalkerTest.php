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

    public function testEmptyOrganizationIsRejected() {
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
    <resource identifier="page1" type="webcontent" href="wiki_content/home.html">
      <file href="wiki_content/home.html"/>
    </resource>
  </resources>
</manifest>
XML;
        $path = $this->dir.'/empty-org.imscc';
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path, \ZipArchive::CREATE) === true);
        $zip->addFromString('imsmanifest.xml', $manifest);
        $zip->addFromString('wiki_content/home.html', '<html><body>Hi</body></html>');
        $zip->close();

        $this->expectException(ImportException::class);
        $this->expectExceptionMessage('empty organization');
        Package::open($path);
    }

    /**
     * Canvas course export whose organization is an empty LearningModules item.
     * The fixture is that export's imsmanifest.xml. Lecture files are omitted
     * because the cartridge is rejected before they are read.
     */
    public function testCanvasNoModulesExportIsRejected() {
        $path = __DIR__.'/../../fixtures/Cartridge/canvas-no-modules.imscc';
        $this->expectException(ImportException::class);
        $this->expectExceptionMessage('empty organization');
        Package::open($path);
    }

    public function testCanvasItemBanksAreIgnored() {
        $dir = __DIR__.'/../../fixtures/Quiz1/canvas-new-quizzes/';
        $bank = file_get_contents($dir.'objectbank-unfiled.xml.qti');
        $quiz = file_get_contents($dir.'quiz-sql.xml.qti');
        $this->assertNotFalse($bank);
        $this->assertNotFalse($quiz);
        $bankType = 'associatedcontent/imscc_xmlv1p1/learning-application-resource';
        $this->assertTrue(Package::shouldSkip($bankType, 'non_cc_assessments/unfiled.xml.qti'));
        $this->assertFalse(Package::shouldSkip('imsqti_xmlv1p2/imscc_xmlv1p1/assessment', 'quiz1/assessment_qti.xml'));

        $manifest = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<manifest identifier="m1" xmlns="http://www.imsglobal.org/xsd/imsccv1p1/imscp_v1p1">
  <organizations>
    <organization identifier="org_1" structure="rooted-hierarchy">
      <item identifier="mod1">
        <title>Week 1</title>
        <item identifier="qitem" identifierref="quiz1"><title>Quiz: SQL</title></item>
      </item>
    </organization>
  </organizations>
  <resources>
    <resource identifier="unfiled" type="associatedcontent/imscc_xmlv1p1/learning-application-resource" href="non_cc_assessments/unfiled.xml.qti">
      <file href="non_cc_assessments/unfiled.xml.qti"/>
    </resource>
    <resource identifier="exporttest" type="associatedcontent/imscc_xmlv1p1/learning-application-resource" href="non_cc_assessments/exporttest.xml.qti">
      <file href="non_cc_assessments/exporttest.xml.qti"/>
    </resource>
    <resource identifier="quiz1" type="imsqti_xmlv1p2/imscc_xmlv1p1/assessment">
      <file href="quiz1/assessment_qti.xml"/>
      <dependency identifierref="quiz1meta"/>
    </resource>
    <resource identifier="quiz1meta" type="associatedcontent/imscc_xmlv1p1/learning-application-resource" href="quiz1/assessment_meta.xml">
      <file href="quiz1/assessment_meta.xml"/>
      <file href="non_cc_assessments/quiz1.xml.qti"/>
    </resource>
  </resources>
</manifest>
XML;
        $empty = '<?xml version="1.0"?><questestinterop><assessment ident="shell" title="Quiz: SQL"><section ident="root_section"/></assessment></questestinterop>';
        $path = $this->dir.'/banks-ignored.imscc';
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path, \ZipArchive::CREATE) === true);
        $zip->addFromString('imsmanifest.xml', $manifest);
        $zip->addFromString('non_cc_assessments/unfiled.xml.qti', $bank);
        $zip->addFromString('non_cc_assessments/exporttest.xml.qti', $bank);
        $zip->addFromString('non_cc_assessments/quiz1.xml.qti', $quiz);
        $zip->addFromString('quiz1/assessment_qti.xml', $empty);
        $zip->addFromString('quiz1/assessment_meta.xml', '<quiz/>');
        $zip->close();

        $pkg = Package::open($path);
        $unfiled = $pkg->resourceById('unfiled');
        $this->assertNotNull($unfiled);
        $this->assertTrue($unfiled['skipped']);
        $exportTest = $pkg->resourceById('exporttest');
        $this->assertNotNull($exportTest);
        $this->assertTrue($exportTest['skipped']);

        $ids = array();
        foreach ( $pkg->importableResources() as $res ) {
            $ids[] = $res['identifier'];
        }
        $this->assertSame(array('quiz1'), $ids);
        $kept = $pkg->resourceById('quiz1');
        $this->assertSame('non_cc_assessments/quiz1.xml.qti', $kept['href']);
        $described = $pkg->describeModules();
        $this->assertSame(1, $described[0]['counts']['quizzes']);
        $pkg->close();

        $row = Walker::scan($path, new Session(31));
        $this->assertSame(0, $row['error_count']);
        $this->assertSame(1, $row['created_count']);
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
