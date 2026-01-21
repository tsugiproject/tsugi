<?php

require_once("src/Core/Annotate.php");
use \Tsugi\Core\Annotate;

class AnnotateTest extends \PHPUnit\Framework\TestCase
{
    public function testInstantiation() {
        $annotate = new \Tsugi\Core\Annotate();
        $this->assertInstanceOf(\Tsugi\Core\Annotate::class, $annotate, 'Annotate should instantiate correctly');
    }

}
