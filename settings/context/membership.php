<?php

use \Tsugi\Util\U;

// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../settings_util.php");

use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;

header('Content-Type: text/html; charset=utf-8');
LTIX::session_start();

if ( ! U::get($_SESSION,'id') ) {
    die('Must be logged in');
}

if ( ! isset($_REQUEST['context_id']) ) {
    $_SESSION['error'] = "No context_id provided";
    header('Location: '.LTIX::curPageUrlFolder());
    return;
}

$context_id = $_REQUEST['context_id'];

// Verify user has access to this context (owns it or owns the key)
$context_check = settings_context_administrable($context_id);
if ( $context_check === false ) {
    $_SESSION['error'] = "You do not have access to this context";
    header('Location: '.LTIX::curPageUrlFolder());
    return;
}

$query_parms = array(":CID" => $context_id);

$searchfields = array("M.membership_id", "C.context_id", "M.user_id", "role", "role_override", 
	"M.created_at", "M.updated_at", "email", "displayname", "user_key");

$sql = "SELECT membership_id, 'detail' AS 'Membership', M.context_id AS Context, M.user_id as User, 
            role, role_override, M.created_at, M.updated_at, email, displayname, user_key
        FROM {$CFG->dbprefix}lti_membership as M
        JOIN {$CFG->dbprefix}lti_user AS U ON M.user_id = U.user_id
        JOIN {$CFG->dbprefix}lti_context AS C ON M.context_id = C.context_id
        WHERE M.context_id = :CID";

$newsql = Table::pagedQuery($sql, $query_parms, $searchfields);
// echo("<pre>\n$newsql\n</pre>\n");
$rows = $PDOX->allRowsDie($newsql, $query_parms);
$newrows = array();
foreach ( $rows as $row ) {
    $newrow = $row;
    $newrows[] = $newrow;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
<p>
  <a href="<?= LTIX::curPageUrlFolder() ?>" class="btn btn-default">View Contexts</a>
  <a href="context-settings?context_id=<?= htmlentities($context_id) ?>" class="btn btn-success">View/Edit Context Settings</a>
</p>
<h1>Roster / Membership</h1>
<?php

$extra_buttons = array(__("All Contexts") =>   $CFG->wwwroot."/settings/context");
$params=false; // Defaults to _GET
Table::pagedTable($newrows, $searchfields, $searchfields, "member-detail", $params, $extra_buttons);

$OUTPUT->footer();

