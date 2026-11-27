<?php

use Tsugi\Core\LTIX;
use Tsugi\Services\Admin\AdminService;
use Tsugi\Services\Lti\PlatformObservation;
use Tsugi\Util\U;

LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');

if ( ! AdminService::isAdmin() ) die('Must be admin');

$key_admin = $CFG->wwwroot.'/admin/key/';
$from_location = $key_admin.'platforms';
$platform_id = (int) U::get($_REQUEST, 'platform_id');
if ( $platform_id < 1 || ! PlatformObservation::ready($CFG->dbprefix) ) {
    U::flashError($platform_id < 1 ? 'Missing platform snapshot' : 'Platform snapshots are not installed. Run admin/upgrade.php.');
    header('Location: '.$from_location);
    return;
}

$p = $CFG->dbprefix;
$row = $PDOX->rowDie(
    "SELECT platform_id, key_id, lti_client_id, platform_guid, platform_name,
            platform_description, platform_url, platform_family, platform_version,
            platform_contact_email, platform_json, registration_json, source, created_at
        FROM {$p}lti_platform
        WHERE platform_id = :id",
    array(':id' => $platform_id)
);
if ( ! is_array($row) ) {
    U::flashError('Platform snapshot not found');
    header('Location: '.$from_location);
    return;
}

$producer_key_id = (int) ($row['key_id'] ?? 0);
$producer_client_id = (int) ($row['lti_client_id'] ?? 0);
$producer_key = $producer_key_id > 0 ? $PDOX->rowDie(
    "SELECT key_id, key_title, key_key FROM {$p}lti_key WHERE key_id = :id",
    array(':id' => $producer_key_id)
) : false;
$producer_client = $producer_client_id > 0 ? AdminService::inboundClientById($producer_client_id) : false;
$pointing_keys = $PDOX->allRowsDie(
    "SELECT key_id, key_title, key_key, platform_id, registration_platform_id
        FROM {$p}lti_key
        WHERE platform_id = :current OR registration_platform_id = :registration
        ORDER BY key_id",
    array(':current' => $platform_id, ':registration' => $platform_id)
);
$pointing_clients = array();
if ( $PDOX->columnExists('platform_id', $p.'lti_client') && $PDOX->columnExists('registration_platform_id', $p.'lti_client') ) {
    $pointing_clients = $PDOX->allRowsDie(
    "SELECT lti_client_id, title, issuer, client_id, platform_id, registration_platform_id
        FROM {$p}lti_client
        WHERE platform_id = :current OR registration_platform_id = :registration
        ORDER BY lti_client_id",
        array(':current' => $platform_id, ':registration' => $platform_id)
    );
}

$show = function ($value) {
    $text = trim((string) ($value ?? ''));
    if ( $text === '' ) return '<p></p>';
    return '<p style="white-space:pre-wrap">'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</p>';
};
$pretty = function ($text) {
    if ( ! is_string($text) || trim($text) === '' ) return null;
    $decoded = json_decode($text, true);
    if ( $decoded === null && trim($text) !== 'null' ) return $text;
    $encoded = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return is_string($encoded) ? $encoded : $text;
};
$key_label = function ($key) {
    $name = U::get($key, 'key_title');
    if ( ! U::isNotEmpty($name) ) $name = U::get($key, 'key_key');
    if ( ! U::isNotEmpty($name) ) $name = 'Key '.$key['key_id'];
    return (string) $name;
};
$role_label = function ($item) use ($platform_id) {
    $roles = array();
    if ( (int) ($item['platform_id'] ?? 0) === $platform_id ) $roles[] = 'current';
    if ( (int) ($item['registration_platform_id'] ?? 0) === $platform_id ) $roles[] = 'original registration';
    return count($roles) > 0 ? ' ('.implode(', ', $roles).')' : '';
};

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<h1>Platform snapshot
  <a class="btn btn-default" href="<?= $key_admin ?>platforms">All Platforms</a>
  <a class="btn btn-default" href="<?= $key_admin ?>clients">All Clients</a>
  <a class="btn btn-default" href="<?= $key_admin ?>">All Keys</a>
