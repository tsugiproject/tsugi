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

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

echo('<h1>Mail sent</h1>');
echo('<p><a href="index">Mail</a> | <a href="'.$CFG->wwwroot.'/admin">Admin</a></p>');
echo('<p>Legacy <code>mail_sent</code> rows (e.g. peer-grade reset notices). Most application mail is not logged here.</p>');

$fields = $PDOX->metadata($CFG->dbprefix . 'mail_sent');
if ( $fields === false ) {
    echo('<p style="color:red">mail_sent table missing.</p>');
    $OUTPUT->footer();
    return;
}

$query_parms = array();
$searchfields = array("sent_id", "context_id", "link_id", "user_to", "user_from", "subject", "created_at");
$orderfields = array("created_at", "sent_id", "context_id", "user_to");
$params = $_GET;
if ( ! isset($params['order_by']) && !isset($params['desc']) ) {
    $params['order_by'] = 'created_at';
    $params['desc'] = '1';
}
$sql = "SELECT sent_id, context_id, link_id, user_to, user_from, subject, created_at
    FROM {$CFG->dbprefix}mail_sent";
$view = false;
$extra_buttons = array("Mail" => "index", "Admin" => $CFG->wwwroot."/admin");
Table::pagedAuto($sql, $query_parms, $searchfields, $orderfields, $view, $params, $extra_buttons);

$OUTPUT->footer();
