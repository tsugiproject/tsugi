<?php
require_once "../../config.php";
require_once $CFG->dirroot."/db.php";
require_once $CFG->dirroot."/lib/lti_util.php";

session_start();
header('Content-type: application/json');

// Sanity checks
if ( !isset($_SESSION['lti']) ) {
	die('This tool need to be launched using LTI');
}
$LTI = $_SESSION['lti'];
if ( !isset($LTI['user_id']) || !isset($LTI['link_id']) ) {
	die('A user_id and link_id are required for this tool to function.');
}

$p = $CFG->dbprefix;
if ( isset($_GET['game']) ) { // I am player 1 since I made this game
	$stmt = $db->prepare("SELECT play1, play2, displayname FROM {$p}rps 
		LEFT JOIN {$p}lti_user ON {$p}rps.user2_id = {$p}lti_user.user_id
		WHERE rps_guid = :GUID");
	$stmt->execute(array(":GUID" => $_GET['game']));
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	if ( $row === FALSE ) {
		echo('{ "error" : "Row not found"}');
		return;
	}
	if ( isset($row['play2']) ) {
		$tie = $row['play1'] == $row['play2'];
		$row['tie'] = $tie;
		$lose = (($row['play1'] + 1) % 3) == $row['play2'];
		$row['win'] = ! $lose;
	}
	echo(json_encode($row));
	return;
}

$play = isset($_GET['play']) ? $_GET['play']+0 : -1;
if ( $play < 0 || $play > 2 ) {
	echo(json_encode(array("error" => "Bad value for play")));
	return;
}

// Check to see if there is an open game
$stmt = $db->prepare("SELECT rps_guid, play1, play2, displayname FROM {$p}rps 
	LEFT JOIN {$p}lti_user ON {$p}rps.user1_id = {$p}lti_user.user_id
	WHERE play2 IS NULL ORDER BY started_at ASC LIMIT 1");
$stmt1 = $db->prepare("UPDATE {$p}rps SET user2_id = :U2ID, play2 = :PLAY
	WHERE rps_guid = :GUID");

// Check to see if there is an open game we can complete
$db->beginTransaction();
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ( $row == FALSE ) {
	$db->rollBack();
} else {
	$stmt1->execute(array(":U2ID" => $LTI['user_id'], ":PLAY" => $play,
		":GUID" => $row['rps_guid']));
	$db->commit();
	$tie = $play == $row['play1'];
	$row['tie'] = $tie;
	// I am player 2 because I finshed this game
	$lose = (($play + 1) % 3) == $row['play1'];
	$row['win'] = ! $lose;
	echo(json_encode($row));
	return;
}

// Start a new game...
$guid = uniqid();
$stmt = $db->prepare("INSERT INTO {$p}rps 
	(rps_guid, link_id, user1_id, play1, started_at) 
	VALUES ( :GUID, :LID, :UID, :PLAY, NOW() )");
$stmt->execute(array(":GUID" => $guid, ":LID" => $LTI['link_id'],
	":UID" => $LTI['user_id'], ":PLAY" => $play));

echo(json_encode(array("guid" => $guid)));

