<?php

require_once("src/Core/Rest.php");
use \Tsugi\Core\Rest;

class RestTest extends \PHPUnit\Framework\TestCase
{
    public function testInstantiation() {
        $rest = new \Tsugi\Core\Rest();
        $this->assertInstanceOf(\Tsugi\Core\Rest::class, $rest, 'Rest should instantiate correctly');
    }

}
