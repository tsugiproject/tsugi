<?php

use phpseclib\Crypt\RSA;
use phpseclib\Crypt\RC2;

class JOSE_JWK_Test extends JOSE_TestCase {
    function testConstructWithoutKTY() {
        $this->setExpectedException('JOSE_Exception_InvalidFormat');
        new JOSE_JWK(array('n' => 'n'));
    }

    function testToString() {
        $jwk = new JOSE_JWK(array('kty' => 'RSA', 'e' => 'e', 'n' => 'n'));
        $this->assertEquals('{"kty":"RSA","e":"e","n":"n","kid":"lPd1Hx7fpYY23pQVKnFvOEtk_jFe5EV8ZISUGTSGA_U"}', $jwk->toString());
    }

    function test__toString() {
        $jwk = new JOSE_JWK(array('kty' => 'RSA', 'e' => 'e', 'n' => 'n'));
        $this->assertEquals('{"kty":"RSA","e":"e","n":"n","kid":"lPd1Hx7fpYY23pQVKnFvOEtk_jFe5EV8ZISUGTSGA_U"}', sprintf('%s', $jwk));
    }

    function testEncodeRSAPublicKey() {
        $rsa = new RSA();
        $rsa->loadKey($this->rsa_keys['public']);
        $jwk = JOSE_JWK::encode($rsa);
        $this->assertInstanceOf('JOSE_JWK', $jwk);
        $this->assertEquals('AQAB', $jwk->components['e']);
        $this->assertEquals('x9vNhcvSrxjsegZAAo4OEuoZOV_oxINEeWneJYczS80_bQ1J6lSSJ81qecxXAzCLPlvsFoP4eeUNXSt_G7hP7SAM479N-kY_MzbihJ5LRY9sRzLbQTMeqsmDAmmQe4y3Ke3bvd70r8VOmo5pqM3IPLGwBkTRTQmyRsDQArilg6WtxDUgy5ol2STHFA8E1iCReh9bck8ZaLxzVhYRXZ0nuOKWGRMppocPlp55HVohOItUZh7uSCchLcVAZuhTTNaDLtLIJ6G0yNJvfEieJUhA8wGBoPhD3LMQwQMxTMerpjZhP_qjm6GgeWpKf-iVil86_PSy_z0Vw06_rD0sfXPtlQ', $jwk->components['n']);
        $this->assertNotContains('d', $jwk->components);
    }

    function testEncodeRSAPrivateKey() {
        $rsa = new RSA();
        $rsa->loadKey($this->rsa_keys['private']);
        $jwk = JOSE_JWK::encode($rsa);
        $this->assertInstanceOf('JOSE_JWK', $jwk);
        $this->assertEquals('AQAB', $jwk->components['e']);
        $this->assertEquals('x9vNhcvSrxjsegZAAo4OEuoZOV_oxINEeWneJYczS80_bQ1J6lSSJ81qecxXAzCLPlvsFoP4eeUNXSt_G7hP7SAM479N-kY_MzbihJ5LRY9sRzLbQTMeqsmDAmmQe4y3Ke3bvd70r8VOmo5pqM3IPLGwBkTRTQmyRsDQArilg6WtxDUgy5ol2STHFA8E1iCReh9bck8ZaLxzVhYRXZ0nuOKWGRMppocPlp55HVohOItUZh7uSCchLcVAZuhTTNaDLtLIJ6G0yNJvfEieJUhA8wGBoPhD3LMQwQMxTMerpjZhP_qjm6GgeWpKf-iVil86_PSy_z0Vw06_rD0sfXPtlQ', $jwk->components['n']);
        $this->assertEquals('S3xQjvVh-PJv9tK_gHeJB0nWBx6bewWdakI7Pm9nR30ZNKYtQc15eoESczhjsPe3z_DGJebohZmmx4bzNlQSFBzj4W1TFXFM05oqSi7DfV1jZyzlNSYKsjT0P4gBoziNwc9uDLPWNUFPo_6gF7rJo2r1chix-Oftpt2Sc0SsdyEESBMR5REMccX5gZIhN-DUTN4gt9GNeDRy9h-gNFxgNNtt17HzEg52gbl3UnEuuPXE2wcctE1nxT3WDdtVqb6nbaNfxLiaAWaL2uYBvU2_AvKu1b7VEPmP9pTEMyriVzh4Jb2ZtIUpna518M044GPKs1TgMHSAxpOaQvnpar9lrQ', $jwk->components['d']);
    }

    function testEncodeWithExtraComponents() {
        $rsa = new RSA();
        $rsa->loadKey($this->rsa_keys['private']);
        $jwk = JOSE_JWK::encode($rsa, array(
            'kid' => '12345',
            'use' => 'sig'
        ));
        $this->assertEquals('12345', $jwk->components['kid']);
        $this->assertEquals('sig', $jwk->components['use']);
    }

