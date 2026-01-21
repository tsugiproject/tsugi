<?php

require_once "src/Util/FileRouter.php";

class FileRouterTest extends \PHPUnit\Framework\TestCase
{
    public function testIndent() {
        $x = new \Tsugi\Util\FileRouter();
        // This will only run if git is available on the system
        $this->assertTrue(is_object($x));
    }
}
