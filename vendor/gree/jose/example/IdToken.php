<?php

// an example of OpenID Connect ID Token implementation

class IdToken {
    var $jwt;

    function __construct($claims = array()) {
        $this->jwt = new JOSE_JWT($claims);
    }

    function sign($private_key_or_secret, $algorithm = 'RS256') {
        $this->jwt = $this->jwt->sign($private_key_or_secret, $algorithm);
        return $this;
    }

    function toString() {
        return $this->jwt->toString();
    }
}

$public_key  = file_get_contents(dirname(__FILE__) . '/../test/fixtures/public_key.pem');
$private_key = file_get_contents(dirname(__FILE__) . '/../test/fixtures/private_key.pem');

$id_token = new IdToken(array(
    'iss' => 'https://gree.net',
    'aud' => 'greeapp_12345',
    'sub' => 'greeuser_12345',
    'iat' => time(),
    'exp' => time() + 1 * 60 * 60
));
$id_token->sign($private_key);

echo $id_token->toString();