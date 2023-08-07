<?php

require_once("src/Core/Annotate.php");
use \Tsugi\Core\Annotate;

class AnnotateTest extends \PHPUnit\Framework\TestCase
{
    public function testTrivial() {
        $x = new  \Tsugi\Core\Annotate();
        $this->assertTrue(true);
    }

}
