<?php

// Note that somehow including config.php here blows up the XML validation...

require_once "../config.php";

require_once "util.php";

session_start();
unset($_SESSION['quiz']);
unset($_SESSION['title']);
unset($_SESSION['name']);
unset($_SESSION['novalidate']);
unset($_SESSION['htmlhack']);
if ( !isset($_POST['text']) ) die('Missing input data');
$text =  $_POST['text'];
if ( isset($_POST['title']) ) $_SESSION['title'] = $_POST['title'];
if ( isset($_POST['name']) ) $_SESSION['name'] = $_POST['name'];

if ( isset($_POST['bypass']) ) $_SESSION['novalidate'] = 'bypass';
if ( isset($_POST['htmlhack']) ) $_SESSION['htmlhack'] = 'true';

echo("<pre>\n");
require_once("parse.php");

$questions = array();
$errors = array();
parse_gift($text, $questions, $errors);

if ( count($questions) < 1 ) {
    print "No questions found.";
    exit();
} else {
    print "Found ".count($questions)." questions in the GIFT input.\n";
}

if ( count($errors) == 0 ) {
    print "Initial parse of GIFT data successful\n";
} else {
    print "Errors in the GIFT data\n";
    print_r($errors);
}
echo("\nCreating and validating the quiz XML....\n");
flush();

// var_dump($questions);

require_once("make_qti.php");
if ( !isset($DOM) ) die("Conversion not completed");

$_SESSION['quiz'] = $DOM->saveXML();
$_SESSION['uuid'] = $uuid;

$add_session = '?' . session_name() . '=' . session_id();

?>
Conversion complete...

</pre>
<p>
<a href="viewxml.php<?= $add_session ?>" target="_blank">View Quiz XML</a> |
<a href="getzip.php<?= $add_session ?>" target="_blank">Download ZIP</a>
</p>
<p>
To upload to an LMS choose the ZIP format - it makes a small IMS Common Cartridge
with just the quiz in it - and it is what most LMS systems prefer to import (i.e.
they are not usually capable of importing the actual QTI XML directly).
</p>
