<?php

require_once "src/Core/I18N.php";
require_once "include/setup_i18n.php";
require_once "src/UI/Lessons.php";
require_once "src/Config/ConfigInfo.php";

/**
 * Regression tests: real PY4E lesson JSON (legacy monolithic vs modern items format)
 * Fixtures live under tests/fixtures/lessons/ so builds do not depend on external paths.
 */
class LessonsJsonFixturesTest extends \PHPUnit\Framework\TestCase
{
    private const EXPECTED_TITLE = 'Python for Everybody (PY4E)';

    private const EXPECTED_DESCRIPTION = 'Hello and welcome to my site where you learn Python even if you have no programming background.';

    private const EXPECTED_MODULE_COUNT = 17;

    private const EXPECTED_BADGE_COUNT = 7;

    private const EXPECTED_LTI_COUNT = 43;

    private const EXPECTED_DISCUSSION_COUNT = 16;

    /** LTI + discussion entries with resource_link_id (matches Lessons constructor indexing). */
    private const EXPECTED_RESOURCE_LINK_COUNT = 59;

    private const EXPECTED_REQUIRED_MODULES = [
        'https://github.com/tsugitools/gift',
        'https://github.com/tsugitools/youtube',
        'https://github.com/tsugitools/peer-grade',
        'https://github.com/tsugitools/tdiscus',
    ];

    private $originalCFG;
    private $originalSession;

    protected function setUp(): void
    {
        global $CFG;
        $this->originalCFG = $CFG;

        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost');
        $CFG->apphome = 'http://localhost/app';
        $CFG->wwwroot = 'http://localhost';
        $CFG->fontawesome = 'http://localhost/fontawesome';

        $this->originalSession = $_SESSION ?? null;
        $_SESSION = ['id' => 1];
    }

    protected function tearDown(): void
    {
        global $CFG;
        $CFG = $this->originalCFG;
        $_SESSION = $this->originalSession;
    }

    private static function fixturesDir(): string
    {
        return __DIR__ . '/../fixtures/lessons/';
    }

    public static function py4eFixtureProvider(): array
    {
        $base = self::fixturesDir();
        return [
            'legacy monolithic lessons.json shape' => [$base . 'py4e-legacy-lessons.json'],
            'lessons-items combined' => [$base . 'py4e-lessons-items-combined.json'],
            'modern items (py4e lessons-items.json)' => [$base . 'py4e-modern-lessons-items.json'],
        ];
    }

    /**
     * @param object $lessons decoded top-level lessons object (e.g. json_decode)
     */
    private static function countLtisInDecoded(object $lessons): int
    {
        $n = 0;
        foreach ($lessons->modules as $mod) {
            if (isset($mod->items) && is_array($mod->items)) {
                foreach ($mod->items as $item) {
                    $o = is_array($item) ? (object) $item : $item;
                    if (isset($o->type) && $o->type === 'lti') {
                        $n++;
                    }
                }
            } elseif (isset($mod->lti)) {
                $ltis = $mod->lti;
                if (!is_array($ltis)) {
                    $ltis = [$ltis];
                }
                $n += count($ltis);
            }
        }
        return $n;
    }

    /**
     * @param object $lessons decoded top-level lessons object
     */
    private static function countDiscussionsInDecoded(object $lessons): int
    {
        $n = 0;
        foreach ($lessons->modules as $mod) {
            if (isset($mod->items) && is_array($mod->items)) {
                foreach ($mod->items as $item) {
                    $o = is_array($item) ? (object) $item : $item;
                    if (isset($o->type) && $o->type === 'discussion') {
                        $n++;
                    }
                }
            } elseif (isset($mod->discussions)) {
                $ds = $mod->discussions;
                if (!is_array($ds)) {
                    $ds = [$ds];
                }
                $n += count($ds);
            }
        }
        return $n;
    }

    /**
     * Sorted list of every resource_link_id under modules (items or legacy arrays).
     *
     * @param object $lessons decoded top-level lessons object
     *
     * @return list<string>
     */
    private static function collectResourceLinkIds(object $lessons): array
    {
        $ids = [];
        foreach ($lessons->modules as $mod) {
            if (isset($mod->items) && is_array($mod->items)) {
                foreach ($mod->items as $item) {
                    $o = is_array($item) ? (object) $item : $item;
                    if (isset($o->resource_link_id)) {
                        $ids[] = $o->resource_link_id;
                    }
                }
            } else {
                if (isset($mod->lti)) {
                    $ltis = $mod->lti;
                    if (!is_array($ltis)) {
                        $ltis = [$ltis];
                    }
                    foreach ($ltis as $x) {
                        if (isset($x->resource_link_id)) {
                            $ids[] = $x->resource_link_id;
                        }
                    }
                }
                if (isset($mod->discussions)) {
                    $ds = $mod->discussions;
                    if (!is_array($ds)) {
                        $ds = [$ds];
                    }
                    foreach ($ds as $x) {
                        if (isset($x->resource_link_id)) {
                            $ids[] = $x->resource_link_id;
                        }
                    }
                }
            }
        }
        sort($ids);

        return $ids;
    }

