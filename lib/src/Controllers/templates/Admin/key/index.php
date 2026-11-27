<?php
// In the top frame, we use cookies for session.

use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');

if ( ! \Tsugi\Services\Admin\AdminService::isAdmin() ) die('Must be admin');

// Fill a missing issuer hash. A blank issuer is a wildcard and stays NULL.
$patch_sql = "UPDATE {$CFG->dbprefix}lti_key SET lms_issuer_sha256 = sha2(lms_issuer, 256)
    WHERE lms_issuer_sha256 IS NULL AND lms_issuer IS NOT NULL AND lms_issuer <> ''
        AND LOWER(TRIM(lms_issuer)) <> 'null'";
$rows = $PDOX->queryDie($patch_sql);

$query_parms = array();
$have_client_table = LTIX::ltiClientTableExists($CFG->dbprefix);
$searchfields = array("K.key_id", "key_title", "key_key", "deploy_key", "K.login_at", "K.updated_at", "K.user_id", "K.lms_client");
if ( $have_client_table ) {
    $searchfields[] = "C.issuer";
    $searchfields[] = "C.client_id";
}
$sql = "SELECT K.key_id AS key_id, key_title, key_key, secret, lms_issuer, K.lms_client AS lms_client,
    lms_oidc_auth, lms_keyset_url, lms_token_url,
    deploy_key, K.login_at AS login_at, K.updated_at as updated_at,
    K.user_id AS user_id";
if ( $have_client_table ) {
    $sql .= ", K.lti_client_id AS lti_client_id, C.issuer AS client_issuer, C.client_id AS client_client_id";
}
$sql .= "
        FROM {$CFG->dbprefix}lti_key AS K
";
if ( $have_client_table ) {
    $sql .= "        LEFT JOIN {$CFG->dbprefix}lti_client AS C
            ON C.lti_client_id = K.lti_client_id
";
}

$newsql = Table::pagedQuery($sql, $query_parms, $searchfields);
// echo("<pre>\n$newsql\n</pre>\n");
$rows = $PDOX->allRowsDie($newsql, $query_parms);
$newrows = array();
foreach ( $rows as $row ) {
    $newrow = array();
    $newrow['key_id'] = $row['key_id'];
    $newrow['key'] = $row['key_key'];
    if ( !empty($row['key_title']) ) $newrow['key'] = $row['key_title'];
    $newrow['owner'] = $row['user_id'];
    $newrow['_header_owner'] = 'User Id';
    $newrow['key_type'] = \Tsugi\Services\Admin\AdminService::keyTypeLabel($row);
    $newrow['_html_key_type'] = \Tsugi\Services\Admin\AdminService::keyTypeHtml($row);
    $issuer_key = $row['lms_issuer'];
    $client_id = $row['lms_client'];
    if ( empty($issuer_key) && empty($client_id) && ! empty($row['client_issuer']) ) {
        $issuer_key = $row['client_issuer'];
        $client_id = $row['client_client_id'];
    }
    $issuer_display = '';
    if ( !empty($issuer_key) || !empty($client_id) ) {
        if ( !empty($issuer_key) ) {
            $issuer_display = $issuer_key;
        }
        if ( !empty($client_id) ) {
            if ( !empty($issuer_display) ) $issuer_display .= ' | ';
            $issuer_display .= $client_id;
        }
        if ( is_string($row['deploy_key']) && strlen(trim($row['deploy_key'])) > 0 ) {
            $issuer_display .= ' | ' . $row['deploy_key'];
        } else {
            $issuer_display .= ' | *';
        }
    }
    $newrow['issuer_|_deployment'] = $issuer_display;
    $newrow['login_at'] = $row['login_at'];
    $newrow['updated_at'] = $row['updated_at'];
    $newrows[] = $newrow;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
echo \Tsugi\Services\Admin\AdminService::clientIdCheckNotices();
?>
<h1>LTI Tenants (Keys)</h1>
<p>
  <a href="<?= $CFG->wwwroot ?>/admin/key/clients" class="btn btn-default">Clients</a>
  <a href="<?= $CFG->wwwroot ?>/admin/key/platforms" class="btn btn-default">Platforms</a>
  <a href="<?= LTIX::curPageUrlFolder() ?>" class="btn btn-default active">Keys</a>
<?php if ( $CFG->providekeys ) { ?>
  <a href="requests" class="btn btn-default">Key Requests</a>
<?php } ?>
  <a href="client-id-check" class="btn btn-default">Client Id Check</a>
  <a href="<?= $CFG->wwwroot ?>/admin" class="btn btn-default">Admin</a>
</p>
<?php if ( count($newrows) < 1 ) { ?>
<p>
You have no Tenant keys for this system.
</p>
<?php }
$extra_buttons = array(
  "Insert Client" => "client-add",
  "Insert Tenant" => "key-add"
);
Table::pagedTable($newrows, $searchfields, false, "key-detail", false, $extra_buttons);
// echo("<pre>\n");print_r($newrows);echo("</pre>\n");
if ( \Tsugi\Services\Admin\AdminService::isAdmin() ) { ?>
<?php } ?>

<?php
$OUTPUT->footer();
