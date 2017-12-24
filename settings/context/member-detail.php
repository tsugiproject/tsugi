<?php

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\CrudForm;

// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");

header('Content-Type: text/html; charset=utf-8');
LTIX::session_start();

if ( ! U::get($_SESSION,'id') ) {
    die('Must be admin');
}

if ( ! isset($_REQUEST['membership_id']) ) {
    die('No membership_id');
}

$row = $PDOX->rowDie("SELECT M.context_id 
    FROM {$CFG->dbprefix}lti_membership AS M
    JOIN {$CFG->dbprefix}lti_context AS C ON M.context_id = C.context_id
    WHERE membership_id = :MID AND
        C.key_id IN (select key_id from {$CFG->dbprefix}lti_key where user_id = :UID )
        OR C.user_id = :UID",
    array(
        ':MID' => $_REQUEST['membership_id'],
        ':UID' => $_SESSION['id'])
);

if ( $row === false || ! isset($row['context_id']) ) {
    die('Bad membership_id');
}


$tablename = "{$CFG->dbprefix}lti_membership";
$current = $CFG->getCurrentFileUrl(__FILE__);
$from_location = "membership?context_id=".$row['context_id'];
$allow_delete = true;
$allow_edit = true;
$where_clause = '';
$query_fields = array();
$fields = array("membership_id", "context_id", "user_id", "role_override", "created_at", "updated_at");

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

$title = "Membership";
echo("<h1>$title</h1>\n<p>\n");
$retval = CrudForm::updateForm($row, $fields, $current, $from_location, $allow_edit, $allow_delete);
if ( is_string($retval) ) die($retval);
echo("</p>\n");

$OUTPUT->footer();