    /**
     * @param object $lessons decoded top-level lessons object
     *
     * @return list<string>
     */
    private static function moduleAnchors(object $lessons): array
    {
        $a = [];
        foreach ($lessons->modules as $m) {
            $a[] = $m->anchor;
        }

        return $a;
    }

    /**
     * @param object $lessons decoded top-level lessons object
     *
     * @return list<string>
     */
    private static function moduleTitles(object $lessons): array
    {
        $t = [];
        foreach ($lessons->modules as $m) {
            $t[] = $m->title;
        }

        return $t;
    }

    /**
     * LTI launch URLs after {@see \Tsugi\UI\Lessons} construction (legacy adjustArray vs items adjustItemsEntryUrls).
     *
     * @param object $lessons same shape as json_decode root (modules with lti[] or items[])
     *
     * @return array<string, string> resource_link_id => normalized launch URL
     */
    private static function collectLtiLaunchUrlsFromLoadedLessons(object $lessons): array
    {
        $map = [];
        foreach ($lessons->modules as $mod) {
            if (isset($mod->items) && is_array($mod->items)) {
                foreach ($mod->items as $item) {
                    $o = is_array($item) ? (object) $item : $item;
                    if (isset($o->type) && $o->type === 'lti' && isset($o->resource_link_id, $o->launch)) {
                        $map[$o->resource_link_id] = $o->launch;
                    }
                }
            } elseif (isset($mod->lti)) {
                $ltis = $mod->lti;
                if (!is_array($ltis)) {
                    $ltis = [$ltis];
                }
                foreach ($ltis as $x) {
                    if (isset($x->resource_link_id, $x->launch)) {
                        $map[$x->resource_link_id] = $x->launch;
                    }
                }
            }
        }
        ksort($map);

        return $map;
    }

    /**
     * Badges differ only by optional `linkedin` between legacy and items fixtures; normalize for comparison.
     *
     * @param array<int, array<string, mixed>> $badges
     *
     * @return array<int, array<string, mixed>>
     */
    private static function normalizeBadgesArray(array $badges): array
    {
        $out = [];
        foreach ($badges as $b) {
            $c = $b;
            unset($c['linkedin']);
            self::ksortDeep($c);
            $out[] = $c;
        }

        return $out;
    }

    /**
     * @param mixed $x
     */
    private static function ksortDeep(&$x): void
    {
        if (!is_array($x)) {
            return;
        }
        ksort($x);
        foreach ($x as &$v) {
            self::ksortDeep($v);
        }
    }

