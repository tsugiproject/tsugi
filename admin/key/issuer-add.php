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

$from_location = "issuers";
$tablename = "{$CFG->dbprefix}lti_issuer";

$fields = array("issuer_title", "issuer_key", "issuer_client", "issuer_sha256",
    "lti13_keyset_url", "lti13_token_url", "lti13_oidc_auth",
    "issuer_guid", "lti13_token_audience",
    "created_at", "updated_at");

$titles = array(
    'issuer_key' => 'LTI 1.3 Issuer URL',
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
$lti13_canvas_json_url = $CFG->wwwroot . '/lti/store/canvas-config-json.php?issuer_guid=' . urlencode($guid);
$lti13_ims_json_url = $CFG->wwwroot . '/lti/store/ims-config/' . urlencode($guid);

?>
<h1>
<img src="<?= $CFG->staticroot ?>/img/logos/tsugi-logo-square.png" style="float:right; width:48px;">
Adding Issuer Entry
  <a class="btn btn-default" href="keys">Exit</a>
</h1>
<ul class="nav nav-tabs">
  <li class="active"><a href="#generic" data-toggle="tab" aria-expanded="true">Issuer Data</a></li>
  <li><a href="#sakai" data-toggle="tab" aria-expanded="false">Sakai</a></li>
  <li><a href="#brightspace" id="brightspace-click" data-toggle="tab" aria-expanded="false">Brightspace</a></li>
  <li><a href="#canvas" data-toggle="tab" aria-expanded="false">Canvas</a></li>
  <li><a href="#moodle" data-toggle="tab" aria-expanded="false">Moodle</a></li>
</ul>
<div id="myTabContent" class="tab-content" style="margin-top:10px;">
  <div class="tab-pane fade active in" id="generic">
<p>
For LTI 1.3, you need to enter these URLs in your LMS configuration
associated with this Issuer/Client ID.
</p>
<p>
Often the LMS expects you
to create the Issuer in Tsugi and provide the configuration URLs
before they will create their side of the security arrangement
and provide you the URLs needed for this screen.
</p>
<p>
You solve this "who goes first" problem by creating a "draft" issuer
with only a title and Issuer URL.  If you don't know the issuer URL
just put something in close - you can edit it later after the LMS Admin
gives you the value.
</p>
<p>
<?php

CrudForm::insertForm($fields, $from_location, $titles, $fields_defaults);

?>
</p>
</div>
<div class="tab-pane fade" id="brightspace">
<p>
Brightspace is the one LMS that will provide you with an <b>audience</b>
value.  Launches and token requests to Brightspace will fail without this value.
</p>
</div>
<div class="tab-pane fade" id="sakai">
<p>
For Sakai 21 and later you can use Dynamic Configuration - which is actually initiated from the
Tenant Key detail page.   You can create a issuer here and link it to a tenant key,
but the simplest thing is just to create a draft Tenant key and then use Dynamic Configuration.
</p><p>
Because a Sakai server usually supports a single tenant they usually set the
<b>deployment id</b> to <b>1</b> in the tenant configuration.
</p>
</div>
<div class="tab-pane fade" id="moodle">
<p>
For later versions of Moodle, you can use Dynamic Configuration - which is actually initiated from the
Tenant Key detail page.   You can create a issuer here and link it to a tenant key,
but the simplest thing is just to create a draft Tenant key and then use Dynamic Configuration.
</p><p>
Because a Moodle server usually supports a single tenant they usually set the
<b>deployment id</b> to <b>1</b> in the tenant configuration.
</p>
</div>
<div class="tab-pane fade" id="canvas">
<p>
Canvas supports a JSON-based configuration URL.  The best strategy is probably to create a
draft issuer in here in Tsugi, then view the issuer, and provide the configuration URL to Canvas.  Then
when the Canvas admin sends you their configuration values and URLs, come back and edit the issuer with
the provided information to complete the security arrangement.
</p>
<p>
Canvas may want you to create multiple issuers for a client (production, test, dev) and then
have you create a tenant key for each issuer.  This is normal.  A Tsugi client / tenant key can
only be associated with one issuer - so if you make three issuers - you will need to make three
tenants.  And you want to "silo" the three sets of data in Tsugi for best security.  It might feel
weird - but it is the right approach.
</div>
</div>
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
