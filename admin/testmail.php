<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../config.php");
session_start();
require_once("gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Services\Mail\MailService;

LTIX::getConnection();

if ( U::get($_POST,'email') && U::get($_POST,'subject') && U::get($_POST,'body')) {
    $to = U::get($_POST,'email');
    $subject =  U::get($_POST,'subject');
    $body = U::get($_POST,'body');
    $mail_type = U::get($_POST, 'mail_type', MailService::TYPE_TRANSACTIONAL);
    if ( $mail_type === MailService::TYPE_BULK ) {
        $detail = MailService::sendDetailedBulk($to, $subject, $body);
    } else {
        $detail = MailService::sendDetailedTransactional($to, $subject, $body);
    }
    $transport = U::get($detail, 'transport', 'php');
    $type = U::get($detail, 'type', MailService::TYPE_TRANSACTIONAL);
    if ( U::get($detail, 'disabled') ) {
        U::flashError('Mail disabled: set $CFG->maildomain in config.php');
    } else if ( U::get($detail, 'suppressed') ) {
        U::flashError('Address is suppressed (bounce, complaint, or unsubscribe); mail not sent');
    } else if ( U::get($detail, 'success') ) {
        $msg = 'Mail sent via ' . $transport . ' (' . $type . ')';
        if ( $transport === 'ses' && U::get($detail, 'message_id') ) {
            $msg .= ' (MessageId: ' . U::get($detail, 'message_id') . ')';
        } else if ( $transport === 'php' ) {
            $msg .= ' (PHP mail() returned true)';
        }
        U::flashSuccess($msg);
    } else {
        $err = U::get($detail, 'error', 'unknown error');
        U::flashError('Mail failed via ' . $transport . ' (' . $type . '): ' . $err);
    }
    header("Location: testmail.php");
    return;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
// No Nav - this is in a frame

$OUTPUT->flashMessages();

$transport = MailService::transport();
$ses_configured = MailService::isSesConfigured();

?>
<h1>Test Mail Sending</h1>
<p>
Configured owneremail:
<?= htmlentities((string) $CFG->owneremail) ?>
</p>
<p>
Transport:
<strong><?= htmlentities($transport) ?></strong>
<?php if ( $ses_configured ) { ?>
 (Amazon SES region <?= htmlentities((string) $CFG->ses_region) ?>)
<?php } else { ?>
 (PHP mail())
<?php } ?>
</p>
<p>
maildomain:
<?= $CFG->maildomain ? htmlentities((string) $CFG->maildomain) : '<em>false (mail disabled)</em>' ?>
</p>
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
Body:<br/>
<textarea style="width: 90%; height: 150px;" name="body">
</textarea>
</p>
<p>
Mail type:<br/>
<label><input type="radio" name="mail_type" value="<?= htmlentities(MailService::TYPE_TRANSACTIONAL) ?>" checked> transactional</label>
&nbsp;
<label><input type="radio" name="mail_type" value="<?= htmlentities(MailService::TYPE_BULK) ?>"> bulk</label>
</p>
<p>
<input type="submit" onclick="$('#myspinner').show();return true;" name="delete" value="Send Mail"/>
<img id="myspinner" src="<?= $OUTPUT->getSpinnerUrl() ?>" alt="" role="presentation" style="display:none">
</p>
</form>
<p>
Use <code>MailService::sendTransactional()</code> for one-to-one notices and
<code>MailService::sendBulk()</code> for list/campaign mail. SES tags
<code>mail_type</code> and can use separate Configuration Sets
(<code>ses_configuration_set</code> / <code>ses_configuration_set_bulk</code>).
See <code>docs/ses.md</code>.
</p>
<p>
<?php if ( $ses_configured ) { ?>
This sends through Amazon SES via <code>Tsugi\Services\Mail\MailService</code>.
<ul>
<li>Success includes an SES MessageId when available.</li>
<li>Failures show the SES/API error message (check verified From address and region).</li>
</ul>
<?php } else { ?>
This uses the PHP <code>mail()</code> function (SES is not configured).
Set <code>$CFG->ses_region</code> to use Amazon SES — see <code>docs/ses.md</code>.
If mail sending does not work, it can be a PHP misconfiguration or a system misconfiguration.
<ul>
<li>If this test returns false, PHP refused to send the mail.</li>
<li>If this test returns true but mail never arrives, the host MTA (postfix/sendmail)
did not deliver it. Configure the MTA or enable SES.</li>
</ul>
<?php } ?>
</p>
<?php
$OUTPUT->footer();
