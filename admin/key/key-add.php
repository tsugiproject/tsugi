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
    'xapi_url', 'xapi_user', 'xapi_password',
    'caliper_url', 'caliper_key', 'created_at', 'updated_at', 'user_id');

$titles = array(
    'key_key' => 'LTI 1.1: OAuth Consumer Key',
    'secret' => 'LTI 1.1: OAuth Consumer Secret',
    'deploy_key' => 'LTI 1.3: Deployment ID (from the Platform)',
    'issuer_id' => 'LTI 1.3: Issuer (from this system)',
);

if ( isset($_POST['issuer_id']) && strlen($_POST['issuer_id']) == 0 ) $_POST['issuer_id'] = null;
if ( isset($_POST['key_key']) && strlen($_POST['key_key']) == 0 ) $_POST['key_key'] = null;
if ( isset($_POST['user_id']) && strlen($_POST['user_id']) < 1 ) $_POST['user_id'] = $_SESSION['id'];

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

/*
if ( count($_POST) > 0 ) {
    $retval = validate_key_details($key_key, $deploy_key, $issuer_id);
    if ( ! $retval ) {
        header("Location: key-add");
        return;
    }
}
*/

$retval = CrudForm::handleInsert($tablename, $fields);
if ( $retval == CrudForm::CRUD_SUCCESS || $retval == CrudForm::CRUD_FAIL ) {
    header("Location: $from_location");
    return;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

?>
<h1>Adding Tsugi Tenant/Key
  <a class="btn btn-default" href="keys">Cancel</a>
</h1>
<ul class="nav nav-tabs">
  <li class="active"><a href="#data" data-toggle="tab" aria-expanded="true">Key Data</a></li>
  <li class=""><a href="#info" data-toggle="tab" aria-expanded="true">About Keys</a></li>
</ul>
<div id="myTabContent" class="tab-content" style="margin-top:10px;">
<div class="tab-pane fade active in" id="data">
<p>
<?php

CrudForm::insertForm($fields, $from_location, $titles);

?>
</p>
</div>
<div class="tab-pane fade" id="info">
<p>
A single entry in this table defines a "distinct tenant" in Tsugi.
Data in Tsugi is isolated to a tenant.  For a key to work it must have at least one of
<ul>
<li>An LTI 1.1 <b>oauth_consumer_key</b> that must be unique in this system and
a <b>secret</b>
<li>An LTI 1.3 <b>issuer</b> and <b>deployment_id</b>.  You may need to add an
issuer if the issuer does not already exist in Tsugi.
</li>
</ul>
Some keys
specify both credentials when an existing LTI 1.1 key is being transitioned to LTI 1.3.
</p>
<p>
If you are planning on using the LTI 1.3 Dynamic Configurtation or you need to provide the LMS
configuration information before they can provide you the values needed on this page,
you can create a draft key here with just a title and then view the key detail page
to see instructions to perform the configuration process.  Launches to draft keys will fail.
</p>
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
</div>
<?php
$OUTPUT->footerStart();

$sql = "SELECT issuer_id, issuer_key, issuer_guid
        FROM {$CFG->dbprefix}lti_issuer";
$rows = $PDOX->allRowsDie($sql);

$select_text = "<select id=\"issuer_id_select\"><option value=\"\">No Issuer Selected</option>";
foreach($rows as $row) {
    $select_text .= '<option value="'.$row['issuer_id'].'">'.htmlentities($row['issuer_key']. ' ('.$row['issuer_guid'].')')."</option>";
}
$select_text .= "</select>";
?>
<script>
$('<?= $select_text ?>').insertBefore('#issuer_id');
$('#issuer_id').hide();
$('#issuer_id_select').on('change', function() {
  $('input[name="issuer_id"]').val(this.value);
});
</script>

<?php
$OUTPUT->footerEnd();

