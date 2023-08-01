<?php

require_once("src/Core/User.php");
require_once("src/Core/JsonTrait.php");
use \Tsugi\Core\User;

class UserTest extends \PHPUnit\Framework\TestCase
{
    public function testTrivial() {
        $x = new  \Tsugi\Core\User();
        $this->assertTrue(true);
    }

}
