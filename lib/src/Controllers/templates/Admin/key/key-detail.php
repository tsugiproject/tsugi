<?php
// In the top frame, we use cookies for session.

use \Tsugi\Util\LTI;
use \Tsugi\Util\U;
use \Tsugi\UI\CrudForm;
use \Tsugi\Core\LTIX;
use \Tsugi\Services\Admin\AdminService;

\Tsugi\Core\LTIX::getConnection();

header('Content-Type: text/html; charset=utf-8');

if ( ! \Tsugi\Services\Admin\AdminService::isAdmin() ) {
    die('Must be admin');
}

$inedit = U::get($_REQUEST,'edit');

$tablename = "{$CFG->dbprefix}lti_key";
$current = LTIX::curPageUrlNoQuery();
$from_location = LTIX::curPageUrlFolder();
$allow_delete = true;
$allow_edit = true;
$where_clause = '';
$query_fields = array();
$fields = array('key_id', 'key_title', 'user_id', 'plus', 'key_key', 'secret',
     'lms_issuer', 'lms_client',
     'deploy_key', 'unlock_code',
     'lms_oidc_auth', 'lms_keyset_url', 'lms_token_url', 'lms_token_audience',
     'created_at', 'updated_at',
);

$realfields = array('key_id', 'key_title', 'user_id', 'plus', 'key_key', 'key_sha256', 'secret',
     'lms_issuer', 'lms_issuer_sha256', 'lms_client',
     'deploy_key', 'deploy_sha256', 'unlock_code',
     'lms_oidc_auth', 'lms_keyset_url', 'lms_token_url', 'lms_token_audience',
     'created_at', 'updated_at',
);
$have_client_table = LTIX::ltiClientTableExists($CFG->dbprefix);
if ( $have_client_table ) {
    $realfields[] = 'lti_client_id';
}

$titles = array(
    'key_key' => 'LTI 1.1: OAuth Consumer Key',
    'secret' => 'LTI 1.1: OAuth Consumer Secret',
    'plus' => 'Tsugi Plus',
    'deploy_key' => 'LTI 1.3: Deployment ID (If you leave this blank, it will match any value from the LMS)',
    'lms_issuer' => 'LTI 1.3 Platform Issuer URL (If you leave this blank, it will match any issuer from the LMS)',
    'lms_client' => 'LTI 1.3 Platform Client ID - usually a GUID',
    'lms_oidc_auth' => 'LTI 1.3 Platform OIDC Login / Authorization Endpoint URL',
    'lms_keyset_url' => 'LTI 1.3 Platform KeySet URL',
    'lms_token_url' => 'LTI 1.3 Platform Token URL',
    'lms_token_audience' => 'LTI 1.3 Platform Audience (optional)',
    'unlock_code' => 'LTI 1.3: Dynamic Registration Unlock Code (one time use)',
);

if ( isset($_POST['doUpdate']) || isset($_POST['doDelete']) ) {
    if ( \Tsugi\Controllers\Tool::csrfRedirect($from_location) ) return;
}

if ( isset($_POST['key_key']) && empty($_POST['key_key']) ) $_POST['key_key'] = null;
if ( isset($_POST['user_id']) && empty($_POST['user_id']) ) $_POST['user_id'] = null;
$raw_deploy_key = array_key_exists('deploy_key', $_POST) ? $_POST['deploy_key'] : null;
foreach ( array('lms_issuer', 'lms_client', 'deploy_key') as $blank_field ) {
    if ( array_key_exists($blank_field, $_POST) ) {
        $_POST[$blank_field] = AdminService::normalize_deploy_key_input($_POST[$blank_field]);
    }
}
if ( isset($_POST['doUpdate']) ) {
    $_POST['plus'] = (isset($_POST['plus']) && (string) $_POST['plus'] === '1') ? '1' : '0';
}

// Check the complex interaction of constraints
$key_id = U::get($_POST,'key_id');
$key_key = U::get($_POST,'key_key');
$deploy_key = U::get($_POST,'deploy_key');

$old_keyset_url = null;
$saw_old_keyset = false;
$old_client_id = 0;

