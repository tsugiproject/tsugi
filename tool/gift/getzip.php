<?php

require_once "../config.php";

require_once "parse.php";
require_once "util.php";

date_default_timezone_set('UTC');
if ( isset($_GET[session_name()]) ) session_id($_GET[session_name()]);
session_start();

if ( !isset($_SESSION['quiz']) ) {
    error_log('GIFT2QTI Missing quiz data '.curPageUrl());
    die('GIFT2QTI Missing quiz data');
}
// Stuff we substitute...
$quiz_id = 'i'.uniqid();
$today = date('Y-m-d');
$ref_id = 'r'.uniqid();
$manifest_id = 'm'.uniqid();
$title = isset($_SESSION['title']) ? htmlentities($_SESSION['title']) : 'Converted by the Gift2QTI Converter';
$desc = "Description goes here";
$source = array("__DATE__", "__QUIZ_ID__","__REF_ID__", "__TITLE__","__DESCRIPTION__", "__MANIFEST_ID__");
$dest = array($today, $quiz_id, $ref_id, $title, $desc, $manifest_id);

// here we go...
$filename = tempnam(sys_get_temp_dir(), "gift2qti");
$zip = new ZipArchive();
if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
    die("Cannot open $filename\n");
}

if ( isset($_SESSION['name']) && strlen($_SESSION['name']) > 0 ) {
    $download = preg_replace('/[^\w\s]/', '', $_SESSION['name']);
    if ( strlen($download) < 1 ) $download = $quiz_id;
} else {
    $download = $quiz_id;
}

header( "Content-Type: application/x-zip" );
header( "Content-Disposition: attachment; filename=\"$download.zip\"" );

// Add the ims Manifest
$manifest = str_replace($source, $dest, file_get_contents('xml/imsmanifest.xml'));
$zip->addFromString('imsmanifest.xml',$manifest);

// Add the Assessment Metadata
$meta = str_replace($source, $dest, file_get_contents('xml/assessment_meta.xml'));
$zip->addFromString($quiz_id.'/assessment_meta.xml',$meta);

// Add the quiz
$zip->addFromString($quiz_id.'/'.$quiz_id.'.xml',$_SESSION['quiz']);

$zip->close();
// Make sure to delete the file even if the download stops
// http://stackoverflow.com/questions/2641667/deleting-a-file-after-user-download-it

ignore_user_abort(true);
readfile($filename);
unlink($filename);
error_log("Downloaded $filename");



