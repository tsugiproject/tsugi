<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;
use \Tsugi\Services\Admin\AdminService;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');

if ( ! AdminService::isAdmin() ) {
    die('Must be admin');
}

$key_admin = $CFG->wwwroot.'/admin/key/';
$from_location = $key_admin.'clients';
$client_id = (int) U::get($_REQUEST, 'lti_client_id');
if ( $client_id < 1 ) {
    U::flashError('Missing client');
    header('Location: '.$from_location);
    return;
}
$detail_location = $key_admin.'client-detail?lti_client_id='.$client_id;

if ( isset($_POST['doSave']) || isset($_POST['doDelete']) ) {
    if ( \Tsugi\Controllers\Tool::csrfRedirect($detail_location) ) return;
}

if ( isset($_POST['doDelete']) ) {
    LTIX::clearKeysetCacheForClient($client_id);
    $gone = $PDOX->queryReturnError(
        "DELETE FROM {$CFG->dbprefix}lti_client WHERE lti_client_id = :id",
        array(':id' => $client_id)
    );
    if ( ! $gone->success ) {
        U::flashError('Could not delete client');
        header('Location: '.$detail_location);
        return;
    }
    U::flashSuccess('Client deleted. Keys that pointed at it are detached.');
    header('Location: '.$from_location);
    return;
}

$editing = U::get($_REQUEST, 'edit') == 'yes';
if ( isset($_POST['doSave']) ) {
    $saved = AdminService::saveInboundClient($client_id, $_POST);
    if ( $saved ) {
        header('Location: '.$detail_location);
        return;
    }
    $editing = true;
}

$row = AdminService::inboundClientById($client_id);
if ( ! is_array($row) ) {
    U::flashError('Client not found');
    header('Location: '.$from_location);
    return;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<h1>Client Details
  <a class="btn btn-default" href="<?= $key_admin ?>clients">All Clients</a>
  <a class="btn btn-default" href="<?= $key_admin ?>">All Keys</a>
</h1>
<p>
<b><?= htmlspecialchars(AdminService::inboundClientLabel($row), ENT_QUOTES, 'UTF-8') ?></b>
</p>
<?php if ( ! $editing ) { ?>
<p>
<a href="<?= $detail_location ?>&amp;edit=yes" class="btn btn-warning">Edit</a>
</p>
<p><strong>Title</strong></p>
<p><?= htmlspecialchars((string) $row['title'], ENT_QUOTES, 'UTF-8') ?></p>
<p><strong>User Id</strong></p>
<p><?= htmlspecialchars((string) ($row['user_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
<p><strong>Issuer</strong></p>
<p><?= htmlspecialchars((string) $row['issuer'], ENT_QUOTES, 'UTF-8') ?></p>
<p><strong>LTI 1.3: Dynamic Registration Unlock Code (one time use)</strong></p>
<p><?= htmlspecialchars((string) $row['unlock_code'], ENT_QUOTES, 'UTF-8') ?></p>
<p><strong>Client id</strong></p>
<p><?= htmlspecialchars((string) $row['client_id'], ENT_QUOTES, 'UTF-8') ?></p>
<p><strong>OIDC login / authorization URL</strong></p>
<p><?= htmlspecialchars((string) $row['oidc_auth_url'], ENT_QUOTES, 'UTF-8') ?></p>
<p><strong>Keyset URL</strong></p>
<p><?= htmlspecialchars((string) $row['keyset_url'], ENT_QUOTES, 'UTF-8') ?></p>
<p><strong>Token URL</strong></p>
<p><?= htmlspecialchars((string) $row['token_url'], ENT_QUOTES, 'UTF-8') ?></p>
<p><strong>LTI 1.3 Platform Audience (optional)</strong></p>
<p><?= htmlspecialchars((string) $row['token_audience'], ENT_QUOTES, 'UTF-8') ?></p>
<p><strong>Notes</strong></p>
<p style="white-space:pre-wrap"><?= htmlspecialchars((string) $row['notes'], ENT_QUOTES, 'UTF-8') ?></p>
<form method="post">
<?= \Tsugi\Controllers\Tool::csrfField() ?>
<input type="hidden" name="lti_client_id" value="<?= $client_id ?>">
<input type="submit" name="doDelete" class="btn btn-danger" value="Delete" onclick="return confirm('Delete this client? Keys that point at it stay, with the link cleared.');">
</form>
<?php } else {
    $client_values = isset($_POST['doSave']) ? $_POST : $row;
    $client_action = $detail_location;
    $client_cancel = $detail_location;
    require __DIR__ . '/client-fields.php';
}
$client_keys = AdminService::keysForInboundClient($client_id);
?>
<h2>Keys</h2>
<?php if ( count($client_keys) < 1 ) { ?>
<p>No keys point at this client.</p>
<?php } else { ?>
<ul>
<?php foreach ( $client_keys as $client_key ) {
    $key_name = U::get($client_key, 'key_title');
    if ( ! U::isNotEmpty($key_name) ) $key_name = U::get($client_key, 'key_key');
    if ( ! U::isNotEmpty($key_name) ) $key_name = 'Key '.$client_key['key_id'];
    $deploy = U::get($client_key, 'deploy_key');
    $deploy_label = is_string($deploy) && strlen(trim($deploy)) > 0 ? trim($deploy) : '*';
?>
<li>
<a href="<?= $key_admin ?>key-detail?key_id=<?= (int) $client_key['key_id'] ?>"><?= htmlspecialchars((string) $key_name, ENT_QUOTES, 'UTF-8') ?></a>
<?= AdminService::keyTypeHtml($client_key) ?>
<?= htmlspecialchars($deploy_label, ENT_QUOTES, 'UTF-8') ?>
</li>
<?php } ?>
</ul>
<?php } ?>
<?php
$OUTPUT->footer();
