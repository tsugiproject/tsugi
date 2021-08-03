<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

use \Tsugi\UI\CrudForm;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( ! isAdmin() ) {
    die('Must be admin');
}

if ( ! isset($_REQUEST['user_id']) ) {
    die('No user_id');
}

$row = $PDOX->rowDie("SELECT user_id FROM {$CFG->dbprefix}lti_user
    WHERE user_id = :UID;",
    array(':UID' => $_REQUEST['user_id'])
);

if ( $row === false || ! isset($row['user_id']) ) {
    die('Bad user_id');
}


$tablename = "{$CFG->dbprefix}lti_user";
$current = $CFG->getCurrentFileUrl(__FILE__);
$allow_delete = true;
$allow_edit = true;
$where_clause = '';
$query_fields = array();
$fields = array("user_id", "displayname", "email", "created_at", "updated_at", "login_at", "login_count");
$from_location = "index";

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

$title = "User Detail";
echo("<h1>$title</h1>\n<p>\n");
?>
<p><b>Warning:</b> If you delete a user, their user record and <b>all</b> of their associated learning
activity data is deleted.  This is <b>not</b> a "soft delete".  A delete  on this screen truly throws away all
of the user's data.
</p>
<?php
$retval = CrudForm::updateForm($row, $fields, $current, $from_location, $allow_edit, $allow_delete);
if ( is_string($retval) ) die($retval);
echo("</p>\n");

$OUTPUT->footer();

