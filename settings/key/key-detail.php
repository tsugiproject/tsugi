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

$inedit = U::get($_REQUEST,'edit');

$tablename = "{$CFG->dbprefix}lti_key";
$current = $CFG->getCurrentFileUrl(__FILE__);
$from_location = LTIX::curPageUrlFolder();
$allow_delete = true;
$allow_edit = true;
$where_clause = '';
$query_fields = array();
if ( $inedit ) {
    $fields = array('key_id', 'key_title', 'key_key', 'secret', 'deploy_key', 'updated_at');
    $realfields = array('key_id', 'key_title', 'key_key', 'key_sha256', 'secret',
        'deploy_key', 'deploy_sha256', 'updated_at');
} else {
    $fields = array('key_id', 'key_title', 'key_key', 'secret', 'issuer_id', 'deploy_key', 'updated_at');
    $realfields = array('key_id', 'key_title', 'key_key', 'key_sha256', 'secret',
        'issuer_id', 'deploy_key', 'deploy_sha256', 'updated_at');
}

$titles = array(
    'key_key' => 'LTI 1.1: OAuth Consumer Key',
    'secret' => 'LTI 1.1: OAuth Consumer Secret',
    'deploy_key' => 'LTI 1.3: Deployment ID (from the Platform)',
    'issuer_id' => 'LTI 1.3: Issuer',
);

$where_clause .= "user_id = :UID";
$query_fields[":UID"] = $_SESSION['id'];

// Handle the post data
$row =  CrudForm::handleUpdate($tablename, $fields, $where_clause,
    $query_fields, $allow_edit, $allow_delete, $titles);

if ( $row === CrudForm::CRUD_FAIL || $row === CrudForm::CRUD_SUCCESS ) {
    header("Location: ".$from_location);
    return;
}

if ( ! $inedit && U::get($row, 'issuer_id') > 0 ) {
    $issuer_row = $PDOX->rowDie("SELECT issuer_key, issuer_client FROM {$CFG->dbprefix}lti_issuer WHERE issuer_id = :issuer_id",
        array(':issuer_id' => U::get($row, 'issuer_id'))
    );
    if ( $issuer_row ) {
        $row['issuer_id'] = $issuer_row['issuer_key'].' ('.$issuer_row['issuer_client'].')';
    }
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

$title = "Key Entry";
echo("<h1>$title</h1>\n<p>\n");
$extra_buttons = false;
$retval = CrudForm::updateForm($row, $fields, $current, $from_location, $allow_edit, $allow_delete, $extra_buttons,$titles);
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
            ' <button onclick="copyToClipboardNoScroll(this, $(\'#text_3\').text());return false;">' +
            '<i class="fa fa-clipboard" aria-hidden="true"></i>Copy</button>' +
            '</p>'
        );
});
</script>

<?php
$OUTPUT->footerEnd();

