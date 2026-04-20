<?php
require_once "../config.php";
require_once "parse.php";
require_once "sample.php";
require_once "util.php";

use \Tsugi\Util\U;
use \Tsugi\Core\Cache;
use \Tsugi\Core\LTIX;

// Sanity checks
$LAUNCH = LTIX::requireData();
if ( ! $USER->instructor ) die("Requires instructor role");

// Model
$p = $CFG->dbprefix;

// If they pressed Submit on the quiz content
if ( isset($_POST['gift']) ) {
    $gift = $_POST['gift'];
    $_SESSION['gift'] = $gift;

    // Some sanity checking...
    $retval = check_gift($gift);
    if ( ! $retval ) {
        header( 'Location: '.addSession('old_configure.php') ) ;
        return;
    }

    // This is not JSON - no one cares
    $LINK->setJson($gift);
    $_SESSION['success'] = 'Quiz updated';
    unset($_SESSION['gift']);
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

// Check to see if we are supposed to preload a quiz
$lock = false;
$files = get_quiz_files();
if ( $files && is_file($CFG->giftquizzes.'/.lock') ) {
    $lock = trim(file_get_contents($CFG->giftquizzes.'/.lock'));
}

if ( $files && count($files) < 1 ) {
    $_SESSION['error'] = "Found no files in ".$CFG->giftquizzes;
    header( 'Location: '.addSession('old_configure.php') ) ;
    return;
}
// print_r($files);
// echo("LOCK = ".$lock);

$default = isset($_SESSION['default_quiz']) ? $_SESSION['default_quiz'] : false;

// Load up the selected file
if ( $files && isset($_POST['file']) ) {
    $key = isset($_POST['lock']) ? $_POST['lock'] : false;
    if ( $lock && $lock != $key ) {
        $_SESSION['error'] = 'Incorrect password';
        header( 'Location: '.addSession('old_configure.php') ) ;
        return;
    }

    $name = $_POST['file'];
    if ( ! in_array($name, $files) ) {
        $_SESSION['error'] = 'Quiz file not found: '.$_POST['file'];
        header( 'Location: '.addSession('old_configure.php') ) ;
        return;
    }

    $gift = file_get_contents($CFG->giftquizzes.'/'.$name);
    $_SESSION['gift'] = $gift;

    // Also pre-check for sanity
    $retval = check_gift($gift);
    if ( ! $retval ) {
        header( 'Location: '.addSession('old_configure.php') ) ;
        return;
    }

    $_SESSION['success'] = 'Preloaded quiz content from file. Make sure to save the quiz below.';
    header( 'Location: '.addSession('old_configure.php') ) ;
    return;
}

// Load up the quiz from session or DB
if ( isset($_SESSION['gift']) ) { 
    $gift = $_SESSION['gift'];
    unset($_SESSION['gift']);
} else {
    $gift = $LINK->getJson();
}

// Clean up the JSON for presentation
if ( $gift === false || strlen($gift) < 1 ) {
    if ( is_array($default) && $lock == false && in_array($default, $files) ) {
        $gift = file_get_contents($CFG->giftquizzes.'/'.$default);
        $_SESSION['success'] = 'Loaded quiz '.$default.' as default';
    } else {
        $gift = getSampleGIFT();
    }
}

$menu = new \Tsugi\UI\MenuSet();
$menu->addLeft('Back', 'index.php');

// View
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav($menu);
$OUTPUT->flashMessages();
?>
<p>Be careful in making any changes if this quiz has submissions.</p>
<?php 
if ( $files !== false ) {
echo("<form method=\"post\">\n");
// echo('<select name="file" onchange="console.dir(this); if(this.value!=0) this.form.submit();">'."\n");
echo('<select name="file">'."\n");
echo('<option value="0">Select Quiz</option>'."\n");
foreach($files as $file) {
    if ( $default && $default == $file ) {
        echo('<option value="'.htmlentities($file).'" selected>'.htmlentities($file).'</option>'."\n");
    } else {
        echo('<option value="'.htmlentities($file).'">'.htmlentities($file).'</option>'."\n");
    }
}
echo("</select>\n");
if ( $lock != false ) {
    echo('<input type="password" name="lock"> Password ');
}
echo('<input type="submit" value="Load Quiz">');
echo("</form>\n");
}
?>
<p>
The assignment is configured by carefully editing the gift below.
The documentation for the GIFT format comes from 
<a href="https://docs.moodle.org/29/en/GIFT_format" target="_blank">Moodle Documentation</a>.
</p>
<form method="post" style="margin-left:5%;">
<p>
<input type="submit" class="btn btn-primary" value="Save">
</p>
<textarea name="gift" rows="25" cols="80" style="width:95%" >
<?php echo(htmlentities($gift)); ?>
</textarea>
<p>
</form>
<?php

$OUTPUT->footer();
