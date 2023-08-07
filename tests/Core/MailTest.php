<?php

require_once("src/Core/Mail.php");
use \Tsugi\Core\Mail;

class MailTest extends \PHPUnit\Framework\TestCase
{
    public function testTrivial() {
        $x = new  \Tsugi\Core\Mail();
        $this->assertTrue(true);
    }

}
