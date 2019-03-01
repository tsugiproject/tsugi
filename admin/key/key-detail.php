<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

use \Tsugi\UI\CrudForm;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();
require_once("../gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

if ( ! isAdmin() ) {
    die('Must be admin');
}

$tablename = "{$CFG->dbprefix}lti_key";
$current = $CFG->getCurrentFileUrl(__FILE__);
$from_location = "keys";
$allow_delete = true;
$allow_edit = true;
$where_clause = '';
$query_fields = array();
$fields = array('key_id', 'key_key', 'secret', 'caliper_url', 'caliper_key',
    'lti13_client_id', 'lti13_keyset_url', 'lti13_token_url', 'lti13_oidc_auth', 'lti13_platform_pubkey',
     'lti13_pubkey', 'lti13_privkey', 'lti13_tool_keyset_url', 'created_at', 'updated_at', 'user_id');
$realfields = array_diff($fields, ['lti13_tool_keyset_url']);

$titles = array(
    'lti13_client_id' => 'LTI 1.3 Client ID (from the Platform)',
    'lti13_keyset_url' => 'LTI 1.3 Platform OAuth2 Well-Known/KeySet URL (from the platform)',
    'lti13_token_url' => 'LTI 1.3 Platform OAuth2 Bearer Token Retrieval URL (from the platform)',
    'lti13_oidc_auth' => 'LTI 1.3 Platform OIDC Authentication URL (from the Platform)',
    'lti13_platform_pubkey' => 'LTI 1.3 Platform Public Key (Usually retrieved via keyset url)',

    'lti13_pubkey' => 'LTI 1.3 Tool Public Key (Provide to the platform)',
    'lti13_privkey' => 'LTI 1.3 Private Key (kept internally only)',
    'lti13_tool_keyset_url' => 'LTI 1.3 Tool Keyset Url (Extension - may not be needed/used by LMS)',
);

// Handle the post data
$row =  CrudForm::handleUpdate($tablename, $realfields, $where_clause,
    $query_fields, $allow_edit, $allow_delete, $titles);

if ( $row === CrudForm::CRUD_FAIL || $row === CrudForm::CRUD_SUCCESS ) {
    header('Location: '.$from_location);
    return;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

$title = 'Key Entry';
echo("<h1>$title</h1>\n<p>\n");
$extra_buttons=false;
$row['lti13_tool_keyset_url'] = $CFG->wwwroot . '/lti/keyset?key_id=' . $row['key_id'];
// $fields[] = 'lti13_tool_keyset_url';
$retval = CrudForm::updateForm($row, $fields, $current, $from_location, $allow_edit, $allow_delete,$extra_buttons,$titles);
if ( is_string($retval) ) die($retval);
echo("</p>\n");

$OUTPUT->footer();

