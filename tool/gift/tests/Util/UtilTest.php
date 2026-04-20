<?php

require_once __DIR__ . "/../../util.php";

class UtilTest extends \PHPUnit\Framework\TestCase
{
    public function testSomething() {
        $ent = htmlentities('<>');
        $this->assertEquals($ent, "&lt;&gt;");
    }
}

