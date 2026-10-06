<?php
use \Tsugi\UI\Table;
use \Tsugi\UI\Output;
use \Tsugi\Core\LTIX;

LTIX::session_start();



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
