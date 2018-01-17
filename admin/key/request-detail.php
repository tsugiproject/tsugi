<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

use \Tsugi\UI\CrudForm;
use \Tsugi\Core\LTIX;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();
require_once("../gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

$tablename = "{$CFG->dbprefix}key_request";
$current = $CFG->getCurrentFileUrl(__FILE__);
$title = "Request Entry";
$from_location = LTIX::curPageUrlFolder();
$allow_delete = isAdmin();
$allow_edit = isAdmin();
$where_clause = '';
$query_fields = array();
if ( isAdmin() ) {
    $fields = array("request_id", "title", "notes", "admin", "state", "lti", "created_at", "updated_at", "user_id");
} else {
    $fields = array("request_id", "title", "notes", "admin", "state", "lti", "created_at", "updated_at");
    $where_clause .= "user_id = :UID";
    $query_fields[":UID"] = $_SESSION['id'];
}

// Handle the post data
$row =  CrudForm::handleUpdate($tablename, $fields, $where_clause,
    $query_fields, $allow_edit, $allow_delete);

if ( $row === CrudForm::CRUD_FAIL || $row === CrudForm::CRUD_SUCCESS ) {
    header("Location: ".$from_location);
    return;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

echo("<h1>$title</h1>\n<p>\n");
$extra_buttons = false;
if ( isAdmin() && $row['state'] == 0 ) {
    $extra_buttons = array(
        'Process' => 'approve-key.php?request_id='.$row['request_id']
    );
}
$retval = CrudForm::updateForm($row, $fields, $current, $from_location, $allow_edit, $allow_delete, $extra_buttons);
if ( is_string($retval) ) die($retval);
echo("</p>\n");

$OUTPUT->footer();

