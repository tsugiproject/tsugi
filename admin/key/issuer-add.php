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
    "created_at", "updated_at");

$titles = array(
    'issuer_key' => 'LTI 1.3 Issuer (from the Platform)',
    'issuer_client' => 'LTI 1.3 Client ID (from the Platform)',
    'lti13_keyset_url' => 'LTI 1.3 Platform OAuth2 Well-Known/KeySet URL (from the platform)',
    'lti13_token_url' => 'LTI 1.3 Platform OAuth2 Bearer Token Retrieval URL (from the platform)',
    'lti13_oidc_auth' => 'LTI 1.3 Platform OIDC Authentication URL (from the Platform)',
    'lti13_platform_pubkey' => 'LTI 1.3 Platform Public Key (Usually retrieved via keyset url)',

    'lti13_pubkey' => 'LTI 1.3 Tool Public Key (Leave blank to auto-generate)',
    'lti13_privkey' => 'LTI 1.3 Tool Private Key (Leave blank to auto-generate)',
    'lti13_tool_keyset_url' => 'LTI 1.3 Tool Keyset Url (Extension - may not be needed/used by LMS)',
);

if ( U::get($_POST,'issuer_key') ) {
    // $_POST['issuer_sha256'] = LTI13::extract_issuer_key_string(U::get($_POST,'issuer_key'));
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

?>
<h1>
<img src="<?= $CFG->staticroot ?>/img/logos/tsugi-logo-square.png" style="float:right; width:48px;">
Adding Issuer Entry</h1>
<p>
For LTI 1.3, you need to enter these URLs in your LMS configuration
associated with this Issuer/Client ID.
<pre>
LTI 1.3 OpenID Connect Endpoint: <?= $CFG->wwwroot ?>/lti/oidc_login
LTI 1.3 Tool Redirect Endpoint: <?= $CFG->wwwroot ?>/lti/oidc_launch
LTI 1.3 Tool Keyset URL (optional):
<?= $CFG->wwwroot ?>/lti/keyset     (contains all keys)
<?= $CFG->wwwroot ?>/lti/keyset?issuer=<b><span id="issuer_id_ui">&lt;issuer&gt;</span></b>
</pre>
</p>
<p>
If your platform needs a tool keyset url (an extension to LTI 1.3),
you can either use the keyset url to retrieve public keys for all integrations,
or just request the public key for this itegration.  The exact URL
for this ussuer will will be shown after you create and view the issuer.
</p>
<p>
<?php

CrudForm::insertForm($fields, $from_location, $titles);

?>
</p>
<?php

$OUTPUT->footer();

