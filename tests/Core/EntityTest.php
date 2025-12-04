<?php

require_once("src/Core/Entity.php");
require_once("src/Core/JsonTrait.php");
use \Tsugi\Core\Entity;

class EntityTest extends \PHPUnit\Framework\TestCase
{
    public function testInstantiation() {
        $entity = new \Tsugi\Core\Entity();
        $this->assertInstanceOf(\Tsugi\Core\Entity::class, $entity, 'Entity should instantiate correctly');
    }

}
