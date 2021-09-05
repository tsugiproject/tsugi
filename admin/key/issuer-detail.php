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
$fields = array('issuer_id', 'issuer_title', 'issuer_key', 'issuer_client', 'issuer_guid',
    'lti13_keyset_url', 'lti13_token_url', 
    'lti13_oidc_auth', 'lti13_token_audience', 
    'created_at', 'updated_at');
$realfields = array('issuer_id', 'issuer_title', 'issuer_key', 'issuer_client', 'issuer_guid', 'issuer_sha256',
    'lti13_keyset_url', 'lti13_token_url', 
    'lti13_oidc_auth', 'lti13_token_audience', 
    'created_at', 'updated_at');

$titles = array(
    'issuer_client' => 'LTI 1.3 Client ID (from the Platform)',
    'issuer_guid' => 'LTI 1.3 Unique Issuer GUID',
    'lti13_keyset_url' => 'LTI 1.3 Platform OAuth2 Well-Known/KeySet URL (from the platform)',
    'lti13_token_url' => 'LTI 1.3 Platform OAuth2 Bearer Token Retrieval URL (from the platform)',
    'lti13_token_audience' => 'LTI 1.3 Platform OAuth2 Bearer Token Audience Value (optional - from the platform)',
    'lti13_oidc_auth' => 'LTI 1.3 Platform OIDC Authentication URL (from the Platform)',
);

// Handle the post data
$row =  CrudForm::handleUpdate($tablename, $realfields, $where_clause,
    $query_fields, $allow_edit, $allow_delete, $titles);

if ( $row === CrudForm::CRUD_FAIL || $row === CrudForm::CRUD_SUCCESS ) {
    header('Location: '.$from_location);
    return;
}

$show_guid = true;
//Show guid if applicable.
if ((!isset($row['issuer_guid']) || empty($row['issuer_guid'])) || ! U::isGUIDValid($row['issuer_guid'])) {
    /*$arrKey = array_search('issuer_guid', $fields);
    if ($arrKey !== false) {
        unset($fields[$arrKey]);
    }*/
    $fields = array_merge(array_diff($fields, array('issuer_guid')));
    $show_guid = false;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

$csql = "SELECT COUNT(key_id) AS count FROM {$CFG->dbprefix}lti_key
        WHERE issuer_id = :IID";
$values = array(":IID" => $row['issuer_id']);
$crow = $PDOX->rowDie($csql, $values);
$count = $crow ? $crow['count'] : 0;

$title = 'Issuer Entry';
?>
<h1>
<img src="<?= $CFG->staticroot ?>/img/logos/tsugi-logo-square.png" style="float:right; width:48px;">
<?= $title ?></h1>
<p>
<b>Keys that use this issuer:</b> <?= $count ?>
</p>
<?php
if ( $count > 0 ) {
    echo('<p style="color:red;">If you delete this issuer, the keys that reference this issuer will stop working.</p>');
}

$extra_buttons=false;
// If we have a valid GUID
if ($show_guid) {
    $lti13_tool_keyset_url = $CFG->wwwroot . '/lti/keyset';
    $lti13_canvas_json_url = $CFG->wwwroot . '/lti/store/canvas-config.json?issuer_guid=' . urlencode($row['issuer_guid']);
} else {
    $lti13_tool_keyset_url = $CFG->wwwroot . '/lti/keyset';
    $lti13_canvas_json_url = $CFG->wwwroot . '/lti/store/canvas-config.json?issuer=' . urlencode($row['issuer_key']);
}
$retval = CrudForm::updateForm($row, $fields, $current, $from_location, $allow_edit, $allow_delete,$extra_buttons,$titles);
if ( is_string($retval) ) die($retval);
echo("</p>\n");

$guid = $row['issuer_guid'];
$oidc_login = $CFG->wwwroot . '/lti/oidc_login' . ($show_guid ? '/'.urlencode($guid): '');
$oidc_redirect = $CFG->wwwroot . '/lti/oidc_launch';
$lti13_keyset = $CFG->wwwroot . '/lti/keyset';
$deep_link = $CFG->wwwroot . '/lti/store/';
$lti13_sakai_json_url = ($show_guid ? $CFG->wwwroot . '/lti/store/sakai-config/' . urlencode($guid): '');

?>
<hr/>
<p>
These URLs need to be in your LMS configuration associated with this Issuer/Client ID.
<pre>
LTI 1.3 OpenID Connect Endpoint: <a href="#" onclick="copyToClipboardNoScroll(this, '<?= $oidc_login ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</a>
<?= $oidc_login ?> 

LTI 1.3 Tool Redirect Endpoint: <a href="#" onclick="copyToClipboardNoScroll(this, '<?= $oidc_redirect ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</a>
<?= $oidc_redirect ?> 

LTI 1.3 Tool Keyset URL: <a href="#" onclick="copyToClipboardNoScroll(this, '<?= $lti13_keyset ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</a>
<?= $lti13_keyset ?> 

LTI Content Item / Deep Link Endpoint: <a href="#" onclick="copyToClipboardNoScroll(this, '<?= $deep_link ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</a>
<?= $deep_link ?> 
</pre>
</p>
<p>
If you use Canvas, you can use this configuration URL to transfer this tool's configuration data:
<pre>
Canvas Configuration URL: <a href="#" onclick="copyToClipboardNoScroll(this, '<?= htmlentities($lti13_canvas_json_url) ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</a>
<?= htmlentities($lti13_canvas_json_url) ?>
</pre>
</p>
<?php

$OUTPUT->footerStart();
?>
<script>
// Hide GUID as readonly
if ($('#issuer_guid').length) {
    $('#issuer_guid_parent').hide();
}
</script>
<?php
$OUTPUT->footerEnd();

