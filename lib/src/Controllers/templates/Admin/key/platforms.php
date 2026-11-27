<?php

use Tsugi\Core\LTIX;
use Tsugi\Services\Admin\AdminService;
use Tsugi\Services\Lti\PlatformObservation;
use Tsugi\UI\Table;
use Tsugi\Util\U;

LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');

if ( ! AdminService::isAdmin() ) die('Must be admin');

$key_admin = $CFG->wwwroot.'/admin/key/';
$ready = PlatformObservation::ready($CFG->dbprefix);

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<h1>Platform snapshots</h1>
<p>
  <a href="<?= $key_admin ?>platforms" class="btn btn-default active">Platforms</a>
  <a href="<?= $key_admin ?>clients" class="btn btn-default">Clients</a>
  <a href="<?= $key_admin ?>" class="btn btn-default">Keys</a>
  <a href="<?= $CFG->wwwroot ?>/admin" class="btn btn-default">Admin</a>
</p>
<p>
One row is a registration or a validated LTI 1.3 launch. These rows do not choose a launch.
</p>
<?php
if ( ! $ready ) {
    echo "<p>Platform snapshots are not installed. Run admin/upgrade.php.</p>\n";
    $OUTPUT->footer();
} else {

$query_parms = array();
$searchfields = array(
    'P.platform_id', 'P.source', 'P.platform_name', 'P.platform_family',
    'P.platform_version', 'P.platform_guid', 'K.key_title', 'K.key_key',
    'C.title', 'C.issuer', 'C.client_id',
);
$orderfields = array('created', 'source', 'platform_name', 'platform_family', 'platform_version', 'platform_guid');
$params = $_GET;
if ( ! isset($params['order_by']) || $params['order_by'] === '' ) {
    $params['order_by'] = 'created';
    $params['desc'] = 1;
}
$sql = "SELECT P.platform_id, P.source, P.platform_name, P.platform_family, P.platform_version,
        P.platform_guid, P.created_at AS created, P.key_id, P.lti_client_id,
        K.key_title, K.key_key, C.title AS client_title, C.issuer AS client_issuer,
        C.client_id AS client_client_id
    FROM {$CFG->dbprefix}lti_platform AS P
    LEFT JOIN {$CFG->dbprefix}lti_key AS K ON K.key_id = P.key_id
    LEFT JOIN {$CFG->dbprefix}lti_client AS C ON C.lti_client_id = P.lti_client_id
";
$newsql = Table::pagedQuery($sql, $query_parms, $searchfields, $orderfields, $params);
$rows = $PDOX->allRowsDie($newsql, $query_parms);
$newrows = array();
foreach ( $rows as $row ) {
    $key_id = (int) ($row['key_id'] ?? 0);
    $client_id = (int) ($row['lti_client_id'] ?? 0);
    $key_name = U::get($row, 'key_title');
    if ( ! U::isNotEmpty($key_name) ) $key_name = U::get($row, 'key_key');
    if ( ! U::isNotEmpty($key_name) && $key_id > 0 ) $key_name = 'Key '.$key_id;
    $client_name = U::get($row, 'client_title');
    if ( ! U::isNotEmpty($client_name) ) $client_name = U::get($row, 'client_issuer');
    if ( ! U::isNotEmpty($client_name) && $client_id > 0 ) $client_name = 'Client '.$client_id;
    $newrow = array();
    $newrow['platform_id'] = (int) $row['platform_id'];
    $newrow['source'] = $row['source'];
    $newrow['platform_name'] = (string) ($row['platform_name'] ?? '');
    $newrow['_header_platform_name'] = 'Name';
    $newrow['platform_family'] = (string) ($row['platform_family'] ?? '');
    $newrow['_header_platform_family'] = 'Family';
    $newrow['platform_version'] = (string) ($row['platform_version'] ?? '');
    $newrow['_header_platform_version'] = 'Version';
    $newrow['platform_guid'] = (string) ($row['platform_guid'] ?? '');
    $newrow['_header_platform_guid'] = 'Guid';
    $newrow['key'] = $key_id > 0 ? (string) $key_name : 'none';
    if ( $key_id > 0 ) $newrow['_href_key'] = $key_admin.'key-detail?key_id='.$key_id;
    $newrow['client'] = $client_id > 0 ? (string) $client_name : 'none';
    if ( $client_id > 0 ) $newrow['_href_client'] = $key_admin.'client-detail?lti_client_id='.$client_id;
    $newrow['created'] = $row['created'];
    $newrow['_header_created'] = 'Created';
    $newrows[] = $newrow;
}
if ( count($newrows) < 1 ) {
    echo "<p>No platform snapshots yet.</p>\n";
}
Table::pagedTable($newrows, $searchfields, $orderfields, $key_admin.'platform-detail', $params);
$OUTPUT->footer();
}
