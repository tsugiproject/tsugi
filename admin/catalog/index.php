<?php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Controllers\Tool;
use \Tsugi\Services\Catalog\CatalogRepository;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../admin_util.php';

LTIX::getConnection();
session_start();
require_once __DIR__ . '/../gate.php';
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

if ( ! isAdmin() ) {
    \Tsugi\Controllers\Login::setReturnUrl(LTIX::curPageUrlFolder());
    header('Location: '.\Tsugi\Controllers\Login::loginUrl());
    return;
}

$list_url = U::addSession('index.php');

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    if ( Tool::csrfRedirect($list_url) ) return;
    $action = (string) U::get($_POST, 'action', '');
    if ( $action === 'delete' ) {
        if ( ! CatalogRepository::tableExists() ) {
            U::flashError(__('Course catalog table is missing. Run Upgrade Database.'));
        } else {
            CatalogRepository::delete((int) U::get($_POST, 'catalog_id', 0));
            U::flashSuccess(__('Catalog entry deleted.'));
        }
    }
    header('Location: '.$list_url);
    return;
}

$table_ok = CatalogRepository::tableExists();
$rows = $table_ok ? CatalogRepository::listAdmin() : array();

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<h1><?= htmlspecialchars(__('Course catalog')) ?></h1>
<p><a href="<?= htmlspecialchars($CFG->wwwroot.'/admin/') ?>"><?= __('Administration console') ?></a></p>
<?php if ( ! $table_ok ) { ?>
<p><?= __('The course_catalog table is missing. Run Upgrade Database in the Administration console, then return here.') ?></p>
<?php
    $OUTPUT->footer();
    return;
}
?>
<p>
    <a class="btn btn-primary" href="<?= htmlspecialchars(U::addSession('edit.php')) ?>"><?= __('Add listing') ?></a>
    <a class="btn btn-default" href="<?= htmlspecialchars($CFG->wwwroot.'/catalog') ?>" target="_blank"><?= __('View catalog') ?></a>
</p>
<?php if ( count($rows) < 1 ) { ?>
<p><?= __('No catalog listings yet.') ?></p>
<?php } else { ?>
<table class="table table-striped">
    <thead>
        <tr>
            <th><?= __('Title') ?></th>
            <th><?= __('Kind') ?></th>
            <th><?= __('Published') ?></th>
            <th><?= __('Order') ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ( $rows as $row ) {
        $id = (int) ($row['catalog_id'] ?? 0);
        $title = (string) ($row['title'] ?? '');
        $is_link = trim((string) ($row['external_url'] ?? '')) !== '';
        $kind = $is_link ? __('Link') : __('Course');
        $pub = ! empty($row['published']) ? __('Yes') : __('No');
        $edit = U::addSession('edit.php?id='.$id);
    ?>
        <tr>
            <td><?= htmlspecialchars($title) ?></td>
            <td><?= htmlspecialchars($kind) ?></td>
            <td><?= htmlspecialchars($pub) ?></td>
            <td><?= (int) ($row['sort_order'] ?? 0) ?></td>
            <td>
                <a href="<?= htmlspecialchars($edit) ?>"><?= __('Edit') ?></a>
                ·
                <form method="post" action="<?= htmlspecialchars($list_url) ?>" style="display:inline;" onsubmit="return confirm(<?= htmlspecialchars(json_encode(__('Delete this catalog listing?')), ENT_QUOTES, 'UTF-8') ?>);">
                    <?= Tool::csrfField() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="catalog_id" value="<?= $id ?>">
                    <button type="submit" class="btn btn-link" style="padding:0;border:0;vertical-align:baseline;"><?= __('Delete') ?></button>
                </form>
            </td>
        </tr>
    <?php } ?>
    </tbody>
</table>
<?php } ?>
<?php
$OUTPUT->footer();
