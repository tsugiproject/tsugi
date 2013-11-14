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
$stmt = $db->prepare("SELECT play1, play2, user1_id, user2_id, 
		U1.displayname AS displayname1, U2.displayname AS displayname2 
		FROM {$p}rps
        JOIN {$p}lti_user AS U1 JOIN {$p}lti_user AS U2
        ON {$p}rps.user1_id = U1.user_id AND {$p}rps.user2_id = U2.user_id 
        WHERE link_id = :LI AND play1 IS NOT NULL AND play2 IS NOT NULL");
$stmt->execute(array(":LI" => $LTI['link_id']));
$scores = array();
$games = array();
$scores = array();
$users = array();
while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
	// Accumulate the user_id's mapped to names
	$user1 = $row['user1_id'];
	$user2 = $row['user2_id'];
	if ( !isset($users[$user1]) ){
		$users[$user1] = $row['displayname1'];
		$games[$user1] = 0;
		$scores[$user1] = 0;
	}
	if ( !isset($users[$user2]) ){
		$users[$user2] = $row['displayname2'];
		$games[$user2] = 0;
		$scores[$user2] = 0;
	}
	// Accumulate the games played 
	$games[$user1] = $games[$user1] + 1;
	$games[$user2] = $games[$user2] + 1;

	// Check to see if we had a tie 
	if ( $row['play1'] == $row['play2'] ) continue;

	// See is player 1 lost..
    if ( (($row['play1'] + 1) % 3) == $row['play2'] ) {
		$scores[$user1] = $scores[$user1] - 1;
		$scores[$user2] = $scores[$user2] + 1;
	} else {
		$scores[$user1] = $scores[$user1] + 1;
		$scores[$user2] = $scores[$user2] - 1;
	}
}

// Sort the scores in descending order and then dump the data
arsort($scores);
$results = array();
foreach ( $scores as $k => $v ) {
	$results[] = array("name" => $users[$k], "score" => $v, "games" => $games[$k]);
}
echo(json_encode($results));
