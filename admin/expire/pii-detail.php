<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");
require_once("expire_util.php");

use \Tsugi\Util\U;
use \Tsugi\UI\Table;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();
require_once("../gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

if ( ! isAdmin() ) {
    die('Must be admin');
}

$fields = array('login_at', 'user_id', 'email', 'displayname', 'created_at');

$sql = "SELECT login_at, user_id, email, displayname, email, created_at 
        FROM {$CFG->dbprefix}lti_user
        WHERE created_at <= CURRENT_DATE() - INTERVAL 1 DAY
        AND (login_at IS NULL OR login_at <= CURRENT_DATE() - INTERVAL 1 DAY)";

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

$extra_buttons = array(
    "Summary" => "index"
);


$query_parms = false;
$searchfields = $fields;
$orderfields = $fields;
$newsql = Table::pagedQuery($sql, $query_parms, $searchfields, $orderfields);
// echo("<pre>\n$newsql\n</pre>\n");
$rows = $PDOX->allRowsDie($newsql, $query_parms);
$newrows = array();
foreach ( $rows as $row ) {
    $newrow = $row;
    $newrows[] = $newrow;
}

$view_url = false;
$params=false; // Defaults to _GET
Table::pagedTable($newrows, $searchfields, $orderfields, $view_url, $params, $extra_buttons);


$OUTPUT->footerStart();

$OUTPUT->footerEnd();
