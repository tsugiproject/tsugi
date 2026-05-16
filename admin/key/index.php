<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();
require_once("../gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

if ( ! isAdmin() ) die('Must be admin');

$lti_issuer_table = "{$CFG->dbprefix}lti_issuer";
$have_lti_issuer_table = ($PDOX->metadata($lti_issuer_table) !== false);
$show_lti_issuer_nav = false;
if ( $have_lti_issuer_table ) {
    $issuer_count_stmt = $PDOX->queryReturnError(
        "SELECT COUNT(*) AS issuer_count FROM {$lti_issuer_table}
            WHERE (deleted IS NULL OR deleted = 0)",
        false,
        false
    );
    if ( $issuer_count_stmt && $issuer_count_stmt->success ) {
        $issuer_count_row = $issuer_count_stmt->fetch(\PDO::FETCH_ASSOC);
        $show_lti_issuer_nav = $issuer_count_row && (int) $issuer_count_row['issuer_count'] > 0;
    }
}

// Patch lms_issuer_sha256 in case it ended up null
$patch_sql = "UPDATE {$CFG->dbprefix}lti_key SET lms_issuer_sha256 = sha2(lms_issuer, 256)
    WHERE lms_issuer_sha256 IS NULL AND lms_issuer IS NOT NULL";
$rows = $PDOX->queryDie($patch_sql);

$query_parms = array();
if ( $have_lti_issuer_table ) {
    $searchfields = array("K.key_id", "key_title", "key_key", "deploy_key", "K.login_at", "K.updated_at", "K.user_id", "issuer_key", "issuer_client", "K.lms_client");
    $sql = "SELECT K.key_id AS key_id, key_title, key_key, secret, lms_issuer, K.lms_client AS lms_client,
        K.issuer_id AS issuer_id, I.issuer_key AS issuer_key, I.issuer_client AS issuer_client,
        deploy_key, K.login_at AS login_at, K.updated_at as updated_at,
        K.user_id AS user_id
            FROM {$CFG->dbprefix}lti_key AS K
            LEFT JOIN {$CFG->dbprefix}lti_issuer AS I
            ON K.issuer_id = I.issuer_id
    ";
} else {
    $searchfields = array("K.key_id", "key_title", "key_key", "deploy_key", "K.login_at", "K.updated_at", "K.user_id", "K.lms_client");
    $sql = "SELECT K.key_id AS key_id, key_title, key_key, secret, lms_issuer, K.lms_client AS lms_client,
        K.issuer_id AS issuer_id, NULL AS issuer_key, NULL AS issuer_client,
        deploy_key, K.login_at AS login_at, K.updated_at as updated_at,
        K.user_id AS user_id
            FROM {$CFG->dbprefix}lti_key AS K
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
    $key_type = '';
    if ( is_string($row['key_key']) && !empty($row['key_key']) && is_string($row['secret']) && !empty($row['secret']) ) {
        $key_type .= 'LTI 1.1';
    }
    $lti13_lms = is_string($row['lms_issuer']) && !empty($row['lms_issuer'])
        && is_string($row['lms_client']) && !empty($row['lms_client']);
    $lti13_global = isset($row['issuer_id']) && is_numeric($row['issuer_id']) && (int) $row['issuer_id'] > 0;
    if ( $lti13_lms ) {
        if ( !empty($key_type) ) $key_type .= ' / ';
        $key_type .= 'LTI 1.3';
    } else if ( $lti13_global ) {
        if ( !empty($key_type) ) $key_type .= ' / ';
        $key_type .= 'LTI 1.3';
    } else if ( isset($row['issuer_key']) && is_string($row['issuer_key']) && !empty($row['issuer_key']) && is_string($row['deploy_key']) && !empty($row['deploy_key'])) {
        if ( !empty($key_type) ) $key_type .= ' / ';
        $key_type .= 'LTI 1.3';
    }
    if ( $key_type == '' ) $key_type = 'Draft';
    $newrow['key_type'] = $key_type;
    $issuer_key = $row['lms_issuer'];
    $client_id = $row['lms_client'];
    if ( !empty($row['issuer_key']) ) {
        $issuer_key = "I: " . $row['issuer_key'];
        if ( !empty($row['issuer_client']) ) {
            $client_id = $row['issuer_client'];
        }
    }
    $issuer_display = '';
    if ( !empty($issuer_key) ) {
        $issuer_display = $issuer_key;
        if ( !empty($client_id) ) {
            $issuer_display .= ' | ' . $client_id;
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
    $newrow['user_id'] = $row['user_id'];
    $newrows[] = $newrow;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<h1>LTI Tenants (Keys)</h1>
<p>
  <a href="<?= LTIX::curPageUrlFolder() ?>" class="btn btn-default active">Tenant Keys</a>
<?php if ( $CFG->providekeys ) { ?>
  <a href="requests" class="btn btn-default">Key Requests</a>
<?php } ?>
<?php if ( $show_lti_issuer_nav ) { ?>
  <a href="issuers" class="btn btn-default">LTI 1.3 Issuers</a>
<?php } ?>
  <a href="<?= $CFG->wwwroot ?>/admin" class="btn btn-default">Admin</a>
</p>
<?php if ( count($newrows) < 1 ) { ?>
<p>
You have no Tenant keys for this system.
</p>
<?php }
$extra_buttons = array(
  "Insert Tenant" => "key-add"
);
Table::pagedTable($newrows, $searchfields, false, "key-detail", false, $extra_buttons);
// echo("<pre>\n");print_r($newrows);echo("</pre>\n");
if ( isAdmin() ) { ?>
<?php } ?>

<?php
$OUTPUT->footer();

