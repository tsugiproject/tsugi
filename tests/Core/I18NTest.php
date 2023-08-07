<?php

require_once("src/Core/I18N.php");
use \Tsugi\Core\I18N;

class I18NTest extends \PHPUnit\Framework\TestCase
{
    public function testTrivial() {
        $x = new  \Tsugi\Core\I18N();
        $this->assertTrue(true);
    }

}
