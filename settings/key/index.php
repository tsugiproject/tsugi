<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");

use \Tsugi\Util\U;
use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;

\Tsugi\Core\LTIX::getConnection();

if ( $CFG->providekeys === false || $CFG->owneremail === false ) {
    U::flashError(_("This service does not accept requests for keys"));
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
  <a href="<?= htmlspecialchars(LTIX::curPageUrlFolder(), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-default active" aria-label="LTI Keys (current page)">LTI Keys</a>
  <a href="using" class="btn btn-default" aria-label="Using Your Key - instructions for LTI integration">Using Your Key</a>
  <a href="requests" class="btn btn-default" aria-label="Key Requests">Key Requests</a>
  <a href="<?= htmlspecialchars($CFG->wwwroot . '/settings/', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-default" aria-label="My Settings">My Settings</a>
</p>
<?php if ( count($newrows) < 1 ) { ?>
<p>
You have no LTI Keys for this system.
</p>
<p>
If you want to use the tools / content in this system
in an LMS like Sakai, Moodle, Canvas, Blackboard or BrightSpace 
you will need to request a key and have it approved.
</p>
<?php } else {
    Table::pagedTable($newrows, $searchfields, false, "key-detail", false);
}

$OUTPUT->footer();

