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
    $delete_error = AdminService::clientDeleteInputError(
        $client_id,
        U::get($_POST, 'delete_site_url', ''),
        U::get($_POST, 'delete_keys', ''),
        U::get($_POST, 'delete_courses', ''),
        U::get($_POST, 'delete_users', '')
    );
    if ( $delete_error !== null ) {
        U::flashError($delete_error);
        header('Location: '.$detail_location);
        return;
    }
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
<?php
$client_usage = AdminService::clientUsageCounts($client_id);
if ( ! is_array($client_usage) ) {
    $delete_box = array(
        'blocked' => 'The keys, courses, and users for this client could not be counted. Delete stays off.',
    );
} else {
    $delete_site_url = AdminService::deleteSiteUrl();
    $delete_site_label = $delete_site_url !== '' ? $delete_site_url : 'this site';
    $delete_key_n = (int) $client_usage['keys'];
    $delete_course_n = (int) $client_usage['courses'];
    $delete_user_n = (int) $client_usage['users'];
    $delete_key_sentence = $delete_key_n === 1
        ? 'This client is used by 1 key.'
        : 'This client is used by '.number_format($delete_key_n).' keys.';
    $delete_course_sentence = $delete_course_n === 1
        ? 'Those keys have 1 course.'
        : 'Those keys have '.number_format($delete_course_n).' courses.';
    $delete_user_sentence = $delete_user_n === 1
        ? 'Those keys have 1 user.'
        : 'Those keys have '.number_format($delete_user_n).' users.';
    $delete_box = array(
        'title' => 'Warning: deleting this client detaches every key that uses it',
        'paragraphs' => array(
            'Deleting this client removes the shared LTI 1.3 issuer, client id, and platform URLs. The keys are not deleted. Their link to this client is cleared, and later launches use only the issuer and URLs still stored on each key.',
            'Courses and users on those keys stay in the database. If a key\'s own issuer or URLs are empty, launches for those courses and users stop.',
            'The counts include hidden and deleted rows.',
        ),
        'lines' => array($delete_key_sentence, $delete_course_sentence, $delete_user_sentence),
        'fields' => array(
            array(
                'name' => 'delete_site_url',
                'label' => 'Type the URL of this site ('.$delete_site_label.').',
                'kind' => 'url',
                'expected' => $delete_site_url,
            ),
            array(
                'name' => 'delete_keys',
                'label' => 'Type the number of keys that use this client ('.number_format($delete_key_n).').',
                'kind' => 'count',
                'expected' => $delete_key_n,
            ),
            array(
                'name' => 'delete_courses',
                'label' => 'Type the number of courses on those keys ('.number_format($delete_course_n).').',
                'kind' => 'count',
                'expected' => $delete_course_n,
            ),
            array(
                'name' => 'delete_users',
                'label' => 'Type the number of users on those keys ('.number_format($delete_user_n).').',
                'kind' => 'count',
                'expected' => $delete_user_n,
            ),
        ),
        'help' => 'Actually Delete stays disabled until the site URL, key count, course count, and user count all match.',
        'mismatch' => 'Type the site URL, the number of keys, the number of courses, and the number of users to delete this client.',
        'confirm' => 'WARNING: This deletes this client on '.$delete_site_label.'. '
            .number_format($delete_key_n).' keys, '.number_format($delete_course_n).' courses, and '
            .number_format($delete_user_n).' users depend on it. The keys stay, and the client link is cleared. Continue?',
        'button' => 'Delete client',
        'hidden' => array('lti_client_id' => $client_id),
    );
}
if ( ! $editing ) { ?>
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
$delete_box['section'] = 'button';
require __DIR__ . '/delete-confirm.inc.php';
$delete_box['section'] = 'panel';
require __DIR__ . '/delete-confirm.inc.php';
$delete_box['section'] = 'form';
require __DIR__ . '/delete-confirm.inc.php';
$OUTPUT->footer();
