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

$from_location = ".";
$tablename = "{$CFG->dbprefix}lti_external";
$fields = array("endpoint", "name", "url", "description", "fa_icon", "json");

$titles = array(
    'endpoint' => 'Launch endpoint on this system under /ext - must be letters, numbers and underscores and must be unique',
    'name' => 'Name of tool shown to user in the store',
    'fa_icon' => "An optional FontAwesome icon like 'fa-fast-forward'",
    'url' => 'URL Where the external tool receives launches',
    'json' => 'Additional settings for your tool registration (see below)'
);

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
<pre>
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
The <b>privacy_level</b> can be "anonymous", "name_only", or "public".
<p>
Use this Public Key for your tool:
<pre>
<?= $CFG->external_public_key ?>
</pre>
<?php

$OUTPUT->footer();

