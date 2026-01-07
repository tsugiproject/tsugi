<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");
require_once("key-util.php");

use \Tsugi\Util\U;
use \Tsugi\UI\CrudForm;
use \Tsugi\Core\LTIX;

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
$from_location = LTIX::curPageUrlFolder();
$allow_delete = true;
$allow_edit = true;
$where_clause = '';
$query_fields = array();
$fields = array('key_id', 'key_title', 'key_key', 'secret', 'issuer_id', 'deploy_key',
     'unlock_code',
     'lms_issuer', 'lms_client', 'lms_oidc_auth', 'lms_keyset_url', 'lms_token_url', 'lms_token_audience',
     'xapi_url', 'xapi_user', 'xapi_password',
     'caliper_url', 'caliper_key',
     'created_at', 'updated_at', 'user_id',
);

$realfields = array('key_id', 'key_title', 'key_key', 'key_sha256', 'secret', 'deploy_key', 'deploy_sha256',
     'issuer_id',
     'unlock_code',
     'lms_issuer', 'lms_issuer_sha256', 'lms_client', 'lms_oidc_auth', 'lms_keyset_url', 'lms_token_url', 'lms_token_audience',
     'xapi_url', 'xapi_user', 'xapi_password',
     'caliper_url', 'caliper_key',
     'created_at', 'updated_at', 'user_id',
);

$titles = array(
    'key_key' => 'LTI 1.1: OAuth Consumer Key',
    'secret' => 'LTI 1.1: OAuth Consumer Secret',
    'deploy_key' => 'LTI 1.3: Deployment ID (from the Platform)',
    'issuer_id' => 'LTI 1.3: Issuer',
    'unlock_code' => 'LTI 1.3: Dynamic Registration Unlock Code (one time use)',
);

if ( isset($_POST['issuer_id']) && empty($_POST['issuer_id']) ) $_POST['issuer_id'] = null;
if ( isset($_POST['key_key']) && empty($_POST['key_key']) ) $_POST['key_key'] = null;
if ( isset($_POST['user_id']) && empty($_POST['user_id']) ) $_POST['user_id'] = null;

// Check the complex interaction of constraints
$key_id = U::get($_POST,'key_id');
$key_key = U::get($_POST,'key_key');
$deploy_key = U::get($_POST,'deploy_key');
$issuer_id = U::get($_POST,'issuer_id');

// Check the complex validation
if ( count($_POST) > 0 && U::get($_POST,'doUpdate') && !empty($key_id) ) {
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
    $lms_issuer = $row['lms_issuer'];

    $retval = validate_key_details($key_key, $deploy_key, $issuer_id, $lms_issuer, $old_key_key, $old_deploy_key, $old_issuer_id);

    if ( ! $retval ) {
        header("Location: ".$redir);
        return;
    }
}

// Handle the post data
/** @var array|int $row */
$row =  CrudForm::handleUpdate($tablename, $realfields, $where_clause,
    $query_fields, $allow_edit, $allow_delete);

