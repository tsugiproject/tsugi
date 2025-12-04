<?php

require_once("src/Core/Link.php");
use \Tsugi\Core\Link;

class LinkTest extends \PHPUnit\Framework\TestCase
{
    public function testInstantiation() {
        $link = new \Tsugi\Core\Link();
        $this->assertInstanceOf(\Tsugi\Core\Link::class, $link, 'Link should instantiate correctly');
        $this->assertInstanceOf(\Tsugi\Core\Entity::class, $link, 'Link should extend Entity');
    }

}
