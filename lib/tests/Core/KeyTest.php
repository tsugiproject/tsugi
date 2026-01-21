<?php

require_once("src/Core/Key.php");
use \Tsugi\Core\Key;

class KeyTest extends \PHPUnit\Framework\TestCase
{
    public function testInstantiation() {
        $key = new \Tsugi\Core\Key();
        $this->assertInstanceOf(\Tsugi\Core\Key::class, $key, 'Key should instantiate correctly');
        $this->assertInstanceOf(\Tsugi\Core\Entity::class, $key, 'Key should extend Entity');
    }

}
