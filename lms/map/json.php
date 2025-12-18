<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../../config.php";

LTIX::getConnection();

header('Content-Type: application/json; charset=utf-8');
session_start();

if ( ! isset($CFG->google_map_api_key) ) {
    http_response_code(400);
    echo(json_encode(array('error' => 'There is no MAP api key ($CFG->google_map_api_key)')));
    return;
}

$PDOX = LTIX::getConnection();

$p = $CFG->dbprefix;
$sql = "SELECT U.user_id, P.displayname, P.json 
    FROM {$p}lti_user AS U JOIN {$p}profile AS P
    ON U.profile_id = P.profile_id
    WHERE P.json LIKE '%\"map\":3%' OR P.json LIKE '%\"map\":2%'";
$rows = $PDOX->allRowsDie($sql);
$center = false;
$points = array();
foreach($rows as $row ) {
    if ( !isset($row['json']) ) continue;
    if ( !isset($row['user_id']) ) continue;
    $json = json_decode($row['json']);
    if ( !isset($json->lat) ) continue;
    if ( !isset($json->lng) ) continue;
    if ( !isset($json->map) ) continue;
    $lat = $json->lat+0;
    $lng = $json->lng+0;
    if ( $lat == 0 && $lng == 0 ) continue;
    if ( abs($lat) > 85 ) $lat = 0;
    if ( abs($lng) > 180 ) $lng = 179.9;
    // 0=not set, 1=show nothing, 2=show location, 3=show location+name
    $map = $json->map+0;
    if ( $map < 2 ) continue;
    $name = $row['displayname'];
    if ( ! isset($_SESSION["admin"]) ) {
        if ( $map < 3 ) $name = '';
    }
    $display = $name;
    $points[] = array($lat, $lng, $display);
    if ( isset($_SESSION['id']) && $_SESSION['id'] == $row['user_id'] ) {
        $center = array($lat,$lng);
    }
}
if ( $center === false ) $center = array(42.279070216140425, -83.73981015789798);
$retval = array('center' => $center, 'points' => $points );
echo(json_encode($retval));