    function testEncodeWithUnexpectedAlg() {
        $key = new RC2();
        $this->setExpectedException('JOSE_Exception_UnexpectedAlgorithm');
        JOSE_JWK::encode($key);
    }

    function testDecodeRSAPublicKey() {
        $components = array(
            'kty' => 'RSA',
            'e' => 'AQAB',
            'n' => 'x9vNhcvSrxjsegZAAo4OEuoZOV_oxINEeWneJYczS80_bQ1J6lSSJ81qecxXAzCLPlvsFoP4eeUNXSt_G7hP7SAM479N-kY_MzbihJ5LRY9sRzLbQTMeqsmDAmmQe4y3Ke3bvd70r8VOmo5pqM3IPLGwBkTRTQmyRsDQArilg6WtxDUgy5ol2STHFA8E1iCReh9bck8ZaLxzVhYRXZ0nuOKWGRMppocPlp55HVohOItUZh7uSCchLcVAZuhTTNaDLtLIJ6G0yNJvfEieJUhA8wGBoPhD3LMQwQMxTMerpjZhP_qjm6GgeWpKf-iVil86_PSy_z0Vw06_rD0sfXPtlQ'
        );
        $key = JOSE_JWK::decode($components);
        $this->assertInstanceOf('phpseclib\Crypt\RSA', $key);
        $this->assertEquals(
            preg_replace("/\r\n|\r|\n/", '', $this->rsa_keys['public']),
            preg_replace("/\r\n|\r|\n/", '', $key->getPublicKey(RSA::PUBLIC_FORMAT_PKCS1_RAW))
        );
    }

    function testDecodeRSAPrivateKey() {
        $components = array(
            'kty' => 'RSA',
            'e' => 'AQAB',
            'n' => 'x9vNhcvSrxjsegZAAo4OEuoZOV_oxINEeWneJYczS80_bQ1J6lSSJ81qecxXAzCLPlvsFoP4eeUNXSt_G7hP7SAM479N-kY_MzbihJ5LRY9sRzLbQTMeqsmDAmmQe4y3Ke3bvd70r8VOmo5pqM3IPLGwBkTRTQmyRsDQArilg6WtxDUgy5ol2STHFA8E1iCReh9bck8ZaLxzVhYRXZ0nuOKWGRMppocPlp55HVohOItUZh7uSCchLcVAZuhTTNaDLtLIJ6G0yNJvfEieJUhA8wGBoPhD3LMQwQMxTMerpjZhP_qjm6GgeWpKf-iVil86_PSy_z0Vw06_rD0sfXPtlQ',
            'd' => 'S3xQjvVh-PJv9tK_gHeJB0nWBx6bewWdakI7Pm9nR30ZNKYtQc15eoESczhjsPe3z_DGJebohZmmx4bzNlQSFBzj4W1TFXFM05oqSi7DfV1jZyzlNSYKsjT0P4gBoziNwc9uDLPWNUFPo_6gF7rJo2r1chix-Oftpt2Sc0SsdyEESBMR5REMccX5gZIhN-DUTN4gt9GNeDRy9h-gNFxgNNtt17HzEg52gbl3UnEuuPXE2wcctE1nxT3WDdtVqb6nbaNfxLiaAWaL2uYBvU2_AvKu1b7VEPmP9pTEMyriVzh4Jb2ZtIUpna518M044GPKs1TgMHSAxpOaQvnpar9lrQ'
        );
        $this->setExpectedException('JOSE_Exception_UnexpectedAlgorithm');
        JOSE_JWK::decode($components);
    }

    function testDecodeWithUnexpectedAlg() {
        $components = array(
            'kty' => 'EC',
            'crv' => 'crv',
            'x' => 'x',
            'y' => 'y'
        );
        $this->setExpectedException('JOSE_Exception_UnexpectedAlgorithm');
        JOSE_JWK::decode($components);
    }

    function testThumbprint() {
        $rsa = new RSA();
        $rsa->loadKey($this->rsa_keys['public']);
        $jwk = JOSE_JWK::encode($rsa);
        $this->assertInstanceOf('JOSE_JWK', $jwk);
        $this->assertEquals('nuBTimkcSt_AuEsD8Yv3l8CoGV31bu_3gsRDGN1iVKA', $jwk->thumbprint());
        $this->assertEquals('nuBTimkcSt_AuEsD8Yv3l8CoGV31bu_3gsRDGN1iVKA', $jwk->thumbprint('sha256'));
        $this->assertEquals('6v7pXTnQLMiQgvJlPJUdhAUSuGLzgF8C1r3ABAMFet6bc53ea-Pq4ZGbGu3RoAFsNRT1-RhTzDqtqXuLU6NOtw', $jwk->thumbprint('sha512'));
    }

}