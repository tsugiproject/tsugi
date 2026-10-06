<?php
// In the top frame, we use cookies for session.

use \Tsugi\UI\CrudForm;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');

if ( ! \Tsugi\Services\Admin\AdminService::isAdmin() ) {
    die('Must be admin');
}

if ( ! isset($_REQUEST['membership_id']) ) {
    die('No membership_id');
}

$row = $PDOX->rowDie("SELECT context_id FROM {$CFG->dbprefix}lti_membership
    WHERE membership_id = :MID;",
    array(':MID' => $_REQUEST['membership_id'])
);

if ( $row === false || ! isset($row['context_id']) ) {
    die('Bad membership_id');
}


$tablename = "{$CFG->dbprefix}lti_membership";
$current = \Tsugi\Core\LTIX::curPageUrlNoQuery();
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

