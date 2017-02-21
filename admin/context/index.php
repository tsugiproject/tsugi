<?php
// In the top frame, we use cookies for session.
define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

use \Tsugi\UI\Table;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( ! isAdmin() ) {
    $_SESSION['login_return'] = $CFG->getUrlFull(__FILE__) . "/index.php";
    header('Location: '.$CFG->wwwroot.'/login.php');
    return;
}

$query_parms = false;
$searchfields = array("context_id", "title", "created_at", "updated_at");
$sql = "SELECT C.context_id AS context_id, title, count(M.user_id) AS members, C.key_id AS key_value, C.created_at, C.updated_at
        FROM {$CFG->dbprefix}lti_context AS C
        LEFT JOIN {$CFG->dbprefix}lti_membership AS M ON C.context_id = M.context_id
        GROUP BY C.context_id";
$orderfields = array("context_id", "key_value", "title", "created_at", "updated_at");

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

Table::pagedTable($newrows, $searchfields, $orderfields, "membership.php");

$OUTPUT->footer();

