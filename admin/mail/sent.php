<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;

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

mail_admin_nav('sent');
echo('<h1>Mail sent</h1>');
echo('<p><code>mail_sent</code> rows from admin Test E-Mail, context bulk mail, and some tools (e.g. peer-grade). Details such as email / MessageId are in <code>json</code>.</p>');

$fields = $PDOX->metadata($CFG->dbprefix . 'mail_sent');
if ( $fields === false ) {
    echo('<p style="color:red">mail_sent table missing.</p>');
    $OUTPUT->footer();
    return;
}

$query_parms = array();
$searchfields = array("sent_id", "context_id", "bulk_id", "user_to", "user_from", "subject", "json", "created_at");
$orderfields = array("created_at", "sent_id", "context_id", "user_to", "bulk_id");
$params = $_GET;
if ( ! isset($params['order_by']) && !isset($params['desc']) ) {
    $params['order_by'] = 'created_at';
    $params['desc'] = '1';
}
$sql = "SELECT sent_id, context_id, bulk_id, user_to, user_from, subject, json, created_at
    FROM {$CFG->dbprefix}mail_sent";
$view = false;
$extra_buttons = array("Mail" => "index", "Admin" => $CFG->wwwroot."/admin");
Table::pagedAuto($sql, $query_parms, $searchfields, $orderfields, $view, $params, $extra_buttons);

$OUTPUT->footer();
