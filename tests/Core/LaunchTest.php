<?php

require_once "src/Core/LTIX.php";
require_once "src/Core/Launch.php";
require_once "src/Util/PDOX.php";
require_once "tests/Mock/MockSession.php";

use \Tsugi\Core\LTIX;

class LaunchTest extends PHPUnit_Framework_TestCase
{
    // Mostly make sure this does not blow up with a traceback
    // The null code paths depends on the existence of the $_SESSION superglobal
    // Which probably is not there in a unit test
    public function testWrappedSessionNothing() {
        $launch = new \Tsugi\Core\Launch();
        $this->assertEquals($launch->session_get('x', 'sam'), 'sam');
        $launch->session_put('x', 'y');
        $launch->session_forget('x');
        $launch->session_put('a', 'b');
        $launch->session_put('a', 'c');
        $launch->session_flush();
    }

    public function exercise($sess) {
        $launch = new \Tsugi\Core\Launch();
        $launch->session_object = $sess;
        $launch->session_put('x', 'y');
        $this->assertEquals($launch->session_get('x', 'sam'), 'y');
        $launch->session_forget('x');
        $this->assertEquals($launch->session_get('x', 'sam'), 'sam');
        $launch->session_put('a', 'b');
        $this->assertEquals($launch->session_get('a', 'sam'), 'b');
        $launch->session_put('a', 'c');
        $this->assertEquals($launch->session_get('a', 'sam'), 'c');
        $launch->session_flush();
        $this->assertEquals($launch->session_get('a', 'sam'), 'sam');
        for($i=1; $i< 100; $i++) {
            $launch->session_put($i, $i*$i);
        }
        $this->assertEquals($launch->session_get(10, 42), 100);
        $launch->session_flush();
        $this->assertEquals($launch->session_get(10, 42), 42);
    }

    public function testWrappedSessionArray() {
        $sess = array();
        $this->exercise($sess);
    }

    public function testWrappedSessionObject() {
        $sess = new MockSession();
        $this->exercise($sess);
    }

}
