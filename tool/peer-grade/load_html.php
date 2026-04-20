<?php
require_once "../config.php";
require_once "peer_util.php";

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

$html_id = U::get($_GET, 'html_id');
$user_id = U::get($_GET, 'user_id');

if ( $html_id && $user_id ) {
    // pass
} else {
    die('Missing parameters');
}

$LAUNCH = LTIX::requireData();

$row = loadAssignment();
$assn_json = null;
$assn_id = false;
if ( $row !== false ) {
    $assn_json = json_decode(upgradeSubmission($row['json']));
    $assn_id = $row['assn_id'];
}

if ( $assn_id == false ) {
    die('This assignment is not yet set up');
    return;
}

$row = $PDOX->rowDie("
    SELECT data FROM {$CFG->dbprefix}peer_text
    WHERE text_id = :TID AND user_id = :UID AND assn_id = :AID",
    array( ":TID" => $html_id,
        ":AID" => $assn_id,
        ":UID" => $user_id)
);

if ( ! $row ) {
    http_response_code(404);
    echo("Count not load text\n");
    return;
}

header('Content-Type: text/html');

$data = $row['data'];
$data = str_replace("\n\n","\n<br/>\n", $data);
echo($data);
