<?php

use Tsugi\Controllers\Tool;
use Tsugi\Core\LTIX;
use Tsugi\Core\ReqScope;
use Tsugi\Services\Permissions\PermissionTables;
use Tsugi\Util\U;

LTIX::getConnection();

if ( ! \Tsugi\Services\Admin\AdminService::isAdmin() ) {
    \Tsugi\Controllers\Login::setReturnUrl(LTIX::curPageUrlFolder());
    header('Location: '.\Tsugi\Controllers\Login::loginUrl());
    return;
}

$list_url = U::addSession('index.php');

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    if ( Tool::csrfRedirect($list_url) ) return;
    $action = (string) U::get($_POST, 'action', '');
    $grantor = ReqScope::loggedInUserIdLegacy();
    if ( $action === 'grant' ) {
        $result = PermissionTables::handGrant(
            (string) U::get($_POST, 'user', ''),
            (string) U::get($_POST, 'permission', ''),
            (string) U::get($_POST, 'expires', ''),
            $grantor
        );
        if ( $result['ok'] ) {
            U::flashSuccess(__('Grant saved.'));
        } else {
            U::flashError($result['error']);
        }
    } else if ( $action === 'revoke' ) {
        $result = PermissionTables::revokeGrant((int) U::get($_POST, 'grant_id', 0), $grantor);
        if ( $result['ok'] ) {
            U::flashSuccess(__('Grant revoked.'));
        } else {
            U::flashError($result['error']);
        }
    } else if ( $action === 'delete_grant' ) {
        $result = PermissionTables::deleteGrant((int) U::get($_POST, 'grant_id', 0));
        if ( $result['ok'] ) {
            U::flashSuccess(__('Grant deleted.'));
        } else {
            U::flashError($result['error']);
        }
    } else if ( $action === 'delete_application' ) {
        $result = PermissionTables::deleteApplication((int) U::get($_POST, 'application_id', 0));
        if ( $result['ok'] ) {
            U::flashSuccess(__('Application deleted.'));
        } else {
            U::flashError($result['error']);
        }
    }
    header('Location: '.$list_url);
    return;
}

$scopes = PermissionTables::scopes();
$applications = PermissionTables::applications();
$grants = PermissionTables::grants();
$registry = PermissionTables::registry();

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<h1><?= htmlspecialchars(__('Permissions')) ?></h1>
<p><a href="<?= htmlspecialchars($CFG->wwwroot.'/admin/') ?>"><?= __('Administration console') ?></a></p>

<h2><?= htmlspecialchars(__('System scope')) ?></h2>
<?php if ( count($scopes) < 1 ) { ?>
<p><?= htmlspecialchars(__('No scope row.')) ?></p>
<?php } else { ?>
<table class="table table-striped">
    <thead>
        <tr>
            <th><?= htmlspecialchars(__('Id')) ?></th>
            <th><?= htmlspecialchars(__('Code')) ?></th>
            <th><?= htmlspecialchars(__('Title')) ?></th>
            <th><?= htmlspecialchars(__('Created')) ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ( $scopes as $scope ) { ?>
        <tr>
            <td><?= (int) $scope['scope_id'] ?></td>
            <td><?= htmlspecialchars((string) $scope['code']) ?></td>
            <td><?= htmlspecialchars((string) $scope['title']) ?></td>
            <td><?= htmlspecialchars((string) $scope['created_at']) ?></td>
        </tr>
    <?php } ?>
    </tbody>
</table>
<?php } ?>

<h2><?= htmlspecialchars(__('Applications')) ?></h2>
<?php if ( count($applications) < 1 ) { ?>
<p><?= htmlspecialchars(__('No applications yet.')) ?></p>
<?php } else { ?>
<table class="table table-striped">
    <thead>
        <tr>
            <th><?= htmlspecialchars(__('Id')) ?></th>
            <th><?= htmlspecialchars(__('User')) ?></th>
            <th><?= htmlspecialchars(__('Permission')) ?></th>
            <th><?= htmlspecialchars(__('Status')) ?></th>
            <th><?= htmlspecialchars(__('Justification')) ?></th>
            <th><?= htmlspecialchars(__('Created')) ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ( $applications as $app ) {
        $who = PermissionTables::userLabel($app);
    ?>
        <tr>
            <td><?= (int) $app['application_id'] ?></td>
            <td><?= htmlspecialchars($who) ?></td>
            <td><?= htmlspecialchars((string) $app['permission']) ?></td>
            <td><?= htmlspecialchars((string) $app['status']) ?></td>
            <td><?= htmlspecialchars((string) $app['justification']) ?></td>
            <td><?= htmlspecialchars((string) $app['created_at']) ?></td>
            <td>
                <form method="post" action="<?= htmlspecialchars($list_url) ?>" style="display:inline;" onsubmit="return confirm(<?= htmlspecialchars(json_encode(__('Delete this application?')), ENT_QUOTES, 'UTF-8') ?>);">
                    <?= Tool::csrfField() ?>
                    <input type="hidden" name="action" value="delete_application">
                    <input type="hidden" name="application_id" value="<?= (int) $app['application_id'] ?>">
                    <button type="submit" class="btn btn-link" style="padding:0;border:0;vertical-align:baseline;"><?= htmlspecialchars(__('Delete')) ?></button>
                </form>
            </td>
        </tr>
    <?php } ?>
    </tbody>
</table>
<?php } ?>