// Check the complex validation
if ( count($_POST) > 0 && U::get($_POST,'doUpdate') && !empty($key_id) ) {
    $row = $PDOX->rowDie( "SELECT * FROM {$CFG->dbprefix}lti_key
            WHERE key_id = :key_id",
        array(':key_id' => $key_id)
    );

    $redir = U::add_url_parm('key-detail', 'key_id', $key_id);
    $redir = U::add_url_parm($redir, 'edit', 'yes');
    if ( ! $row ) {
        U::flashError("Could not load the old row");
        header("Location: ".$redir);
        return;
    }

    $old_key_key = $row['key_key'];
    $old_deploy_key = $row['deploy_key'];
    $old_keyset_url = $row['lms_keyset_url'];
    $old_client_id = (int) U::get($row, 'lti_client_id');
    $saw_old_keyset = true;
    if ( $have_client_table && ! AdminService::applyPostedClientToKey() ) {
        header("Location: ".$redir);
        return;
    }
    $lms_issuer_val = array_key_exists('lms_issuer', $_POST) ? $_POST['lms_issuer'] : $row['lms_issuer'];
    $lms_client_val = array_key_exists('lms_client', $_POST) ? $_POST['lms_client'] : $row['lms_client'];
    $posted_secret = array_key_exists('secret', $_POST) ? $_POST['secret'] : $row['secret'];
    if ( $posted_secret === '' ) {
        $posted_secret = $row['secret'];
    }
    $posted_deploy = array_key_exists('deploy_key', $_POST) ? $raw_deploy_key : $deploy_key;
    $identity_message = LTI::ascii255First(array(
        'OAuth consumer key' => $key_key === '' ? null : $key_key,
        'Secret' => $posted_secret === '' ? null : $posted_secret,
        'Issuer' => $lms_issuer_val === '' ? null : $lms_issuer_val,
        'Client id' => $lms_client_val === '' ? null : $lms_client_val,
        'Deployment id' => $posted_deploy === '' ? null : $posted_deploy,
    ));
    if ( is_string($identity_message) ) {
        U::flashError($identity_message);
        header("Location: ".$redir);
        return;
    }

    $posted_client_id = array_key_exists('lti_client_id', $_POST) ? $_POST['lti_client_id'] : $old_client_id;
    $retval = AdminService::validate_key_details($key_key, $deploy_key, $lms_issuer_val, $old_key_key, $old_deploy_key, $lms_client_val, $key_id, $posted_client_id);

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
    if ( $row === CrudForm::CRUD_SUCCESS && isset($_POST['doUpdate']) && $saw_old_keyset
        && array_key_exists('lms_keyset_url', $_POST) ) {
        $posted_keyset = trim((string) (U::get($_POST, 'lms_keyset_url') ?? ''));
        $stored_keyset = trim((string) ($old_keyset_url ?? ''));
        $posted_client_id = array_key_exists('lti_client_id', $_POST) ? (int) $_POST['lti_client_id'] : 0;
        if ( $posted_keyset !== $stored_keyset || $posted_client_id !== $old_client_id ) {
            LTIX::clearKeysetCache($key_id);
        }
    }
    header('Location: '.$from_location);
    return;
}
if ( ! is_array($row) ) {
    U::flashError('Unable to load key details');
    header('Location: '.$from_location);
    return;
}
if ( $have_client_table ) {
    $client_link = $PDOX->rowDie(
        "SELECT lti_client_id FROM {$CFG->dbprefix}lti_key WHERE key_id = :id",
        array(':id' => $row['key_id'])
    );
    $row['lti_client_id'] = is_array($client_link) ? $client_link['lti_client_id'] : null;
}

$key_type = AdminService::keyTypeHtml($row);

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

