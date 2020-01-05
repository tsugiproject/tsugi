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
$searchfields = array("key_id", "key_key", "created_at", "updated_at", "user_id");
$sql = "SELECT key_id, key_key, secret, login_at, created_at, updated_at, user_id
        FROM {$CFG->dbprefix}lti_key";

$newsql = Table::pagedQuery($sql, $query_parms, $searchfields);
// echo("<pre>\n$newsql\n</pre>\n");
$rows = $PDOX->allRowsDie($newsql, $query_parms);
$newrows = array();
foreach ( $rows as $row ) {
    $newrow = $row;
    $newrow['secret'] = '****';
    $newrows[] = $newrow;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<h1>LTI Keys</h1>
<p>
  <a href="<?= LTIX::curPageUrlFolder() ?>" class="btn btn-default">Key Requests</a>
  <a href="issuers" class="btn btn-default">LTI 1.3 Issuers</a>
  <a href="keys" class="btn btn-default active">Tenant Keys</a>
</p>
<?php if ( count($newrows) < 1 ) { ?>
<p>
You have no Tenant keys for this system.
</p>
<?php } 
$extra_buttons = array(
  "Insert New Key" => "key-add"
);
Table::pagedTable($newrows, $searchfields, false, "key-detail", false, $extra_buttons);
if ( isAdmin() ) { ?>
<?php } ?>

<?php
$OUTPUT->footer();

