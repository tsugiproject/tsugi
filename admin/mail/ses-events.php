<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");
require_once("purge_util.php");

use \Tsugi\UI\Table;
use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;
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

if ( $_SERVER['REQUEST_METHOD'] === 'POST' && U::get($_POST, 'purge_old') ) {
    if ( ! U::get($_POST, 'confirm_purge') ) {
        U::flashError('You must confirm the purge');
        header('Location: ses-events');
        return;
    }
    if ( ! MailService::sesEventsTableExists() ) {
        U::flashError('mail_ses_events table missing');
        header('Location: ses-events');
        return;
    }
    $deleted = mail_admin_purge_delete('mail_ses_events', MAIL_ADMIN_PURGE_DAYS);
    if ( $deleted < 0 ) {
        U::flashError('Purge failed');
    } else {
        U::flashSuccess('Deleted '.$deleted.' SES event(s) older than '.MAIL_ADMIN_PURGE_DAYS.' days');
    }
    header('Location: ses-events');
    return;
}

require_once("nav.php");

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

mail_admin_nav('events');
echo('<h1>SES events</h1>');
echo('<p>Each row is an SES notification Tsugi processed, including the <code>action</code> taken (suppress, ignore_soft_bounce, ignore_delivery, ignore, error).</p>');

if ( ! MailService::sesEventsTableExists() ) {
    echo('<p style="color:red">mail_ses_events table missing — run Admin → Database Upgrade.</p>');
    $OUTPUT->footer();
    return;
}

mail_admin_purge_form('ses-events', 'mail_ses_events', 'SES events');

$query_parms = array();
$searchfields = array("event_id", "email", "event_type", "event_subtype", "action", "mail_type", "ses_message_id", "sns_message_id", "detail", "created_at");
$orderfields = array("created_at", "event_id", "email", "event_type", "action");
$params = $_GET;
if ( ! isset($params['order_by']) && !isset($params['desc']) ) {
    $params['order_by'] = 'created_at';
    $params['desc'] = '1';
}
$sql = "SELECT event_id, created_at, event_type, event_subtype, email, action, mail_type, ses_message_id, detail
    FROM {$CFG->dbprefix}mail_ses_events";
$view = "event-detail";
$extra_buttons = array("Mail" => "index", "Admin" => $CFG->wwwroot."/admin");
Table::pagedAuto($sql, $query_parms, $searchfields, $orderfields, $view, $params, $extra_buttons);

$OUTPUT->footer();
