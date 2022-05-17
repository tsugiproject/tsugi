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

// Patch lms_issuer_sha256 in case it ended up null
$patch_sql = "UPDATE {$CFG->dbprefix}lti_key SET lms_issuer_sha256 = sha2(lms_issuer, 256)
    WHERE lms_issuer_sha256 IS NULL AND lms_issuer IS NOT NULL";
$rows = $PDOX->queryDie($patch_sql);

$query_parms = false;
$searchfields = array("K.key_id", "key_title", "key_key", "deploy_key", "K.login_at", "K.updated_at", "K.user_id", "issuer_key");
$sql = "SELECT K.key_id AS key_id, key_title, key_key, secret, lms_issuer, I.issuer_key AS issuer_key, deploy_key, K.login_at AS login_at, K.updated_at as updated_at,
    lms_issuer,
    K.user_id AS user_id
        FROM {$CFG->dbprefix}lti_key AS K
        LEFT JOIN {$CFG->dbprefix}lti_issuer AS I
        ON K.issuer_id = I.issuer_id
";

$newsql = Table::pagedQuery($sql, $query_parms, $searchfields);
// echo("<pre>\n$newsql\n</pre>\n");
$rows = $PDOX->allRowsDie($newsql, $query_parms);
$newrows = array();
foreach ( $rows as $row ) {
    $newrow = array();
    $newrow['key_id'] = $row['key_id'];
    $newrow['key'] = $row['key_key'];
    if ( strlen($row['key_title']) > 0 ) $newrow['key'] = $row['key_title'];
    $key_type = '';
    if ( is_string($row['key_key']) && strlen($row['key_key']) > 1 && is_string($row['secret']) && strlen($row['secret']) > 0 ) {
        $key_type .= 'LTI 1.1';
    }
    if ( is_string($row['lms_issuer']) && strlen($row['lms_issuer']) > 0 && is_string($row['deploy_key']) && strlen($row['deploy_key']) > 0) {
        if ( strlen($key_type) > 0 ) $key_type .= ' / ';
        $key_type .= 'LTI 1.3';
    } else if ( isset($row['issuer_key']) && is_string($row['issuer_key']) && strlen($row['issuer_key']) > 0 && is_string($row['deploy_key']) && strlen($row['deploy_key']) > 0) {
        if ( strlen($key_type) > 0 ) $key_type .= ' / ';
        $key_type .= 'LTI 1.3';
    }
    if ( $key_type == '' ) $key_type = 'Draft';
    $newrow['key_type'] = $key_type;
    $issuer_key = $row['lms_issuer'];
    if ( strlen($row['issuer_key']) > 0 ) $issuer_key = "I: " . $row['issuer_key'];
    if ( strlen($issuer_key) > 0 && strlen($row['deploy_key']) > 0 ) $issuer_key .= ' | ' . $row['deploy_key'];
    $newrow['issuer_|_deployment'] = $issuer_key;
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
  <a href="<?= LTIX::curPageUrlFolder() ?>" class="btn btn-default">Key Requests</a>
  <a href="issuers" class="btn btn-default">LTI 1.3 Issuers</a>
  <a href="keys" class="btn btn-default active">Tenant Keys</a>
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

