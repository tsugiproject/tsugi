<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

use \Tsugi\UI\CrudForm;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();
require_once("../gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

if ( ! ( isset($_SESSION['id']) || isAdmin() ) ) {
    die('Must be logged in or admin');
}

$tablename = "{$CFG->dbprefix}lti_key";
$current = $CFG->getCurrentFileUrl(__FILE__);
$from_location = "keys";
$allow_delete = true;
$allow_edit = true;
$where_clause = '';
$query_fields = array();
if ( isAdmin() ) {
    $fields = array("key_id", "key_key", "secret", "caliper_url", "caliper_key", "created_at", "updated_at", "user_id");
} else {
    $fields = array("key_id", "key_key", "secret", "created_at", "updated_at");
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

$title = "Key Entry";
echo("<h1>$title</h1>\n<p>\n");
$retval = CrudForm::updateForm($row, $fields, $current, $from_location, $allow_edit, $allow_delete);
if ( is_string($retval) ) die($retval);
echo("</p>\n");

$OUTPUT->footer();

