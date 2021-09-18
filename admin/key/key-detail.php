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

$inedit = U::get($_REQUEST,'edit');

$tablename = "{$CFG->dbprefix}lti_key";
$current = $CFG->getCurrentFileUrl(__FILE__);
$from_location = "keys";
$allow_delete = true;
$allow_edit = true;
$where_clause = '';
$query_fields = array();
$fields = array('key_id', 'key_title', 'key_key', 'secret', 'deploy_key', 'issuer_id',
     'xapi_url', 'xapi_user', 'xapi_password',
     'caliper_url', 'caliper_key', 'created_at', 'updated_at', 'user_id');
$realfields = array('key_id', 'key_title', 'key_key', 'key_sha256', 'secret', 'deploy_key', 'deploy_sha256', 'issuer_id',
     'xapi_url', 'xapi_user', 'xapi_password',
     'lms_issuer', 'lms_client', 'lms_oidc_auth', 'lms_keyset_url', 'lms_token_url', 'lms_token_audience',
     'caliper_url', 'caliper_key', 'created_at', 'updated_at', 'user_id');

$titles = array(
    'key_key' => 'LTI 1.1: OAuth Consumer Key',
    'secret' => 'LTI 1.1: OAuth Consumer Secret',
    'deploy_key' => 'LTI 1.3: Deployment ID (from the Platform)',
    'issuer_id' => 'LTI 1.3: Issuer',
);

if ( isset($_POST['issuer_id']) && strlen($_POST['issuer_id']) == 0 ) $_POST['issuer_id'] = null;
if ( isset($_POST['key_key']) && strlen($_POST['key_key']) == 0 ) $_POST['key_key'] = null;
if ( isset($_POST['user_id']) && strlen($_POST['user_id']) == 0 ) $_POST['user_id'] = null;

// Check the complex interaction of constraints
$key_id = U::get($_POST,'key_id');
$key_key = U::get($_POST,'key_key');
$deploy_key = U::get($_POST,'deploy_key');
$issuer_id = U::get($_POST,'issuer_id');

// Check the complex validation
if ( count($_POST) > 0 && U::get($_POST,'doUpdate') && strlen($key_id) > 0 ) {
    $row = $PDOX->rowDie( "SELECT * FROM {$CFG->dbprefix}lti_key
            WHERE key_id = :key_id",
        array(':key_id' => $key_id)
    );

    $redir = U::add_url_parm('key-detail', 'key_id', $key_id);
    $redir = U::add_url_parm($redir, 'edit', 'yes');
    if ( ! $row ) {
        $_SESSION['error'] = "Could not load the old row";
        header("Location: ".$redir);
        return;
    }

    $old_key_key = $row['key_key'];
    $old_deploy_key = $row['deploy_key'];
    $old_issuer_id = $row['issuer_id'];

    $retval = validate_key_details($key_key, $deploy_key, $issuer_id, $old_key_key, $old_deploy_key, $old_issuer_id);

    if ( ! $retval ) {
        header("Location: ".$redir);
        return;
    }
}

// Handle the post data
$row =  CrudForm::handleUpdate($tablename, $realfields, $where_clause,
    $query_fields, $allow_edit, $allow_delete, $titles);

if ( $row === CrudForm::CRUD_FAIL || $row === CrudForm::CRUD_SUCCESS ) {
    header('Location: '.$from_location);
    return;
}

if ( ! $inedit && U::get($row, 'issuer_id') > 0 ) {
    $issuer_row = $PDOX->rowDie("SELECT issuer_key, issuer_client FROM {$CFG->dbprefix}lti_issuer WHERE issuer_id = :issuer_id",
        array(':issuer_id' => U::get($row, 'issuer_id'))
    );
    if ( $issuer_row ) {
        $row['issuer_id'] = $issuer_row['issuer_key'].' ('.$issuer_row['issuer_client'].')';
    }
}

