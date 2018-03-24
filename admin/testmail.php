<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../config.php");
session_start();
require_once("gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Mail;

LTIX::getConnection();

if ( U::get($_POST,'email') && U::get($_POST,'subject') && U::get($_POST,'body')) {
    $to = U::get($_POST,'email');
    $subject =  U::get($_POST,'subject');
    $body = U::get($_POST,'body');
    $retval = Mail::send($to, $subject, $body);
var_dump($retval);
die();
    header("Location: testmail.php");
    return;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
// No Nav - this is in a frame

$OUTPUT->flashMessages();

?>
<h1>Test Mail Sending</h1>
<p>
<form method="POST">
<p>
Mail address:
<input type="email" name="email">
</p>
<p>
Subject:
<input type="text" name="subject">
</p>
<p>
Subject:<br/>
<textarea name="body">
</textarea>
</p>
<p>
<input type="submit" onclick="$('#myspinner').show();return true;" name="delete" value="Delete Records"/>
<img id="myspinner" src="<?= $OUTPUT->getSpinnerUrl() ?>" style="display:none">
</p>
</form>
</p>
<?php
$OUTPUT->footer();