?>
<h1>Tenant Details
  <a class="btn btn-default" href="#" onclick="window.location.reload(); return false;">Refresh</a>
  <a class="btn btn-success" href="key-settings.php?key_id=<?= htmlentities($row['key_id']) ?>">View/Edit Key Settings</a>
  <a class="btn btn-default" href="clients">All Clients</a>
  <a class="btn btn-default" href="<?= LTIX::curPageUrlFolder() ?>">All Keys</a>
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
$editing = U::get($_REQUEST, 'edit') == 'yes';
$client_html = AdminService::matchingClientNote($row);
$client_html .= AdminService::inboundClientControl((int) U::get($row, 'lti_client_id'), $editing);
$after_fields = array('secret' => $client_html);
if ( $editing ) {
    $after_fields['lms_token_audience'] = '<p id="create-client-from-key" style="display:none">'
        .'<button type="button" class="btn btn-primary" id="create-client-from-key-button">Create Client From This Data</button>'
        .'</p>'
        .'<p id="create-client-from-key-note" style="display:none"></p>';
}
$retval = CrudForm::updateForm($row, $fields, $current, $from_location, $allow_edit, $allow_delete, $extra_buttons, $titles, array('plus'), $after_fields);
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
</div>
<div class="tab-pane fade" id="info">
<h2>Tenant Key Options</h2>
<p>
A single entry in this table defines a "distinct tenant" in Tsugi.
Data in Tsugi is isolated to a tenant.  You can route both
LTI 1.1 and LTI 1.3 launches to one tenant by setting fields on
this entry properly.   If a tenant key is in "Draft" status - launches
will not work.
</p>
<p>
For LTI 1.1, set the <b>oauth_consumer_key</b> and <b>secret</b>.
</p>
<p>
For LTI 1.3, you can either
(a) create a global issuer and select it here, and then set the <b>deployment_id</b> if you want this tenant to match only that deployment,
<b>or</b> you can
(b) leave the global issuer unset and set all of the LMS values in this screen.  If you mix
a global issuer and per-key LTI 1.3 settings, you will most likely end up with a tenant that
won't be able to receive launches.
</p>
<p>
For the <b>LTI 1.3 Deployment ID</b> field on this key: leave blank to accept any value from the LMS
(Tsugi still uses the deployment id from the launch for services such as grading).
Enter a specific deployment id when you want this tenant to accept only that deployment.
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
and Tsugi automatically updates the Tenant key with the LMS values.  It is basically a one-click
LTI Advantage install.
Create a tenant key with at least a <b>title</b> and <b>issuer</b> (or start from a draft key), then use the Dynamic Registration URL below in the LMS.
</p>
<?php showDynamicConfig($dynamicConfigUrl); ?>
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
later if you want a specific deployment. You can also leave Deployment ID blank so the key accepts any value from the LMS.
Many single-tenant Sakai or Moodle installs still send a concrete deployment id in launches (for example <b>1</b>);
a blank Deployment ID field accepts that without storing it on the key row.
</p>
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
URL and press <b>Register</b>.  Brightspace will open another new tab which will come back to Tsugi.  Once Tsugi
and Brightspace exchange all their data, and update their respective configurations,
press <b>Continue Tool Registration in the LMS</b>.
</p>
<p>
Then in Brightspace make sure you have a Tool, Deployment, and then set up one or more <b>Links</b> to tell BrightSpace
where to show Tsugi in the Brightspace UI.  The <b>Deep Linking Quicklink</b> works pretty well for Tsugi but
others might be useful as well.   The URL to provide to use for Deep Linking Quicklink is the Tsugi store URL:
</p>
<p>
<b>Tsugi App Store URL:
<button href="#" onclick="copyToClipboardNoScroll(this, '<?= $contentItemUrl ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</button></b>
</p>
<p>
<?= htmlentities($contentItemUrl) ?>
</p>
<?php showDynamicConfig($dynamicConfigUrl); ?>
<?php require __DIR__ . '/plus-config.php'; ?>
<h2>LTI 1.1</h2>
<p>
You can install Tsugi as a
<a href="http://www.imsglobal.org/specs/lticiv1p0/specification" target="_blank">Content-Item</a>
(i.e. a tool picker) by using the <b>consumer_key</b> and <b>consumer_secret</b> and the following
URL.
</p>
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
In Sakai, paths vary by version; a typical path is <b>Administration Workspace</b> -&gt; <b>External Tools</b> -&gt; <b>LTI Advantage Auto Provision</b> (or similar LTI Advantage registration).
</p>
<p>
Give the tool a title, and press <b>Auto Provision</b>.  Sakai will create a draft integration and give you the
option to <b>Use LTI Advantage Auto Configuration</b> - press that button and enter the Dynamic Configuration
URL.
</p>
<?php showDynamicConfig($dynamicConfigUrl); ?>
<p>
Sakai supports the optional part of Dynamic Registration and so it provides the <b>Deployment ID</b> for this tenant / key
in the Dynamic Registration process so once the process is done - the Tenant should be fully configured and ready to launch.
You may leave Deployment ID blank on the Tsugi key so it accepts any value from the LMS; Sakai often sends <b>deployment_id</b> <b>1</b> in launches, which is fine when this field is blank.
</p>
<?php require __DIR__ . '/plus-config.php'; ?>
<h2>LTI 1.1</h2>
<p>
You can install Tsugi as a
<a href="http://www.imsglobal.org/specs/lticiv1p0/specification" target="_blank">Content-Item</a>
(i.e. a tool picker) by using the <b>consumer_key</b> and <b>consumer_secret</b> and the following
URL.
</p>
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
In Moodle, menus vary by version; look for external tool / LTI Advantage registration (for example <b>Site Administration</b> -&gt; <b>Plugins</b> -&gt; <b>Manage tools</b> and an option to register by URL or dynamic registration).
</p>
<p>
Moodle will prompt you for the Dynamic Registration URL.
</p>
<?php showDynamicConfig($dynamicConfigUrl); ?>
<p>
Moodle supports the optional part of Dynamic Registration and so it provides the <b>Deployment ID</b> for this tenant / key
in the Dynamic Registration process so once the process is done - the Tenant should be fully configured and ready to launch.
You may leave Deployment ID blank on the Tsugi key so it accepts any value from the LMS; Moodle may still send a concrete deployment id in launches.
</p>
<?php require __DIR__ . '/plus-config.php'; ?>
<h2>LTI 1.1</h2>
<p>
You can install Tsugi as a
<a href="http://www.imsglobal.org/specs/lticiv1p0/specification" target="_blank">Content-Item</a>
(i.e. a tool picker) by using the <b>consumer_key</b> and <b>consumer_secret</b> and the following
URL.
</p>
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
Then the LMS can use the values to
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
<?php require __DIR__ . '/plus-config.php'; ?>
<h2>LTI 1.1</h2>
<p>
You can install Tsugi as a
<a href="http://www.imsglobal.org/specs/lticiv1p0/specification" target="_blank">Content-Item</a>
(i.e. a tool picker) by using the <b>consumer_key</b> and <b>consumer_secret</b> and the following
URL.
</p>
<p>
<b>Canvas LTI 1.1 Configuration URL:
<button href="#" onclick="copyToClipboardNoScroll(this, '<?= $canvasContentItemUrl ?>');return false;"><i class="fa fa-clipboard" aria-hidden="true"></i>Copy</button></b>
</p>
<p>
<?= htmlentities($canvasContentItemUrl) ?>
</p>

