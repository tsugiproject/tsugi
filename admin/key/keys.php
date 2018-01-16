<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;

\Tsugi\Core\LTIX::getConnection();

if ( $CFG->providekeys === false || $CFG->owneremail === false ) {
    $_SESSION['error'] = _("This service does not accept instructor requests for keys");
    header('Location: '.$CFG->wwwroot);
    return;
}

header('Content-Type: text/html; charset=utf-8');
session_start();
require_once("../gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

if ( ! ( isset($_SESSION['id']) || isAdmin() ) ) {
    $_SESSION['login_return'] = LTIX::curPageUrlFolder();
    header('Location: '.$CFG->wwwroot.'/login');
    return;
}

$query_parms = false;
$searchfields = array("key_id", "key_key", "created_at", "updated_at", "user_id");
$sql = "SELECT key_id, key_key, secret, login_at, created_at, updated_at, user_id
        FROM {$CFG->dbprefix}lti_key";

if ( !isAdmin() ) {
    $sql .= "\nWHERE user_id = :UID";
    $query_parms = array(":UID" => $_SESSION['id']);
}

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
  <a href="<?= LTIX::curPageUrlFolder() ?>" class="btn btn-default">View Key Requests</a>
  <a href="keys" class="btn btn-default active">View Keys</a>
</p>
<?php if ( count($newrows) < 1 ) { ?>
<p>
You have no IMS LTI 1.1 Keys for this system.
</p>
<?php } else {
    $extra_buttons = array(
        "Insert New Key" => "key-add"
    );
    Table::pagedTable($newrows, $searchfields, false, "key-detail", false, $extra_buttons);
}
if ( isAdmin() ) { ?>
<?php } ?>

<?php
$OUTPUT->footer();

