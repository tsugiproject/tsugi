<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');

if ( ! \Tsugi\Services\Admin\AdminService::isAdmin() ) die('Must be admin');

$rows = \Tsugi\Services\Admin\AdminService::issuerClientDuplicateRows();
$groups = \Tsugi\Services\Admin\AdminService::clientConfigConflictGroups();
$deployment_groups = \Tsugi\Services\Admin\AdminService::keyDeploymentConflictGroups();
$long_values = \Tsugi\Services\Admin\AdminService::valuesLongerThan255();

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
<h2>Deployment ids</h2>
<p>
Each key should have one deployment id. This check reads the deployment id on the key and the deployment id stored on each context.
A blank value does not count. Links have no deployment id column.
</p>
<?php if ( count($deployment_groups) < 1 ) { ?>
<p>There are no keys with more than one deployment id.</p>
<?php } else {
    $key_word = count($deployment_groups) === 1 ? 'key has' : 'keys have';
?>
<div class="alert alert-danger" style="margin: 10px;">
<p><?= count($deployment_groups) ?> <?= $key_word ?> more than one deployment id.</p>
</div>
<?php foreach ( $deployment_groups as $group ) {
    $key_id = (int) U::get($group, 'key_id', 0);
    $title = U::get($group, 'key_title');
    $label = (is_string($title) && trim($title) !== '') ? trim($title) : ('key '.$key_id);
    $label .= ' ('.$key_id.')';
    if ( (int) U::get($group, 'deleted', 0) === 1 ) $label .= ' (deleted)';
    $detail = 'key-detail?key_id='.$key_id;
    $values = U::get($group, 'values', array());
    if ( ! is_array($values) ) $values = array();
?>
<h2><a href="<?= htmlentities($detail) ?>"><?= htmlentities($label) ?></a></h2>
<table class="table table-striped">
<thead>
<tr>
  <th>Deployment id</th>
  <th>Where</th>
  <th>Rows</th>
  <th>Example id</th>
</tr>
</thead>
<tbody>
<?php foreach ( $values as $value ) {
    $places = U::get($value, 'places', array());
    if ( ! is_array($places) ) continue;
    $deployment_id = (string) U::get($value, 'deployment_id', '');
    foreach ( $places as $place ) {
?>
<tr>
  <td><?= htmlentities($deployment_id) ?></td>
  <td><?= htmlentities((string) U::get($place, 'label', '')) ?></td>
  <td><?= (int) U::get($place, 'count', 0) ?></td>
  <td><?= (int) U::get($place, 'example_id', 0) ?></td>
</tr>
<?php } ?>
<?php } ?>
</tbody>
</table>
<?php } ?>
<?php } ?>
<h2>Longer than 255 characters</h2>
<p>
Issuer, client id, and deployment id are read from the key. Deployment id is also read from each context.
Context id is read from each course, including the calendar copy. Resource link id is read from each link and each content row.
A varchar(255) column cannot store a longer value. Outbound tool client ids and deployment ids are already varchar(255).
</p>
<?php if ( count($long_values) < 1 ) { ?>
<p>There are no issuer, client id, deployment id, context id, or resource link id values longer than 255 characters.</p>
<?php } else {
    $value_word = count($long_values) === 1 ? 'value is' : 'values are';
?>
<div class="alert alert-danger" style="margin: 10px;">
<p><?= count($long_values) ?> <?= $value_word ?> longer than 255 characters.</p>
</div>
<table class="table table-striped">
<thead>
<tr>
  <th>Field</th>
  <th>Key</th>
  <th>Row</th>
  <th>Length</th>
  <th>Starts with</th>
</tr>
</thead>
<tbody>
<?php foreach ( $long_values as $long_row ) {
    $key_id = (int) U::get($long_row, 'key_id', 0);
    $row_id = (int) U::get($long_row, 'row_id', 0);
    $table = (string) U::get($long_row, 'table', '');
    $where = $table.' '.$row_id;
    $detail = 'key-detail?key_id='.$key_id;
?>
<tr>
  <td><?= htmlentities((string) U::get($long_row, 'label', '')) ?></td>
  <td><?php if ( $key_id > 0 ) { ?><a href="<?= htmlentities($detail) ?>"><?= $key_id ?></a><?php } else { ?>0<?php } ?></td>
  <td><?= htmlentities($where) ?></td>
  <td><?= (int) U::get($long_row, 'length', 0) ?></td>
  <td><?= htmlentities((string) U::get($long_row, 'sample', '')) ?></td>
</tr>
<?php } ?>
</tbody>
</table>
<?php } ?>
<?php
$OUTPUT->footer();
