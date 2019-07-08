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
$fields = array('issuer_id', 'issuer_key', 'issuer_client',
     'lti13_keyset_url', 'lti13_token_url', 'lti13_token_audience', 'lti13_oidc_auth', 'lti13_platform_pubkey',
     'lti13_pubkey', 'lti13_privkey', 'lti13_tool_keyset_url', 'lti13_canvas_json_url',
     'created_at', 'updated_at');
$realfields = array('issuer_id', 'issuer_key', 'issuer_client', 'issuer_sha256',
     'lti13_keyset_url', 'lti13_token_url', 'lti13_token_audience', 'lti13_oidc_auth', 'lti13_platform_pubkey',
     'lti13_pubkey', 'lti13_privkey', 'created_at', 'updated_at');

$titles = array(
    'issuer_client' => 'LTI 1.3 Client ID (from the Platform)',
    'lti13_keyset_url' => 'LTI 1.3 Platform OAuth2 Well-Known/KeySet URL (from the platform)',
    'lti13_token_url' => 'LTI 1.3 Platform OAuth2 Bearer Token Retrieval URL (from the platform)',
    'lti13_token_audience' => 'LTI 1.3 Platform OAuth2 Bearer Token Audience Value (optional - from the platform)',
    'lti13_oidc_auth' => 'LTI 1.3 Platform OIDC Authentication URL (from the Platform)',
    'lti13_platform_pubkey' => 'LTI 1.3 Platform Public Key (Usually retrieved via keyset url)',

    'lti13_pubkey' => 'LTI 1.3 Tool Public Key (Provide to the platform)',
    'lti13_privkey' => 'LTI 1.3 Private Key (kept internally only)',
    'lti13_tool_keyset_url' => 'LTI 1.3 Tool Keyset Url (Extension - may not be needed/used by LMS)',
    'lti13_canvas_json_url' => 'Canvas Configuration URL (json)',
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

$title = 'Issuer Entry';
?>
<h1>
<img src="<?= $CFG->staticroot ?>/img/logos/tsugi-logo-square.png" style="float:right; width:48px;">
<?= $title ?></h1>
<?php
$extra_buttons=false;
$row['lti13_tool_keyset_url'] = $CFG->wwwroot . '/lti/keyset?issuer=' . urlencode($row['issuer_key']);
$row['lti13_canvas_json_url'] = $CFG->wwwroot . '/lti/store/canvas-config.json?issuer=' . urlencode($row['issuer_key']);
$retval = CrudForm::updateForm($row, $fields, $current, $from_location, $allow_edit, $allow_delete,$extra_buttons,$titles);
if ( is_string($retval) ) die($retval);
echo("</p>\n");
?>
<hr/>
<p>
These URLs need to be in your LMS configuration associated with this Issuer/Client ID.
<pre>
LTI 1.3 OpenID Connect Endpoint: <?= $CFG->wwwroot ?>/lti/oidc_login
LTI 1.3 Tool Redirect Endpoint: <?= $CFG->wwwroot ?>/lti/oidc_launch
</pre>
</p>
<?php


$OUTPUT->footerStart();
?>
<script>
$('#lti13_platform_pubkey').css('white-space', 'pre');
$('#lti13_privkey').css('white-space', 'pre');
$('#lti13_pubkey').css('white-space', 'pre');
</script>
<?php
$OUTPUT->footerEnd();

