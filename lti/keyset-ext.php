<?php

require_once "../config.php";

use Tsugi\Core\LTIX;
use Tsugi\Core\Keyset;

LTIX::getConnection();

use Tsugi\Util\U;

// See the end of the file for some documentation references

// TODO: Fix this when the external tool has migrated to the new signing pattern
$rows = $PDOX->allRowsDie(
    "SELECT DISTINCT pubkey FROM {$CFG->dbprefix}lti_external WHERE pubkey IS NOT NULL"
);

if ( count($rows) < 1 ) die("Could not load keys");

$jwks = array();
foreach ( $rows as $row ) {
    $pubkey = $row['pubkey'];
    $components = Keyset::build_jwk($pubkey);
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
