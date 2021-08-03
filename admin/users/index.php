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

$query_parms = false;
$searchfields = array("U.user_id", "displayname", "email", "key_title", "key_key", "U.created_at", "U.updated_at", "U.login_at", "U.login_count");
$sql = "SELECT U.user_id AS user_id, displayname, email, U.key_id AS key_value, key_title, key_key,
            U.login_at, U.login_count, U.created_at, U.updated_at
        FROM {$CFG->dbprefix}lti_user AS U
        LEFT JOIN {$CFG->dbprefix}lti_key AS K ON U.key_id = K.key_id
        ";
$orderfields = array("U.user_id", "key_value", "displayname", "email", "key_key", "key_title", "U.created_at", "U.updated_at", "U.login_at", "U.login_count");

$newsql = Table::pagedQuery($sql, $query_parms, $searchfields, $orderfields);
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

$view_url = "user-detail";
$params=false; // Defaults to _GET
$extra_buttons = array("Admin" =>   $CFG->wwwroot."/admin");
Table::pagedTable($newrows, $searchfields, $orderfields, $view_url, $params, $extra_buttons);

$OUTPUT->footer();

