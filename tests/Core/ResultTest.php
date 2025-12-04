<?php

require_once("src/Core/Result.php");
use \Tsugi\Core\Result;

class ResultTest extends \PHPUnit\Framework\TestCase
{
    public function testInstantiation() {
        $result = new \Tsugi\Core\Result();
        $this->assertInstanceOf(\Tsugi\Core\Result::class, $result, 'Result should instantiate correctly');
        $this->assertInstanceOf(\Tsugi\Core\Entity::class, $result, 'Result should extend Entity');
    }

}
