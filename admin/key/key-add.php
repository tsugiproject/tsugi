<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");
require_once("key-util.php");

use \Tsugi\Util\U;
use \Tsugi\UI\CrudForm;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');
session_start();
require_once("../gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

if ( ! isAdmin() ) {
    die('Must be admin');
}

$from_location = "keys";
$tablename = "{$CFG->dbprefix}lti_key";
$fields = array('key_title', 'key_key', 'key_sha256', 'secret', 'deploy_key', 'deploy_sha256', 'issuer_id',
    'lms_issuer', 'lms_issuer_sha256', 'lms_client', 'lms_oidc_auth', 'lms_keyset_url', 'lms_token_url', 'lms_token_audience',
    'xapi_url', 'xapi_user', 'xapi_password',
    'caliper_url', 'caliper_key', 'created_at', 'updated_at', 'user_id');

$titles = array(
    'key_key' => 'LTI 1.1: OAuth Consumer Key',
    'secret' => 'LTI 1.1: OAuth Consumer Secret',
    'deploy_key' => 'LTI 1.3: Deployment ID (from the Platform)',
    'issuer_id' => 'LTI 1.3: Issuer (from this system)',

    'lms_issuer' => 'LTI 1.3 Platform Issuer URL',
	'lms_client' => 'LTI 1.3 Platform Client ID - usually a GUID',
	'lms_oidc_auth' => 'LTI 1.3 Platform OIDC Login / Authorization Endpoint URL',
	'lms_keyset_url' => 'LTI 1.3 Platform KeySet URL',
	'lms_token_url' => 'LTI 1.3 Platform Token URL',
	'lms_token_audience' => 'LTI 1.3 Platform Audience (optional)',
);

if ( isset($_POST['issuer_id']) && strlen($_POST['issuer_id']) == 0 ) $_POST['issuer_id'] = null;
if ( isset($_POST['key_key']) && strlen($_POST['key_key']) == 0 ) $_POST['key_key'] = null;
if ( isset($_POST['user_id']) && strlen($_POST['user_id']) < 1 && isset($_SESSION['id']) ) $_POST['user_id'] = $_SESSION['id'];

// Check the complex interaction of constraints
$key_key = U::get($_POST,'key_key');
$deploy_key = U::get($_POST,'deploy_key');
$issuer_id = U::get($_POST,'issuer_id');
if ( count($_POST) > 0 ) {
    $key_title = U::get($_POST,'key_title');
    if ( !is_string($key_title) || strlen($key_title) < 1 ) {
        $_SESSION['error'] = 'Key title is required';
        header("Location: key-add");
        return;
    }
}

$retval = CrudForm::handleInsert($tablename, $fields);
if ( $retval == CrudForm::CRUD_SUCCESS || $retval == CrudForm::CRUD_FAIL ) {
    header("Location: $from_location");
    return;
}

// Get the issuer count
$sql = "SELECT issuer_id, issuer_key, issuer_guid
        FROM {$CFG->dbprefix}lti_issuer";
$rows = $PDOX->allRowsDie($sql);


$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

?>
<h1>Adding Tsugi Tenant/Key
  <a class="btn btn-default" href="keys">Exit</a>
</h1>
<ul class="nav nav-tabs">
  <li class="active"><a href="#data" data-toggle="tab" aria-expanded="true">Key Data</a></li>
  <li class=""><a href="#info" data-toggle="tab" aria-expanded="true">About Keys</a></li>
  <li class=""><a href="#dynamic" data-toggle="tab" aria-expanded="true">Brightspace / Sakai / Moodle</a></li>
  <li class=""><a href="#blackboard" data-toggle="tab" aria-expanded="true">Blackboard</a></li>
  <li class=""><a href="#canvas" data-toggle="tab" aria-expanded="true">Canvas</a></li>
</ul>
<div id="myTabContent" class="tab-content" style="margin-top:10px;">
<div class="tab-pane fade active in" id="data">
<p>
Sometimes you need to give the LMS the Tsugi URLs to make a new security arrangement
<b>before</b> they can give you the Platform values to put into either a global issuer
or this form.  See "About Keys" for detail on how to create a <b>draft</b> key.
<p>
<?php

CrudForm::insertForm($fields, $from_location, $titles);

?>
</p>
</div>
<div class="tab-pane fade" id="info">
<h2>Tenant Key Options</h2>
<p>
A single entry in this table defines a "distinct tenant" in Tsugi.
Data in Tsugi is isolated to a tenant.  For a key to work it must have at least one of
<ul>
<li>An LTI 1.1 <b>oauth_consumer_key</b> that must be unique in this system and
a <b>secret</b>
<li>LTI 1.3 <b>issuer</b> configuration data and a <b>deployment_id</b>.  Some LMS's support
dynamic registration do not need to have a pre-created Tsugi Issuer entry and other LMS's
work well by first creating an issuer and then associating a tenant key with that issuer.
See the per-LMS documentation for more details.
</li>
</ul>
Some keys
specify both credentials when an existing LTI 1.1 key is being transitioned to LTI 1.3
or if you want to flow LTI 1.1 and LTI 1.3 launches into a single tenant.
</p>
<h2>LTI 1.3 Dynamic Registration</h2>
<p>
If you are planning on using the
<a href="https://www.imsglobal.org/spec/lti-dr/v1p0" target="_blank">IMS Dynamic Registration</a>
or you need to provide the LMS tool
configuration information before they can provide you the values needed on this page,
you can create a <b>draft</b> key here with just a title and then view the key detail page
to see instructions to perform the configuration process.  Launches to draft keys will fail.
</p>
<p>
You can create a <i>draft</i> key by entering a title and nothing else and saving
it. You can then view all the URLs for this security arrangement and send that to the LMS
administrators - and then they will give you the URLs generated by their system.
The you can come back to this screen and edit the data to enter all of the Platform / LMS
field values to set complete up and have a launchable key.
</p>
<h2>LTI 1.1 to LTI 1.3 Migration</h2>
<p>
To receive both LTI 1.1 and LTI 1.3 launches to this "tenant", simply set all four fields.
If you are adding LTI 1.3 to a pre-existing LTI 1.1 tenant, the LMS must
support
LTI Advantage legacy LTI 1.1 support as described in the
<a href="http://www.imsglobal.org/spec/lti/v1p3/migr#lti-1-1-migration-claim" target="_blank">
IMS Learning Tools® Interoperability Migration Guide - Migration Claim
</a>.   The LMS must sign the claim using both the LTI 1.1 and LTI 1.3 security data.
The migration claim is not required - but if it is present, Tsugi will insist that it is properly
signed or it will reject the launch.
</p>
</div>
<div class="tab-pane fade" id="dynamic">
<h2>LTI 1.3</h2>
<p>
Congratulations! Your LMS supports
<a href="https://www.imsglobal.org/spec/lti-dr/v1p0" target="_blank">IMS Dynamic Registration</a>.
This standard greatly simplifies the process of creating and configuring a security relationship between
Tsugi and an LMS.
Create a <b>draft</b> key in Tsugi by entering the <b>title</b> and <b>issuer</b>.  Leave the rest of the fields blank
and then save the key.  Then immediately view the key to get details on how to run dynamic configuration
on each LMS.
</p>
<p>
The issuer for all three systems is the base URL of the system as deployed with no slash at the end:
<pre>
https://d2l.msu.edu
https://sakai.dayton.edu
https://modules.lancaster.ac.uk
</pre>
If you are using a test server the issuer is still the base url of the server:
<pre>
https://lrngexps.brightspacedemo.com
https://qa22-mysql.nightly.sakaiproject.org
https://dev1.sakaicloud.com
</pre>
<h2>LTI 1.1</h2>
<p>
Create a Tennant key with the LTI key and secret. Then come back here and view the key to
see the various URLs you can use to install Tsugi in your LMS.
</p>

</div>
<div class="tab-pane fade" id="canvas">
<h2>LTI 1.3</h2>
<p>
Canvas works a little differently from the rest of the widely used LMS systems.  Canvas
has nice a propretary auto-installation process - but that process works on a <b>Issuer</b>
rather than an <b>Tenant</b>.
</p>
<p>
To use LTI 1.3 in Canvas,
you should first create an Issuer in Tsugi and then use that Issuer to create
the Tenant Key.  A Canvas Issuer is a set of URLs and a <b>Client ID</b>
(like <b>38288000000000436</b>).  Once the issuer is created, you need
to create a <b>deployment</b> in Canvas to get a <b>Deployment ID</b>
(like <b>a16eaea622168ab8327cddef847ccabeea459a79</b>).
</p>
<p>
Create a Tsugi tenant key by selecting the Canvas issuer and adding
the Deployment Id.  At that point the tenant key should start working.
</p>
<p>
In Canvas you create a <b>Deployment ID</b> by using the <b>+ App</b>
in your course settings or by having an administrator do the <b>+ App</b>
for you.
</p>
<h2>LTI 1.1</h2>
<p>
To use LTI 1.1 in Canvas, create your key with a title, key, and secret and come back here
to view the XML-based Canvas auto-installation URL.   Auto-installation of LTI 1.1 tools
in Canvas is on a per-Tenant basis.
</p>
</div>
<div class="tab-pane fade" id="blackboard">
<?php
require_once("blackboard-detail.php");
?>
<h2>LTI 1.1</h2>
<p>
Create a Tennant key with the LTI key and secret.  Then come back here and view the key
to see the various URLs you can use to install Tsugi in Blackboard.
</p>
</div>
</div>
<?php
$OUTPUT->footerStart();
$select_text = "<select id=\"issuer_id_select\"><option value=\"\">No Global Issuer Selected</option>";
foreach($rows as $row) {
    $select_text .= '<option value="'.$row['issuer_id'].'">'.htmlentities($row['issuer_key']. ' ('.$row['issuer_guid'].')')."</option>";
}
$select_text .= "</select>";

if ( count($rows) > 0 ) {
?>
<script>
    function showHideLMSValues(issuer_id)
    {
        const array = ["lms_issuer", "lms_client", "lms_keyset_url", "lms_oidc_auth", "lms_token_url", "lms_token_audience"];
        if ( issuer_id > 0 ) {
            $("#lms_note").hide();
            var ignored = '';
            array.forEach(function (item, index) {
                $('#'+item).closest('div').hide();
                var val = $('#'+item).val();
                if ( val.length > 0 ) {
                    if ( ignored.length > 0 ) ignored = ignored + ' ';
                    ignored = ignored + item;
                }
            });
            if ( ignored.length > 0 ) {
                alert("By choosing a Global Issuer the following tenant key fields will be ignored: "+ignored);
            }
        } else {
            array.forEach(function (item, index) { $('#'+item).closest('div').show(); });
            $("#lms_note").show();
        }
    }
$('#lms_issuer').closest('div').before("<p id=\"lms_note\">If you enter per-tenant LTI 1.3 Platform data in the fields below, (a) set them all and (b) do not select a global issuer for this tenant. If you select a global issuer, the LTI 1.3 Platform fields below will be ignored.</p>");
$('<?= $select_text ?>').insertBefore('#issuer_id');
$('#issuer_id').hide();
$('#issuer_id_select').on('change', function() {
  $('input[name="issuer_id"]').val(this.value);
  showHideLMSValues(this.value);
});
</script>

<?php
}
$OUTPUT->footerEnd();

