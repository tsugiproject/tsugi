<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\CrudForm;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();

if ( ! U::get($_SESSION,'id') ) {
    die('Must be logged in');
}

$tablename = "{$CFG->dbprefix}lti_key";
$current = $CFG->getCurrentFileUrl(__FILE__);
$from_location = LTIX::curPageUrlFolder();
$allow_delete = true;
$allow_edit = true;
$where_clause = '';
$query_fields = array();
$fields = array("key_id", "key_key", "secret", "created_at", "updated_at");
$where_clause .= "user_id = :UID";
$query_fields[":UID"] = $_SESSION['id'];

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

$autoConfigUrl = $from_location . "/auto?tsugi_key=" . $row['key_id'];
?>
<p>
<b>LTI Advantage Auto Configuration URL:
<button href="#" onclick="copyToClipboardNoScroll(this, '<?= $autoConfigUrl ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</button></b>
<br/><?= htmlentities($autoConfigUrl) ?>
<p>
To use the auto configuration URL in your Learning Management System,
keep this window open in a separate tab while using the LMS in another tab
as the LTI Advantage auto configuration process requires that you are logged in to this system
in order to complete the auto configuration process.
</p>
<?php
$OUTPUT->footerStart();
?>
<script>
$(document).ready( function() {
        $( "#key_key_label" ).after(
            ' <button onclick="copyToClipboardNoScroll(this, $(\'#key_key\').text());return false;">' +
            '<i class="fa fa-clipboard" aria-hidden="true"></i>Copy</button>' +
            '</p>'
        );
        $( "#secret_label" ).after(
            ' <button onclick="copyToClipboardNoScroll(this, $(\'#text_2\').text());return false;">' +
            '<i class="fa fa-clipboard" aria-hidden="true"></i>Copy</button>' +
            '</p>'
        );
});
</script>

<?php
$OUTPUT->footerEnd();

