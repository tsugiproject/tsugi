<?php

require "src/Core/LTIX.php";
require "src/Util/PDOX.php";

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

}
