<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
use \Tsugi\UI\Table;
use \Tsugi\UI\OUTPUT;
use \Tsugi\Core\LTIX;

require_once("../config.php");
LTIX::session_start();

require_once("gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;


$OUTPUT->header();
$OUTPUT->bodyStart();

$query_parms = array();
$searchfields = array("email", "displayname", "ipaddr");
$orderfields = array("login_at", "login_count");
$params = $_GET;
if ( ! isset($params['order_by']) && !isset($params['desc']) ) {
    $params['order_by'] = 'login_at';
    $params['desc'] = '1';
}
$params['page_length'] = 15;
$user_sql =
"SELECT email, displayname, login_at, login_count, ipaddr FROM {$CFG->dbprefix}lti_user";
$view = false;

Table::pagedAuto($user_sql, $query_parms, $searchfields, $orderfields, $view, $params);

$OUTPUT->footer();

