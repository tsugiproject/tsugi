<?php

use phpseclib\Crypt\RSA;
use Tsugi\Core\LTIX;

LTIX::getConnection();

class Helpers {
  public static function build_jwk($pubkey) {
    $key = new RSA();
    $key->setPublicKey($pubkey);
    if ( ! $key->publicExponent ) die('Invalid public key');

    $kid = LTIX::getKidForKey($pubkey);

    $components = array(
                    'kty' => 'RSA',
                    'alg' => 'RS256',
                    'e' => JOSE_URLSafeBase64::encode($key->publicExponent->toBytes()),
                    'n' => JOSE_URLSafeBase64::encode($key->modulus->toBytes()),
                    'kid' => $kid,
                    'use' => 'sig',
    );

    if ($key->exponent != $key->publicExponent) {
        $components = array_merge($components, array(
        'd' => JOSE_URLSafeBase64::encode($key->exponent->toBytes())
        ));
    }

    return $components;
  }
}
