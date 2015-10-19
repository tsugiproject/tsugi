<?php
require_once("../../../config.php");
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once("../locations.php");

use \Tsugi\Core\LTIX;

$address = isset($_GET['address']) ? $_GET['address'] : false;
header('Content-Type: application/json; charset=utf-8');

if ( $address === false ) {
    sort($LOCATIONS);
    echo(jsonIndent(json_encode($LOCATIONS)));
    return;
}

die('yo');
