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

if ( ! isAdmin() ) {
    $_SESSION['login_return'] = LTIX::curPageUrlFolder();
    header('Location: '.$CFG->wwwroot.'/login.php');
    return;
}

$query_parms = array();
$searchfields = array(
    "P.profile_id", "P.displayname", "P.email", "P.premium", "P.premium_at",
    "P.login_at", "P.created_at", "P.updated_at"
);
$sql = "SELECT P.profile_id AS profile_id, P.displayname, P.email, P.premium, P.premium_at,
            P.login_at, P.created_at, P.updated_at,
            (SELECT COUNT(*) FROM {$CFG->dbprefix}lti_user AS U WHERE U.profile_id = P.profile_id) AS linked_users
        FROM {$CFG->dbprefix}profile AS P
        WHERE (P.deleted IS NULL OR P.deleted = 0)";
$orderfields = array(
    "P.profile_id", "P.displayname", "P.email", "P.premium", "P.premium_at",
    "P.login_at", "P.created_at", "P.updated_at", "linked_users"
);

$newsql = Table::pagedQuery($sql, $query_parms, $searchfields, $orderfields);
$rows = $PDOX->allRowsDie($newsql, $query_parms);

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

$view_url = "profile-detail";
$params = false;
$extra_buttons = array("Admin" => $CFG->wwwroot."/admin");
Table::pagedTable($rows, $searchfields, $orderfields, $view_url, $params, $extra_buttons);

$OUTPUT->footer();
