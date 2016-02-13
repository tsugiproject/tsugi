<?php

require "src/Util/LTI.php";

use \Tsugi\Util\LTI;

class LTITest extends PHPUnit_Framework_TestCase
{
    public function testIndent() {
        $retval = LTI::jsonIndent('{ "a": "b"; "c": "d"; }');
        $this->assertEquals($retval,'{
   "a": "b"; "c": "d"; 
}');
    }

}
