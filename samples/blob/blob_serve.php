<?php
require_once "../../config.php";
require_once $CFG->dirroot."/db.php";
require_once $CFG->dirroot."/lib/lti_util.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once "blob_util.php";

session_start();

// Sanity checks
requireData(array('user_id', 'context_id'));
$LTI = $_SESSION['lti'];

$id = $_REQUEST['id'];
if ( strlen($id) < 1 ) {
    die("File not found");
}

$p = $CFG->dbprefix;
$stmt = $db->prepare("SELECT contenttype, content FROM {$p}blob 
			WHERE file_id = :ID AND context_id = :CID");
$stmt->execute(array(":ID" => $id, ":CID" => $LTI['user_id']));
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ( $row === false ) {
    die("File not loaded");
}

$mimetype = $row['contenttype'];
// die($mimetype);

if ( strlen($mimetype) > 0 ) header('Content-Type: '.$mimetype );
// header('Content-Disposition: attachment; filename="'.$fn.'"');
// header('Content-Type: text/data');

echo($row['content']);
