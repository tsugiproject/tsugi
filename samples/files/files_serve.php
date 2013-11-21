<?php
require_once "../../config.php";
require_once $CFG->dirroot."/db.php";
require_once $CFG->dirroot."/lib/lti_util.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once "files_util.php";

session_start();

// Sanity checks
requireData(array('user_id', 'link_id'));
$LTI = $_SESSION['lti'];


$fn = $_REQUEST['file'];
if ( strlen($fn) < 1 ) {
    die("File name not found");
}

$fn = fixFileName($fn);
$foldername = getFolderName($LTI);
$filename = $foldername . '/' . fixFileName($fn);

if ( ! file_exists($filename) ) {
   die("File does not exist");
}

// Get the mime type
$finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
$mimetype = finfo_file($finfo, $filename);
finfo_close($finfo);

// die($mimetype);

if ( strlen($mimetype) > 0 ) header('Content-Type: '.$mimetype );
header('Content-Disposition: attachment; filename="'.$fn.'"');

echo readfile($filename);
