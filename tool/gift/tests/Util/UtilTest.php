<?php

require_once "util.php";

class UtilTest extends \PHPUnit\Framework\TestCase
{
    public function testSomething() {
        $ent = htmlentities('<>');
        $this->assertEquals($ent, "&lt;&gt;");
    }
}

