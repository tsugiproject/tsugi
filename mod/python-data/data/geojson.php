<?php
require_once("../../../config.php");
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once("../locations.php");

use \Tsugi\Util\Net;

$expire_seconds = 24*60*60;  // Keep in cache for a day
$expire_seconds = 120;  // Keep in cache for a day

$p = $CFG->dbprefix;

$address = isset($_GET['address']) ? $_GET['address'] : false;
// header('Content-Type: application/json; charset=utf-8');
header('Content-Type: text/plain; charset=utf-8');

if ( $address === false ) {
    sort($LOCATIONS);
    echo(jsonIndent(json_encode($LOCATIONS)));
    return;
}

$where = array_search($address, $LOCATIONS);
if ( $where === false ) {
    http_response_code(400);
    $retval = array('error' => 'Address not found in the list of available locations',
    'locations' => $LOCATIONS);
    echo(jsonIndent(json_encode($retval)));
    return;
}

// Check to see if we already have this in the cache

$address_sha256 = lti_sha256($address);
// echo("address=$address address_sha256=$address_sha256\n");

$row = $PDOX->rowDie("SELECT json_content, updated_at, NOW() as now 
    FROM {$p}pydata_geo WHERE geo_sha256 = :AD",
    array(':AD' => $address_sha256)
);

$json_content = false;
$updated_at = false;
if ( $row !== false && strlen($row['json_content']) > 0 ) {
    $now_str = $row['now'];
    $now = strtotime($now_str);
    $updated_at = $row['updated_at'];
    $updated_time = strtotime($updated_at);
    $datediff = $now - $updated_time;
    $json_content = json_decode($row['json_content']);
    if ( $json_content == null ) {
        error_log("JSON error in cache for $address: ".json_last_error_msg());
    }
    if ( $json_content != null && $datediff < $expire_seconds ) {
        error_log("Retrieved $address from cache delta=$datediff updated_at=".$row['updated_at']);
        echo(jsonIndent(json_encode($json_content)));
        return;
    }
}

// Must retrieve the information
$getUrl = 'http://maps.googleapis.com/maps/api/geocode/json?sensor=false&address='.urlencode($address);

$data = Net::doGet($getUrl);
$response = Net::getLastHttpResponse();
$json_data = json_decode($data);
if ( $json_data  == null ) {
    error_log("JSON error from Google for $address: ".json_last_error_msg());
    error_log($data);
}

// If there is a problem and we have cache even if expired, use cached copy
if ( $json_data == null || $response != 200 ) {
    if ( $json_content != null ) {
        error_log("Error $response on $getUrl - cached copy used");
        echo(jsonIndent(json_encode($json_content)));
        return;
    }
    error_log("Error $response on $getUrl");
    http_response_code($response);
    $retval = array('error' => 'Failure to connect to Google',
        "url" => $getUrl,
        "response" => $response,
        "data" => $data);
    echo(jsonIndent(json_encode($retval)));
    return;
}
$PDOX->queryDie("INSERT INTO {$p}pydata_geo 
    (geo_key, geo_sha256, json_content, created_at, updated_at) VALUES 
    ( :KEY, :SHA, :JSON, NOW(), NOW() )
    ON DUPLICATE KEY
    UPDATE json_content = :JSON, updated_at = NOW()",
    array(':SHA' => $address_sha256,
        ':KEY' => $address,
        ':JSON' => jsonIndent(json_encode($json_data))
    )
);

if ( $json_content != null ) {
    error_log("Updated cache for $address updated_at=$updated_at");
} else {
    error_log("Inserted $address into cache");
}

echo(jsonIndent(json_encode($json_data))."\n");
