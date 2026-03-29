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

    public static function py4eFixtureProvider(): array
    {
        $base = __DIR__ . '/../fixtures/lessons/';
        return [
            'legacy monolithic lessons.json shape' => [$base . 'py4e-legacy-lessons.json'],
            'lessons-items combined' => [$base . 'py4e-lessons-items-combined.json'],
            'modern items (py4e lessons-items.json)' => [$base . 'py4e-modern-lessons-items.json'],
        ];
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
        $this->assertEquals('Python for Everybody (PY4E)', $lessons->lessons->title);
        $this->assertObjectHasProperty('modules', $lessons->lessons);
        $this->assertIsArray($lessons->lessons->modules);
        $this->assertGreaterThan(0, count($lessons->lessons->modules));

        $first = $lessons->lessons->modules[0];
        $this->assertObjectHasProperty('title', $first);
        $this->assertObjectHasProperty('anchor', $first);

        $this->assertIsArray($lessons->resource_links);
        $this->assertGreaterThan(0, count($lessons->resource_links));
    }
}
