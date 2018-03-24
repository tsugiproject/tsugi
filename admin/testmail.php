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
    if ( $retval ) {
        $_SESSION['success'] = 'PHP mail() returned true';
    } else {
        $_SESSION['error'] = 'PHP mail() returned false';
    }
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
Mail address:<br/>
<input style="width: 90%" type="email" name="email">
</p>
<p>
Subject:<br/>
<input style="width: 90%" type="text" name="subject">
</p>
<p>
Subject:<br/>
<textarea style="width: 90%; height: 150px;" name="body">
</textarea>
</p>
<p>
<input type="submit" onclick="$('#myspinner').show();return true;" name="delete" value="Send Mail"/>
<img id="myspinner" src="<?= $OUTPUT->getSpinnerUrl() ?>" style="display:none">
</p>
</form>
<p>
This uses the PHP mail() function to send mail.  If mail sending does not work,
it can be a PHP misconfiguration or a system misconfiguration. 
<ul>
<li>If this test returns 'false', it means PHP refused to send the mail.
<li>If this test returns 'true', it means that PHP thought it sent the mail.  If the mail
does not arrive after a few minutes it means the underlying operating system
or environment did not spool the mail out.   You need to investigate things like
postfix or sendmail or even SES on Amazon to get outbound mail flowing.
</li>
</ul>
</p>
<?php
$OUTPUT->footer();

