<?php

require_once("src/Core/Cache.php");
use \Tsugi\Core\Cache;

class CacheTest extends \PHPUnit\Framework\TestCase
{
    public function testInstantiation() {
        // Cache is a static class, so we test static methods exist
        $this->assertTrue(method_exists(\Tsugi\Core\Cache::class, 'check'), 'Cache should have check method');
        $this->assertTrue(method_exists(\Tsugi\Core\Cache::class, 'set'), 'Cache should have set method');
    }

}
