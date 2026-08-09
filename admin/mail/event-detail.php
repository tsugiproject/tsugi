<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

use \Tsugi\Core\LTIX;
use \Tsugi\Services\Mail\MailService;
use \Tsugi\Util\U;

LTIX::getConnection();
session_start();

require_once("../gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

if ( ! isAdmin() ) {
    \Tsugi\Controllers\Login::setReturnUrl(LTIX::curPageUrlFolder());
    header('Location: '.\Tsugi\Controllers\Login::loginUrl());
    return;
}

$event_id = isset($_REQUEST['event_id']) ? $_REQUEST['event_id'] + 0 : 0;
if ( $event_id < 1 || ! MailService::sesEventsTableExists() ) {
    U::flashError('Event not found');
    header('Location: ses-events');
    return;
}

$row = $PDOX->rowDie(
    "SELECT * FROM {$CFG->dbprefix}mail_ses_events WHERE event_id = :E",
    array(':E' => $event_id)
);
if ( $row === false || $row === null ) {
    U::flashError('Event not found');
    header('Location: ses-events');
    return;
}

require_once("nav.php");

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

$payload = U::get($row, 'payload_json', '');
$pretty = $payload;
if ( is_string($payload) && $payload !== '' ) {
    $decoded = json_decode($payload, true);
    if ( is_array($decoded) ) {
        $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}

?>
<?php mail_admin_nav('events'); ?>
<h1>SES event #<?= (int) $event_id ?></h1>
<p>
<a href="ses-events">Back to SES events</a>
</p>
<table class="table table-striped" style="max-width: 900px;">
<tr><th>created_at</th><td><?= htmlentities((string) U::get($row, 'created_at', '')) ?></td></tr>
<tr><th>event_type</th><td><?= htmlentities((string) U::get($row, 'event_type', '')) ?></td></tr>
<tr><th>event_subtype</th><td><?= htmlentities((string) U::get($row, 'event_subtype', '')) ?></td></tr>
<tr><th>email</th><td><?= htmlentities((string) U::get($row, 'email', '')) ?></td></tr>
<tr><th>action</th><td><strong><?= htmlentities((string) U::get($row, 'action', '')) ?></strong></td></tr>
<tr><th>mail_type</th><td><?= htmlentities((string) U::get($row, 'mail_type', '')) ?></td></tr>
<tr><th>detail</th><td><?= htmlentities((string) U::get($row, 'detail', '')) ?></td></tr>
<tr><th>ses_message_id</th><td><?= htmlentities((string) U::get($row, 'ses_message_id', '')) ?></td></tr>
<tr><th>sns_message_id</th><td><?= htmlentities((string) U::get($row, 'sns_message_id', '')) ?></td></tr>
</table>
<h2>payload_json</h2>
<pre style="white-space: pre-wrap; max-width: 900px;"><?= htmlentities((string) $pretty) ?></pre>
<?php
$OUTPUT->footer();
