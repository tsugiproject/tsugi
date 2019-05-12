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

$tablename = "{$CFG->dbprefix}lti_issuer";
$current = $CFG->getCurrentFileUrl(__FILE__);
$from_location = "issuers";
$allow_delete = true;
$allow_edit = true;
$where_clause = '';
$query_fields = array();
$fields = array('issuer_id', 'issuer_issuer', 'issuer_client_id', 'issuer_sha256',
     'lti13_keyset_url', 'lti13_token_url', 'lti13_oidc_auth', 'lti13_platform_pubkey',
     'lti13_pubkey', 'lti13_privkey', 'lti13_tool_keyset_url', 'created_at', 'updated_at');
$realfields = array('issuer_id', 'issuer_issuer', 'issuer_client_id', 'issuer_sha256',
     'lti13_keyset_url', 'lti13_token_url', 'lti13_oidc_auth', 'lti13_platform_pubkey',
     'lti13_pubkey', 'lti13_privkey', 'created_at', 'updated_at');

$titles = array(
    'issuer_client_id' => 'LTI 1.3 Client ID (from the Platform)',
    'lti13_keyset_url' => 'LTI 1.3 Platform OAuth2 Well-Known/KeySet URL (from the platform)',
    'lti13_token_url' => 'LTI 1.3 Platform OAuth2 Bearer Token Retrieval URL (from the platform)',
    'lti13_oidc_auth' => 'LTI 1.3 Platform OIDC Authentication URL (from the Platform)',
    'lti13_platform_pubkey' => 'LTI 1.3 Platform Public Key (Usually retrieved via keyset url)',

    'lti13_pubkey' => 'LTI 1.3 Tool Public Key (Provide to the platform)',
    'lti13_privkey' => 'LTI 1.3 Private Key (kept internally only)',
    'lti13_tool_keyset_url' => 'LTI 1.3 Tool Keyset Url (Extension - may not be needed/used by LMS)',
);

// Handle the post data
if ( U::get($_POST,'issuer_issuer') ) {
    $_POST['issuer_sha256'] = LTI13::extract_issuer_key_string(U::get($_POST,'issuer_issuer'), U::get($_POST,'issuer_client_id'));
    $row =  CrudForm::handleUpdate($tablename, $realfields, $where_clause,
        $query_fields, $allow_edit, $allow_delete, $titles);

    if ( $row === CrudForm::CRUD_FAIL || $row === CrudForm::CRUD_SUCCESS ) {
        header('Location: '.$from_location);
        return;
    }
} else { // Just load the row
    $row =  CrudForm::handleUpdate($tablename, $realfields, $where_clause,
        $query_fields, $allow_edit, $allow_delete, $titles);
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

$title = 'Issuer Entry';
echo("<h1>$title</h1>\n<p>\n");
$extra_buttons=false;
$row['lti13_tool_keyset_url'] = $CFG->wwwroot . '/lti/keyset?issuer_id=' . $row['issuer_id'];
$retval = CrudForm::updateForm($row, $fields, $current, $from_location, $allow_edit, $allow_delete,$extra_buttons,$titles);
if ( is_string($retval) ) die($retval);
echo("</p>\n");

$OUTPUT->footer();