    /**
     * Raw JSON parity: all three files share module lists, activity counts, resource ids, and badge semantics.
     */
    public function testPy4eCrossFixtureParityFromRawJson(): void
    {
        $base = self::fixturesDir();
        $paths = [
            $base . 'py4e-legacy-lessons.json',
            $base . 'py4e-lessons-items-combined.json',
            $base . 'py4e-modern-lessons-items.json',
        ];

        $decoded = [];
        foreach ($paths as $path) {
            $this->assertFileExists($path);
            $json = file_get_contents($path);
            $this->assertNotFalse($json);
            $obj = json_decode($json);
            $this->assertNotNull($obj, $path . ': ' . json_last_error_msg());
            $decoded[] = $obj;
        }

        [$legacy, $combined, $modern] = $decoded;

        foreach ([$legacy, $combined, $modern] as $j) {
            $this->assertSame(self::EXPECTED_TITLE, $j->title);
            $this->assertSame(self::EXPECTED_DESCRIPTION, $j->description);
            $this->assertTrue(isset($j->count) && $j->count === true);
            $this->assertObjectHasProperty('required_modules', $j);
            $this->assertSame(self::EXPECTED_REQUIRED_MODULES, $j->required_modules);
            $this->assertCount(self::EXPECTED_MODULE_COUNT, $j->modules);
            $this->assertCount(self::EXPECTED_BADGE_COUNT, $j->badges);
            $this->assertSame(self::EXPECTED_LTI_COUNT, self::countLtisInDecoded($j));
            $this->assertSame(self::EXPECTED_DISCUSSION_COUNT, self::countDiscussionsInDecoded($j));
            $rl = self::collectResourceLinkIds($j);
            $this->assertCount(self::EXPECTED_RESOURCE_LINK_COUNT, $rl);
            $this->assertSame(count($rl), count(array_unique($rl)), 'resource_link_id values must be unique');
        }

        $anchorsLegacy = self::moduleAnchors($legacy);
        $this->assertSame($anchorsLegacy, self::moduleAnchors($combined));
        $this->assertSame($anchorsLegacy, self::moduleAnchors($modern));

        $titlesLegacy = self::moduleTitles($legacy);
        $this->assertSame($titlesLegacy, self::moduleTitles($combined));
        $this->assertSame($titlesLegacy, self::moduleTitles($modern));

        $rlLegacy = self::collectResourceLinkIds($legacy);
        $this->assertSame($rlLegacy, self::collectResourceLinkIds($combined));
        $this->assertSame($rlLegacy, self::collectResourceLinkIds($modern));

        $badgesCombined = json_decode(file_get_contents($paths[1]), true)['badges'];
        $badgesModern = json_decode(file_get_contents($paths[2]), true)['badges'];
        $this->assertSame(
            json_encode($badgesCombined, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            json_encode($badgesModern, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'items-format fixtures must have identical badges JSON'
        );

        $badgesLegacy = json_decode(file_get_contents($paths[0]), true)['badges'];
        $this->assertSame(
            self::normalizeBadgesArray($badgesLegacy),
            self::normalizeBadgesArray($badgesModern),
            'legacy badges match items-format badges when ignoring optional linkedin and key order'
        );
    }

    /**
     * Regression: legacy lti[] and items[] LTI entries must normalize to the same launch URLs (expandLink / absolute_url_ref).
     */
    public function testPy4eLtiLaunchUrlsMatchAcrossFixturesAfterLessonsLoad(): void
    {
        $base = self::fixturesDir();
        $paths = [
            'legacy' => $base . 'py4e-legacy-lessons.json',
            'combined' => $base . 'py4e-lessons-items-combined.json',
            'modern' => $base . 'py4e-modern-lessons-items.json',
        ];

        $maps = [];
        foreach ($paths as $label => $path) {
            $this->assertFileExists($path);
            $L = new \Tsugi\UI\Lessons($path);
            $maps[$label] = self::collectLtiLaunchUrlsFromLoadedLessons($L->lessons);
        }

        $this->assertCount(self::EXPECTED_LTI_COUNT, $maps['legacy'], 'every LTI must have resource_link_id and launch');
        $this->assertSame($maps['legacy'], $maps['combined']);
        $this->assertSame($maps['legacy'], $maps['modern']);
    }

    /**
     * @dataProvider py4eFixtureProvider
     */
    public function testLessonsConstructorLoadsPy4eFixtureWithoutError(string $path): void
    {
        $this->assertFileExists($path);

        $lessons = new \Tsugi\UI\Lessons($path);

        $this->assertNotNull($lessons->lessons);
        $this->assertObjectHasProperty('title', $lessons->lessons);
        $this->assertSame(self::EXPECTED_TITLE, $lessons->lessons->title);
        $this->assertObjectHasProperty('description', $lessons->lessons);
        $this->assertSame(self::EXPECTED_DESCRIPTION, $lessons->lessons->description);
        $this->assertTrue(isset($lessons->lessons->count) && $lessons->lessons->count === true);

        $this->assertObjectHasProperty('required_modules', $lessons->lessons);
        $this->assertSame(self::EXPECTED_REQUIRED_MODULES, $lessons->lessons->required_modules);

        $this->assertObjectHasProperty('modules', $lessons->lessons);
        $this->assertIsArray($lessons->lessons->modules);
        $this->assertCount(self::EXPECTED_MODULE_COUNT, $lessons->lessons->modules);

        $this->assertObjectHasProperty('badges', $lessons->lessons);
        $this->assertCount(self::EXPECTED_BADGE_COUNT, $lessons->lessons->badges);

        $this->assertSame(self::EXPECTED_LTI_COUNT, self::countLtisInDecoded($lessons->lessons));
        $this->assertSame(self::EXPECTED_DISCUSSION_COUNT, self::countDiscussionsInDecoded($lessons->lessons));

        $first = $lessons->lessons->modules[0];
        $this->assertObjectHasProperty('title', $first);
        $this->assertObjectHasProperty('anchor', $first);

        $this->assertIsArray($lessons->resource_links);
        $this->assertCount(self::EXPECTED_RESOURCE_LINK_COUNT, $lessons->resource_links);
        foreach (self::collectResourceLinkIds($lessons->lessons) as $rlid) {
            $this->assertArrayHasKey($rlid, $lessons->resource_links, 'Constructor must index every resource_link_id');
        }
    }
}
