<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

use \Tsugi\Util\U;
use \Tsugi\Util\LTI13;
use \Tsugi\UI\CrudForm;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();
require_once("../gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

if ( ! isAdmin() ) {
    die('Must be admin');
}

$tablename = "{$CFG->dbprefix}lti_external";
$fields = array("external_id", "endpoint", "name", "url", "description", "pubkey", "privkey", "fa_icon", "json");
$realfields = array("external_id", "endpoint", "name", "url", "description", "pubkey", "privkey", "fa_icon", "json");
$current = $CFG->getCurrentFileUrl(__FILE__);
$from_location = ".";
$allow_delete = true;
$allow_edit = true;
$where_clause = '';
$query_fields = array();
$titles = array(
    'endpoint' => 'Launch endpoint on this system under /ext - must be letters, numbers and underscores and must be unique',
    'name' => 'Name of tool shown to user in the store',
    'fa_icon' => "An optional FontAwesome icon like 'fa-fast-forward'",
    'url' => 'URL Where the external tool receives launches',
    'pubkey' => 'External Tool Public Key (Do not edit this value, set to blank to re-generate)',
    'json' => 'Additional settings for your tool registration (see below)'
);

// Handle the post data
if ( U::get($_POST,'endpoint') ) {
    if ( strlen(U::get($_POST,'pubkey')) < 1 || strlen(U::get($_POST,'privkey'))) {
        $success = LTI13::generatePKCS8Pair($publicKey, $privateKey);
        if ( is_string($success) ) {
            $_SESSION['error'] = "Could not create key pair:".$success;
            header("Location: ".U::addsession($from_location));
            return;
        }
        $_POST['pubkey'] = $publicKey;
        $_POST['privkey'] = $privateKey;
    }
}
$row =  CrudForm::handleUpdate($tablename, $realfields, $where_clause,
    $query_fields, $allow_edit, $allow_delete, $titles);

if ( $row === CrudForm::CRUD_FAIL || $row === CrudForm::CRUD_SUCCESS ) {
    header("Location: ".U::addsession($from_location));
    return;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

$title = 'External Tool Entry';
echo("<h1>$title</h1>\n<p>\n");
$extra_buttons=false;
$retval = CrudForm::updateForm($row, $fields, $current, $from_location, $allow_edit, $allow_delete,$extra_buttons,$titles);
if ( is_string($retval) ) die($retval);
echo("</p>\n");

$OUTPUT->footer();

