<?php
// In the top frame, we use cookies for session.
define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once($CFG->dirroot."/pdo.php");
require_once($CFG->dirroot."/lib/lms_lib.php");

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( ! ( isset($_SESSION['id']) || is_admin() ) ) {
    die('Must be logged in or admin');
}

$tablename = "{$CFG->dbprefix}lti_key";
$current = get_current_file_url(__FILE__);
$title = "Key Entry";
$from_location = "keys.php";
$allow_delete = true;
$allow_edit = true;
$where_clause = '';
$query_fields = array();
if ( is_admin() ) {
    $fields = array("key_id", "key_key", "secret", "created_at", "updated_at", "user_id");
} else {
    $fields = array("key_id", "key_key", "secret", "created_at", "updated_at");
    $where_clause .= "user_id = :UID";
    $query_fields[":UID"] = $_SESSION['id'];
}

// Handle the post data
$row =  crud_update_handle($pdo, $tablename, $fields, $query_fields,
    $where_clause, $allow_edit, $allow_delete);

if ( $row === CRUD_UPDATE_FAIL || $row === CRUD_UPDATE_SUCCESS ) {
    header("Location: ".$from_location);
    return;
}

html_header_content();
html_start_body();
html_top_nav();
flash_messages();

echo("<h1>$title</h1>\n<p>\n");
$retval = crud_update_form($row, $fields, $current, $from_location, $allow_edit, $allow_delete);
if ( is_string($retval) ) die($retval);
echo("</p>\n");

html_footer_content();

