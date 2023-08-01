<?php

require_once("src/Core/Rest.php");
use \Tsugi\Core\Rest;

class RestTest extends \PHPUnit\Framework\TestCase
{
    public function testTrivial() {
        $x = new  \Tsugi\Core\Rest();
        $this->assertTrue(true);
    }

}
