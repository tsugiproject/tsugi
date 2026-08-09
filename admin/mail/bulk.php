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

echo('<h1>Bulk mail campaigns</h1>');
echo('<p><a href="index">Mail</a> | <a href="'.$CFG->wwwroot.'/admin">Admin</a> | <a href="'.$CFG->wwwroot.'/admin/context/">Contexts</a></p>');
echo('<p>Campaigns created from <strong>Admin → Context → Bulk mail</strong>. Click a bulk_id for detail.</p>');

$fields = $PDOX->metadata($CFG->dbprefix . 'mail_bulk');
if ( $fields === false ) {
    echo('<p style="color:red">mail_bulk table missing — run Database Upgrade.</p>');
    $OUTPUT->footer();
    return;
}

$query_parms = array();
$searchfields = array("bulk_id", "context", "from_user", "subject", "created_at", "context_title");
$orderfields = array("created_at", "bulk_id", "context", "from_user");
$params = $_GET;
if ( ! isset($params['order_by']) && !isset($params['desc']) ) {
    $params['order_by'] = 'created_at';
    $params['desc'] = '1';
}
// Only bulk_id may end with _id so Table links to bulk-detail correctly.
$sql = "SELECT B.bulk_id, B.context_id AS context, B.user_id AS from_user, B.subject, B.created_at, C.title AS context_title
    FROM {$CFG->dbprefix}mail_bulk B
    LEFT JOIN {$CFG->dbprefix}lti_context C ON C.context_id = B.context_id";
$view = $CFG->wwwroot."/admin/context/bulk-detail";
$extra_buttons = array("Mail" => "index", "Admin" => $CFG->wwwroot."/admin");
Table::pagedAuto($sql, $query_parms, $searchfields, $orderfields, $view, $params, $extra_buttons);

$OUTPUT->footer();
