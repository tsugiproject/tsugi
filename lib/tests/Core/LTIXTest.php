<?php

require_once "src/Core/LTIX.php";
require_once "src/Util/PDOX.php";
require_once "src/Config/ConfigInfo.php";

use \Tsugi\Core\LTIX;

class LTIXTest extends \PHPUnit\Framework\TestCase
{
    public function testSecretEncrypt() {
        global $CFG;
        $CFG = $CFG ?? new \Tsugi\Config\ConfigInfo("dir", "www");
        $CFG->cookiesecret = "hockey";
        $zap = LTIX::encrypt_secret("apereo");
        $this->assertTrue(strpos($zap,"AES::") === 0 );
        $zot = LTIX::decrypt_secret($zap);
        $this->assertEquals($zot,"apereo");
    }

    public function testCSRF() {
        $this->assertEquals(LTIX::ltiParameter('bob', 'sam'), 'sam');
    }

    /**
     * Test session access via $_SESSION (replaces former wrapped_session tests)
     */
    public function testSessionAccess() {
        $level_before = ob_get_level();
        @session_id('test-session-'.uniqid());
        @session_start();
        $_SESSION = [];

        $this->assertEquals('sam', $_SESSION['x'] ?? 'sam');
        $_SESSION['x'] = 'y';
        $this->assertEquals('y', $_SESSION['x'] ?? 'sam');
        unset($_SESSION['x']);
        $this->assertEquals('sam', $_SESSION['x'] ?? 'sam');
        $_SESSION['a'] = 'b';
        $_SESSION['a'] = 'c';
        $this->assertEquals('c', $_SESSION['a'] ?? 'sam');
        $_SESSION = [];
        $this->assertEquals('sam', $_SESSION['a'] ?? 'sam');

        while (ob_get_level() > $level_before) {
            ob_end_clean();
        }
    }
}
