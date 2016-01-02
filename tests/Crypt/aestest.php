<?php

use \Tsugi\Crypt\Aes;
use \Tsugi\Crypt\AesCtr;

require_once('src/Crypt/Aes.php');
require_once('src/Crypt/AesCtr.php');

// From: http://www.movable-type.co.uk/scripts/aes-php.html

class AesTest extends PHPUnit_Framework_TestCase
{
    public function testGet() {
        $pw = 'L0ck it up saf3';
        $pt = 'pssst ... đon’t tell anyøne!';
        $encr = AesCtr::encrypt($pt, $pw, 256) ;
        $this->assertNotEquals($encr,$pw);
        $this->assertNotEquals($encr,$pt);
        $decr = AesCtr::decrypt($encr, $pw, 256);
        $this->assertEquals($decr,$pt);
    }

}

?>
