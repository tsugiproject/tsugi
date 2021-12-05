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

$query_parms = false;
$searchfields = array("issuer_id", "issuer_title", "issuer_key", "issuer_guid",
    "issuer_client", "created_at", "updated_at");
$orderfields = $searchfields;
$sql = "SELECT issuer_id, issuer_title, issuer_key, issuer_guid, issuer_client,
        lti13_keyset_url, lti13_token_url, lti13_oidc_auth,
        created_at, updated_at
        FROM {$CFG->dbprefix}lti_issuer";

$newsql = Table::pagedQuery($sql, $query_parms, $searchfields, $orderfields);
// echo("<pre>\n$newsql\n</pre>\n");
$rows = $PDOX->allRowsDie($newsql, $query_parms);
$newrows = array();
foreach ( $rows as $row ) {
    $sql = "SELECT COUNT(key_id) AS count FROM {$CFG->dbprefix}lti_key
        WHERE issuer_id = :IID";
    $values = array(":IID" => $row['issuer_id']);
    $crow = $PDOX->rowDie($sql, $values);
    $count = $crow ? $crow['count'] : 0;

    $status = 'Ready';
    foreach($row as $key => $value ) {
        if ( strpos($key, "lti13_") !== 0 ) continue;
        if ( ! is_string($value) || strlen($value) < 1 ) $status = 'Draft';
    }
    $newrow['issuer_id'] = $row['issuer_id'];
    $newrow['issuer_title'] = $row['issuer_title'];
    $newrow['issuer_client'] = $row['issuer_client'];
    $newrow['status'] = $status;
    $newrow['key_count'] = $count;
    $newrow['created_at'] = $row['created_at'];
    $newrow['updated_at'] = $row['updated_at'];

    $newrows[] = $newrow;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<h1>LTI 1.3 Issuers</h1>
<p>
  <a href="<?= LTIX::curPageUrlFolder() ?>" class="btn btn-default">Key Requests</a>
  <a href="issuers" class="btn btn-default active">LTI 1.3 Issuers</a>
  <a href="keys" class="btn btn-default">Tenant Keys</a>
  <a href="<?= $CFG->wwwroot ?>/admin" class="btn btn-default">Admin</a>
</p>
<?php if ( count($newrows) < 1 ) { ?>
<p>
<a href="issuer-add" class="btn btn-default">Add Issuer</a>
</p>
<?php } else {
    $extra_buttons = array(
        "New Issuer" => "issuer-add",
        "Maintenance" => "issuer-maint"
    );
    Table::pagedTable($newrows, $searchfields, $orderfields, "issuer-detail", false, $extra_buttons);
}
if ( isAdmin() ) { ?>
<?php } ?>

<?php
$OUTPUT->footer();

