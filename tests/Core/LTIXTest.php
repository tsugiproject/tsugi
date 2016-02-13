<?php

require "src/Core/LTIX.php";
require "src/Util/PDOX.php";

use \Tsugi\Core\LTIX;

class LTIXTest extends PHPUnit_Framework_TestCase
{
    public function testAnon() {
        // $LTI = LTIX::requireData(LTIX::NONE);
    }

    public function testCSRF() {
        // $LTI = LTIX::requireData(LTIX::NONE);
        $this->assertEquals(LTIX::sessionGet('bob', 'sam'), 'sam');
    }

}
