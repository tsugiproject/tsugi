<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');

if ( ! \Tsugi\Services\Admin\AdminService::isAdmin() ) die('Must be admin');

$check_error = '';
try {
    $rows = \Tsugi\Services\Admin\AdminService::issuerClientDuplicateRows();
    $groups = \Tsugi\Services\Admin\AdminService::clientConfigConflictGroups();
    $deployment_groups = \Tsugi\Services\Admin\AdminService::keyDeploymentConflictGroups();
    $long_values = \Tsugi\Services\Admin\AdminService::valuesLongerThan255();
    $non_ascii_values = \Tsugi\Services\Admin\AdminService::nonAsciiValues();
} catch (\Throwable $e) {
    error_log('Client id check failed: '.$e->getMessage());
    $check_error = 'Client id check failed.';
    $rows = array();
    $groups = array();
    $deployment_groups = array();
    $long_values = array();
    $non_ascii_values = array();
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<h1>Client Id Check</h1>
<?php if ( $check_error !== '' ) { ?>
<div class="alert alert-warning" style="margin: 10px;"><p><?= htmlentities($check_error) ?></p></div>
<?php
$OUTPUT->footer();
return;
}
echo \Tsugi\Services\Admin\AdminService::issuerClientDuplicateNotice();
?>
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
The LTI 1.1 consumer key and secret are read from the key, including a pending new secret and the calendar copy. A domain stores its own consumer key and secret. An outbound tool registration stores its own secret.
A varchar(255) column cannot store a longer value. Outbound tool client ids, deployment ids, and LTI 1.1 consumer keys are already varchar(255).
The start of a secret is not shown.
</p>
<?php if ( count($long_values) < 1 ) { ?>
<p>There are no issuer, client id, deployment id, context id, resource link id, consumer key, or secret values longer than 255 characters.</p>
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
  <td><?php if ( ! empty($long_row['redacted']) ) { ?>(not shown)<?php } else { ?><?= htmlentities((string) U::get($long_row, 'sample', '')) ?><?php } ?></td>
</tr>
<?php } ?>
</tbody>
</table>
<?php } ?>
<h2>Characters outside ASCII</h2>
<p>
Same columns as the length check. These columns are utf8, so a character outside ASCII takes more than one byte.
An ascii column, including ascii_bin, cannot store that character. A blank value is ASCII.
The start of a secret is not shown.
</p>
<?php if ( count($non_ascii_values) < 1 ) { ?>
<p>There are no issuer, client id, deployment id, context id, resource link id, consumer key, or secret values with a character outside ASCII.</p>
<?php } else {
    $ascii_word = count($non_ascii_values) === 1 ? 'value contains' : 'values contain';
?>
<div class="alert alert-danger" style="margin: 10px;">
<p><?= count($non_ascii_values) ?> <?= $ascii_word ?> a character outside ASCII.</p>
</div>
<table class="table table-striped">
<thead>
<tr>
  <th>Field</th>
  <th>Key</th>
  <th>Row</th>
  <th>Characters</th>
  <th>Bytes</th>
  <th>Sample</th>
</tr>
</thead>
<tbody>
<?php foreach ( $non_ascii_values as $ascii_row ) {
    $key_id = (int) U::get($ascii_row, 'key_id', 0);
    $row_id = (int) U::get($ascii_row, 'row_id', 0);
    $table = (string) U::get($ascii_row, 'table', '');
    $where = $table.' '.$row_id;
    $detail = 'key-detail?key_id='.$key_id;
?>
<tr>
  <td><?= htmlentities((string) U::get($ascii_row, 'label', '')) ?></td>
  <td><?php if ( $key_id > 0 ) { ?><a href="<?= htmlentities($detail) ?>"><?= $key_id ?></a><?php } else { ?>0<?php } ?></td>
  <td><?= htmlentities($where) ?></td>
  <td><?= (int) U::get($ascii_row, 'length', 0) ?></td>
  <td><?= (int) U::get($ascii_row, 'bytes', 0) ?></td>
  <td><?php if ( ! empty($ascii_row['redacted']) ) { ?>(not shown)<?php } else { ?><?= htmlentities((string) U::get($ascii_row, 'sample', '')) ?><?php } ?></td>
</tr>
<?php } ?>
</tbody>
</table>
<?php } ?>
<?php
$OUTPUT->footer();
