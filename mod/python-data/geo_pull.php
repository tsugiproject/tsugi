<?php

require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once "data_util.php";
require_once "names.php";
require_once "locations.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;
use \Tsugi\Util\Net;

if ( ! isCli() ) die("CLI only");

$p = $CFG->dbprefix;

$rows = $PDOX->allRowsDie("SELECT geo_key, json_content FROM {$p}pydata_geo");

$i = 400;
foreach($rows as $row) {
    // print_r($row);
    $location = $row['geo_key'];
    $sample_data = $row['json_content'];
    $sample_json = json_decode($sample_data);
    if ( $sample_json == null || ( !isset($sample_json->results[0])) ) {
        echo("*** Bad $location json_error=".json_last_error_msg()."\n");
        echo(jsonIndent($sample_data));
        continue;
    }
    $sample_place =  $sample_json->results[0]->place_id;
    // echo("    response=$response place=$sample_place\n");
    $GEO[$location] = $sample_data;
    if ( $i-- < 0 ) break;
}

echo count($GEO)."\n";
$fh = fopen('locations.txt', 'w') or die("can't open file");
fwrite($fh, json_encode($GEO));
fclose($fh);

