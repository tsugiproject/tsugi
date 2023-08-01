<?php

require_once("src/Core/Result.php");
use \Tsugi\Core\Result;

class ResultTest extends \PHPUnit\Framework\TestCase
{
    public function testTrivial() {
        $x = new  \Tsugi\Core\Result();
        $this->assertTrue(true);
    }

}
