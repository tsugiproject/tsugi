<?php

require_once("src/Core/Cache.php");
use \Tsugi\Core\Cache;

class CacheTest extends \PHPUnit\Framework\TestCase
{
    public function testTrivial() {
        $x = new  \Tsugi\Core\Cache();
        $this->assertTrue(true);
    }

}
