<?php

require_once "src/Core/LTIX.php";
require_once "src/Core/Launch.php";
require_once "src/Util/PDOX.php";
require_once "src/Util/U.php";
require_once "src/Util/LTIConstants.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

class LaunchTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test U::session_empty and direct $_SESSION access
     */
    public function testSessionEmpty() {
        @session_id('test-session-'.uniqid());
        @session_start();
        $_SESSION = [];

        $this->assertEquals('sam', $_SESSION['x'] ?? 'sam');
        $_SESSION['x'] = 'y';
        $this->assertEquals('y', $_SESSION['x'] ?? 'sam');
        unset($_SESSION['x']);
        $this->assertEquals('sam', $_SESSION['x'] ?? 'sam');
        $_SESSION['a'] = 'b';
        $this->assertEquals('b', $_SESSION['a'] ?? 'sam');
        $_SESSION['a'] = 'c';
        $this->assertEquals('c', $_SESSION['a'] ?? 'sam');
        U::session_empty();
        $this->assertEquals('sam', $_SESSION['a'] ?? 'sam');
        for ($i = 1; $i < 100; $i++) {
            $_SESSION[$i] = $i * $i;
        }
        $this->assertEquals(100, $_SESSION[10] ?? 42);
        U::session_empty();
        $this->assertEquals(42, $_SESSION[10] ?? 42);
    }

    public function testIsFunctions() {
        @session_id('test-session-'.uniqid());
        @session_start();
        $_SESSION = [];
        $_SESSION[TSUGI_SESSION_LTI] = [];

        $launch = LTIX::buildLaunch([]);
        $this->assertFalse($launch->isSakai());
        $this->assertFalse($launch->isCanvas());
        $this->assertFalse($launch->isMoodle());
        $this->assertFalse($launch->isCoursera());
    }

    public function testCascadeSetting() {
        @session_id('test-session-'.uniqid());
        @session_start();
        $_SESSION = [];
        $_SESSION[TSUGI_SESSION_LTI] = [];

        $launch = LTIX::buildLaunch([]);
        $zap = $launch->settingsCascade('bob', 'sarah');
        $this->assertEquals('sarah', $zap);
    }
}
