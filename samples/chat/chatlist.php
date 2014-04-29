<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

session_start();
header_json();

// Retrieve the launch data if present
$LTI = requireData(array('user_id', 'result_id', 'role','context_id'));
$p = $CFG->dbprefix;
$displayname = $LTI['user_displayname'];

// Cleanup old chats
$stmt = $pdo->prepare("DELETE FROM {$p}sample_chat 
        WHERE created_at < DATE_SUB( NOW(), INTERVAL 10 DAY)");
$stmt->execute();

$stmt = $pdo->prepare("SELECT chat, displayname, {$p}sample_chat.created_at AS created_at
        FROM {$p}sample_chat JOIN {$p}lti_user 
        ON {$p}sample_chat.user_id = {$p}lti_user.user_id 
        WHERE link_id = :LI ORDER BY {$p}sample_chat.created_at DESC LIMIT 0,15");
$stmt->execute(array(":LI" => $LTI['link_id']));

$messages = array();

while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    $messages[] = $row;
}

echo(json_encode($messages));
