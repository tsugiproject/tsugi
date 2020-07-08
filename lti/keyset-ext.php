<?php

require_once "../config.php";
require_once "../util/helpers.php";

use Tsugi\Util\U;

// See the end of the file for some documentation references

$rows = $PDOX->allRowsDie(
    "SELECT DISTINCT pubkey FROM {$CFG->dbprefix}lti_external WHERE pubkey IS NOT NULL"
);

if ( count($rows) < 1 ) die("Could not load keys");

// $pubkey = "-----BEGIN PUBLIC KEY-----
// MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvESXFmlzHz+nhZXTkjo2 9SBpamCzkd7SnpMXgdFEWjLfDeOu0D3JivEEUQ4U67xUBMY9voiJsG2oydMXjgkm GliUIVg+rhyKdBUJu5v6F659FwCj60A8J8qcstIkZfBn3yyOPVwp1FHEUSNvtbDL SRIHFPv+kh8gYyvqz130hE37qAVcaNME7lkbDmH1vbxi3D3A8AxKtiHs8oS41ui2 MuSAN9MDb7NjAlFkf2iXlSVxAW5xSek4nHGr4BJKe/13vhLOvRUCTN8h8z+SLORW abxoNIkzuAab0NtfO/Qh0rgoWFC9T69jJPAPsXMDCn5oQ3xh/vhG0vltSSIzHsZ8 pwIDAQAB
// -----END PUBLIC KEY-----";

// https://8gwifi.org/jwkconvertfunctions.jsp
// https://github.com/nov/jose-php/blob/master/src/JOSE/JWK.php
// https://github.com/nov/jose-php/blob/master/test/JOSE/JWK_Test.php

$jwks = array();
foreach ( $rows as $row ) {
    $pubkey = $row['pubkey'];
    $components = Helpers::build_jwk($pubkey);
    $jwks[] = $components;
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

$json->keys = $jwks;

echo(json_encode($json, JSON_PRETTY_PRINT));
