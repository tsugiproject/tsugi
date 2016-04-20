<?php
require_once "../../config.php";
\Tsugi\Core\LTIX::getConnection();

use \Tsugi\Core\LTIX;
use \Tsugi\UI\Output;

// Retrieve the launch data if present
$LAUNCH = LTIX::requireData();
$p = $CFG->dbprefix;
$displayname = $USER->displayname;

Output::headerJson();

// TODO: Cleanup old chats
$stmt = $PDOX->prepare("DELETE FROM {$p}sample_chat
        WHERE created_at < DATE_SUB( NOW(), INTERVAL 10 DAY)");
$stmt->execute();

$stmt = $PDOX->prepare("SELECT chat, displayname, 
        {$p}sample_chat.created_at AS created_at
        FROM {$p}sample_chat JOIN {$p}lti_user
        ON {$p}sample_chat.user_id = {$p}lti_user.user_id
        WHERE link_id = :LI ORDER BY {$p}sample_chat.created_at DESC LIMIT 0,15");
$stmt->execute(array(":LI" => $LINK->id));

$messages = array();

while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    $messages[] = $row;
}

echo(json_encode($messages));
