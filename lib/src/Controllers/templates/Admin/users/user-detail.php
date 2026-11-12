<?php
// In the top frame, we use cookies for session.

use \Tsugi\UI\CrudForm;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');

if ( ! \Tsugi\Services\Admin\AdminService::isAdmin() ) {
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
// This file is included by the users controller. Links stay on that request,
// not on this template's path under lib/src.
$current = \Tsugi\Core\LTIX::curPageUrlNoQuery();
$allow_delete = true;
$allow_edit = true;
$where_clause = '';
$query_fields = array();
$fields = array("user_id", "displayname", "email", "create_courses", "created_at", "updated_at", "login_at", "login_count");
$from_location = "index";
$titles = array("create_courses" => "Create courses (0 or 1)");

// Handle the post data
$syncFlag = isset($_POST['doUpdate']) && array_key_exists('create_courses', $_POST)
    ? ((int) $_POST['create_courses'] === 1)
    : null;
$row =  CrudForm::handleUpdate($tablename, $fields, $where_clause,
    $query_fields, $allow_edit, $allow_delete);

if ( $syncFlag !== null && $row === CrudForm::CRUD_SUCCESS ) {
    \Tsugi\Services\Permissions\PermissionTables::syncCourseCreateFlag(
        (int) $_REQUEST['user_id'],
        $syncFlag
    );
}

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
<p><b>Create courses:</b> Set to 1 to give this user a <?= htmlspecialchars(\Tsugi\Services\Courses\CoursesService::COURSE_CREATE) ?> grant.
Set to 0 to revoke the grant that came from this flag. A grant handed out
on the Permissions page is left alone.
</p>
<?php
$retval = CrudForm::updateForm($row, $fields, $current, $from_location, $allow_edit, $allow_delete, false, $titles);
if ( is_string($retval) ) die($retval);
echo("</p>\n");

$OUTPUT->footer();

