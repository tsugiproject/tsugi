<?php
require_once "../../config.php";
require_once "parse.php";
require_once "sample.php";

use \Tsugi\Core\Cache;
use \Tsugi\Core\LTIX;

// Sanity checks
$LAUNCH = LTIX::requireData();
if ( ! $USER->instructor ) die("Requires instructor role");

// Model
$p = $CFG->dbprefix;

if ( isset($_POST['gift']) ) {
    $gift = $_POST['gift'];
    if ( get_magic_quotes_gpc() ) $gift = stripslashes($gift);
    $_SESSION['gift'] = $gift;

    // Some sanity checking...
    $questions = array();
    $errors = array();
    parse_gift($gift, $questions, $errors);

    if ( count($questions) < 1 ) {
        $_SESSION['error'] = "No valid questions found in input data";
        header( 'Location: '.addSession('configure.php') ) ;
        return;
    }

    if ( count($errors) > 0 ) {
        $msg = "Errors in GIFT data: ";
        $i = 1;
        foreach ( $errors as $error ) {
            $msg .= " ($i) ".$error;
        }
        $_SESSION['error'] = $msg;
        header( 'Location: '.addSession('configure.php') ) ;
        return;
    }

    // This is not JSON - no one cares
    $LINK->setJson($gift);
    $_SESSION['success'] = 'Quiz updated';
    unset($_SESSION['gift']);
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

// Load up the quiz
if ( isset($_SESSION['gift']) ) { 
    $gift = $_SESSION['gift'];
    unset($_SESSION['gift']);
} else {
    $gift = $LINK->getJson();
}

// Clean up the JSON for presentation
if ( $gift === false || strlen($gift) < 1 ) $gift = getSampleGIFT();

// View
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->flashMessages();

?>
<p>Be careful in making any changes if this quiz has submissions.</p>
<p>
The assignment is configured by carefully editing the gift below without 
introducing syntax errors.  Someday this will have a slick configuration 
screen but for now we edit the gift.  I borrowed this user interface from the early
days of Coursera configuration screens :).
<p>
The documentation for the GIFT format comes from 
<a href="https://docs.moodle.org/29/en/GIFT_format" target="_blank">Moodle Documentation</a>.
</p>
<form method="post" style="margin-left:5%;">
<textarea name="gift" rows="25" cols="80" style="width:95%" >
<?php echo(htmlent_utf8($gift)); ?>
</textarea>
<p>
<input type="submit" value="Save">
<input type=submit name=doCancel onclick="location='<?php echo(addSession('index.php'));?>'; return false;" value="Cancel"></p>
</form>
<?php

$OUTPUT->footer();
