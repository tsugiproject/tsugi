<?php

use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');

if ( ! \Tsugi\Services\Admin\AdminService::isAdmin() ) die('Must be admin');

$query_parms = array();
$searchfields = array('lti_registration_id', 'title', 'issuer', 'client_id');
$sql = "SELECT lti_registration_id, title, issuer, client_id, updated_at
    FROM {$CFG->dbprefix}lti_registration
";

$newsql = Table::pagedQuery($sql, $query_parms, $searchfields);
$rows = $PDOX->allRowsDie($newsql, $query_parms);
$newrows = array();
foreach ( $rows as $row ) {
    $newrow = array();
    $newrow['lti_registration_id'] = $row['lti_registration_id'];
    $newrow['title'] = $row['title'];
    $newrow['issuer'] = $row['issuer'];
    $newrow['client'] = $row['client_id'];
    $newrow['_header_client'] = 'Client ID';
    $newrow['updated_at'] = $row['updated_at'];
    $newrows[] = $newrow;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<h1>LTI 1.3 Registrations</h1>
<p>
  <a href="registrations" class="btn btn-default active">Registrations</a>
  <a href="<?= LTIX::curPageUrlFolder() ?>" class="btn btn-default">Keys</a>
  <a href="<?= $CFG->wwwroot ?>/admin" class="btn btn-default">Admin</a>
</p>
<p>
A registration needs an issuer. Client id and the platform URLs can wait until the platform assigns them.
A key can point at one and leave its own LTI 1.3 platform fields empty.
</p>
<?php if ( count($newrows) < 1 ) { ?>
<p>You have no registrations yet.</p>
<?php }
$extra_buttons = array(
  "Insert Registration" => "registration-add"
);
Table::pagedTable($newrows, $searchfields, false, "registration-detail", false, $extra_buttons);

$OUTPUT->footer();
