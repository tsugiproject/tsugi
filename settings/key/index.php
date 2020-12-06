<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");

use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;

\Tsugi\Core\LTIX::getConnection();

if ( $CFG->providekeys === false || $CFG->owneremail === false ) {
    $_SESSION['error'] = _("This service does not accept requests for keys");
    header('Location: '.$CFG->wwwroot);
    return;
}

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( ! isset($_SESSION['id']) ) {
    $_SESSION['login_return'] = LTIX::curPageUrlFolder();
    header('Location: '.$CFG->wwwroot.'/login');
    return;
}

$query_parms = array(":UID" => $_SESSION['id']);
$searchfields = array("key_id", "key_key", "created_at", "updated_at", "user_id");
$sql = "SELECT key_id, key_key, secret, login_at, created_at, updated_at, user_id
        FROM {$CFG->dbprefix}lti_key
        WHERE user_id = :UID";

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
  <a href="<?= LTIX::curPageUrlFolder() ?>" class="btn btn-default active">LTI Keys</a>
  <a href="using" class="btn btn-default">Using Your Key</a>
  <a href="requests" class="btn btn-default">Key Requests</a>
  <a href="<?= $CFG->wwwroot.'/settings/' ?>" class="btn btn-default">My Settings</a>
</p>
<?php if ( count($newrows) < 1 ) { ?>
<p>
You have no IMS LTI Keys for this system.
</p>
<p>
If you want to use the tools / content in this system
in an LMS like Sakai, Moodle, Canvase, Blackboard or BrightSpace 
you will need to request a key and have it approved.
</p>
<?php } else {
    Table::pagedTable($newrows, $searchfields, false, "key-detail", false);
}

$OUTPUT->footer();

