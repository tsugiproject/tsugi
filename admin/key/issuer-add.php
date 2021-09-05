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

$fields = array("issuer_title", "issuer_key", "issuer_client", "issuer_sha256",
    "lti13_keyset_url", "lti13_token_url", "lti13_oidc_auth",
    "issuer_guid", "lti13_token_audience",
    "created_at", "updated_at");

$titles = array(
    'issuer_key' => 'LTI 1.3 Issuer / Platform ID',
    'issuer_client' => 'LTI 1.3 Client ID',
    'lti13_keyset_url' => 'LTI 1.3 Platform KeySet URL',
    'lti13_token_url' => 'LTI 1.3 Platform Access Token URL',
    'lti13_oidc_auth' => 'LTI 1.3 Platform OIDC Authentication Request URL',

    'lti13_tool_keyset_url' => 'LTI 1.3 Tool Keyset Url',
    'lti13_token_audience' => 'LTI 1.3 Platform OAuth2 Bearer Token Audience Value (Optional)',
    'issuer_guid' => 'LTI 1.3 Unique Issuer GUID (within Tool)',
);

if ( U::get($_POST,'issuer_key') ) {
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
$guid = U::createGUID();
$fields_defaults = array(
    'issuer_guid' => $guid
);

$oidc_login = $CFG->wwwroot . '/lti/oidc_login/' . urlencode($guid);
$oidc_redirect = $CFG->wwwroot . '/lti/oidc_launch';
$lti13_keyset = $CFG->wwwroot . '/lti/keyset';
$deep_link = $CFG->wwwroot . '/lti/store/';
$lti13_canvas_json_url = $CFG->wwwroot . '/lti/store/canvas-config.json?issuer_guid=' . urlencode($guid);
$lti13_ims_json_url = $CFG->wwwroot . '/lti/store/ims-config/' . urlencode($guid);

function addLinks() {
    global $oidc_login, $oidc_redirect, $oidc_redirect, $lti13_keyset, $deep_link;
?>
LTI 1.3 OpenID Connect Endpoint: <a href="#" onclick="copyToClipboardNoScroll(this, '<?= $oidc_login ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</a>
<?= $oidc_login ?> 

LTI 1.3 Tool Redirect Endpoint: <a href="#" onclick="copyToClipboardNoScroll(this, '<?= $oidc_redirect ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</a>
<?= $oidc_redirect ?> 

LTI 1.3 Tool Keyset URL: <a href="#" onclick="copyToClipboardNoScroll(this, '<?= $lti13_keyset ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</a>
<?= $lti13_keyset ?> 

LTI Content Item / Deep Link Endpoint: <a href="#" onclick="copyToClipboardNoScroll(this, '<?= $deep_link ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</a>
<?= $deep_link ?> 
<?php
}
?>
<h1>
<img src="<?= $CFG->staticroot ?>/img/logos/tsugi-logo-square.png" style="float:right; width:48px;">
Adding Issuer Entry</h1>
<ul class="nav nav-tabs">
  <li class="active"><a href="#generic" data-toggle="tab" aria-expanded="true">Generic Instructions</a></li>
  <li><a href="#brightspace" id="brightspace-click" data-toggle="tab" aria-expanded="false">Brightspace</a></li>
  <li><a href="#canvas" data-toggle="tab" aria-expanded="false">Canvas</a></li>
  <li><a href="#sakai" data-toggle="tab" aria-expanded="false">Sakai</a></li>
</ul>
<div id="myTabContent" class="tab-content" style="margin-top:10px;">
  <div class="tab-pane fade active in" id="generic">
<p>
For LTI 1.3, you need to enter these URLs in your LMS configuration
associated with this Issuer/Client ID.
<pre>
<?php addLinks(); ?>
</pre>
Once you have created the security arrangement in the LMS you can fill in the
provided values below.
</p>
</div>
<div class="tab-pane fade" id="brightspace">
<p>
For LTI 1.3, you need to enter these URLs in your Brightspace configuration
associated with this Issuer/Client ID. Brightspace provides a value for
"Bearer Token Audience Value" that is not necessary for other LMS systems.
<pre>
<?php addLinks(); ?>
</pre>
Once you have created the security arrangement in the LMS you can fill in the
provided values below.
</p>
</div>
<div class="tab-pane fade" id="sakai">
<p>
For Sakai 21 and later you can use Dynamic Configuration - which is actually initiated from the
Tenant Key detail page.   You can create a issuer here, but the simplest thing is just to create
a draft Tenant key and then use Dynamic Configuration.
</p>
<pre>
<?php addLinks(); ?>
</pre>
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
you the values to fill in the fields below.
</p>
</div>
<div class="tab-pane fade" id="ims" style="display: none;">
IMS is working on a draft auto-provisioning spec.   This is a place to explore
that spec as it is implemented.
</p>
<pre>
IMS Configuration URL: <a href="#" onclick="copyToClipboardNoScroll(this, '<?= htmlentities($lti13_ims_json_url) ?>');return false;"><i class="fas fa-file-export" aria-hidden="true"></i> <i class="fa fa-clipboard" aria-hidden="true"></i>Copy</a>
<?= htmlentities($lti13_ims_json_url) ?>
</pre>
<p>
There is not yet a documented flow to use this url.
</p>
</div>
</div>
<p>
<?php

CrudForm::insertForm($fields, $from_location, $titles, $fields_defaults);

?>
</p>
<?php

$OUTPUT->footerStart();
?>
<script>
// Make GUID as readonly
// $('#issuer_guid').attr('readonly', 'readonly');
$('#issuer_guid_label').parent().hide();
$('#lti13_token_audience').parent().parent().parent().hide();
$('#brightspace-click').on('click', 
    function () {$('#lti13_token_audience').parent().parent().parent().show();}
);

// Test
// https://trunk-mysql.nightly.sakaiproject.org/imsblis/lti13/sakai_config?key=4&clientId=8e96d26d-5c69-4b41-aae4-8e8aa8524636&issuerURL=http%3A%2F%2Ftrunk-mysql.nightly.sakaiproject.org&deploymentId=1
function importLTI13Config() {
    var importUrl = prompt("Enter JSON Configuration URL");
    importUrl = "<?= $CFG->wwwroot . '/admin/proxy_small_json.php' ?>" + '?proxyUrl=' + encodeURIComponent(importUrl);
    console.log(importUrl);

    jQuery.getJSON( importUrl, function(data) {
        console.log(data);
        if ( data.issuerURL ) jQuery("#issuer_key").val(data.issuerURL);
        if ( data.issuerUrl ) jQuery("#issuer_key").val(data.issuerUrl);
        if ( data.clientId ) jQuery("#issuer_client").val(data.clientId);
        if ( data.keySetUrl ) jQuery("#lti13_keyset_url").val(data.keySetUrl);
        if ( data.tokenUrl ) jQuery("#lti13_token_url").val(data.tokenUrl);
        if ( data.authOIDC ) jQuery("#lti13_oidc_auth").val(data.authOIDC);
    })
    .fail(function() {
        alert("Could not retrieve JSON" );
    });

}

</script>
<?php
$OUTPUT->footerEnd();
