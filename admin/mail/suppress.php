<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;
use \Tsugi\Services\Mail\MailService;

LTIX::getConnection();
session_start();

require_once("../gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

if ( ! isAdmin() ) {
    \Tsugi\Controllers\Login::setReturnUrl(LTIX::curPageUrlFolder());
    header('Location: '.\Tsugi\Controllers\Login::loginUrl());
    return;
}

require_once("nav.php");

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

mail_admin_nav('suppress');
echo('<h1>Mail suppress</h1>');

if ( ! MailService::suppressTableExists() ) {
    echo('<p style="color:red">mail_suppress table missing — run Admin → Database Upgrade.</p>');
    $OUTPUT->footer();
    return;
}

$query_parms = array();
$searchfields = array("email", "reason", "detail", "message_id", "created_at", "updated_at");
$orderfields = array("updated_at", "created_at", "email", "reason");
$params = $_GET;
if ( ! isset($params['order_by']) && !isset($params['desc']) ) {
    $params['order_by'] = 'updated_at';
    $params['desc'] = '1';
}
$sql = "SELECT suppress_id, email, reason, detail, message_id, created_at, updated_at
    FROM {$CFG->dbprefix}mail_suppress";
$view = false;
$extra_buttons = array("Mail" => "index", "Admin" => $CFG->wwwroot."/admin");
Table::pagedAuto($sql, $query_parms, $searchfields, $orderfields, $view, $params, $extra_buttons);

$OUTPUT->footer();
