<?php
require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";
require_once "files_util.php";

use \Tsugi\Core\LTIX;

// Sanity checks
$LTI = LTIX::requireData();

$fn = $_REQUEST['file'];
if ( strlen($fn) < 1 ) {
    die("File name not found");
}

$fn = fixFileName($fn);
$foldername = getFolderName();
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
