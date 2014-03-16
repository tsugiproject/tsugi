<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

session_start();

// Sanity checks
$LTI = requireData(array('user_id', 'link_id', 'role','context_id'));

$p = $CFG->dbprefix;
if ( isset($_POST['lat']) && isset($_POST['lng']) ) {
    if ( abs($_POST['lat']) > 85 || abs($_POST['lng']) > 180 ) {
        echo(json_encode(array('status'=> 'failure', 'lat' => $lat, 'lng' =>  $lng)));
        return;
    }
    $lat = $_POST['lat']+0;
    $lng = $_POST['lng']+0;
	$sql = "INSERT INTO {$p}context_map 
		(context_id, user_id, lat, lng, updated_at) 
		VALUES ( :CID, :UID, :LAT, :LNG, NOW() ) 
		ON DUPLICATE KEY 
		UPDATE lat = :LAT, lng = :LNG, updated_at = NOW()";
	$stmt = $pdo->prepare($sql);
	$stmt->execute(array(
		':CID' => $LTI['context_id'],
		':UID' => $LTI['user_id'],
		':LAT' => $lat,
		':LNG' => $lng));
    echo(json_encode(array('status'=> 'success', 'lat' => $lat, 'lng' =>  $lng)));
    return;
}
echo(json_encode(array('status'=> 'failure')));
