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

$keep = array();
foreach ( array('app_start', 'grant_start') as $pageKey ) {
    $pageAt = (int) U::get($_GET, $pageKey, 0);
    if ( $pageAt > 0 ) {
        $keep[$pageKey] = $pageAt;
    }
}
$pageQuery = http_build_query($keep);
$list_url = U::addSession('index.php'.($pageQuery !== '' ? '?'.$pageQuery : ''));

$permissionsPageUrl = function ($param, $start) {
    $q = $_GET;
    unset($q[session_name()]);
    if ( $start > 0 ) {
        $q[$param] = $start;
    } else {
        unset($q[$param]);
    }
    $qs = http_build_query($q);
    return U::addSession('index.php'.($qs !== '' ? '?'.$qs : ''));
};

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
    } else if ( $action === 'approve_application' || $action === 'reject_application' || $action === 'revise_application' ) {
        if ( $action === 'approve_application' ) {
            $decision = PermissionTables::STATUS_APPROVED;
        } else if ( $action === 'revise_application' ) {
            $decision = PermissionTables::STATUS_REVISION;
        } else {
            $decision = PermissionTables::STATUS_DENIED;
        }
        $result = PermissionTables::reviewApplication(
            (int) U::get($_POST, 'application_id', 0),
            $decision,
            $grantor,
            (string) U::get($_POST, 'response', '')
        );
        if ( $result['ok'] ) {
            if ( $action === 'approve_application' ) {
                U::flashSuccess(__('Application approved.'));
            } else if ( $action === 'revise_application' ) {
                U::flashSuccess(__('Application sent back for revision.'));
            } else {
                U::flashSuccess(__('Application rejected.'));
            }
        } else {
            U::flashError($result['error']);
        }
    }
    header('Location: '.$list_url);
    return;
}

$scopes = PermissionTables::scopes();
$appPage = PermissionTables::applications((int) U::get($_GET, 'app_start', 0));
$grantPage = PermissionTables::grants((int) U::get($_GET, 'grant_start', 0));
$applications = $appPage['rows'];
$grants = $grantPage['rows'];
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
<?php if ( $appPage['start'] > 0 || $appPage['more'] ) { ?>
<p>
<?php if ( $appPage['start'] > 0 ) { ?>
<a href="<?= htmlspecialchars($permissionsPageUrl('app_start', max(0, $appPage['start'] - $appPage['length']))) ?>"><?= htmlspecialchars(__('Back')) ?></a>
<?php } ?>
<?php if ( $appPage['more'] ) { ?>
<a href="<?= htmlspecialchars($permissionsPageUrl('app_start', $appPage['start'] + $appPage['length'])) ?>"><?= htmlspecialchars(__('Next')) ?></a>
<?php } ?>
</p>
<?php } ?>
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
            <th><?= htmlspecialchars(__('Response')) ?></th>
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
            <td><?= htmlspecialchars((string) (array(
                PermissionTables::STATUS_PENDING => __('Pending'),
                PermissionTables::STATUS_APPROVED => __('Approved'),
                PermissionTables::STATUS_DENIED => __('Denied'),
                PermissionTables::STATUS_REVISION => __('Sent back for revision'),
            )[(string) $app['status']] ?? (string) $app['status'])) ?></td>
            <td><?= htmlspecialchars((string) $app['justification']) ?></td>
            <td><?= htmlspecialchars((string) ($app['response'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string) $app['created_at']) ?></td>
            <td>
                <?php if ( (string) $app['status'] === PermissionTables::STATUS_PENDING ) {
                    $approveConfirm = json_encode(__('Approve this application?'));
                    $rejectConfirm = json_encode(__('Reject this application?'));
                    $reviseConfirm = json_encode(__('Send this application back for revision?'));
                    $reviseNeed = json_encode(__('Write a reply before sending this back.'));
                ?>
                <form method="post" action="<?= htmlspecialchars($list_url) ?>" style="margin-bottom:0.4em;min-width:16em;" onsubmit="var button = event.submitter; var action = button ? button.value : ''; var note = (this.response && this.response.value || '').trim(); if (action === 'revise_application' && note === '') { alert(<?= htmlspecialchars($reviseNeed, ENT_QUOTES, 'UTF-8') ?>); return false; } var ask = action === 'revise_application' ? <?= htmlspecialchars($reviseConfirm, ENT_QUOTES, 'UTF-8') ?> : (action === 'reject_application' ? <?= htmlspecialchars($rejectConfirm, ENT_QUOTES, 'UTF-8') ?> : <?= htmlspecialchars($approveConfirm, ENT_QUOTES, 'UTF-8') ?>); return confirm(ask);">
                    <?= Tool::csrfField() ?>
                    <input type="hidden" name="application_id" value="<?= (int) $app['application_id'] ?>">
                    <label><?= htmlspecialchars(__('Reply')) ?>
                        <textarea name="response" class="form-control" rows="3" maxlength="<?= (int) PermissionTables::JUSTIFICATION_MAX ?>" placeholder="<?= htmlspecialchars(__('Tell them what to change. Required to send back.')) ?>"></textarea>
                    </label>
                    <div style="margin-top:0.4em;">
                    <button type="submit" name="action" value="approve_application" class="btn btn-primary btn-sm"><?= htmlspecialchars(__('Approve')) ?></button>
                    <button type="submit" name="action" value="revise_application" class="btn btn-default btn-sm"><?= htmlspecialchars(__('Send back')) ?></button>
                    <button type="submit" name="action" value="reject_application" class="btn btn-default btn-sm"><?= htmlspecialchars(__('Reject')) ?></button>
                    </div>
                </form>
                <?php } ?>
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
<?php if ( $grantPage['start'] > 0 || $grantPage['more'] ) { ?>
<p>
<?php if ( $grantPage['start'] > 0 ) { ?>
<a href="<?= htmlspecialchars($permissionsPageUrl('grant_start', max(0, $grantPage['start'] - $grantPage['length']))) ?>"><?= htmlspecialchars(__('Back')) ?></a>
<?php } ?>
<?php if ( $grantPage['more'] ) { ?>
<a href="<?= htmlspecialchars($permissionsPageUrl('grant_start', $grantPage['start'] + $grantPage['length'])) ?>"><?= htmlspecialchars(__('Next')) ?></a>
<?php } ?>
</p>
<?php } ?>
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
