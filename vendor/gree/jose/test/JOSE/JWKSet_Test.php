<?php

class JOSE_JWKSet_Test extends JOSE_TestCase {
    function testJWKInput() {
        $key = new JOSE_JWK(array(
            'kty' => 'RSA',
            'e' => 'AQAB',
            'n' => 'x9vNhcvSrxjsegZAAo4OEuoZOV_oxINEeWneJYcz...'
        ));
        $jwks = new JOSE_JWKSet($key);
        $this->assertEquals('{"keys":[{"kty":"RSA","e":"AQAB","n":"x9vNhcvSrxjsegZAAo4OEuoZOV_oxINEeWneJYcz...","kid":"Nxz0OiuV92r008w3aI60jWb9tCuT0SixwtyllpaIzW0"}]}', $jwks->toString());
    }

    function testArrayInput() {
        $key = array(
            'kty' => 'RSA',
            'e' => 'AQAB',
            'n' => 'x9vNhcvSrxjsegZAAo4OEuoZOV_oxINEeWneJYcz...'
        );
        $jwks = new JOSE_JWKSet($key);
        $this->assertEquals('{"keys":[{"kty":"RSA","e":"AQAB","n":"x9vNhcvSrxjsegZAAo4OEuoZOV_oxINEeWneJYcz..."}]}', $jwks->toString());
    }

    function test__toString() {
        $key = array(
            'kty' => 'RSA',
            'e' => 'AQAB',
            'n' => 'x9vNhcvSrxjsegZAAo4OEuoZOV_oxINEeWneJYcz...'
        );
        $jwks = new JOSE_JWKSet($key);
        $this->assertEquals('{"keys":[{"kty":"RSA","e":"AQAB","n":"x9vNhcvSrxjsegZAAo4OEuoZOV_oxINEeWneJYcz..."}]}', sprintf('%s', $jwks));
    }
}