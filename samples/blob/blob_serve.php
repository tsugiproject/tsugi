<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once "blob_util.php";

use \Tsugi\Core\LTIX;

// Sanity checks
$LTI = LTIX::requireData(array('context_id'));

$id = $_REQUEST['id'];
if ( strlen($id) < 1 ) {
    die("File not found");
}

$p = $CFG->dbprefix;
$stmt = $PDOX->prepare("SELECT contenttype, content FROM {$p}sample_blob
            WHERE file_id = :ID AND context_id = :CID");
$stmt->execute(array(":ID" => $id, ":CID" => $CONTEXT->id));
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
