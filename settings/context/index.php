<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");

use \Tsugi\Util\U;
use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( ! U::get($_SESSION,'id') ) {
    die('Must be logged in');
}

$query_parms = false;
$searchfields = array("C.context_id", "title", "C.created_at", "C.updated_at", "C.login_at", "C.login_count");
$sql = "SELECT C.context_id AS context_id, title, count(M.user_id) AS members, C.key_id AS key_value,
            C.login_at, C.login_count, C.created_at, C.updated_at
        FROM {$CFG->dbprefix}lti_context AS C
        LEFT JOIN {$CFG->dbprefix}lti_membership AS M ON C.context_id = M.context_id
        WHERE C.key_id IN (select key_id from {$CFG->dbprefix}lti_key where user_id = ".$_SESSION['id'].") 
         OR C.user_id = ".$_SESSION['id']."
        GROUP BY C.context_id";
$orderfields = array("C.context_id", "key_value", "title", "C.created_at", "C.updated_at", "C.login_at", "C.login_count");

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

Table::pagedTable($newrows, $searchfields, $orderfields, "membership");

$OUTPUT->footer();

