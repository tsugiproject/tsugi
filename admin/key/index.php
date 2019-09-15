<?php
// In the top frame, we use cookies for session.
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

use \Tsugi\UI\Table;
use \Tsugi\Core\Mail;
use \Tsugi\Core\LTIX;

\Tsugi\Core\LTIX::getConnection();

session_start();

require_once("../gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

if ( ! ( isset($_SESSION['id']) || isAdmin() ) ) {
    $_SESSION['login_return'] = LTIX::curPageUrlFolder();
    header('Location: '.$CFG->wwwroot.'/login');
    return;
}

$query_parms = false;
$searchfields = array("request_id", "title", "notes", "state", "admin", "email", "displayname", "R.created_at", "R.updated_at");
$sql = "SELECT request_id, title, notes, state, admin, R.created_at, R.updated_at, email, displayname
        FROM {$CFG->dbprefix}key_request  as R
        JOIN {$CFG->dbprefix}lti_user AS U ON R.user_id = U.user_id ";

if ( !isAdmin() ) {
    $sql .= "\nWHERE R.user_id = :UID";
    $query_parms = array(":UID" => $_SESSION['id']);
}

$newsql = Table::pagedQuery($sql, $query_parms, $searchfields);
// echo("<pre>\n$newsql\n</pre>\n");
$rows = $PDOX->allRowsDie($newsql, $query_parms);
$newrows = array();
foreach ( $rows as $row ) {
    $newrow = $row;
    $state = $row['state'];
    if ( $state == 0 ) {
        $newrow['state'] = "0 (Waiting)";
    } else if ( $state == 1 ) {
        $newrow['state'] = "1 (Approved)";
    } else if ( $state == 2 ) {
        $newrow['state'] = "2 (Not approved)";
    }
    $newrows[] = $newrow;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<h1>LTI Key Requests</h1>
<p>
  <a href="<?= LTIX::curPageUrlFolder() ?>" class="btn btn-default active">Key Requests</a>
  <a href="issuers" class="btn btn-default">LTI 1.3 Issuers</a>
  <a href="keys" class="btn btn-default">Tenant Keys</a>
  <a href="<?= $CFG->wwwroot ?>/admin" class="btn btn-default">Admin</a>
</p>
<?php
Table::pagedTable($newrows, $searchfields, false, "request-detail");
$OUTPUT->footer();

