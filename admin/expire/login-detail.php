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

if ( ! isset($_GET['base']) ) die('Base required');

$base = $_GET['base'];
if ( $base == 'user' ) {
    $table = 'lti_user';
    $fields = array('login_at', 'user_id', 'email', 'displayname', 'created_at');
    $select = 'user_id, email, displayname';
} else if ( $base == 'context' ) {
    $table = 'lti_context';
    $fields = array('login_at', 'context_id', 'title', 'created_at');
    $select = 'context_id, title';
} else if ( $base == 'tenant' ) {
    $table = 'lti_key';
    $fields = array('login_at', 'key_id', 'key_key', 'created_at');
    $select = 'key_id, key_key';
} else {
    die('Invalid base value');
}

if ( ! isset($_GET[$base.'_days']) ) die('Required parameter '.$base.'_days');
if ( ! is_numeric($_GET[$base.'_days']) ) die($base.'_days must be a number');
$days = $_GET[$base.'_days'] + 0;
if ($days < 1 ) die('Bad value for '.$base.'_days');

$sql = "SELECT login_at, {$select}, created_at 
        FROM {$CFG->dbprefix}{$table} " . get_expirable_where($days);

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
