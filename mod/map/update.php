<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

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

if ( isset($_POST['allow_name']) && isset($_POST['allow_email']) &&
    isset($_POST['allow_first']) ) {
	$sql = "INSERT INTO {$p}context_map 
		(context_id, user_id, name, email, first, updated_at) 
		VALUES ( :CID, :UID, :NAME, :EMAIL, :FIRST, NOW() ) 
		ON DUPLICATE KEY 
		UPDATE name = :NAME, email = :EMAIL, first = :FIRST, updated_at = NOW()";
	$stmt = $pdo->prepare($sql);
	$stmt->execute(array(
		':CID' => $LTI['context_id'],
		':UID' => $LTI['user_id'],
		':NAME' => $_POST['allow_name']+0,
		':FIRST' => $_POST['allow_first']+0,
		':EMAIL' => $_POST['allow_email']+0));
    echo(json_encode(array('status'=> 'success', 'email' =>  $_POST['allow_email'],
        'name' => $_POST['allow_name'], 'first' => $_POST['allow_first'])));
    return;
}
echo(json_encode(array('status'=> 'failure')));
