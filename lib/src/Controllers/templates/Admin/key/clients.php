<?php

use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');

if ( ! \Tsugi\Services\Admin\AdminService::isAdmin() ) die('Must be admin');

if ( ! LTIX::ltiClientTableExists($CFG->dbprefix) ) {
    die('lti_client is not installed. Run admin/upgrade.php.');
}

$query_parms = array();
$searchfields = array('lti_client_id', 'title', 'user_id', 'issuer', 'client_id');
$sql = "SELECT lti_client_id, title, user_id, issuer, client_id, updated_at
    FROM {$CFG->dbprefix}lti_client
";

$newsql = Table::pagedQuery($sql, $query_parms, $searchfields);
$rows = $PDOX->allRowsDie($newsql, $query_parms);
$newrows = array();
foreach ( $rows as $row ) {
    $newrow = array();
    $newrow['lti_client_id'] = $row['lti_client_id'];
    $newrow['title'] = $row['title'];
    $newrow['owner'] = $row['user_id'];
    $newrow['_header_owner'] = 'User Id';
    $newrow['issuer'] = $row['issuer'];
    $newrow['client'] = $row['client_id'];
    $newrow['_header_client'] = 'Client ID';
    $newrow['updated_at'] = $row['updated_at'];
    $newrows[] = $newrow;
}

$key_admin = $CFG->wwwroot.'/admin/key/';
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<h1>LTI 1.3 Clients</h1>
<p>
  <a href="<?= $key_admin ?>clients" class="btn btn-default active">Clients</a>
  <a href="<?= $key_admin ?>platforms" class="btn btn-default">Platforms</a>
  <a href="<?= $key_admin ?>" class="btn btn-default">Keys</a>
  <a href="<?= $CFG->wwwroot ?>/admin" class="btn btn-default">Admin</a>
</p>
<p>
A client needs an issuer. Client id and the platform URLs can wait until the platform assigns them.
A key can point at one and leave its own LTI 1.3 platform fields empty.
Launch uses the client only when that key points at it and the issuer, client id, and platform URLs are all set.
</p>
<?php if ( count($newrows) < 1 ) { ?>
<p>You have no clients yet.</p>
<?php }
$extra_buttons = array(
  "Insert Client" => $key_admin."client-add"
);
Table::pagedTable($newrows, $searchfields, false, $key_admin."client-detail", false, $extra_buttons);

$OUTPUT->footer();
