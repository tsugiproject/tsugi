<?php

use \Tsugi\Crypt\AesOpenSSL;

// To be removed / deprecated
use \Tsugi\Crypt\Aes;
use \Tsugi\Crypt\AesCtr;

require_once('src/Crypt/AesOpenSSL.php');
require_once('src/Crypt/Aes.php');
require_once('src/Crypt/AesCtr.php');

// From: http://www.movable-type.co.uk/scripts/aes-php.html

class AesTest extends \PHPUnit\Framework\TestCase
{
    public function testOpenSSLNull() {
        $pw = 'L0ck it up saf3';
        $pt = 'pssst ... đon’t tell anyøne!';

        $encr = AesOpenSSL::encrypt(null, $pw) ;
        $this->assertNull($encr);
        $encr = AesOpenSSL::encrypt($pt, null) ;
        $this->assertNull($encr);
        $encr = AesOpenSSL::encrypt(null, null) ;
        $this->assertNull($encr);

        $decr = AesOpenSSL::decrypt(null, $pw) ;
        $this->assertNull($decr);
        $decr = AesOpenSSL::decrypt($pt, null) ;
        $this->assertNull($decr);
        $decr = AesOpenSSL::decrypt(null, null) ;
        $this->assertNull($decr);

    }

    public function testOpenSSL() {
        $pw = 'L0ck it up saf3';
        $pt = 'pssst ... đon’t tell anyøne!';
        $encr = AesOpenSSL::encrypt($pt, $pw) ;
        $this->assertNotEquals($encr,$pw);
        $this->assertNotEquals($encr,$pt);
        $decr = AesOpenSSL::decrypt($encr, $pw);
        $this->assertEquals($decr,$pt);
        $this->assertNotEquals($encr,$pw);
        $decr = AesOpenSSL::decrypt($encr, $pw."x");
        $this->assertNotEquals($decr,$pt);

        // Test legacy
        $decr = AesCtr::decrypt($encr, $pw, 256);
        $this->assertEquals($decr,$pt);
        $this->assertNotEquals($encr,$pw);
        $decr = AesCtr::decrypt($encr, $pw."x", 256);
        $this->assertNotEquals($decr,$pt);
    }

    public function testLegacy() {
        $pw = 'L0ck it up saf3';
        $pt = 'pssst ... đon’t tell anyøne!';

        // This is just to make sure decrypt can handle old encrypted secrets
        $encr = AesCtr::legacyEncrypt($pt, $pw, 256) ;
        $this->assertNotEquals($encr,$pw);
        $this->assertNotEquals($encr,$pt);
        $decr = AesCtr::decrypt($encr, $pw, 256);
        $this->assertEquals($decr,$pt);
        $this->assertNotEquals($encr,$pw);
        $decr = AesCtr::decrypt($encr, $pw."x", 256);
        $this->assertNotEquals($decr,$pt);
    }

    public function testGet() {
        $pw = 'L0ck it up saf3';
        $pt = 'pssst ... đon’t tell anyøne!';
        $encr = AesCtr::encrypt($pt, $pw, 256) ;
        $this->assertNotEquals($encr,$pw);
        $this->assertNotEquals($encr,$pt);
        $decr = AesCtr::decrypt($encr, $pw, 256);
        $this->assertEquals($decr,$pt);
        $this->assertNotEquals($encr,$pw);
        $decr = AesCtr::decrypt($encr, $pw."x", 256);
        $this->assertNotEquals($decr,$pt);
    }

    public function testShift() {
        $ival = 256;
        $sh = AesCtr::urs($ival, 1);
        $this->assertEquals($sh,128);

        // This is super funky and probably means the moveable-type code was insecure
        // but we don't use it for encrypting any more :)
        // We put this here to make sure that we don't break in 7.4 and earlier and 8.1 and later
        // since 8.1 insisted on ( (int) $var ) to be added
        $fval = -0.9999999;
        $sh = AesCtr::urs($fval, 1);
        $this->assertEquals($sh,0);
        $fval = 0.9999999;
        $sh = AesCtr::urs($fval, 1);
        $this->assertEquals($sh,0);
    }

}

?>
