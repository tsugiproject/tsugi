<?php

require_once "src/Core/SessionTrait.php";
require_once "src/UI/Output.php";

use \Tsugi\UI\Output;

class OutputTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct() {
        $OUTPUT = new Output();
        $this->assertTrue(is_object($OUTPUT));
    }


}