if ( $row === CrudForm::CRUD_FAIL || $row === CrudForm::CRUD_SUCCESS ) {
    header('Location: '.$from_location);
    return;
}
if ( ! is_array($row) ) {
    $_SESSION['error'] = 'Unable to load key details';
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
if ( is_string($row['key_key']) && !empty($row['key_key']) && is_string($row['secret']) && !empty($row['secret']) ) {
    $key_type .= 'LTI 1.1';
}
if ( is_string($row['lms_issuer']) && !empty($row['lms_issuer']) && is_string($row['deploy_key']) && !empty($row['deploy_key'])) {
    if ( !empty($key_type) ) $key_type .= ' / ';
    $key_type .= 'LTI 1.3';
} else if ( isset($row['issuer_key']) && is_string($row['issuer_key']) && !empty($row['issuer_key']) && is_string($row['deploy_key']) && !empty($row['deploy_key'])) {
    if ( !empty($key_type) ) $key_type .= ' / ';
    $key_type .= 'LTI 1.3';
}
if ( $key_type == '' ) $key_type = 'Draft';

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

?>
<h1>Tenant Details
  <a class="btn btn-default" href="#" onclick="window.location.reload(); return false;">Refresh</a>
  <a class="btn btn-success" href="key-settings.php?key_id=<?= htmlentities($row['key_id']) ?>">View/Edit Key Settings</a>
  <a class="btn btn-default" href="<?= LTIX::curPageUrlFolder() ?>">Exit</a>
</h1>
<p>
<b>Key status: <?= $key_type ?></b>
</p>
<ul class="nav nav-tabs">
  <li class="active"><a href="#data" data-toggle="tab" aria-expanded="true">Key Data</a></li>
  <li class=""><a href="#info" data-toggle="tab" aria-expanded="true">About Keys</a></li>
  <li class=""><a href="#brightspace" data-toggle="tab" aria-expanded="true">Brightspace</a></li>
  <li class=""><a href="#sakai" data-toggle="tab" aria-expanded="true">Sakai</a></li>
  <li class=""><a href="#moodle" data-toggle="tab" aria-expanded="true">Moodle</a></li>
  <li class=""><a href="#blackboard" data-toggle="tab" aria-expanded="true">Blackboard</a></li>
  <li class=""><a href="#canvas" data-toggle="tab" aria-expanded="true">Canvas</a></li>
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
$dynamicConfigUrl = null;
if ( is_string(U::get($row, 'unlock_code', null)) ) {
    $dynamicConfigUrl = $CFG->wwwroot . "/admin/key/auto.php?tsugi_key=" . $row['key_id'] . "&unlock_code=" . $row['unlock_code'];
}
$contentItemUrl = $CFG->wwwroot . "/lti/store/";
$canvasContentItemUrl = $CFG->wwwroot . "/lti/store/canvas-config.xml";
$storeUrl = $CFG->wwwroot . "/store/";

function showDynamicConfig($url) {
    if ( is_string($url) ) {
?>
<p><b>LTI Advantage Dynamic Registration URL:</b>
<button href="#" onclick="copyToClipboardNoScroll(this, '<?= $url ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</button></b>
</p>
<p>
<?= htmlentities($url) ?>
</p>
<?php
    } else {
        echo("<p><b>NOTE: You must set the one-time use unlock_code in the key data to get a dynamic registration URL.</b></p>\n");
    }

}
?>
</pre>
</div>
<div class="tab-pane fade" id="info">
<h2>Tenant Key Options</h2>
<p>
A single entry in this table defines a "distinct tenant" in Tsugi.
Data in Tsugi data is isolated to a tenant.  You can route both
LTI 1.1 and LTI 1.3 launches to one tenant by setting fields on
this entry properly.   If a tenant key is in "Draft" status - launches
will not work.
</p>
<p>
For LTI 1.1, set the <b>oauth_consumer_key</b> and <b>secret</b>.
</p>
<p>
For LTI 1.3, you can either
(a) create a global issuer and select it here, and then set the <b>deployment_id</b>
<b>or</b> you can
(b) leave the global issuer unset and set all of the LMS values in this screen.  If you mix
a global issuer and per-key LTI 1.3 settings, you will most likely end up with a tenant that
won't be able to receive launches.
</p>
<p>
If this is a pre-existing LTI 1.1 tenant, the LMS must provide the <b>oauth_consumer_key</b>
and <b>secret</b> connected to its LTI 1.3 launches, and then Tsugi can link the accounts
and courses regardless of the type of launch.  For this to work, the LMS must support
LTI Advantage legacy LTI 1.1 support.
</p>
<p>
Sometimes you need to give the LMS the Tsugi URLs to make a new security arrangement
<b>before</b> they can give you the Platform values to put into either a global issuer
on this form.  We solve this "who goes first" problem in Tsugi by allowing you to create
a "draft" or incomplete key and then come back later to add the LMS / Platform provided data.
</p>
<b>LTI Advantage Dynamic Registration</b>
<p>
Sakai, Moodle, and Brightspace support the
<a href="https://www.imsglobal.org/spec/lti-dr/v1p0" target="_blank">LTI Dynamic Registration</a>
process.  This process takes
a draft Tsugi key, and sends its data to the LMS and the LMS responds with all of its values
and Tsugi automatically update the Tenant key with the LMS values.  It is basically a one-click
LTI Advantage install.
</p>
<p>
<?php showDynamicConfig($dynamicConfigUrl); ?>
<p>
The
<a href="https://www.imsglobal.org/spec/lti-dr/v1p0" target="_blank">LTI Dynamic Registration</a>
process is supported by Brightspace, Moodle, and Sakai.  You create a Tenant key in
Tsugi with a title, and issuer, then take the Dynamic Registration URL
from this page and paste it into the LMS configuration process.
</p>
<p>
Dynamic Registration sets up the security relationship between a tool and LMS.  Values like
the Issuer, ClientID, Keyset URL, etc. will be set up.  If the issuer data returned by the LMS
matches an existing issuer in this system, that issuer will be used for this key, otherwise
the new issuer data will be added to this key.
</p>
<p>
A secondary and optional part of Dynamic Registration is to communicate the <b>Deployment ID</b> for this tenant / key.
Sometimes this is included in the dynamic registration exchange (Sakai, Moodle, and Brightspace for example).
For other LMS's you may need to run the dynamic registration process, and then manually enter the Deployment Id
later.  LTI Advantage keys without a Deployment ID will not work in Tsugi.  Since Sakai and Moodle usually run in a "single-tenant"
model, they usually use a <b>deployment_id</b> of <b>1</b>.
</p>
<p>
</div>
<div class="tab-pane fade" id="brightspace">

<h2>LTI 1.3</h2>
<p>
Brightspace supports
<a href="https://www.imsglobal.org/spec/lti-dr/v1p0" target="_blank">LTI Dynamic Registration</a>.
</p>
<p>
In Brightspace, go to the settings (gear icon) drop down, and select
<b>External Learning Tools</b> -&gt; <b>LTI Advantage</b> -&gt; <b>New Deployment</b>.  Before
we can create the deployment, we need to register a tool which can be launched
from the help/question mark link by the "Tool" drop
down that brings up a pop up - then click on <b>Register</b> and it will pop up a new window.  Then
press <b>Register Tool</b>.
</p>
<p>
Select <b>Dynamic</b>, check the <b>Configure Deployment</b> checkbox and enter the Dynamic Configuration
URL and press <b>Register</b>.  Bright space will open another new tab which will come back to Tsugi.  Once Tsugi
and Brightspace exchange all their data, and update their respective configurations,
press <b>Continue Tool Registration in the LMS</b>.
</p>
<p>
Then in Brightspace make sure you have a Tool, Deployment, and then set up one or more <b>Links</b> to tell BrightSpace
where to show Tsugi in the Brightspace UI.  The <b>Deep Linking Quicklink</b> works pretty well for Tsugi but
others might be useful as well.   The URL to provide to use for Deep Linking Quicklink is the Tsugi store URL:
<p>
<b>Tsugi App Store URL:
<button href="#" onclick="copyToClipboardNoScroll(this, '<?= $contentItemUrl ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</button></b>
</p>
<p>
<?= htmlentities($contentItemUrl) ?>
</p>
<p>
<?php showDynamicConfig($dynamicConfigUrl); ?>
<h2>LTI 1.1</h2>
<p>
You can install Tsugi as a
<a href="http://www.imsglobal.org/specs/lticiv1p0/specification" target="_blank">Content-Item</a>
(i.e. a tool picker) by using the <b>consumer_key</b> and <b>consumer_secret</b> and the following
URL.
<p>
<b>Tsugi App Store URL:
<button href="#" onclick="copyToClipboardNoScroll(this, '<?= $contentItemUrl ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</button></b>
</p>
<p>
<?= htmlentities($contentItemUrl) ?>
</p>
<p>
You can also install direct LTI 1.1 launches to individual tools on this system.  You can
see any installed tools in the
<a href="<?= htmlentities($storeUrl) ?>" target="_blank">Tsugi App Store</a> on this system.
Each tool listing provides its direct launch endpoints.
</p>

</div>
<div class="tab-pane fade" id="sakai">

<h2>LTI 1.3</h2>
<p>
Sakai supports
<a href="https://www.imsglobal.org/spec/lti-dr/v1p0" target="_blank">LTI Dynamic Registration</a>.
</p>
<p>
In Sakai go into <b>Administration Workspace</b> -&gt; <b>External Tools</b> -&gt; <b>LTI Advantage Auto Provision</b>
</p>
<p>
Give the tool a title, and press <b>Auto Provision</b>.  Sakai will create a draft integration and give you the
option to <b>Use LTI Advantage Auto Configuration</b> - press that button and enter the Dynamic Configuration
URL.
<p>
<?php showDynamicConfig($dynamicConfigUrl); ?>
<p>
Sakai supports the optional part of Dynamic Registration and so it provides the <b>Deployment ID</b> for this tenant / key
in the Dynamic Registration process so once the process is done - the Tenant should be fully configured and ready to launch.
Sakai systems are not usually multi-tenant so they usually use a <b>deployment_id</b> of <b>1</b>.
</p>
<h2>LTI 1.1</h2>
<p>
You can install Tsugi as a
<a href="http://www.imsglobal.org/specs/lticiv1p0/specification" target="_blank">Content-Item</a>
(i.e. a tool picker) by using the <b>consumer_key</b> and <b>consumer_secret</b> and the following
URL.
<p>
<b>Tsugi App Store URL:
<button href="#" onclick="copyToClipboardNoScroll(this, '<?= $contentItemUrl ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</button></b>
</p>
<p>
<?= htmlentities($contentItemUrl) ?>
</p>
<p>
You can also install direct LTI 1.1 launches to individual tools on this system.  You can
see any installed tools in the
<a href="<?= htmlentities($storeUrl) ?>" target="_blank">Tsugi App Store</a> on this system.
Each tool listing provides its direct launch endpoints.
</p>

</div>
<div class="tab-pane fade" id="moodle">
<h2>LTI 1.3</h2>
<p>
Moodle supports
<a href="https://www.imsglobal.org/spec/lti-dr/v1p0" target="_blank">LTI Dynamic Registration</a>.
</p>
<p>
In Moodle go into <b>Site Administration</b> -&gt; <b>Plugins</b> -&gt; <b>Manage Tools</b> -&gt; <b>Add LTI Advantage</b>
</p>
<p>
Moodle will prompt you for the Dynamic Registration URL.
<p>
<?php showDynamicConfig($dynamicConfigUrl); ?>
<p>
Moodle supports the optional part of Dynamic Registration and so it provides the <b>Deployment ID</b> for this tenant / key
in the Dynamic Registration process so once the process is done - the Tenant should be fully configured and ready to launch.
Moodle systems are not usually multi-tenant so they usually use a <b>deployment_id</b> of <b>1</b>.
</p>
<h2>LTI 1.1</h2>
<p>
You can install Tsugi as a
<a href="http://www.imsglobal.org/specs/lticiv1p0/specification" target="_blank">Content-Item</a>
(i.e. a tool picker) by using the <b>consumer_key</b> and <b>consumer_secret</b> and the following
URL.
</p>
<b>Tsugi App Store URL:
<button href="#" onclick="copyToClipboardNoScroll(this, '<?= $contentItemUrl ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</button></b>
</p>
<p>
<?= htmlentities($contentItemUrl) ?>
</p>
<p>
You can also install direct LTI 1.1 launches to individual tools on this system.  You can
see any installed tools in the
<a href="<?= htmlentities($storeUrl) ?>" target="_blank">Tsugi App Store</a> on this system.
Each tool listing provides its direct launch endpoints.
</p>
</div>
<div class="tab-pane fade" id="manual">
<h2>LTI 1.3</h2>
<?php
$key_id = $row['key_id'];
$oidc_login = $CFG->wwwroot . '/lti/oidc_login/' . $key_id;
$oidc_redirect = $CFG->wwwroot . '/lti/oidc_launch';
$lti13_keyset = $CFG->wwwroot . '/lti/keyset';
$deep_link = $CFG->wwwroot . '/lti/store/';

?>
<p>
These URLs need to be provided to your LMS registration associated with this key.
You can create a draft key, then provide these values to the LMS.
Then the LMS can us the values to
complete its configuration process and provide you the needed
values (which may include a new issuer) so that you can finish configuring this key.
</p>
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
<h2>LTI 1.1</h2>
<p>
You can install Tsugi as a
<a href="http://www.imsglobal.org/specs/lticiv1p0/specification" target="_blank">Content-Item</a>
(i.e. a tool picker) by using the <b>consumer_key</b> and <b>consumer_secret</b> and the following
URL.
<p>
<b>Canvas LTI 1.1 Configuration URL:
<button href="#" onclick="copyToClipboardNoScroll(this, '<?= $canvasContentItemUrl ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</button></b>
</p>
<p>
<?= htmlentities($canvasContentItemUrl) ?>
</p>

</div>
<div class="tab-pane fade" id="canvas">
<h2>LTI 1.3</h2>
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
You can install Tsugi as a
<a href="http://www.imsglobal.org/specs/lticiv1p0/specification" target="_blank">Content-Item</a>
(i.e. a tool picker) by using the <b>consumer_key</b> and <b>consumer_secret</b> and the following
URL.
<p>
<b>Canvas LTI 1.1 Configuration URL:
<button href="#" onclick="copyToClipboardNoScroll(this, '<?= $canvasContentItemUrl ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</button></b>
</p>
<p>
<?= htmlentities($canvasContentItemUrl) ?>
</p>

</div>
<div class="tab-pane fade" id="blackboard">


<?php
require_once("blackboard-detail.php");
?>
<h2>LTI 1.1</h2>
<p>
You can install Tsugi as a
<a href="http://www.imsglobal.org/specs/lticiv1p0/specification" target="_blank">Content-Item</a>
(i.e. a tool picker) by using the <b>consumer_key</b> and <b>consumer_secret</b> and the following
URL.
<p>
<b>Tsugi App Store URL:
<button href="#" onclick="copyToClipboardNoScroll(this, '<?= $contentItemUrl ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</button></b>
</p>
<p>
<?= htmlentities($contentItemUrl) ?>
</p>
<p>
You can also install direct LTI 1.1 launches to individual tools on this system.  You can
see any installed tools in the
<a href="<?= htmlentities($storeUrl) ?>" target="_blank">Tsugi App Store</a> on this system.
Each tool listing provides its direct launch endpoints.
</p>

</div>
</div>
<?php
$OUTPUT->footerStart();

$sql = "SELECT issuer_id, issuer_key, issuer_client
    FROM {$CFG->dbprefix}lti_issuer";
$issuer_rows = $PDOX->allRowsDie($sql);


if ( $inedit && count($issuer_rows) > 0 ) {
    $select_text = "<select id=\"issuer_id_select\"><option value=\"\">No Global Issuer Selected</option>";
    foreach($issuer_rows as $issuer_row) {
        $selected = $row['issuer_id'] == $issuer_row['issuer_id'] ? ' selected ' : '';
        $select_text .= '<option value="'.$issuer_row['issuer_id'].'"'.$selected.'>'.htmlentities($issuer_row['issuer_key']. ' ('.$issuer_row['issuer_client'].')')."</option>";
    }
    $select_text .= "</select>";
    // echo(htmlentities($select_text));

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

    $(document).ready(function(){
        $('form').attr('autocomplete', 'off');
    });
    $('#lms_issuer').closest('div').before("<p  id=\"lms_note\">If you enter data into the the LTI 1.3 fields below, (a) set them all and (b) do not select a global issuer for this tenant. If you select a global issuer, the LTI 1.3 Platform fields below should not be set.</p>");
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
