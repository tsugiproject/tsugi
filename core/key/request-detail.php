<?php
// In the top frame, we use cookies for session.
define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once($CFG->dirroot."/pdo.php");
require_once($CFG->dirroot."/lib/lms_lib.php");

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( ! ( isset($_SESSION['id']) || isAdmin() ) ) {
    die('Must be logged in or admin');
}

$tablename = "{$CFG->dbprefix}key_request";
$current = getCurrentFileUrl(__FILE__);
$title = "Request Entry";
$from_location = "index.php";
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
$row =  crudUpdateHandle($pdo, $tablename, $fields, $query_fields,
    $where_clause, $allow_edit, $allow_delete);

if ( $row === CRUD_UPDATE_FAIL || $row === CRUD_UPDATE_SUCCESS ) {
    header("Location: ".$from_location);
    return;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

echo("<h1>$title</h1>\n<p>\n");
$retval = crudUpdateForm($row, $fields, $current, $from_location, $allow_edit, $allow_delete);
if ( is_string($retval) ) die($retval);
echo("</p>\n");

$OUTPUT->footer();

