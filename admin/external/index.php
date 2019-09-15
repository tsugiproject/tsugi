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
$searchfields = array("endpoint", "name", "description", "fa_icon", "url", "created_at", "updated_at");
$sql = "SELECT external_id, endpoint, name, url, created_at, updated_at
        FROM {$CFG->dbprefix}lti_external";
$orderfields = $searchfields;

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

echo("<h1>Manage External Tools</h1>\n");

$extra_buttons = array(
    "Add Tool" => "ext-add",
    "Admin" =>   $CFG->wwwroot."/admin"
);
Table::pagedTable($newrows, $searchfields, false, "ext-detail", false, $extra_buttons);

$OUTPUT->footer();

