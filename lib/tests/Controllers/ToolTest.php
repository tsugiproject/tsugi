<?php

require_once "src/Config/ConfigInfo.php";
require_once "src/Util/U.php";
require_once "src/Controllers/Tool.php";
require_once "src/Controllers/StaticFiles.php";

/**
 * Unit tests for Tool controller base class.
 *
 * Tool is abstract, so we use a concrete stub to test protected methods.
 * Excludes methods that require PDOX (database): isInstructor, lmsEnsureAnalyticsLink,
 * lmsRecordLaunchAnalytics, showAnalytics.
 */
class ToolTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Tsugi\Config\ConfigInfo */
    private $originalCFG;

    /** @var array */
    private $originalServer;

    /** @var array */
    private $originalSession;

    /**
     * Stub with ROUTE constant for toolParent(null) and toolHome tests
     */
    private function createAnnouncementsStub(): object
    {
        return new class extends \Tsugi\Controllers\Tool {
            const ROUTE = '/announcements';
            public function exposeToolHome(string $route): string { return $this->toolHome($route); }
            public function exposeToolParent(?string $route = null): string { return $this->toolParent($route); }
            public function exposeControllerUrl(string $controllerRoute, ?string $currentRoute = null): string {
                return $this->controllerUrl($controllerRoute, $currentRoute);
            }
            public function exposeExternalLinkAttrs(string $url, string $linkLabel = 'Learn more'): array {
                return $this->externalLinkAttrs($url, $linkLabel);
            }
            public function exposeIsAdmin(): bool { return $this->isAdmin(); }
            public function exposeRequireAuth(): void { $this->requireAuth(); }
            public function exposeLmsAnalyticsPath(): string { return $this->lmsAnalyticsPath(); }
            public function exposeLmsAnalyticsKey(?string $path = null): string { return $this->lmsAnalyticsKey($path); }
            public function exposeStaticUrl(string $filename, ?string $controllerName = null): string {
                return $this->staticUrl($filename, $controllerName);
            }
            public function exposeAssetUrl(string $filename, ?string $controllerName = null): string {
                return $this->assetUrl($filename, $controllerName);
            }
        };
    }

    /**
     * Stub with ROUTE = '/lessons' for testing lessons-style paths (/lessons/x and /course/1234/lessons/x)
     */
    private function createLessonsStub(): object
    {
        return new class extends \Tsugi\Controllers\Tool {
            const ROUTE = '/lessons';
            public function exposeToolHome(string $route): string { return $this->toolHome($route); }
            public function exposeToolParent(?string $route = null): string { return $this->toolParent($route); }
            public function exposeControllerUrl(string $controllerRoute, ?string $currentRoute = null): string {
                return $this->controllerUrl($controllerRoute, $currentRoute);
            }
            public function exposeStaticUrl(string $filename, ?string $controllerName = null): string {
                return $this->staticUrl($filename, $controllerName);
            }
        };
    }

    /**
     * Stub without ROUTE constant (uses apphome fallback for toolParent)
     */
    private function createToolStub(): object
    {
        return new class extends \Tsugi\Controllers\Tool {
            public function exposeExternalLinkAttrs(string $url, string $linkLabel = 'Learn more'): array {
                return $this->externalLinkAttrs($url, $linkLabel);
            }
        };
    }

    protected function setUp(): void
    {
        global $CFG;
        $this->originalCFG = $CFG;
        $CFG = new \Tsugi\Config\ConfigInfo(__DIR__, 'https://local.ca4e.com');
        $CFG->apphome = 'https://local.ca4e.com';
        $this->originalServer = $_SERVER ?? [];
        $this->originalSession = $_SESSION ?? [];
    }

    protected function tearDown(): void
    {
        global $CFG;
        $CFG = $this->originalCFG;
        $_SERVER = $this->originalServer;
        $_SESSION = $this->originalSession;
    }

    // --- externalLinkAttrs tests ---

    public function testExternalLinkAttrsExternalUrl(): void
    {
        $tool = $this->createToolStub();
        $result = $tool->exposeExternalLinkAttrs('https://example.com/page');

        $this->assertTrue($result['external']);
        $this->assertStringContainsString('target="_blank"', $result['attrs']);
        $this->assertStringContainsString('rel="noopener noreferrer"', $result['attrs']);
        $this->assertStringContainsString('opens in new window', $result['aria_label']);
    }

    public function testExternalLinkAttrsSameSiteByApphome(): void
    {
        $tool = $this->createToolStub();
        $result = $tool->exposeExternalLinkAttrs('https://local.ca4e.com/lessons/intro');

        $this->assertFalse($result['external']);
        $this->assertSame('', $result['attrs']);
        $this->assertSame('Learn more', $result['aria_label']);
    }

    public function testExternalLinkAttrsSameSiteRelativePath(): void
    {
        $tool = $this->createToolStub();
        $result = $tool->exposeExternalLinkAttrs('/announcements');

        $this->assertFalse($result['external']);
        $this->assertSame('', $result['attrs']);
        $this->assertSame('Learn more', $result['aria_label']);
    }

    public function testExternalLinkAttrsProtocolRelativeTreatedAsExternal(): void
    {
        $tool = $this->createToolStub();
        $result = $tool->exposeExternalLinkAttrs('//cdn.example.com/script.js');

        $this->assertTrue($result['external']);
        $this->assertStringContainsString('target="_blank"', $result['attrs']);
    }

    public function testExternalLinkAttrsCustomLinkLabel(): void
    {
        $tool = $this->createToolStub();
        $result = $tool->exposeExternalLinkAttrs('https://example.com', 'View URL');

        $this->assertTrue($result['external']);
        $this->assertSame('View URL (opens in new window)', $result['aria_label']);
    }

    public function testExternalLinkAttrsApphomeWithoutTrailingSlash(): void
    {
        global $CFG;
        $CFG->apphome = 'https://local.ca4e.com';
        $tool = $this->createToolStub();
        $result = $tool->exposeExternalLinkAttrs('https://local.ca4e.com/pages/1');

        $this->assertFalse($result['external']);
    }

    public function testExternalLinkAttrsEmptyApphomeExternalByDefault(): void
    {
        global $CFG;
        $CFG->apphome = '';
        $tool = $this->createToolStub();
        $result = $tool->exposeExternalLinkAttrs('https://example.com');

        $this->assertTrue($result['external']);
    }

    // --- toolHome tests ---

    public function testToolHomeFromRequestUri(): void
    {
        $_SERVER['REQUEST_URI'] = '/py4e/announcements';
        $_SERVER['SCRIPT_NAME'] = '/other/script.php'; // so rest_path won't match
        $tool = $this->createAnnouncementsStub();

        $this->assertSame('/py4e/announcements', $tool->exposeToolHome('/announcements'));
    }

    public function testToolHomeStripsQueryString(): void
    {
        $_SERVER['REQUEST_URI'] = '/py4e/announcements?foo=bar';
        $_SERVER['SCRIPT_NAME'] = '/other/script.php';
        $tool = $this->createAnnouncementsStub();

        $this->assertSame('/py4e/announcements', $tool->exposeToolHome('/announcements'));
    }

    public function testToolHomeFallbackToApphome(): void
    {
        $_SERVER['REQUEST_URI'] = '/other/path';
        $_SERVER['SCRIPT_NAME'] = '/different/script.php'; // rest_path returns false
        global $CFG;
        $CFG->apphome = 'https://local.ca4e.com';
        $tool = $this->createAnnouncementsStub();

        $this->assertSame('https://local.ca4e.com/announcements', $tool->exposeToolHome('/announcements'));
    }

    /**
     * Controllers can be mounted at different levels: /lessons/x (simple) or /course/1234/lessons/x (nested)
     */
    public function testToolHomeLessonsSimplePath(): void
    {
        $_SERVER['REQUEST_URI'] = '/lessons/intro';
        $_SERVER['SCRIPT_NAME'] = '/other/script.php';
        $tool = $this->createAnnouncementsStub();

        $this->assertSame('/lessons', $tool->exposeToolHome('/lessons'));
    }

    public function testToolHomeLessonsNestedPath(): void
    {
        $_SERVER['REQUEST_URI'] = '/course/1234/lessons/intro';
        $_SERVER['SCRIPT_NAME'] = '/other/script.php';
        $tool = $this->createAnnouncementsStub();

        $this->assertSame('/course/1234/lessons', $tool->exposeToolHome('/lessons'));
    }

    public function testToolHomePagesNestedPath(): void
    {
        $_SERVER['REQUEST_URI'] = '/course/5678/pages/edit/42';
        $_SERVER['SCRIPT_NAME'] = '/other/script.php';
        $tool = $this->createAnnouncementsStub();

        $this->assertSame('/course/5678/pages', $tool->exposeToolHome('/pages'));
    }

    // --- toolParent tests ---

    public function testToolParentFromRequestUri(): void
    {
        $_SERVER['REQUEST_URI'] = '/py4e/announcements';
        $_SERVER['SCRIPT_NAME'] = '/other/script.php';
        $tool = $this->createAnnouncementsStub();

        $this->assertSame('/py4e', $tool->exposeToolParent('/announcements'));
    }

    public function testToolParentUsesRouteConstantWhenNull(): void
    {
        $_SERVER['REQUEST_URI'] = '/py4e/announcements';
        $_SERVER['SCRIPT_NAME'] = '/other/script.php';
        $tool = $this->createAnnouncementsStub();

        $this->assertSame('/py4e', $tool->exposeToolParent(null));
    }

    public function testToolParentRootPath(): void
    {
        $_SERVER['REQUEST_URI'] = '/announcements';
        $_SERVER['SCRIPT_NAME'] = '/other/script.php';
        $tool = $this->createAnnouncementsStub();

        $this->assertSame('', $tool->exposeToolParent('/announcements'));
    }

    /**
     * Controllers mounted at /lessons/x (simple) or /course/1234/lessons/x (nested)
     */
    public function testToolParentLessonsSimplePath(): void
    {
        $_SERVER['REQUEST_URI'] = '/lessons/intro';
        $_SERVER['SCRIPT_NAME'] = '/other/script.php';
        $tool = $this->createLessonsStub();

        $this->assertSame('', $tool->exposeToolParent('/lessons'));
    }

    public function testToolParentLessonsNestedPath(): void
    {
        $_SERVER['REQUEST_URI'] = '/course/1234/lessons/transistors';
        $_SERVER['SCRIPT_NAME'] = '/other/script.php';
        $tool = $this->createLessonsStub();

        $this->assertSame('/course/1234', $tool->exposeToolParent('/lessons'));
    }

    public function testToolParentLessonsNestedUsesRouteConstantWhenNull(): void
    {
        $_SERVER['REQUEST_URI'] = '/course/5678/lessons/intro';
        $_SERVER['SCRIPT_NAME'] = '/other/script.php';
        $tool = $this->createLessonsStub();

        $this->assertSame('/course/5678', $tool->exposeToolParent(null));
    }

    // --- controllerUrl tests ---

    public function testControllerUrl(): void
    {
        $_SERVER['REQUEST_URI'] = '/py4e/announcements';
        $_SERVER['SCRIPT_NAME'] = '/other/script.php';
        $tool = $this->createAnnouncementsStub();

        $this->assertSame('/py4e/pages', $tool->exposeControllerUrl('/pages'));
        $this->assertSame('/py4e/lessons', $tool->exposeControllerUrl('/lessons', '/announcements'));
    }

    /**
     * controllerUrl from /lessons/x (simple) and /course/1234/lessons/x (nested)
     */
    public function testControllerUrlLessonsSimplePath(): void
    {
        $_SERVER['REQUEST_URI'] = '/lessons/intro';
        $_SERVER['SCRIPT_NAME'] = '/other/script.php';
        $tool = $this->createLessonsStub();

        $this->assertSame('/pages', $tool->exposeControllerUrl('/pages'));
    }

    public function testControllerUrlLessonsNestedPath(): void
    {
        $_SERVER['REQUEST_URI'] = '/course/1234/lessons/transistors';
        $_SERVER['SCRIPT_NAME'] = '/other/script.php';
        $tool = $this->createLessonsStub();

        $this->assertSame('/course/1234/pages', $tool->exposeControllerUrl('/pages'));
        $this->assertSame('/course/1234/announcements', $tool->exposeControllerUrl('/announcements'));
    }

    // --- determineParentPath tests (static) ---

    public function testDetermineParentPathWithRoute(): void
    {
        $_SERVER['REQUEST_URI'] = '/py4e/announcements';
        $this->assertSame('/py4e', \Tsugi\Controllers\Tool::determineParentPath('/announcements'));
    }

    public function testDetermineParentPathWithRouteNullDetectsFromUri(): void
    {
        $_SERVER['REQUEST_URI'] = '/py4e/pages';
        $this->assertSame('/py4e', \Tsugi\Controllers\Tool::determineParentPath(null));
    }

    public function testDetermineParentPathFallbackToApphome(): void
    {
        $_SERVER['REQUEST_URI'] = '/unknown/path';
        $_SERVER['SCRIPT_NAME'] = '/different/script.php';
        global $CFG;
        $CFG->apphome = 'https://local.ca4e.com';
        $this->assertSame('https://local.ca4e.com', \Tsugi\Controllers\Tool::determineParentPath('/nonexistent'));
    }

    /**
     * determineParentPath for /lessons/x (simple) and /course/1234/lessons/x (nested)
     */
    public function testDetermineParentPathLessonsSimplePath(): void
    {
        $_SERVER['REQUEST_URI'] = '/lessons/intro';
        $this->assertSame('', \Tsugi\Controllers\Tool::determineParentPath('/lessons'));
    }

    public function testDetermineParentPathLessonsNestedPath(): void
    {
        $_SERVER['REQUEST_URI'] = '/course/1234/lessons/transistors';
        $this->assertSame('/course/1234', \Tsugi\Controllers\Tool::determineParentPath('/lessons'));
    }

    public function testDetermineParentPathPagesNestedPath(): void
    {
        $_SERVER['REQUEST_URI'] = '/course/5678/pages/edit/42';
        $this->assertSame('/course/5678', \Tsugi\Controllers\Tool::determineParentPath('/pages'));
    }

    // --- isAdmin tests ---

    public function testIsAdminTrue(): void
    {
        $_SESSION['admin'] = 'yes';
        $tool = $this->createAnnouncementsStub();
        $this->assertTrue($tool->exposeIsAdmin());
    }

    public function testIsAdminFalse(): void
    {
        $_SESSION = [];
        $tool = $this->createAnnouncementsStub();
        $this->assertFalse($tool->exposeIsAdmin());
    }

    public function testIsAdminFalseWhenNotYes(): void
    {
        $_SESSION['admin'] = 'no';
        $tool = $this->createAnnouncementsStub();
        $this->assertFalse($tool->exposeIsAdmin());
    }

    // --- requireAuth tests ---

    public function testRequireAuthPassesWhenSessionSet(): void
    {
        $_SESSION['id'] = 42;
        $_SESSION['context_id'] = 1;
        $tool = $this->createAnnouncementsStub();
        $tool->exposeRequireAuth();
        $this->assertTrue(true); // if we get here, requireAuth didn't die
    }

    // --- lmsAnalyticsPath tests ---

    public function testLmsAnalyticsPathFromScriptName(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/py4e/tsugi.php';
        $tool = $this->createAnnouncementsStub();
        $this->assertSame('/py4e', $tool->exposeLmsAnalyticsPath());
    }

    public function testLmsAnalyticsPathFromPhpSelf(): void
    {
        unset($_SERVER['SCRIPT_NAME']);
        $_SERVER['PHP_SELF'] = '/lms/announcements/index.php';
        $tool = $this->createAnnouncementsStub();
        $this->assertSame('/lms/announcements', $tool->exposeLmsAnalyticsPath());
    }

    public function testLmsAnalyticsPathRoot(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $tool = $this->createAnnouncementsStub();
        $this->assertSame('/', $tool->exposeLmsAnalyticsPath());
    }

    // --- lmsAnalyticsKey tests ---

    public function testLmsAnalyticsKey(): void
    {
        $tool = $this->createAnnouncementsStub();
        $this->assertSame('lms:/announcements', $tool->exposeLmsAnalyticsKey('/announcements'));
    }

    public function testLmsAnalyticsKeyUsesLmsAnalyticsPathWhenNull(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/py4e/tsugi.php';
        $tool = $this->createAnnouncementsStub();
        $this->assertSame('lms:/py4e', $tool->exposeLmsAnalyticsKey(null));
    }

    // --- staticUrl and assetUrl tests ---

    public function testStaticUrl(): void
    {
        $_SERVER['REQUEST_URI'] = '/py4e/announcements';
        $_SERVER['SCRIPT_NAME'] = '/other/script.php';
        $tool = $this->createAnnouncementsStub();

        $url = $tool->exposeStaticUrl('tsugi-announce.js');
        $this->assertStringContainsString('/static/', $url);
        $this->assertStringContainsString('tsugi-announce.js', $url);
    }

    /**
     * staticUrl from /course/1234/lessons/x (nested) - base path must include /course/1234
     */
    public function testStaticUrlNestedPath(): void
    {
        $_SERVER['REQUEST_URI'] = '/course/1234/lessons/transistors';
        $_SERVER['SCRIPT_NAME'] = '/other/script.php';
        $tool = $this->createLessonsStub();

        $url = $tool->exposeStaticUrl('lessons.js');
        $this->assertStringContainsString('/course/1234/static/', $url);
        $this->assertStringContainsString('lessons.js', $url);
    }

    public function testAssetUrlAlias(): void
    {
        $_SERVER['REQUEST_URI'] = '/py4e/announcements';
        $_SERVER['SCRIPT_NAME'] = '/other/script.php';
        $tool = $this->createAnnouncementsStub();

        $staticUrl = $tool->exposeStaticUrl('test.js');
        $assetUrl = $tool->exposeAssetUrl('test.js');
        $this->assertSame($staticUrl, $assetUrl);
    }
}