<h2><?= htmlspecialchars(__('Grants')) ?></h2>
<form method="post" action="<?= htmlspecialchars($list_url) ?>" class="form-inline" style="margin-bottom:1em;">
    <?= Tool::csrfField() ?>
    <input type="hidden" name="action" value="grant">
    <label><?= htmlspecialchars(__('User id or email')) ?>
        <input type="text" name="user" class="form-control" required>
    </label>
    <label><?= htmlspecialchars(__('Permission')) ?>
        <select name="permission" class="form-control" required>
            <?php foreach ( $registry as $code => $perm ) {
                $title = (string) ($perm['title'] ?? $code);
            ?>
            <option value="<?= htmlspecialchars((string) $code) ?>"><?= htmlspecialchars($title.' ('.$code.')') ?></option>
            <?php } ?>
        </select>
    </label>
    <label><?= htmlspecialchars(__('Expires')) ?>
        <input type="datetime-local" name="expires" class="form-control">
    </label>
    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(__('Grant')) ?></button>
</form>

<?php if ( count($grants) < 1 ) { ?>
<p><?= htmlspecialchars(__('No grants yet.')) ?></p>
<?php } else { ?>
<table class="table table-striped">
    <thead>
        <tr>
            <th><?= htmlspecialchars(__('Id')) ?></th>
            <th><?= htmlspecialchars(__('User')) ?></th>
            <th><?= htmlspecialchars(__('Permission')) ?></th>
            <th><?= htmlspecialchars(__('Source')) ?></th>
            <th><?= htmlspecialchars(__('Active')) ?></th>
            <th><?= htmlspecialchars(__('Granted')) ?></th>
            <th><?= htmlspecialchars(__('Expires')) ?></th>
            <th><?= htmlspecialchars(__('Revoked')) ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ( $grants as $grant ) {
        $who = PermissionTables::userLabel($grant);
        $active = PermissionTables::isActive($grant);
        $gid = (int) $grant['grant_id'];
    ?>
        <tr>
            <td><?= $gid ?></td>
            <td><?= htmlspecialchars($who) ?></td>
            <td><?= htmlspecialchars((string) $grant['permission']) ?></td>
            <td><?= htmlspecialchars((string) $grant['source']) ?></td>
            <td><?= $active ? htmlspecialchars(__('Yes')) : htmlspecialchars(__('No')) ?></td>
            <td><?= htmlspecialchars((string) $grant['granted_at']) ?></td>
            <td><?= htmlspecialchars((string) ($grant['expires_at'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string) ($grant['revoked_at'] ?? '')) ?></td>
            <td>
                <?php if ( $active ) { ?>
                <form method="post" action="<?= htmlspecialchars($list_url) ?>" style="display:inline;" onsubmit="return confirm(<?= htmlspecialchars(json_encode(__('Revoke this grant?')), ENT_QUOTES, 'UTF-8') ?>);">
                    <?= Tool::csrfField() ?>
                    <input type="hidden" name="action" value="revoke">
                    <input type="hidden" name="grant_id" value="<?= $gid ?>">
                    <button type="submit" class="btn btn-link" style="padding:0;border:0;vertical-align:baseline;"><?= htmlspecialchars(__('Revoke')) ?></button>
                </form>
                ·
                <?php } ?>
                <form method="post" action="<?= htmlspecialchars($list_url) ?>" style="display:inline;" onsubmit="return confirm(<?= htmlspecialchars(json_encode(__('Delete this grant row?')), ENT_QUOTES, 'UTF-8') ?>);">
                    <?= Tool::csrfField() ?>
                    <input type="hidden" name="action" value="delete_grant">
                    <input type="hidden" name="grant_id" value="<?= $gid ?>">
                    <button type="submit" class="btn btn-link" style="padding:0;border:0;vertical-align:baseline;"><?= htmlspecialchars(__('Delete')) ?></button>
                </form>
            </td>
        </tr>
    <?php } ?>
    </tbody>
</table>
<?php } ?>
<?php
$OUTPUT->footer();
