<?php

use Tsugi\Config\ConfigInfo;
use Tsugi\Controllers\Settings;

class SettingsCartridgeUploadTest extends \PHPUnit\Framework\TestCase
{
    /** @var mixed */
    private $originalCFG;

    /** @var array */
    private $originalGet;
    /** @var array */
    private $originalPost;
    /** @var mixed */
    private $originalUri;
    /** @var array */
    private $originalSession;

    protected function setUp(): void
    {
        global $CFG;
        $this->originalCFG = $CFG;
        $this->originalGet = $_GET;
        $this->originalPost = $_POST;
        $this->originalUri = $_SERVER['REQUEST_URI'] ?? null;
        $this->originalSession = isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array();
        $_GET = array();
        $_POST = array();
        $_SESSION = array();
        $CFG = new ConfigInfo(dirname(__DIR__, 3), 'https://www.example.com/tsugi');
        $CFG->apphome = 'https://www.example.com';
        $CFG->dirroot = dirname(__DIR__, 3);
        if (!function_exists('currentContextId')) {
            require_once dirname(__DIR__, 2) . '/include/lms_lib.php';
        }
        if (function_exists('_tsugiResetIdentitySnapshot')) {
            _tsugiResetIdentitySnapshot();
        }
    }

    protected function tearDown(): void
    {
        global $CFG;
        $CFG = $this->originalCFG;
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
        $_SESSION = $this->originalSession;
        if ( $this->originalUri === null ) {
            unset($_SERVER['REQUEST_URI']);
        } else {
            $_SERVER['REQUEST_URI'] = $this->originalUri;
        }
        if (function_exists('_tsugiResetIdentitySnapshot')) {
            _tsugiResetIdentitySnapshot();
        }
    }

    public function testCartridgeUploadUrlUsesWwwroot() {
        $this->assertSame('128M', Settings::CARTRIDGE_UPLOAD_MAX);
        $this->assertSame(
            'https://www.example.com/tsugi/lib/src/Controllers/util/upload/',
            Settings::cartridgeUploadUrl()
        );
        $this->assertSame(
            'https://www.example.com/tsugi/lib/src/Controllers/util/upload/?context=2',
            Settings::cartridgeUploadUrl(2)
        );
        $this->assertSame(
            'https://www.example.com/tsugi/courses/2/settings/import',
            Settings::cartridgeImportPageUrl(2)
        );
    }

    public function testIsCartridgeUploadRequestMatchesUtilUpload() {
        $orig = $_SERVER['REQUEST_URI'] ?? null;
        $_SERVER['REQUEST_URI'] = '/tsugi/lib/src/Controllers/util/upload/?context=2';
        $this->assertTrue(Settings::isCartridgeUploadRequest());
        $_SERVER['REQUEST_URI'] = '/tsugi/lib/src/Controllers/util/upload/index.php';
        $this->assertTrue(Settings::isCartridgeUploadRequest());
        $_SERVER['REQUEST_URI'] = '/tsugi/cc/export.php';
        $this->assertFalse(Settings::isCartridgeUploadRequest());
        $_SERVER['REQUEST_URI'] = '/tsugi/courses/2/settings/import';
        $this->assertFalse(Settings::isCartridgeUploadRequest());
        if ( $orig === null ) {
            unset($_SERVER['REQUEST_URI']);
        } else {
            $_SERVER['REQUEST_URI'] = $orig;
        }
    }

    public function testCartridgeUploadContextIdFromRequest() {
        $origGet = $_GET;
        $origPost = $_POST;
        $_GET = array('context' => '9');
        $_POST = array();
        $this->assertSame(9, Settings::cartridgeUploadContextIdFromRequest());
        $_GET = array();
        $_POST = array('context' => '4');
        $this->assertSame(4, Settings::cartridgeUploadContextIdFromRequest());
        $_POST = array(
            'return' => 'https://www.example.com/tsugi/courses/12/settings/import',
        );
        $this->assertSame(12, Settings::cartridgeUploadContextIdFromRequest());
        $_GET = $origGet;
        $_POST = $origPost;
    }

    public function testIsSafeImportReturnAllowsSameSiteImportUrls() {
        $this->assertTrue(Settings::isSafeImportReturn('/courses/2/settings/import'));
        $this->assertTrue(Settings::isSafeImportReturn('/tsugi/courses/2/settings/import'));
        $this->assertTrue(Settings::isSafeImportReturn(
            'https://www.example.com/tsugi/courses/2/settings/import'
        ));
        $this->assertTrue(Settings::isSafeImportReturn(
            'https://www.example.com/tsugi/courses/2/settings/import?PHPSESSID=abc'
        ));
        $this->assertTrue(Settings::isSafeImportReturn(
            'https://www.example.com/tsugi/settings/import'
        ));
    }

    public function testIsSafeImportReturnRejectsOpenRedirects() {
        $this->assertFalse(Settings::isSafeImportReturn('https://evil.example/settings/import'));
        $this->assertFalse(Settings::isSafeImportReturn('//evil.example/settings/import'));
        $this->assertFalse(Settings::isSafeImportReturn(
            "https://www.example.com/tsugi/courses/2/settings/import\nLocation: https://evil.example"
        ));
        $this->assertFalse(Settings::isSafeImportReturn('https://www.example.com/tsugi/settings'));
        $this->assertFalse(Settings::isSafeImportReturn('https://www.example.com/tsugi/courses/2/settings/export'));
        $this->assertFalse(Settings::isSafeImportReturn('http://www.example.com/tsugi/courses/2/settings/import'));
        $this->assertFalse(Settings::isSafeImportReturn('https://www.example.com/phishing?next=/settings/import'));
        $this->assertFalse(Settings::isSafeImportReturn(''));
        $this->assertFalse(Settings::isSafeImportReturn(null));
    }
}
