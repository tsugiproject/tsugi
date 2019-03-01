<?php

require_once "../config.php";

use Tsugi\Util\U;
use phpseclib\Crypt\RSA;
use Tsugi\Core\LTIX;

LTIX::getConnection();

// See the end of the file for some documentation references 

$kid = U::get($_GET,"key_id",false);
if ( ! $kid ) die("missing key_id parameter");

$row = $PDOX->rowDie(
    "SELECT lti13_pubkey FROM {$CFG->dbprefix}lti_key 
        WHERE key_id = :KID AND lti13_pubkey IS NOT NULL",
    array(":KID" => $kid)
);
if ( ! $row ) die("Could not load key");

$pubkey = $row['lti13_pubkey'];

// $pubkey = "-----BEGIN PUBLIC KEY----- 
// MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvESXFmlzHz+nhZXTkjo2 9SBpamCzkd7SnpMXgdFEWjLfDeOu0D3JivEEUQ4U67xUBMY9voiJsG2oydMXjgkm GliUIVg+rhyKdBUJu5v6F659FwCj60A8J8qcstIkZfBn3yyOPVwp1FHEUSNvtbDL SRIHFPv+kh8gYyvqz130hE37qAVcaNME7lkbDmH1vbxi3D3A8AxKtiHs8oS41ui2 MuSAN9MDb7NjAlFkf2iXlSVxAW5xSek4nHGr4BJKe/13vhLOvRUCTN8h8z+SLORW abxoNIkzuAab0NtfO/Qh0rgoWFC9T69jJPAPsXMDCn5oQ3xh/vhG0vltSSIzHsZ8 pwIDAQAB
// -----END PUBLIC KEY-----";

// https://8gwifi.org/jwkconvertfunctions.jsp
// https://github.com/nov/jose-php/blob/master/src/JOSE/JWK.php
// https://github.com/nov/jose-php/blob/master/test/JOSE/JWK_Test.php

$key = new RSA();
$key->setPublicKey($pubkey);

$kid = hash('sha256', trim($pubkey));

$jwk = array(
                    'kty' => 'RSA',
                    'alg' => 'RS256',
                    'e' => JOSE_URLSafeBase64::encode($key->publicExponent->toBytes()),
                    'n' => JOSE_URLSafeBase64::encode($key->modulus->toBytes()),
                    'kid' => $kid,
                );
                if ($key->exponent != $key->publicExponent) {
                    $components = array_merge($components, array(
                        'd' => JOSE_URLSafeBase64::encode($key->exponent->toBytes())
                    ));
                }

// echo(json_encode($jwk));
// echo("\n");

header("Content-type: application/json");
// header("Content-type: text/plain");
$json = json_decode(<<<JSON
{
  "keys": [ ]
}
JSON
);

if ( ! $json ) {
    die('Unable to parse JSON '.json_last_error_msg());
}

$json->keys[] = $jwk;

echo(json_encode($json, JSON_PRETTY_PRINT));
