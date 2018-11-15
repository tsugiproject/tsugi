<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");
require_once("../../admin/admin_util.php");

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
$fields = array("key_key", "key_sha256", "secret", "lti13_keyset_url", "lti13_pubkey", "lti13_token_url", "lti13_privkey", "lti13_client_id", "lti13_oidc_auth", "created_at", "updated_at", "user_id");

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
<h1>Adding Key Entry</h1>
<p>
If you are using LTI 1.3, the best practice is to include a Key Set
URL <i>or</i> a Public Key but not both.  A single key can be used for LTI 1.1
and LTI 1.3 simultaneously by setting an LTI 1.1 key as well
as the LTI 1.3 values.   If you don't want to use LTI 1.1 for this key
just leave the secret blank or set it to a large randomly generated value.
</p>
<p>
The convention for lti13 tenant key (key_key) in this screen is:
<pre>
lti13_issuer
lti13_https://dev1.tsugicloud.com
</pre>
</p>
<p>
For LTI 1.3, you need to enter these URLs in your LMS configuration:
<pre>
LTI 1.3 OpenID Connect Endpoint: <?= $CFG->wwwroot ?>/tsugi/lti/oidc_login
LTI 1.3 Tool Redirect Endpoint: <?= $CFG->wwwroot ?>/tsugi/lti/oidc_launch
</pre>
</p>
<p>
<?php


CrudForm::insertForm($fields, $from_location);

echo("</p>\n");

$OUTPUT->footer();

