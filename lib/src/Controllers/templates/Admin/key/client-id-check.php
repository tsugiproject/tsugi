<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');

if ( ! \Tsugi\Services\Admin\AdminService::isAdmin() ) die('Must be admin');

$rows = \Tsugi\Services\Admin\AdminService::issuerClientDuplicateRows();
$groups = \Tsugi\Services\Admin\AdminService::clientConfigConflictGroups();

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
echo \Tsugi\Services\Admin\AdminService::issuerClientDuplicateNotice();
?>
<h1>Client Id Check</h1>
<p>
  <a href="<?= LTIX::curPageUrlFolder() ?>" class="btn btn-default">Tenant Keys</a>
  <a href="client-id-check" class="btn btn-default active">Client Id Check</a>
  <a href="<?= $CFG->wwwroot ?>/admin" class="btn btn-default">Admin</a>
</p>
<h2>Duplicate (client id, issuer, deployment id)</h2>
<?php if ( count($rows) < 1 ) { ?>
<p>There are no duplicate (client id, issuer, deployment id) rows.</p>
<?php } else { ?>
<table class="table table-striped">
<thead>
<tr>
  <th>Key</th>
  <th>Client id</th>
  <th>Issuer</th>
  <th>Deployment id</th>
</tr>
</thead>
<tbody>
<?php foreach ( $rows as $row ) {
    $key_id = (int) U::get($row, 'key_id', 0);
    $title = U::get($row, 'key_title');
    $label = (is_string($title) && trim($title) !== '') ? trim($title) : ('key '.$key_id);
    if ( (int) U::get($row, 'deleted', 0) === 1 ) $label .= ' (deleted)';
    $stored_deploy = U::get($row, 'deploy_key');
    $deploy_label = (is_string($stored_deploy) && trim($stored_deploy) !== '') ? trim($stored_deploy) : '(none)';
    $detail = 'key-detail?key_id='.$key_id;
?>
<tr>
  <td><a href="<?= htmlentities($detail) ?>"><?= htmlentities($label) ?></a></td>
  <td><?= htmlentities((string) U::get($row, 'lms_client', '')) ?></td>
  <td><?= htmlentities((string) U::get($row, 'lms_issuer', '')) ?></td>
  <td><?= htmlentities($deploy_label) ?></td>
</tr>
<?php } ?>
</tbody>
</table>
<?php } ?>
<h2>Configuration URLs</h2>
<p>
Keys that share an issuer and client id must agree on OIDC auth URL, keyset URL, token URL, and token audience.
A blank value does not conflict with a filled one.
</p>
<?php if ( count($groups) < 1 ) { ?>
<p>There are no (client id, issuer) rows with conflicting configuration URLs.</p>
<?php } else {
    $pair_word = count($groups) === 1 ? 'pair' : 'pairs';
?>
<div class="alert alert-danger" style="margin: 10px;">
<p><?= count($groups) ?> issuer and client id <?= $pair_word ?> will collide when collapsed.</p>
</div>
<?php foreach ( $groups as $group ) { ?>
<h2><?= htmlentities($group['lms_issuer']) ?></h2>
<p>Client id: <?= htmlentities($group['lms_client']) ?></p>
<p>Problematic fields: <?= htmlentities(implode(', ', $group['fields'])) ?></p>
<table class="table table-striped">
<thead>
<tr>
  <th>Key</th>
<?php foreach ( $group['fields'] as $label ) { ?>
  <th><?= htmlentities($label) ?></th>
<?php } ?>
</tr>
</thead>
<tbody>
<?php foreach ( $group['keys'] as $row ) {
    $key_id = (int) U::get($row, 'key_id', 0);
    $title = U::get($row, 'key_title');
    $label = (is_string($title) && trim($title) !== '') ? trim($title) : ('key '.$key_id);
    $label .= ' ('.$key_id.')';
    if ( (int) U::get($row, 'deleted', 0) === 1 ) $label .= ' (deleted)';
    $detail = 'key-detail?key_id='.$key_id;
?>
<tr>
  <td><a href="<?= htmlentities($detail) ?>"><?= htmlentities($label) ?></a></td>
<?php foreach ( $group['fields'] as $column => $field_label ) {
    $value = U::get($row, $column);
    $shown = (is_string($value) && trim($value) !== '') ? trim($value) : '(blank)';
?>
  <td><?= htmlentities($shown) ?></td>
<?php } ?>
</tr>
<?php } ?>
</tbody>
</table>
<?php } ?>
<?php } ?>
<?php
$OUTPUT->footer();
