<?php

require_once "src/Core/LTIX.php";
require_once "src/Core/Launch.php";
require_once "src/Util/PDOX.php";
require_once "src/Util/LTIConstants.php";

use \Tsugi\Core\LTIX;

class LaunchTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test Launch session methods with $_SESSION
     */
    public function testSessionMethods() {
        @session_id('test-session-'.uniqid());
        @session_start();
        $_SESSION = [];

        $launch = new \Tsugi\Core\Launch();
        $this->assertEquals('sam', $launch->session_get('x', 'sam'));
        $launch->session_put('x', 'y');
        $this->assertEquals('y', $launch->session_get('x', 'sam'));
        $launch->session_forget('x');
        $this->assertEquals('sam', $launch->session_get('x', 'sam'));
        $launch->session_put('a', 'b');
        $this->assertEquals('b', $launch->session_get('a', 'sam'));
        $launch->session_put('a', 'c');
        $this->assertEquals('c', $launch->session_get('a', 'sam'));
        $launch->session_flush();
        $this->assertEquals('sam', $launch->session_get('a', 'sam'));
        for ($i = 1; $i < 100; $i++) {
            $launch->session_put($i, $i * $i);
        }
        $this->assertEquals(100, $launch->session_get(10, 42));
        $launch->session_flush();
        $this->assertEquals(42, $launch->session_get(10, 42));
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
