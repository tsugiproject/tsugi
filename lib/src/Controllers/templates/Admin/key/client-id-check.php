<?php

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');

if ( ! \Tsugi\Services\Admin\AdminService::isAdmin() ) die('Must be admin');

$rows = \Tsugi\Services\Admin\AdminService::issuerClientDuplicateRows();

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
<?php if ( count($rows) < 1 ) { ?>
<p>There are no duplicate (client id, issuer) rows.</p>
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
<?php
$OUTPUT->footer();
