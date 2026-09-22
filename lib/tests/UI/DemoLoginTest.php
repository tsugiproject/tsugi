<?php

require_once "src/Config/ConfigInfo.php";
require_once "src/Core/LTIX.php";
require_once "src/UI/DemoLogin.php";
require_once "src/Util/U.php";

use \Tsugi\Core\LTIX;
use \Tsugi\UI\DemoLogin;

class DemoLoginTest extends \PHPUnit\Framework\TestCase
{
    private $originalCFG;

    protected function setUp(): void
    {
        global $CFG;
        $this->originalCFG = $CFG;
        $CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__), 'http://localhost');
        $CFG->wwwroot = 'http://localhost/tsugi';
        $CFG->apphome = 'http://localhost/app';
    }

    protected function tearDown(): void
    {
        global $CFG;
        $CFG = $this->originalCFG;
    }

    public function testDisabledByDefault() {
        $this->assertFalse(DemoLogin::isEnabled());
    }

    public function testDisabledWhenFlagTrueButSecretMissing() {
        global $CFG;
        $CFG->demo_login = true;
        $CFG->demo_secret = false;
        $this->assertFalse(DemoLogin::isEnabled());
    }

    public function testDisabledWhenSecretEmpty() {
        global $CFG;
        $CFG->demo_login = true;
        $CFG->demo_secret = '';
        $this->assertFalse(DemoLogin::isEnabled());
    }

    public function testEnabledWhenFlagAndSecretSet() {
        global $CFG;
        $CFG->demo_login = true;
        $CFG->demo_secret = 's3cret';
        $this->assertTrue(DemoLogin::isEnabled());
    }

    public function testSecretMatchesPlaintext() {
        global $CFG;
        $CFG->demo_login = true;
        $CFG->demo_secret = 's3cret';
        $this->assertTrue(DemoLogin::secretMatches('s3cret'));
        $this->assertFalse(DemoLogin::secretMatches('wrong'));
        $this->assertFalse(DemoLogin::secretMatches(''));
    }

    public function testSecretMatchesSha256() {
        global $CFG;
        $CFG->demo_login = true;
        $CFG->demo_secret = 'sha256:'.hash('sha256', 's3cret');
        $this->assertTrue(DemoLogin::secretMatches('s3cret'));
        $this->assertFalse(DemoLogin::secretMatches('wrong'));
    }

    public function testSecretDoesNotMatchWhenDisabled() {
        global $CFG;
        $CFG->demo_login = false;
        $CFG->demo_secret = 's3cret';
        $this->assertFalse(DemoLogin::secretMatches('s3cret'));
    }

    public function testFifteenPersonas() {
        $personas = DemoLogin::personas();
        $this->assertCount(15, $personas);
        $this->assertArrayHasKey('instructor-01', $personas);
        $this->assertArrayHasKey('instructor-05', $personas);
        $this->assertArrayHasKey('student-01', $personas);
        $this->assertArrayHasKey('student-10', $personas);
        $this->assertSame('instructor01@notgoogle.com', $personas['instructor-01']['email']);
        $this->assertSame('googlemail:student10@notgoogle.com', $personas['student-10']['user_key']);
        $instructors = array_filter($personas, function($p) { return ! empty($p['instructor']); });
        $students = array_filter($personas, function($p) { return empty($p['instructor']); });
        $this->assertCount(5, $instructors);
        $this->assertCount(10, $students);
    }

    public function testPersonaLookup() {
        $this->assertNull(DemoLogin::persona('nope'));
        $p = DemoLogin::persona('instructor-02');
        $this->assertNotNull($p);
        $this->assertSame('Instructor 02', $p['label']);
        $this->assertTrue($p['instructor']);
    }

    public function testSessionOptionsInstructor() {
        $opts = DemoLogin::sessionOptions(DemoLogin::persona('instructor-01'));
        $this->assertSame(1, $opts['create_courses']);
        $this->assertSame(LTIX::ROLE_INSTRUCTOR, $opts['membership_role']);
        $this->assertTrue($opts['force_membership_role']);
    }

    public function testSessionOptionsStudent() {
        $opts = DemoLogin::sessionOptions(DemoLogin::persona('student-01'));
        $this->assertSame(0, $opts['create_courses']);
        $this->assertSame(LTIX::ROLE_LEARNER, $opts['membership_role']);
        $this->assertTrue($opts['force_membership_role']);
    }

    public function testSimulateUrl() {
        $this->assertSame('http://localhost/app/login/simulate', DemoLogin::simulateUrl());
    }
}
