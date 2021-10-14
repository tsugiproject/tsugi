<?php

use \Tsugi\Util\U;
use \Tsugi\UI\Output;
use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;

// In the top frame, we use cookies for session.
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../settings_util.php");
require_once("expire_util.php");

session_start();

if ( ! U::get($_SESSION,'id') ) {
    $login_return = U::reconstruct_query($CFG->wwwroot . '/settings/expire');
    $_SESSION['login_return'] = $login_return;
    Output::doRedirect($CFG->wwwroot.'/login.php');
    return;
}

\Tsugi\Core\LTIX::getConnection();

if ( ! isset($_GET['pii_days']) ) die('Required parameter pii_days');
if ( ! is_numeric($_GET['pii_days']) ) die('pii_days must be a number');
$days = $_GET['pii_days'] + 0;
if ($days < 1 ) die('bad value for pii_days');

$fields = array('login_at', 'user_id', 'email', 'displayname', 'created_at');

$sql = "SELECT login_at, user_id, email, displayname, email, created_at 
        FROM {$CFG->dbprefix}lti_user " . get_pii_where($days);

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
