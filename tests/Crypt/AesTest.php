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

        $encr = AesOpenSSL::encrypt(null, $pw);
        $this->assertNull($encr, 'Encrypting null plaintext should return null');
        $encr = AesOpenSSL::encrypt($pt, null);
        $this->assertNull($encr, 'Encrypting with null password should return null');
        $encr = AesOpenSSL::encrypt(null, null);
        $this->assertNull($encr, 'Encrypting with both null should return null');

        $decr = AesOpenSSL::decrypt(null, $pw);
        $this->assertNull($decr, 'Decrypting null ciphertext should return null');
        $decr = AesOpenSSL::decrypt($pt, null);
        $this->assertNull($decr, 'Decrypting with null password should return null');
        $decr = AesOpenSSL::decrypt(null, null);
        $this->assertNull($decr, 'Decrypting with both null should return null');

    }

    public function testOpenSSL() {
        $pw = 'L0ck it up saf3';
        $pt = 'pssst ... đon’t tell anyøne!';
        $encr = AesOpenSSL::encrypt($pt, $pw);
        $this->assertNotEquals($encr, $pw, 'Encrypted text should not equal password');
        $this->assertNotEquals($encr, $pt, 'Encrypted text should not equal plaintext');
        $decr = AesOpenSSL::decrypt($encr, $pw);
        $this->assertEquals($decr, $pt, 'Decryption with correct password should return original plaintext');
        $this->assertNotEquals($encr, $pw, 'Encrypted text should not equal password');
        $decr = AesOpenSSL::decrypt($encr, $pw."x");
        $this->assertNotEquals($decr, $pt, 'Decryption with wrong password should not return original plaintext');

        // Test legacy
        $decr = AesCtr::decrypt($encr, $pw, 256);
        $this->assertEquals($decr, $pt, 'Legacy decrypt should work with OpenSSL encrypted data');
        $this->assertNotEquals($encr, $pw, 'Encrypted text should not equal password');
        $decr = AesCtr::decrypt($encr, $pw."x", 256);
        $this->assertNotEquals($decr, $pt, 'Legacy decrypt with wrong password should fail');
    }

    public function testLegacy() {
        $pw = 'L0ck it up saf3';
        $pt = 'pssst ... đon’t tell anyøne!';

        // This is just to make sure decrypt can handle old encrypted secrets
        $encr = AesCtr::legacyEncrypt($pt, $pw, 256);
        $this->assertNotEquals($encr, $pw, 'Legacy encrypted text should not equal password');
        $this->assertNotEquals($encr, $pt, 'Legacy encrypted text should not equal plaintext');
        $decr = AesCtr::decrypt($encr, $pw, 256);
        $this->assertEquals($decr, $pt, 'Legacy decryption with correct password should return original plaintext');
        $this->assertNotEquals($encr, $pw, 'Encrypted text should not equal password');
        $decr = AesCtr::decrypt($encr, $pw."x", 256);
        $this->assertNotEquals($decr, $pt, 'Legacy decryption with wrong password should fail');
    }

    public function testGet() {
        $pw = 'L0ck it up saf3';
        $pt = 'pssst ... đon’t tell anyøne!';
        $encr = AesCtr::encrypt($pt, $pw, 256);
        $this->assertNotEquals($encr, $pw, 'Encrypted text should not equal password');
        $this->assertNotEquals($encr, $pt, 'Encrypted text should not equal plaintext');
        $decr = AesCtr::decrypt($encr, $pw, 256);
        $this->assertEquals($decr, $pt, 'Decryption with correct password should return original plaintext');
        $this->assertNotEquals($encr, $pw, 'Encrypted text should not equal password');
        $decr = AesCtr::decrypt($encr, $pw."x", 256);
        $this->assertNotEquals($decr, $pt, 'Decryption with wrong password should fail');
    }

    public function testShift() {
        $ival = 256;
        $sh = AesCtr::urs($ival, 1);
        $this->assertEquals($sh, 128, 'Unsigned right shift of 256 by 1 should equal 128');

        // This is super funky and probably means the moveable-type code was insecure
        // but we don't use it for encrypting any more :)
        // We put this here to make sure that we don't break in 7.4 and earlier and 8.1 and later
        // since 8.1 insisted on ( (int) $var ) to be added
        $fval = -0.9999999;
        $sh = AesCtr::urs($fval, 1);
        $this->assertEquals($sh, 0, 'Unsigned right shift of negative float should equal 0');
        $fval = 0.9999999;
        $sh = AesCtr::urs($fval, 1);
        $this->assertEquals($sh, 0, 'Unsigned right shift of positive float less than 1 should equal 0');
    }

}

?>
