<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");
require_once("issuer-util.php");

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
    'issuer_guid' => 'LTI 1.3 Tsugi Unique Issuer GUID',
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
Editing Issuer
  <a class="btn btn-default" href="#" onclick="window.location.reload(); return false;">Refresh</a>
  <a class="btn btn-default" href="keys">Exit</a>
</h1>
<ul class="nav nav-tabs">
  <li class="active"><a href="#generic" data-toggle="tab" aria-expanded="true">Issuer Data</a></li>
  <li><a href="#canvas" data-toggle="tab" aria-expanded="false">Canvas</a></li>
<li><a href="#sakai" data-toggle="tab" aria-expanded="false">Sakai</a></li>
  <li><a href="#brightspace" id="brightspace-click" data-toggle="tab" aria-expanded="false">Brightspace</a></li>
  <li><a href="#blackboard" data-toggle="tab" aria-expanded="false">Blackboard</a></li>
<li><a href="#moodle" data-toggle="tab" aria-expanded="false">Moodle</a></li>
</ul>
<div id="myTabContent" class="tab-content" style="margin-top:10px;">
<div class="tab-pane fade active in" id="generic">
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
$lti13_canvas_json_url = $CFG->wwwroot . '/lti/store/canvas-config-json.php?issuer_guid=' . urlencode($row['issuer_guid']);
} else {
$lti13_tool_keyset_url = $CFG->wwwroot . '/lti/keyset';
$lti13_canvas_json_url = $CFG->wwwroot . '/lti/store/canvas-config-json.php?issuer=' . urlencode($row['issuer_key']);
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
</div>
<div class="tab-pane fade" id="canvas">
<p>
For Canvas, you can use this URL to copy configuration data using
JSON instead of copying all of the Tsugi configuration values.
<pre>
Canvas Configuration URL: <a href="#" onclick="copyToClipboardNoScroll(this, '<?= htmlentities($lti13_canvas_json_url) ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</a>
<?= htmlentities($lti13_canvas_json_url) ?>
</pre>
Once you have completed the registration process in Canvas, it should provide
you the values to fill in the configuration URLs and
<b>LTI 1.3 Client ID</b> that you have been assigned.  A Canvas Client ID looks 
like <b>38288000000000436</b>.
</p>
<p>
The following are the typical values the other configuration values 
for a production / cloud hosted instance of Canvas:
<pre>
<b>Issuer Key</b>
https://canvas.instructure.com

<b>LTI 1.3 Platform OAuth2 Well-Known/KeySet URL (from the platform)</b>
https://canvas.instructure.com/api/lti/security/jwks

<b>LTI 1.3 Platform OAuth2 Bearer Token Retrieval URL (from the platform)</b>
https://canvas.instructure.com/login/oauth2/token

<b>LTI 1.3 Platform OIDC Authentication URL (from the Platform)</b>
https://canvas.instructure.com/api/lti/authorize_redirect
</pre>
<p>
If you are being installed in a cloud-hosted test or dev environment, the
<b>canvas.instructure.com</b> in the above URLs might be changed to
<b>canvas.test.instructure.com</b> or <b>canvas.beta.instructure.com</b>.
If this is a locally hosted environment, you will use your own domain name for these URLs.
</p>
The <b>Keyset URL</b> is a readonly URL that you should be able to open in your browser -
(<a href="https://canvas.instructure.com/api/lti/security/jwks" target="_blank">Try it</a>).
It is a simple way to test if the URLs are somewhat correct.
</p>
<p>
Once the issuer is complete you need to get a<b>deployment id</b> and use it to add 
a <b>Tenant Key</b> in Tsugi.
</p>
<h2>If you are the Canvas Administrator</h2>
<p>
If you see an <b>Admin</b> button in your Canvas UI, you may be an account or sub-account admin and may be able
to set up the Issuer.  Go into
<b>Admin -> Developer Keys -> + Developer Key -> + LTI Key -> (Method) Enter URL</b> 
and paste in the configuration URL.  Before you save the developer key, make sure to
mark the key as <b>Public</b>.
</p>
<p>
Once you save the developer key it will give you the new Client Id 
(like <b>38288000000000436</b>)
that you can then put into the Tsugi Issuer entry
to complete the issuer.
</p>
</div>
<?php require_once("issuer-not.php"); ?>
</div>
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

