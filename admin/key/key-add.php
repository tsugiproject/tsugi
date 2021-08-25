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
    $retval = validate_key_details($key_key, $deploy_key, $issuer_id);
    if ( ! $retval ) {
        header("Location: key-add");
        return;
    }
}

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
<h1>Adding Tsugi Tenant/Key</h1>
<p>
A single entry in this table defines a "distinct tenant" in Tsugi.
Data in Tsugi is isolated to a tenant.
All keys need an <b>oauth_consumer_key</b> that must be unique in this system.
To support LTI 1.1 launches you also also need to specify a <b>secret</b>.
If this tenant will be LTI 1.3 only, the secret can be left blank.
<p>
For LTI 1.3, you need to set choose the <b>issuer</b> and set the <b>deployment_id</b> or
these can be left blank and set by the LMS using the LTI 1.3 Auto Provisioning process.
The LMS auto provisioning URL for this key will be shown after you create and view the key.
See below for information about LTI 1.1 to LTI 1.3 migration.
</p>
<p>
<?php

CrudForm::insertForm($fields, $from_location, $titles);

?>
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

