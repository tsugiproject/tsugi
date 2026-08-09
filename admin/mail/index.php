<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

use \Tsugi\Core\LTIX;
use \Tsugi\Services\Mail\MailService;
use \Tsugi\Controllers\Tool;

LTIX::getConnection();
session_start();

require_once("../gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

if ( ! isAdmin() ) {
    \Tsugi\Controllers\Login::setReturnUrl(LTIX::curPageUrlFolder());
    header('Location: '.\Tsugi\Controllers\Login::loginUrl());
    return;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

$ses = MailService::isSesConfigured();
$cfgset = (isset($CFG->ses_configuration_set) && $CFG->ses_configuration_set !== false)
    ? trim((string) $CFG->ses_configuration_set) : '';
$cfgset_bulk = (isset($CFG->ses_configuration_set_bulk) && $CFG->ses_configuration_set_bulk !== false)
    ? trim((string) $CFG->ses_configuration_set_bulk) : '';

$suppress_ok = MailService::suppressTableExists();
$events_ok = MailService::sesEventsTableExists();
$sent_meta = $PDOX->metadata($CFG->dbprefix . 'mail_sent');
$sent_ok = $sent_meta !== false;

?>
<h1>Mail</h1>
<p>
<a href="<?= htmlentities($CFG->wwwroot) ?>/admin">Admin</a>
|
<a href="<?= htmlentities($CFG->wwwroot) ?>/admin/testmail">Test E-Mail</a>
</p>
<p>
Transport:
<strong><?= htmlentities(MailService::transport()) ?></strong>
<?php if ( $ses ) { ?>
 — SES region <?= htmlentities((string) $CFG->ses_region) ?>
<?php } ?>
</p>
<ul>
<li>maildomain: <?= $CFG->maildomain ? htmlentities((string) $CFG->maildomain) : '<em>false (disabled)</em>' ?></li>
<li>ses_configuration_set: <?= $cfgset !== '' ? htmlentities($cfgset) : '<em>not set</em>' ?></li>
<li>ses_configuration_set_bulk: <?= $cfgset_bulk !== '' ? htmlentities($cfgset_bulk) : '<em>not set</em>' ?></li>
<li>mail_suppress table: <?= $suppress_ok ? 'ok' : '<span style="color:red">missing — run Database Upgrade</span>' ?></li>
<li>mail_ses_events table: <?= $events_ok ? 'ok' : '<span style="color:red">missing — run Database Upgrade</span>' ?></li>
<li>mail_sent table: <?= $sent_ok ? 'ok' : 'missing' ?></li>
</ul>
<ul>
<li><a href="bulk">Bulk campaigns</a></li>
<li><a href="suppress">Suppressed addresses</a></li>
<li><a href="ses-events">SES events</a></li>
<li><a href="sent">Sent log (mail_sent)</a></li>
</ul>
<p>
To send: open a context under <a href="<?= htmlentities($CFG->wwwroot) ?>/admin/context/">Contexts</a>
→ Membership → <strong>Bulk mail</strong> (site admin only).
</p>
<p>
See <code>docs/ses.md</code> for Configuration Set / SNS setup.
<?php
$home = Tool::configuredHomeUrl();
?>
SNS webhook: <code><?= htmlentities($home) ?>/ses/sns</code>
</p>
<?php
$OUTPUT->footer();
