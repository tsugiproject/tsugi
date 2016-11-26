<?php

require_once "src/Core/LTIX.php";
require_once "src/Util/PDOX.php";
require_once "tests/Mock/MockSession.php";

use \Tsugi\Core\LTIX;

class LTIXTest extends PHPUnit_Framework_TestCase
{
    public function testAnon() {
        // $LAUNCH = LTIX::requireData(LTIX::NONE);
    }

    public function testCSRF() {
        // $LAUNCH = LTIX::requireData(LTIX::NONE);
        $this->assertEquals(LTIX::ltiParameter('bob', 'sam'), 'sam');
    }

    // Mostly make sure this does not blow up with a traceback
    // The null code paths depends on the existence of the $_SESSION superglobal
    // Which probably is not there in a unit test
    public function testWrappedSessionNothing() {
        $sess = null;
        $this->assertEquals(LTIX::wrapped_session_get($sess,'x', 'sam'), 'sam');
        LTIX::wrapped_session_put($sess,'x', 'y');
        LTIX::wrapped_session_forget($sess,'x');
        LTIX::wrapped_session_put($sess,'a', 'b');
        LTIX::wrapped_session_put($sess,'a', 'c');
        LTIX::wrapped_session_flush($sess);
    }

    public function exercise($sess) {
        LTIX::wrapped_session_put($sess,'x', 'y');
        $this->assertEquals(LTIX::wrapped_session_get($sess,'x', 'sam'), 'y');
        LTIX::wrapped_session_forget($sess,'x');
        $this->assertEquals(LTIX::wrapped_session_get($sess,'x', 'sam'), 'sam');
        LTIX::wrapped_session_put($sess,'a', 'b');
        $this->assertEquals(LTIX::wrapped_session_get($sess,'a', 'sam'), 'b');
        LTIX::wrapped_session_put($sess,'a', 'c');
        $this->assertEquals(LTIX::wrapped_session_get($sess,'a', 'sam'), 'c');
        LTIX::wrapped_session_flush($sess);
        $this->assertEquals(LTIX::wrapped_session_get($sess,'a', 'sam'), 'sam');
        for($i=1; $i< 100; $i++) {
            LTIX::wrapped_session_put($sess, $i, $i*$i);
        }
        $this->assertEquals(LTIX::wrapped_session_get($sess, 10, 42), 100);
        LTIX::wrapped_session_flush($sess);
        $this->assertEquals(LTIX::wrapped_session_get($sess, 10, 42), 42);
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