$key_type = '';
if ( is_string($row['key_key']) && strlen($row['key_key']) > 1 && is_string($row['secret']) && strlen($row['secret']) > 0 ) {
    $key_type .= 'LTI 1.1';
}
if ( isset($row['issuer_key']) && is_string($row['issuer_key']) && strlen($row['issuer_key']) > 1 && is_string($row['deploy_key']) && strlen($row['deploy_key']) > 0) {
    if ( strlen($key_type) > 0 ) $key_type .= ' / ';
    $key_type .= 'LTI 1.3';
}
if ( $key_type == '' ) $key_type = 'Draft';

// Move the lms_values to the auxiliary row
$aux_row = array();
foreach($row as $k => $v) {
  if ( strpos($k, "lms_") !== 0 ) continue;
  $aux_row[$k] = $v;
  unset($row[$k]);
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

?>
<h1>Tennant Details
  <a class="btn btn-default" href="#" onclick="window.location.reload(); return false;">Refresh</a>
  <a class="btn btn-default" href="keys">Exit</a>
</h1>
<p>
<b>Key status: <?= $key_type ?></b>
</p>
<ul class="nav nav-tabs">
  <li class="active"><a href="#data" data-toggle="tab" aria-expanded="true">Key Data</a></li>
  <li class=""><a href="#info" data-toggle="tab" aria-expanded="true">About Keys</a></li>
  <li class=""><a href="#auto" data-toggle="tab" aria-expanded="true">Dynamic Configuration</a></li>
  <li class=""><a href="#manual" data-toggle="tab" aria-expanded="false">Manual Configuration</a></li>
</ul>
<div id="myTabContent" class="tab-content" style="margin-top:10px;">
<div class="tab-pane fade active in" id="data">
<?php
$extra_buttons=false;
$row['lti13_tool_keyset_url'] = $CFG->wwwroot . '/lti/keyset';
$from_location = null;
$retval = CrudForm::updateForm($row, $fields, $current, $from_location, $allow_edit, $allow_delete,$extra_buttons,$titles);
if ( is_string($retval) ) die($retval);
echo("</p>\n");
$dynamicConfigUrl = U::addSession($CFG->wwwroot . "/admin/key/auto.php?tsugi_key=" . $row['key_id'], true);

$global_issuer = $row['issuer_id'] > 0 ;
if ( count($aux_row) > 0 && ! $global_issuer ) {
    echo("<h2>Key-Local Issuer Data</h2>\n");
    foreach($aux_row as $k => $v ) {
        if ( strlen($v) < 1 ) continue;
        echo("<p><b>".htmlentities($k)."</b>: ".htmlentities($v)."</p>\n");
    }
}
?>
</pre>
</div>
<div class="tab-pane fade" id="info">
<p>
A single entry in this table defines a "distinct tenant" in Tsugi.
Data in Tsugi data is isolated to a tenant.  You can route both
LTI 1.1 and LTI 1.3 launches to one tenant by setting fields on
this entry properly.  See below for details.
</p>
<p>
For LTI 1.1, set the <b>oauth_consumer_key</b> and <b>secret</b>.
For LTI 1.3, you first need to create an issuer/client_id, select it and then enter
the <b>deployment_id</b> for this integration from the LMS to define the tenant.
</p>
<p>
To receive both LTI 1.1 and LTI 1.3 launches to this "tenant", simply set all four fields.
</p>
<p>
If this is a pre-existing LTI 1.1 tenant, the LMS must have the <b>oauth_consumer_key</b>
and <b>secret</b> connected to its LTI 1.3 launches, and then Tsugi can link the accounts
and courses regardless of the type of launch.  For this to work, the LMS must support
LTI Advantage legacy LTI 1.1 support.
</p>
</div>
  <div class="tab-pane fade" id="auto">
<p>
<b>LTI Advantage Dynamic Configuration URL:
<button href="#" onclick="copyToClipboardNoScroll(this, '<?= $dynamicConfigUrl ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</button></b>
</p>
<p>
<?= htmlentities($dynamicConfigUrl) ?>
</p>
<p>
To use the Dynamic Configuration URL in your Learning Management System,
keep this window open in a separate tab while using the LMS in another tab
as the Tsugi Dynamic Configuration process requires that you stay logged in to this system
in order to ensure you have permission to perform this confguration exchange.
</p>
<p>
Dynamic Configuration sets up the security relationship between a tool and LMS.  Values like
the Issuer, ClientID, Keyset URL, etc. will be set up.  If the issuer data returned by the LMS
matches an existing issuer in this system, that issuer will be used for this key, otherwise
the new issuer data will be added to this key.
</p>
<p>
A secondary and optional part of Dynamic Configuration is to communicate the <b>Deployment ID</b> for this tenant / key.
Sometimes this is included in the dynamic configuration exchange (Sakai, Moodle, and Brightspace for example).
For other LMS's you may need to run the dynamic configuration process, and then manually enter the Deployment Id
later.  LTI Advantage keys without a Deployment ID will not work in Tsugi.
</p>
<p>
<b>Important:</b>
Once the LMS has finished its configuration in the other tab, come back to this tab or window, press "Refresh"
and check to verify that the key has been set up properly.
</p>
</div>
<div class="tab-pane fade" id="manual">
<?php
$key_id = $row['key_id'];
$oidc_login = $CFG->wwwroot . '/lti/oidc_login/' . $key_id;
$oidc_redirect = $CFG->wwwroot . '/lti/oidc_launch';
$lti13_keyset = $CFG->wwwroot . '/lti/keyset';
$deep_link = $CFG->wwwroot . '/lti/store/';

?>
<p>
These URLs need to be provided to your LMS configuration associated with this key.
You can create a draft key, then provide these values to the LMS.</p>
<p>
Then the LMS can us the values to
complete its configuration process and provide you the needed
values (which may include a new issuer) so that you can finish configuring this key.
</p>
<p>
LTI 1.3 OpenID Connect Endpoint: <a href="#" onclick="copyToClipboardNoScroll(this, '<?= $oidc_login ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</a><br/>
<?= $oidc_login ?>
</p>
<p>
LTI 1.3 Tool Redirect Endpoint: <a href="#" onclick="copyToClipboardNoScroll(this, '<?= $oidc_redirect ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</a><br/>
<?= $oidc_redirect ?>
</p>
<p>
LTI 1.3 Tool Keyset URL: <a href="#" onclick="copyToClipboardNoScroll(this, '<?= $lti13_keyset ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</a><br/>
<?= $lti13_keyset ?>
</p>
<p>
LTI Content Item / Deep Link Endpoint: <a href="#" onclick="copyToClipboardNoScroll(this, '<?= $deep_link ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</a><br/>
<?= $deep_link ?>
</p>

</div>
<?php
$OUTPUT->footerStart();

if ( $inedit ) {
    $sql = "SELECT issuer_id, issuer_key, issuer_client
        FROM {$CFG->dbprefix}lti_issuer";
    $issuer_rows = $PDOX->allRowsDie($sql);

    $select_text = "<select id=\"issuer_id_select\"><option value=\"\">No Global Issuer Selected</option>";
    foreach($issuer_rows as $issuer_row) {
        $selected = $row['issuer_id'] == $issuer_row['issuer_id'] ? ' selected ' : '';
        $select_text .= '<option value="'.$issuer_row['issuer_id'].'"'.$selected.'>'.htmlentities($issuer_row['issuer_key']. ' ('.$issuer_row['issuer_client'].')')."</option>";
    }
    $select_text .= "</select>";
    // echo(htmlentities($select_text));

?>
<script>
    $('<?= $select_text ?>').insertBefore('#issuer_id');
    $('#issuer_id').hide();
    $('#issuer_id_select').on('change', function() {
    $('input[name="issuer_id"]').val(this.value);
    });
</script>
<?php
}

$OUTPUT->footerEnd();
