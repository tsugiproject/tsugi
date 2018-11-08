<?php

use \Tsugi\Util\U;
use \Tsugi\Util\LTI13;

require_once "../config.php";

$id_token = U::get($_POST, 'id_token');
$state = U::get($_POST, 'state');

if ( ! $state || ! $id_token ) {
    die('Missing id_token and/or state');
}

$url_claim = "https://purl.imsglobal.org/spec/lti/claim/launch_url";

$jwt = LTI13::parse_jwt($id_token);

if ( ! $jwt ) {
    die("Unable to parse JWT");
}

if ( ! isset($jwt->body) ) {
    die("Missing body in JWT");
}

if ( ! isset($jwt->body->{$url_claim}) || ! is_string($jwt->body->{$url_claim})) {
    die("Missing or incorrect launch_url claim in body");
}

$launch_url = $jwt->body->{$url_claim};

if ( ! U::startsWith($launch_url, $CFG->wwwroot) ) {
    die("Launch_url must start with ".$CFG->wwwroot);
}

// Looks good - time to forward
?>
<form method="POST" id="oidc_forward" action="<?= htmlspecialchars($launch_url) ?>">
<input type="hidden" name="id_token" value="<?= htmlspecialchars($id_token) ?>">
<input type="hidden" name="state" value="<?= htmlspecialchars($state) ?>">
</form>
<script>
document.getElementById("oidc_forward").submit();
</script>