</h1>
<p>This snapshot is view only.</p>
<p><strong>Id</strong></p>
<p><?= (int) $row['platform_id'] ?></p>
<p><strong>Source</strong></p>
<p><?= htmlspecialchars((string) $row['source'], ENT_QUOTES, 'UTF-8') ?></p>
<p><strong>Created</strong></p>
<p><?= htmlspecialchars((string) $row['created_at'], ENT_QUOTES, 'UTF-8') ?></p>
<p><strong>Guid</strong></p>
<?= $show($row['platform_guid']) ?>
<p><strong>Name</strong></p>
<?= $show($row['platform_name']) ?>
<p><strong>Description</strong></p>
<?= $show($row['platform_description']) ?>
<p><strong>URL</strong></p>
<?= $show($row['platform_url']) ?>
<p><strong>Family</strong></p>
<?= $show($row['platform_family']) ?>
<p><strong>Version</strong></p>
<?= $show($row['platform_version']) ?>
<p><strong>Contact email</strong></p>
<?= $show($row['platform_contact_email']) ?>

<h2>Produced by</h2>
<p><strong>Key</strong></p>
<?php if ( $producer_key_id < 1 ) { ?>
<p>None. The key link is empty.</p>
<?php } elseif ( ! is_array($producer_key) ) { ?>
<p>Key <?= $producer_key_id ?> is not in the key table.</p>
<?php } else { ?>
<p><a href="<?= $key_admin ?>key-detail?key_id=<?= $producer_key_id ?>"><?= htmlspecialchars($key_label($producer_key), ENT_QUOTES, 'UTF-8') ?></a></p>
<?php } ?>
<p><strong>Client</strong></p>
<?php if ( $producer_client_id < 1 ) { ?>
<p>None. The client link is empty.</p>
<?php } elseif ( ! is_array($producer_client) ) { ?>
<p>Client <?= $producer_client_id ?> is not in the client table.</p>
<?php } else { ?>
<p><a href="<?= $key_admin ?>client-detail?lti_client_id=<?= $producer_client_id ?>"><?= htmlspecialchars(AdminService::inboundClientLabel($producer_client), ENT_QUOTES, 'UTF-8') ?></a></p>
<?php } ?>

<h2>Keys that point here</h2>
<?php if ( count($pointing_keys) < 1 ) { ?>
<p>No key uses this snapshot as its current or original registration.</p>
<?php } else { ?>
<ul>
<?php foreach ( $pointing_keys as $pointing_key ) { ?>
<li><a href="<?= $key_admin ?>key-detail?key_id=<?= (int) $pointing_key['key_id'] ?>"><?= htmlspecialchars($key_label($pointing_key), ENT_QUOTES, 'UTF-8') ?></a><?= htmlspecialchars($role_label($pointing_key), ENT_QUOTES, 'UTF-8') ?></li>
<?php } ?>
</ul>
<?php } ?>

<h2>Clients that point here</h2>
<?php if ( count($pointing_clients) < 1 ) { ?>
<p>No client uses this snapshot as its current or original registration.</p>
<?php } else { ?>
<ul>
<?php foreach ( $pointing_clients as $pointing_client ) { ?>
<li><a href="<?= $key_admin ?>client-detail?lti_client_id=<?= (int) $pointing_client['lti_client_id'] ?>"><?= htmlspecialchars(AdminService::inboundClientLabel($pointing_client), ENT_QUOTES, 'UTF-8') ?></a><?= htmlspecialchars($role_label($pointing_client), ENT_QUOTES, 'UTF-8') ?></li>
<?php } ?>
</ul>
<?php } ?>

<h2>Launch JSON</h2>
<?php $launch_json = $pretty($row['platform_json']); if ( $launch_json === null ) { ?>
<p>None stored.</p>
<?php } else { ?>
<pre style="max-height:24em;overflow:auto;white-space:pre-wrap"><?= htmlspecialchars($launch_json, ENT_QUOTES, 'UTF-8') ?></pre>
<?php } ?>
<h2>Registration JSON</h2>
<?php $registration_json = $pretty($row['registration_json']); if ( $registration_json === null ) { ?>
<p>None stored.</p>
<?php } else { ?>
<pre style="max-height:24em;overflow:auto;white-space:pre-wrap"><?= htmlspecialchars($registration_json, ENT_QUOTES, 'UTF-8') ?></pre>
<?php } ?>
<?php
$OUTPUT->footer();
