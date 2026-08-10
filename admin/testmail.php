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
    $to = trim((string) U::get($_POST,'email'));
    $subject = (string) U::get($_POST,'subject');
    $body = (string) U::get($_POST,'body');
    $mail_type = U::get($_POST, 'mail_type', MailService::TYPE_TRANSACTIONAL);

    // Resolve recipient user for signed unsubscribe (required for bulk List-Unsubscribe).
    $user_to = null;
    $urow = $PDOX->rowDie(
        "SELECT user_id FROM {$CFG->dbprefix}lti_user WHERE LOWER(email) = :E ORDER BY user_id DESC LIMIT 1",
        array(':E' => MailService::normalizeEmail($to))
    );
    if ( is_array($urow) && isset($urow['user_id']) && (int) $urow['user_id'] > 0 ) {
        $user_to = (int) $urow['user_id'];
    }
    $token = $user_to ? MailService::computeCheck($user_to) : false;

    if ( $mail_type === MailService::TYPE_BULK ) {
        if ( !$user_to ) {
            U::flashError('Bulk test mail needs a matching lti_user email so unsubscribe headers can be signed. No user found for '.$to);
            header("Location: testmail.php");
            return;
        }
        $detail = MailService::sendDetailedBulk($to, $subject, $body, $user_to, $token);
    } else {
        $detail = MailService::sendDetailedTransactional($to, $subject, $body, $user_to ?: false, $token ?: false);
    }
    $transport = U::get($detail, 'transport', 'php');
    $type = U::get($detail, 'type', MailService::TYPE_TRANSACTIONAL);

    $status = 'failed';
    if ( U::get($detail, 'disabled') ) {
        $status = 'disabled';
        U::flashError('Mail disabled: set $CFG->maildomain in config.php');
    } else if ( U::get($detail, 'suppressed') ) {
        $status = 'suppressed';
        U::flashError('Address is suppressed (bounce, complaint, or unsubscribe); mail not sent');
    } else if ( U::get($detail, 'success') ) {
        $status = 'sent';
        $msg = 'Mail sent via ' . $transport . ' (' . $type . ')';
        if ( $transport === 'ses' && U::get($detail, 'message_id') ) {
            $msg .= ' (MessageId: ' . U::get($detail, 'message_id') . ')';
        } else if ( $transport === 'php' ) {
            $msg .= ' (PHP mail() returned true)';
        }
        if ( $type === MailService::TYPE_BULK && $user_to ) {
            $msg .= ' — includes List-Unsubscribe for user_id='.$user_to;
        }
        U::flashSuccess($msg);
    } else {
        $err = U::get($detail, 'error', 'unknown error');
        U::flashError('Mail failed via ' . $transport . ' (' . $type . '): ' . $err);
    }

    // Log admin test sends (no context_id).
    $user_from = loggedInUserId();
    if ( $user_from < 1 ) {
        $user_from = null;
    }
    $message_id = U::get($detail, 'message_id');
    if ( !is_string($message_id) || $message_id === '' ) {
        $message_id = null;
    }
    $sent_json = json_encode(array(
        'source' => 'testmail',
        'email' => $to,
        'status' => $status,
        'mail_type' => $type,
        'transport' => $transport,
        'message_id' => $message_id,
        'error' => U::get($detail, 'error'),
        'unsubscribe' => ($type === MailService::TYPE_BULK && $user_to) ? 1 : 0,
    ));
    $PDOX->queryReturnError(
        "INSERT INTO {$CFG->dbprefix}mail_sent
            (context_id, bulk_id, user_to, user_from, subject, body, message_id, json, created_at)
         VALUES (NULL, NULL, :UTO, :UFR, :SUB, NULL, :MID, :JSON, NOW())",
        array(
            ':UTO' => $user_to,
            ':UFR' => $user_from,
            ':SUB' => substr($subject, 0, 256),
            ':MID' => $message_id,
            ':JSON' => $sent_json,
        )
    );

    header("Location: testmail.php");
    return;
}

require_once("mail/nav.php");

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

$transport = MailService::transport();
$ses_configured = MailService::isSesConfigured();

?>
<?php mail_admin_nav('test'); ?>
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
<p class="help-block">
Bulk includes List-Unsubscribe headers and footer; the address must match an
<code>lti_user.email</code> so the link can be signed.
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
