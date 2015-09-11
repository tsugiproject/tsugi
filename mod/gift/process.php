<?php

require_once "util.php";

session_start();
unset($_SESSION['quiz']);
unset($_SESSION['title']);
unset($_SESSION['name']);
unset($_SESSION['novalidate']);
if ( !isset($_POST['text']) ) die('Missing input data');
$text =  $_POST['text'];
if ( isset($_POST['title']) ) $_SESSION['title'] = $_POST['title'];
if ( isset($_POST['name']) ) $_SESSION['name'] = $_POST['name'];

if ( isset($_POST['bypass']) ) $_SESSION['novalidate'] = 'bypass';

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

?>
Conversion complete...

</pre>
<p>
<a href="viewxml.php" target="_blank">View Quiz XML</a> |
<a href="getzip.php" target="_blank">Download ZIP</a>
    <?php
    if (isset($_SESSION['content_item_return_url'])){
        if ( isset($_POST['title']) ) {
          $name = $_POST['title'];
        } else{
          $name = 'Gift Quiz';
        }
        $abs_url = str_replace("convert.php", "getzip.php?", curPageURL());
        $return_url = htmlspecialchars($_SESSION['content_item_return_url']) .
          "?return_type=file&text=". htmlspecialchars($name) . "&url=" .
          urlencode(htmlspecialchars($abs_url . session_name() . '='. session_id() ));
        ?>
         | <a href="<?php echo $return_url ?>" target="_parent">Return Zip to LMS</a>
    <?php
    }
    ?>
</p>
<p>
To upload to an LMS choose the ZIP format - it makes a small IMS Common Cartridge
with just the quiz in it - and it is what most LMS systems prefer to import (i.e.
they don't want you to upload the XML directly).
</p>


