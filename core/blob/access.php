<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once "blob_util.php";

// Sanity checks
$LTI = lti_require_data(array('context_id'));

$id = $_REQUEST['id'];
if ( strlen($id) < 1 ) {
    die("File not found");
}

$p = $CFG->dbprefix;
$stmt = $pdo->prepare("SELECT contenttype, content, file_name FROM {$p}blob_file 
            WHERE file_id = :ID AND context_id = :CID");
$stmt->execute(array(":ID" => $id, ":CID" => $CONTEXT->id));
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ( $row === false ) {
    error_log('File not loaded: '.$id);
    die("File not loaded");
}

if ( ! safeFileSuffix($row['file_name']) )  {
    error_log('Unsafe file suffix: '.$row['file_name']);
    die('Unsafe file suffix');
}

$mimetype = $row['contenttype'];
// die($mimetype);

if ( strlen($mimetype) > 0 ) header('Content-Type: '.$mimetype );
// header('Content-Disposition: attachment; filename="'.$fn.'"');
// header('Content-Type: text/data');

echo($row['content']);
