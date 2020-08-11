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

$from_location = "issuers";
$tablename = "{$CFG->dbprefix}lti_issuer";

$fields = array("issuer_key", "issuer_client", "issuer_sha256",
    "lti13_keyset_url", "lti13_token_url", "lti13_oidc_auth",
    "lti13_pubkey", "lti13_privkey",
    "issuer_guid", "lti13_token_audience",
    "created_at", "updated_at");

$titles = array(
    'issuer_key' => 'LTI 1.3 Issuer (from the Platform)',
    'issuer_client' => 'LTI 1.3 Client ID (from the Platform)',
    'lti13_keyset_url' => 'LTI 1.3 Platform OAuth2 Well-Known/KeySet URL (from the platform)',
    'lti13_token_url' => 'LTI 1.3 Platform OAuth2 Bearer Token Retrieval URL (from the platform)',
    'lti13_oidc_auth' => 'LTI 1.3 Platform OIDC Authentication URL (from the Platform)',

    'lti13_pubkey' => 'LTI 1.3 Tool Public Key (Provide to the platform)',
    'lti13_privkey' => 'LTI 1.3 Private Key (kept internally only)',
    'lti13_tool_keyset_url' => 'LTI 1.3 Tool Keyset Url',
    'lti13_token_audience' => 'LTI 1.3 Platform OAuth2 Bearer Token Audience Value (from the platform - Optional)',
    'issuer_guid' => 'LTI 1.3 Unique Issuer GUID (within Tool)',
);

if ( U::get($_POST,'issuer_key') ) {
    if ( strlen(U::get($_POST,'lti13_pubkey')) < 1 && strlen(U::get($_POST,'lti13_privkey')) < 1 ) {
        LTI13::generatePKCS8Pair($publicKey, $privateKey);
        $_POST['lti13_pubkey'] = $publicKey;
        $_POST['lti13_privkey'] = $privateKey;
    }
    $retval = CrudForm::handleInsert($tablename, $fields);
    if ( $retval == CrudForm::CRUD_SUCCESS || $retval == CrudForm::CRUD_FAIL ) {
        header("Location: $from_location");
        return;
    }
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
// Create a new GUID
$guid = createGUID();
$fields_defaults = array(
    'issuer_guid' => $guid
);

$oidc_login = $CFG->wwwroot . '/lti/oidc_login/' . urlencode($guid);
$oidc_redirect = $CFG->wwwroot . '/lti/oidc_launch';
$lti13_keyset = $CFG->wwwroot . '/lti/keyset/' . urlencode($guid);
$deep_link = $CFG->wwwroot . '/lti/store/';
?>
<h1>
<img src="<?= $CFG->staticroot ?>/img/logos/tsugi-logo-square.png" style="float:right; width:48px;">
Adding Issuer Entry</h1>
<p>
For LTI 1.3, you need to enter these URLs in your LMS configuration
associated with this Issuer/Client ID.
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
<?php

CrudForm::insertForm($fields, $from_location, $titles, $fields_defaults);

$lti13_canvas_json_url = $CFG->wwwroot . '/lti/store/canvas-config.json?issuer_guid=' . urlencode($guid);
$lti13_sakai_json_url = $CFG->wwwroot . '/lti/store/sakai-config/' . urlencode($guid);

?>
</p>
<p>
After you have saved this entry, you can use this URL in Canvas to transfer this tool's configuration data:
<pre>
Canvas Configuration URL: <a href="#" onclick="copyToClipboardNoScroll(this, '<?= htmlentities($lti13_canvas_json_url) ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</a>
<?= htmlentities($lti13_canvas_json_url) ?>
</pre>
</p>
<p>
For Sakai-21 and later, you can use this URL to copy configuration data instead of copying all of the above values:
<pre>
Sakai Configuration URL: <a href="#" onclick="copyToClipboardNoScroll(this, '<?= htmlentities($lti13_sakai_json_url) ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</a>
<?= htmlentities($lti13_sakai_json_url) ?>
</pre>
</p>
<?php

$OUTPUT->footerStart();
?>
<script>
// Make GUID as readonly
// $('#issuer_guid').attr('readonly', 'readonly');
$('#issuer_guid_label').parent().hide();
$('#lti13_pubkey_label').parent().hide();
$('#lti13_privkey_label').parent().hide();
</script>
<?php
$OUTPUT->footerEnd();
