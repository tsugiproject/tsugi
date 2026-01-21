<?php

require_once("src/Core/Mail.php");
use \Tsugi\Core\Mail;

class MailTest extends \PHPUnit\Framework\TestCase
{
    public function testInstantiation() {
        $mail = new \Tsugi\Core\Mail();
        $this->assertInstanceOf(\Tsugi\Core\Mail::class, $mail, 'Mail should instantiate correctly');
    }

}