</div>
<div class="tab-pane fade" id="canvas">
<?php require_once __DIR__ . '/canvas-detail.php'; ?>
</div>
<div class="tab-pane fade" id="blackboard">


<?php
require_once __DIR__ . '/blackboard-detail.php';
?>
<p>
Blackboard LTI 1.3 / Advantage setup depends on your Learn version and admin UI; use your Blackboard documentation for developer keys, placements, and deployments. The URLs on the <b>Manual Configuration</b> tab are what you register at the LMS.
</p>
<?php require __DIR__ . '/plus-config.php'; ?>
<h2>LTI 1.1</h2>
<p>
You can install Tsugi as a
<a href="http://www.imsglobal.org/specs/lticiv1p0/specification" target="_blank">Content-Item</a>
(i.e. a tool picker) by using the <b>consumer_key</b> and <b>consumer_secret</b> and the following
URL.
</p>
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
if ( $editing && $have_client_table ) {
?>
<div id="create-client-modal" class="modal fade" role="dialog" aria-modal="true" aria-labelledby="create-client-modal-title">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="create-client-modal-title">Create Client</h4>
      </div>
      <div class="modal-body" id="create-client-modal-body"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="create-client-confirm" style="display:none">Create Client</button>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var buttonWrap = document.getElementById('create-client-from-key');
    var openButton = document.getElementById('create-client-from-key-button');
    var note = document.getElementById('create-client-from-key-note');
    var confirmButton = document.getElementById('create-client-confirm');
    var body = document.getElementById('create-client-modal-body');
    var keyAdmin = <?= json_encode($CFG->wwwroot.'/admin/key/', JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    var names = ['key_title', 'user_id', 'lms_issuer', 'lms_client', 'lms_oidc_auth', 'lms_keyset_url', 'lms_token_url', 'lms_token_audience', 'unlock_code'];
    var checkSeq = 0;
    var checkedFields = null;
    function fieldValue(name) {
        var el = document.getElementById(name);
        return el ? el.value.trim() : '';
    }
    function valuesNow() {
        var values = {};
        names.forEach(function(name) { values[name] = fieldValue(name); });
        return values;
    }
    function ready() {
        return fieldValue('lms_issuer') !== '' && fieldValue('lms_client') !== ''
            && fieldValue('lms_oidc_auth') !== '' && fieldValue('lms_keyset_url') !== ''
            && fieldValue('lms_token_url') !== '';
    }
    function refreshButton() {
        if (!buttonWrap) return;
        var show = ready();
        buttonWrap.style.display = show ? '' : 'none';
    }
    window.tsugiRefreshCreateClient = refreshButton;
    names.forEach(function(name) {
        var el = document.getElementById(name);
        if (el) el.addEventListener('input', refreshButton);
    });
    refreshButton();
    function csrf() {
        var token = document.querySelector('input[name="CSRF_TOKEN"]');
        return token ? token.value : '';
    }
    function paramsFrom(values, create) {
        var data = new URLSearchParams();
        data.set('CSRF_TOKEN', csrf());
        names.forEach(function(name) { data.set(name, values[name] == null ? '' : values[name]); });
        if (create) data.set('doCreate', '1');
        return data;
    }
    function esc(text) {
        var div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }
    function render(data) {
        var html = '';
        if (data && data.fields) {
            html += '<dl>';
            Object.keys(data.fields).forEach(function(label) {
                html += '<dt>'+esc(label)+'</dt><dd>'+esc(data.fields[label] == null ? '' : data.fields[label])+'</dd>';
            });
            html += '</dl>';
        }
        if (!data || data.ok !== true) {
            html += '<p>'+esc(data && data.error ? data.error : 'Could not check this client.')+'</p>';
            if (data && data.existing_id) {
                html += '<p><a href="'+esc(keyAdmin+'client-detail?lti_client_id='+encodeURIComponent(data.existing_id))+'">Open the existing client</a></p>';
            }
        }
        if (body) body.innerHTML = html;
        if (confirmButton) confirmButton.style.display = data && data.ok === true ? '' : 'none';
    }
    function post(data) {
        return fetch(keyAdmin+'client-from-key', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
            body: data.toString(),
            credentials: 'same-origin'
        }).then(function(response) { return response.json(); });
    }
    function applyCreated(data, hideModal) {
        var select = document.getElementById('lti_client_id');
        if (select && data && data.id) {
            var found = false;
            for (var i = 0; i < select.options.length; i++) {
                if (select.options[i].value === String(data.id)) found = true;
            }
            if (!found) {
                var option = document.createElement('option');
                option.value = String(data.id);
                option.textContent = data.label || ('Client '+data.id);
                if (select.options.length === 1 && select.options[0].value === '') {
                    select.options[0].textContent = 'Select';
                }
                select.add(option);
            }
            select.value = String(data.id);
            if (window.tsugiSyncEmptyKeyPlatformFields) window.tsugiSyncEmptyKeyPlatformFields();
        }
        if (note) {
            note.style.display = '';
            note.textContent = 'You must save this key to complete the connection to the newly created client.';
        }
        if (hideModal && window.jQuery) window.jQuery('#create-client-modal').modal('hide');
    }
    if (openButton) openButton.addEventListener('click', function() {
        var seq = ++checkSeq;
        var values = valuesNow();
        checkedFields = null;
        if (confirmButton) confirmButton.style.display = 'none';
        if (body) body.innerHTML = '';
        if (window.jQuery) window.jQuery('#create-client-modal').modal('show');
        post(paramsFrom(values, false)).then(function(data) {
            if (seq !== checkSeq) return;
            if (data && data.ok === true) checkedFields = values;
            render(data);
        }).catch(function() {
            if (seq !== checkSeq) return;
            render({ok: false, error: 'Could not check this client.'});
        });
    });
    if (confirmButton) confirmButton.addEventListener('click', function() {
        if (!checkedFields) return;
        var seq = checkSeq;
        post(paramsFrom(checkedFields, true)).then(function(data) {
            if (data && data.ok === true && data.id) {
                applyCreated(data, seq === checkSeq);
                return;
            }
            if (seq !== checkSeq) return;
            render(data || {ok: false, error: 'Could not create client.'});
        }).catch(function() {
            if (seq !== checkSeq) return;
            render({ok: false, error: 'Could not create client.'});
        });
    });
});
</script>
<?php
}
$OUTPUT->footerStart();
$OUTPUT->footerEnd();
