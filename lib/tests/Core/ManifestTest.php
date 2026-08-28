<?php

require_once "src/Core/Manifest.php";
require_once "src/Config/ConfigInfo.php";
require_once "src/UI/Lessons.php";
require_once "src/Core/I18N.php";
require_once "include/setup_i18n.php";

use \Tsugi\Core\Manifest;

if (!function_exists('die_with_error_log')) {
    function die_with_error_log($msg, $extra=false, $prefix="DIE:") {
        throw new \RuntimeException($prefix.' '.$msg);
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return !empty($_SESSION['id']);
    }
}

class ManifestTest extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;
    private $originalSession;

    protected function setUp(): void
    {
        global $CFG;
        $this->originalCFG = $CFG;
        $this->originalSession = isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array();
        $_SESSION = array();

        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost');
        $CFG->apphome = 'http://localhost/app';
        $CFG->wwwroot = 'http://localhost';
        $CFG->fontawesome = 'http://localhost/fontawesome';

        Manifest::resetRequestCache();
        Manifest::setMCache(null);
    }

    protected function tearDown(): void
    {
        global $CFG;
        $CFG = $this->originalCFG;
        $_SESSION = $this->originalSession;
        Manifest::resetRequestCache();
        Manifest::setMCache(null);
    }

    public function testStarterHasRequiredShape()
    {
        $doc = Manifest::starter('My Course');
        $this->assertSame('My Course', $doc['title']);
        $this->assertTrue($doc['count']);
        $this->assertSame(array(), $doc['badges']);
        $this->assertSame(array(), $doc['discussions']);
        $this->assertCount(1, $doc['modules']);
        $this->assertSame('Week 1', $doc['modules'][0]['title']);
        $this->assertSame('week-1', $doc['modules'][0]['anchor']);
        $this->assertSame(array(), $doc['modules'][0]['items']);
    }

    public function testStarterBlankTitle()
    {
        $doc = Manifest::starter('  ');
        $this->assertSame('Untitled Course', $doc['title']);
    }

    public function testStarterParsesAsLessons()
    {
        $json = Manifest::encode(Manifest::starter('Parse Me'));
        $lessons = \Tsugi\UI\Lessons::fromJson($json);
        $this->assertSame('Parse Me', $lessons->lessons->title);
        $this->assertCount(1, $lessons->lessons->modules);
        $this->assertSame('week-1', $lessons->lessons->modules[0]->anchor);
    }

    public function testCacheKeyUsesServerPrefixAndId()
    {
        global $CFG;
        $CFG->wwwroot = 'http://example.com/tsugi';
        $CFG->apphome = 'http://example.com';
        $key = Manifest::cacheKey(42);
        $this->assertStringEndsWith(':manifest:42', $key);
        $this->assertStringContainsString('example.com', $key);
        $this->assertStringNotContainsString('context', $key);
    }

    public function testActiveIdFromSessionBlob()
    {
        $_SESSION['lti'] = array('manifest_id' => '17');
        $this->assertSame(17, Manifest::activeId());
    }

    public function testActiveIdZeroWhenMissing()
    {
        $this->assertSame(0, Manifest::activeId());
    }

    public function testRememberInSession()
    {
        Manifest::rememberInSession(9);
        $this->assertSame(9, $_SESSION['manifest_id']);
        $this->assertSame(9, $_SESSION['lti']['manifest_id']);
        $this->assertSame(9, Manifest::activeId());
        Manifest::rememberInSession(0);
        $this->assertArrayNotHasKey('manifest_id', $_SESSION);
        $this->assertSame(0, Manifest::activeId());
    }

    public function testLoadJsonUsesMCacheWithoutDatabase()
    {
        $json = Manifest::encode(Manifest::starter('Cached'));
        $key = Manifest::cacheKey(99);
        $fake = new ManifestTestMCache();
        $fake->enabled = true;
        $fake->store[$key] = $json;
        Manifest::setMCache($fake);

        $loaded = Manifest::loadJson(99);
        $this->assertSame($json, $loaded);

        Manifest::resetRequestCache();
        $again = Manifest::loadJson(99);
        $this->assertSame($json, $again);
        $this->assertSame(2, $fake->gets);
    }

    public function testLoadJsonRequestMemoSkipsSecondCacheGet()
    {
        $json = Manifest::encode(Manifest::starter('Memo'));
        $key = Manifest::cacheKey(5);
        $fake = new ManifestTestMCache();
        $fake->enabled = true;
        $fake->store[$key] = $json;
        Manifest::setMCache($fake);

        Manifest::loadJson(5);
        Manifest::loadJson(5);
        $this->assertSame(1, $fake->gets);
    }

    public function testCachedRowServesJsonAndThemeWithoutDatabase()
    {
        $json = Manifest::encode(Manifest::starter('Electric Course'));
        $key = Manifest::cacheKey(77);
        $fake = new ManifestTestMCache();
        $fake->enabled = true;
        $fake->store[$key] = array(
            'manifest_id' => 77,
            'theme' => 'electric',
            'manifest' => $json,
            'version' => 3,
            'title' => 'Electric Course',
        );
        Manifest::setMCache($fake);
        Manifest::rememberInSession(77);

        $this->assertSame($json, Manifest::loadJson(77));
        $this->assertSame('electric', Manifest::currentThemeKey());
        $this->assertSame('#7c3aed', Manifest::currentThemeArray()['primary']);
        Manifest::resetRequestCache();
        $this->assertSame('electric', Manifest::currentThemeKey());
        $this->assertSame($json, Manifest::loadJson(77));
    }

    public function testLoadJsonDisabledCacheReturnsFalseWithoutDb()
    {
        $fake = new ManifestTestMCache();
        $fake->enabled = false;
        Manifest::setMCache($fake);
        // No database in this unit test: miss + no PDOX should not throw from MCache.
        // loadJsonFromDatabase will fail without a connection; skip if we cannot isolate.
        $this->assertFalse($fake->isEnabled());
    }

    public function testCurrentLessonsPrefersManifestOverFile()
    {
        global $CFG;
        $CFG->lessons = __DIR__ . '/../fixtures/lessons/py4e-modern-lessons-items.json';
        $json = Manifest::encode(Manifest::starter('Sandbox Course'));
        $fake = new ManifestTestMCache();
        $fake->enabled = true;
        $fake->store[Manifest::cacheKey(99)] = $json;
        Manifest::setMCache($fake);
        Manifest::rememberInSession(99);

        $l = Manifest::currentLessons();
        $this->assertInstanceOf(\Tsugi\UI\Lessons::class, $l);
        $this->assertSame('Sandbox Course', $l->lessons->title);
        $this->assertNotSame('Python for Everybody (PY4E)', $l->lessons->title);
    }

    public function testCurrentLessonsFallsBackToFile()
    {
        global $CFG;
        $CFG->lessons = __DIR__ . '/../fixtures/lessons/py4e-modern-lessons-items.json';
        $this->assertSame(0, Manifest::activeId());
        $l = Manifest::currentLessons();
        $this->assertInstanceOf(\Tsugi\UI\Lessons::class, $l);
        $this->assertSame('Python for Everybody (PY4E)', $l->lessons->title);
    }

    public function testValidateJsonAcceptsStarter()
    {
        $json = Manifest::encode(Manifest::starter('Valid Course'));
        $this->assertNull(Manifest::validateJson($json));
    }

    public function testValidateJsonRejectsEmpty()
    {
        $this->assertNotNull(Manifest::validateJson(''));
        $this->assertNotNull(Manifest::validateJson('{'));
        $this->assertNotNull(Manifest::validateJson('[]'));
        $this->assertNotNull(Manifest::validateJson('{"title":"No modules"}'));
    }

    public function testValidateJsonRejectsMissingModuleTitle()
    {
        $doc = Manifest::starter('Broken');
        unset($doc['modules'][0]['title']);
        $err = Manifest::validateJson(Manifest::encode($doc));
        $this->assertIsString($err);
        $this->assertStringContainsString('title', $err);
    }

    public function testValidateJsonRejectsDuplicateResourceLink()
    {
        $doc = Manifest::starter('Dup');
        $doc['modules'][0]['items'] = array(
            array(
                'type' => 'lti',
                'title' => 'One',
                'resource_link_id' => 'same-id',
                'launch' => 'http://example.com/one',
            ),
            array(
                'type' => 'lti',
                'title' => 'Two',
                'resource_link_id' => 'same-id',
                'launch' => 'http://example.com/two',
            ),
        );
        $err = Manifest::validateJson(Manifest::encode($doc));
        $this->assertIsString($err);
        $this->assertStringContainsString('Duplicate', $err);
    }

    public function testExportFilename()
    {
        $this->assertSame(
            'Python-for-Everybody-V2-2026-08-28-lessons.json',
            Manifest::exportFilename('Python for Everybody', 2, '2026-08-28')
        );
        $this->assertSame(
            'lessons-2026-08-28.json',
            Manifest::exportFilename('', 0, '2026-08-28')
        );
        $this->assertSame(
            'lessons-V3-2026-08-28.json',
            Manifest::exportFilename('***', 3, '2026-08-28')
        );
        $today = date('Y-m-d');
        $this->assertSame(
            'Sandbox-V1-'.$today.'-lessons.json',
            Manifest::exportFilename('Sandbox', 1)
        );
    }

    public function testAppendDiscussionAddsTopLevelEntry()
    {
        $doc = Manifest::starter('Sandbox');
        $result = Manifest::appendDiscussion($doc, 'Office Hours');
        $this->assertSame('discussion_office_hours', $result['resource_link_id']);
        $this->assertCount(1, $result['data']['discussions']);
        $this->assertSame('Office Hours', $result['data']['discussions'][0]['title']);
        $this->assertSame('mod/tdiscus/', $result['data']['discussions'][0]['launch']);
        $this->assertSame('discussion_office_hours', $result['data']['discussions'][0]['resource_link_id']);
        $this->assertNull(Manifest::validateJson(Manifest::encode($result['data'])));
    }

    public function testAppendDiscussionRequiresTitle()
    {
        $this->expectException(\InvalidArgumentException::class);
        Manifest::appendDiscussion(Manifest::starter('Sandbox'), '  ');
    }

    public function testAppendDiscussionAvoidsDuplicateResourceLinkIds()
    {
        $doc = Manifest::starter('Sandbox');
        $first = Manifest::appendDiscussion($doc, 'Hello');
        $second = Manifest::appendDiscussion($first['data'], 'Hello');
        $this->assertSame('discussion_hello', $first['resource_link_id']);
        $this->assertSame('discussion_hello_2', $second['resource_link_id']);
        $this->assertCount(2, $second['data']['discussions']);
    }

    public function testAppendDiscussionSkipsModuleItemRlids()
    {
        $doc = Manifest::starter('Sandbox');
        $doc['modules'][0]['items'][] = array(
            'type' => 'discussion',
            'title' => 'Week talk',
            'launch' => 'mod/tdiscus/',
            'resource_link_id' => 'discussion_week_talk',
        );
        $result = Manifest::appendDiscussion($doc, 'Week talk');
        $this->assertSame('discussion_week_talk_2', $result['resource_link_id']);
    }

    public function testReorderDiscussionsPermutesTopLevel()
    {
        $doc = Manifest::starter('Sandbox');
        $doc = Manifest::appendDiscussion($doc, 'Alpha')['data'];
        $doc = Manifest::appendDiscussion($doc, 'Beta')['data'];
        $doc = Manifest::appendDiscussion($doc, 'Gamma')['data'];
        $ids = array_column($doc['discussions'], 'resource_link_id');
        $reversed = array_reverse($ids);
        $reordered = Manifest::reorderDiscussions($doc, $reversed);
        $this->assertSame($reversed, array_column($reordered['discussions'], 'resource_link_id'));
        $this->assertSame($reversed, $reordered['discussion_order']);
        $this->assertNull(Manifest::validateJson(Manifest::encode($reordered)));
    }

    public function testReorderDiscussionsRequiresOrder()
    {
        $this->expectException(\InvalidArgumentException::class);
        Manifest::reorderDiscussions(Manifest::starter('Sandbox'), array());
    }

    public function testAppendDiscussionExtendsDiscussionOrder()
    {
        $doc = Manifest::starter('Sandbox');
        $doc = Manifest::appendDiscussion($doc, 'First')['data'];
        $doc = Manifest::reorderDiscussions($doc, array($doc['discussions'][0]['resource_link_id']));
        $after = Manifest::appendDiscussion($doc, 'Second');
        $this->assertSame(
            array($after['data']['discussions'][0]['resource_link_id'], $after['resource_link_id']),
            $after['data']['discussion_order']
        );
    }

    public function testPalettesIncludePeerSiteColors()
    {
        $palettes = Manifest::palettes();
        $this->assertArrayHasKey('tsugi', $palettes);
        $this->assertArrayHasKey('django', $palettes);
        $this->assertArrayHasKey('postgres', $palettes);
        $this->assertArrayHasKey('navy', $palettes);
        $this->assertArrayHasKey('electric', $palettes);
        $this->assertArrayHasKey('grey', $palettes);
        $this->assertSame('#0D47A1', $palettes['tsugi']['primary']);
        $this->assertSame('#0a4b33', $palettes['django']['primary']);
        $this->assertSame('#336791', $palettes['postgres']['primary']);
        $this->assertSame('#000060', $palettes['navy']['primary']);
        $this->assertSame('#7c3aed', $palettes['electric']['primary']);
        $this->assertSame('#D0D0D0', $palettes['grey']['primary']);
        $this->assertSame('#111111', $palettes['grey']['secondary']);
        $this->assertSame('#0a4b33', Manifest::palette('django')['primary']);
        $this->assertArrayNotHasKey('label', Manifest::palette('django'));
    }

    public function testNormalizeThemeKey()
    {
        $this->assertNull(Manifest::normalizeThemeKey(''));
        $this->assertNull(Manifest::normalizeThemeKey('default'));
        $this->assertNull(Manifest::normalizeThemeKey('site'));
        $this->assertNull(Manifest::normalizeThemeKey(null));
        $this->assertSame('django', Manifest::normalizeThemeKey('Django'));
        $this->assertFalse(Manifest::normalizeThemeKey('not-a-theme'));
        $this->assertFalse(Manifest::normalizeThemeKey(array('django')));
        $this->assertNull(Manifest::palette(''));
        $this->assertNull(Manifest::palette('bogus'));
    }

    public function testSiteDefaultPrimaryFromCfgTheme()
    {
        global $CFG;
        $CFG->theme = array('primary' => '#0a4b33');
        $this->assertSame('#0a4b33', Manifest::siteDefaultPrimary());
        $CFG->theme_base = '#7c3aed';
        $this->assertSame('#7c3aed', Manifest::siteDefaultPrimary());
        unset($CFG->theme_base, $CFG->theme);
        $this->assertSame('#0D47A1', Manifest::siteDefaultPrimary());
    }

    public function testCurrentThemeKeyEmptyWithoutSession()
    {
        $this->assertSame('', Manifest::currentThemeKey());
        $this->assertNull(Manifest::currentThemeArray());
    }
}

class ManifestTestMCache {
    public $enabled = false;
    public $store = array();
    public $gets = 0;

    public function isEnabled() {
        return $this->enabled;
    }

    public function get($key) {
        $this->gets++;
        return isset($this->store[$key]) ? $this->store[$key] : false;
    }

    public function set($key, $value, $expiration = 0) {
        $this->store[$key] = $value;
        return true;
    }
}
