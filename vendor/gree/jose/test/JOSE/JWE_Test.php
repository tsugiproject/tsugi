<?php

use phpseclib\Crypt\Random;

class JOSE_JWE_Test extends JOSE_TestCase {
    var $plain_text;
    var $rsa_keys;

    function setUp() {
        parent::setUp();
        $this->plain_text = 'Hello World';
    }

    function testToString() {
        $jwe = new JOSE_JWE($this->plain_text);
        $jwe->encrypt($this->rsa_keys['public']);
        $segments = explode('.', $jwe->toString());
        $this->assertEquals(5, count($segments));
    }

    function test__toString() {
        $jwe = new JOSE_JWE($this->plain_text);
        $jwe->encrypt($this->rsa_keys['public']);
        $segments = explode('.', sprintf('%s', $jwe));
        $this->assertEquals(5, count($segments));
    }

    function testEncryptRSA15_A128CBCHS256() {
        $jwe = new JOSE_JWE($this->plain_text);
        $jwe->encrypt($this->rsa_keys['public']);
        $jwe_decoded = JOSE_JWT::decode($jwe->toString());
        $this->assertEquals($this->plain_text, $jwe_decoded->decrypt($this->rsa_keys['private'])->plain_text);
    }

    function testEncryptRSA15_A256CBCHS512() {
        $jwe = new JOSE_JWE($this->plain_text);
        $jwe->encrypt($this->rsa_keys['public'], 'RSA1_5', 'A256CBC-HS512');
        $jwe_decoded = JOSE_JWT::decode($jwe->toString());
        $this->assertEquals($this->plain_text, $jwe_decoded->decrypt($this->rsa_keys['private'])->plain_text);
    }

    function testEncryptRSA15_A128GCM() {
        $jwe = new JOSE_JWE($this->plain_text);
        $this->setExpectedException('JOSE_Exception_UnexpectedAlgorithm');
        $jwe->encrypt($this->rsa_keys['public'], 'RSA1_5', 'A128GCM');
    }

    function testEncryptRSA15_A256GCM() {
        $jwe = new JOSE_JWE($this->plain_text);
        $this->setExpectedException('JOSE_Exception_UnexpectedAlgorithm');
        $jwe->encrypt($this->rsa_keys['public'], 'RSA1_5', 'A256GCM');
    }

    function testEncryptRSAOAEP_A128CBCHS256() {
        $jwe = new JOSE_JWE($this->plain_text);
        $jwe->encrypt($this->rsa_keys['public'], 'RSA-OAEP');
        $jwe_decoded = JOSE_JWT::decode($jwe->toString());
        $this->assertEquals($this->plain_text, $jwe_decoded->decrypt($this->rsa_keys['private'])->plain_text);
    }

    function testEncryptRSAOAEP_A256CBCHS512() {
        $jwe = new JOSE_JWE($this->plain_text);
        $jwe->encrypt($this->rsa_keys['public'], 'RSA-OAEP', 'A256CBC-HS512');
        $jwe_decoded = JOSE_JWT::decode($jwe->toString());
        $this->assertEquals($this->plain_text, $jwe_decoded->decrypt($this->rsa_keys['private'])->plain_text);
    }

    function testEncryptDir_A128CBCHS256() {
        $secret = Random::string(256 / 8);
        $jwe = new JOSE_JWE($this->plain_text);
        $jwe = $jwe->encrypt($secret, 'dir');
        $jwe_decoded = JOSE_JWT::decode($jwe->toString());
        $this->assertEquals($this->plain_text, $jwe_decoded->decrypt($secret)->plain_text);
    }

    function testEncryptA128KW_A128CBCHS256() {
        $jwe = new JOSE_JWE($this->plain_text);
        $this->setExpectedException('JOSE_Exception_UnexpectedAlgorithm');
        $jwe->encrypt($this->rsa_keys['public'], 'A128KW');
    }

    function testEncryptRSA15_Unknown() {
        $jwe = new JOSE_JWE($this->plain_text);
        $this->setExpectedException('JOSE_Exception_UnexpectedAlgorithm');
        $jwe->encrypt($this->rsa_keys['public'], 'RSA1_5', 'Unknown');
    }

    function testEncryptUnknown_A128CBCHS256() {
        $jwe = new JOSE_JWE($this->plain_text);
        $this->setExpectedException('JOSE_Exception_UnexpectedAlgorithm');
        $jwe->encrypt($this->rsa_keys['public'], 'Unknown');
    }

    function testDecryptRSA15_A128CBCHS256() {
        $input = 'eyJhbGciOiJSU0ExXzUiLCJlbmMiOiJBMTI4Q0JDLUhTMjU2In0.A2_4qS-61x17Q9NdT5kE0Qv5vw-D7zqGxACw42qM6l1iIHu31cENA8O5GTUhWordW3f93WY4ap1ZvCHO7pbbCF4NpOIMKjZtHObHRtPnA12zn-JZIxPCUHDtIQ6ucT-B0g5AmKDEDFO78Murz5l9QZH_Tl5t5x5-Asi3BO9Mm4s5dldykMvFxdC1j5IZ1ZBgN243OdKmvkTa0dn9wgjz9XEZHXoX_TKE4kDMyzIgW_U6Y4mP-cfZjQhTZAGwsBEz1kYTbM0bCf-FK3BBktpWZzjp4Y7cL6Zc7CabkNWAmMPcenxOFQZCOTeikmj4xrgZ9uPJ-DwJJNlnW_jPhEaesw.E1-sid2lZsrNOqc9vjgajg.s9vv7y5Qt5MwpA2AEGeuBQ.gH1oQlBSCdMK_jJEtoyWAw';
        $jwe = JOSE_JWE::decode($input);
        $jwe->decrypt($this->rsa_keys['private']);
        $this->assertEquals($this->plain_text, $jwe->plain_text);
    }

    function testDecode() {
        $input = 'eyJhbGciOiJSU0ExXzUiLCJlbmMiOiJBMTI4Q0JDLUhTMjU2In0.A2_4qS-61x17Q9NdT5kE0Qv5vw-D7zqGxACw42qM6l1iIHu31cENA8O5GTUhWordW3f93WY4ap1ZvCHO7pbbCF4NpOIMKjZtHObHRtPnA12zn-JZIxPCUHDtIQ6ucT-B0g5AmKDEDFO78Murz5l9QZH_Tl5t5x5-Asi3BO9Mm4s5dldykMvFxdC1j5IZ1ZBgN243OdKmvkTa0dn9wgjz9XEZHXoX_TKE4kDMyzIgW_U6Y4mP-cfZjQhTZAGwsBEz1kYTbM0bCf-FK3BBktpWZzjp4Y7cL6Zc7CabkNWAmMPcenxOFQZCOTeikmj4xrgZ9uPJ-DwJJNlnW_jPhEaesw.E1-sid2lZsrNOqc9vjgajg.s9vv7y5Qt5MwpA2AEGeuBQ.gH1oQlBSCdMK_jJEtoyWAw';
        $jwe = JOSE_JWE::decode($input);
        $this->assertNull($jwe->plain_text);
        $this->assertEquals(array(
            "alg" => "RSA1_5",
            "enc" => "A128CBC-HS256"
        ), $jwe->header);
    }
}
