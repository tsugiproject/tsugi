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

$from_location = ".";
$tablename = "{$CFG->dbprefix}lti_external";
$fields = array("endpoint", "name", "url", "description", "fa_icon", "pubkey", "privkey", "json");

$titles = array(
    'endpoint' => 'Launch endpoint on this system under /ext - must be letters, numbers and underscores and must be unique',
    'name' => 'Short title of tool shown to user in the store',
    'fa_icon' => "An optional FontAwesome icon like 'fa-fast-forward'",
    'url' => 'URL Where the external tool receives launches',
    'pubkey' => 'External Tool Public Key (Leave blank to auto-generate)',
    'privkey' => 'External Tool Private Key (Leave blank to auto-generate)',
    'json' => 'Additional settings for your tool registration (see below)'
);

if ( U::get($_POST,'endpoint') ) {
    if ( strlen(U::get($_POST,'pubkey')) < 1 && strlen(U::get($_POST,'privkey')) < 1 ) {
        $success = LTI13::generatePKCS8Pair($publicKey, $privateKey);
        if ( is_string($success) ) {
            $_SESSION['error'] = "Could not create key pair:".$success;
            header("Location: ".U::addsession($from_location));
            return;
        }
        $_POST['pubkey'] = $publicKey;
        $_POST['privkey'] = $privateKey;
    }
}

$retval = CrudForm::handleInsert($tablename, $fields);
if ( $retval == CrudForm::CRUD_SUCCESS || $retval == CrudForm::CRUD_FAIL ) {
    header("Location: ".U::addsession($from_location));
    return;
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

?>
<h1>Adding External Tool</h1>
<p>
And external tool is registered in this system and launches are forwarded to the
tool after Tsugi validates the incoming launch.   Tools are provided an API callback
to access Tsugi APIs remotely.  
</p>
<p>
<?php

CrudForm::insertForm($fields, $from_location, $titles);

?>
</p>
<p>
Here is some sample JSON for the additional settings:
<pre id="sample_json">
{
    "messages": ["launch", "launch_grade"],
    "privacy_level": "name_only",
    "languages": [ "English" ],
    "license": "Apache",
    "source_url": "https://github.com/tsugiproject/djtest",
    "analytics": [ "internal" ],
    "tool_phase": "emerging"
}
</pre>
<p>
The <b>privacy_level</b> can be "anonymous", "name_only", or "public".
</p>
<?php

$OUTPUT->footerStart();
?>
<script>
textbox = $(document.createElement('textarea')).attr('id', 'json').attr('name', 'json').attr('cols', 80).attr('rows', 10);
$('#json').replaceWith(textbox);
$('#json').val($('#sample_json').text());
</script>
<?php
$OUTPUT->footerEnd();

