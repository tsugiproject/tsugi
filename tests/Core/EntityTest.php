<?php

require_once("src/Core/Entity.php");
require_once("src/Core/JsonTrait.php");
use \Tsugi\Core\Entity;

class EntityTest extends \PHPUnit\Framework\TestCase
{
    public function testTrivial() {
        $x = new  \Tsugi\Core\Entity();
        $this->assertTrue(true);
    }

}
