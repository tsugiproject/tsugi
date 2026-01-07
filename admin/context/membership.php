<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( ! isset($_REQUEST['context_id']) ) {
    $_SESSION['error'] = "No context_id provided";
    header('Location: '.LTIX::curPageUrlFolder());
    return;
}

if ( ! is_numeric($_REQUEST['context_id']) ) {
    $_SESSION['error'] = "Invalid context_id";
    header('Location: '.LTIX::curPageUrlFolder());
    return;
}

$context_id = $_REQUEST['context_id'] + 0;

// Check if user is site admin OR instructor/admin for this context
$is_context_admin = false;
if ( isAdmin() ) {
    $is_context_admin = true;
} else if ( U::get($_SESSION, 'id') ) {
    // Check if user is instructor/admin for this context
    $membership = $PDOX->rowDie(
        "SELECT role FROM {$CFG->dbprefix}lti_membership 
         WHERE context_id = :CID AND user_id = :UID",
        array(':CID' => $context_id, ':UID' => $_SESSION['id'])
    );
    if ( $membership && isset($membership['role']) ) {
        $role = $membership['role'] + 0;
        // ROLE_INSTRUCTOR = 1000, ROLE_ADMINISTRATOR = 5000
        if ( $role >= LTIX::ROLE_INSTRUCTOR ) {
            $is_context_admin = true;
        }
    }
    // Also check if user owns the context or its key
    if ( ! $is_context_admin ) {
        $context_check = $PDOX->rowDie(
            "SELECT context_id FROM {$CFG->dbprefix}lti_context
             WHERE context_id = :CID AND (
                 key_id IN (SELECT key_id FROM {$CFG->dbprefix}lti_key WHERE user_id = :UID)
                 OR user_id = :UID
             )",
            array(':CID' => $context_id, ':UID' => $_SESSION['id'])
        );
        if ( $context_check ) {
            $is_context_admin = true;
        }
    }
}

if ( ! $is_context_admin ) {
    $_SESSION['error'] = "You must be an administrator or instructor for this context";
    $_SESSION['login_return'] = LTIX::curPageUrlFolder();
    header('Location: '.$CFG->wwwroot.'/login.php');
    return;
}

$query_parms = array(":CID" => $context_id);

$searchfields = array("M.membership_id", "context_id", "M.user_id", "role", "role_override", 
	"M.created_at", "U.login_at", "email", "displayname", "user_key");

$sql = "SELECT membership_id, 'detail' AS 'Membership', context_id AS Context, M.user_id as User, 
            role, role_override, M.created_at, U.login_at, email, displayname, user_key
        FROM {$CFG->dbprefix}lti_membership as M
        JOIN {$CFG->dbprefix}lti_user AS U ON M.user_id = U.user_id
        WHERE context_id = :CID";

if ( !isAdmin() ) {
    // Non-admins are already redirected above.
}

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
  <a href="mailing-list.php?context_id=<?= htmlentities($context_id) ?>" class="btn btn-primary">Generate Mailing List</a>
</p>

<?php

Table::pagedTable($newrows, $searchfields, $searchfields, "member-detail");

$OUTPUT->footer();
