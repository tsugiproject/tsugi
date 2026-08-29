<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");
require_once("purge_util.php");

use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;

LTIX::getConnection();
session_start();

require_once("../gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

if ( ! isAdmin() ) {
    \Tsugi\Controllers\Login::setReturnUrl(LTIX::curPageUrlFolder());
    header('Location: '.\Tsugi\Controllers\Login::loginUrl());
    return;
}

$fields = $PDOX->metadata($CFG->dbprefix . 'mail_sent');

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    if ( \Tsugi\Controllers\Tool::csrfRedirect('sent') ) return;
}

if ( $_SERVER['REQUEST_METHOD'] === 'POST' && U::get($_POST, 'purge_old') ) {
    if ( ! U::get($_POST, 'confirm_purge') ) {
        U::flashError('You must confirm the purge');
        header('Location: sent');
        return;
    }
    if ( $fields === false ) {
        U::flashError('mail_sent table missing');
        header('Location: sent');
        return;
    }
    $deleted = mail_admin_purge_delete('mail_sent', MAIL_ADMIN_PURGE_DAYS);
    if ( $deleted < 0 ) {
        U::flashError('Purge failed');
    } else {
        U::flashSuccess('Deleted '.$deleted.' mail_sent row(s) older than '.MAIL_ADMIN_PURGE_DAYS.' days');
    }
    header('Location: sent');
    return;
}

require_once("nav.php");

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

mail_admin_nav('sent');
echo('<h1>Mail sent</h1>');
echo('<p><code>mail_sent</code> rows from admin Test E-Mail, context bulk mail, and some tools (e.g. peer-grade). SES <code>message_id</code> joins to <code>mail_ses_events.ses_message_id</code>.</p>');

if ( $fields === false ) {
    echo('<p style="color:red">mail_sent table missing.</p>');
    $OUTPUT->footer();
    return;
}

mail_admin_purge_form('sent', 'mail_sent', 'mail_sent');

$query_parms = array();
$searchfields = array("sent_id", "context_id", "bulk_id", "user_to", "user_from", "subject", "message_id", "json", "created_at");
$orderfields = array("created_at", "sent_id", "context_id", "user_to", "bulk_id", "message_id");
$params = $_GET;
if ( ! isset($params['order_by']) && !isset($params['desc']) ) {
    $params['order_by'] = 'created_at';
    $params['desc'] = '1';
}
$sql = "SELECT sent_id, context_id, bulk_id, user_to, user_from, subject, message_id, json, created_at
    FROM {$CFG->dbprefix}mail_sent";
$view = false;
$extra_buttons = array("Mail" => "index", "Admin" => $CFG->wwwroot."/admin");
Table::pagedAuto($sql, $query_parms, $searchfields, $orderfields, $view, $params, $extra_buttons);

$OUTPUT->footer();
