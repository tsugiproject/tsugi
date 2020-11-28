<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Core\LTIX;

$openid_configuration = U::get($_REQUEST, 'openid_configuration');
$registration_token = U::get($_REQUEST, 'registration_token');

session_start();

$LTI = U::get($_SESSION, 'lti');

$display_name = U::get($LTI, 'displayname');
$user_id = U::get($LTI, 'user_id');

$OUTPUT->header();
$OUTPUT->bodyStart();

if ( ! $user_id ) {
?>
<p>You are not logged in.
</p>
<p>
<a href="<?= $CFG->apphome ?>" target="_blank"><?= $CFG->apphome ?></a>
</p>
<p>
Open this in a new tab, login, and come back to this tab and
re-check your login status.
</p>
<p>
<form>
<input type="hidden" name="openid_configuration" value="<?= htmlentities($openid_configuration) ?>">
<input type="hidden" name="registration_token" value="<?= htmlentities($registration_token) ?>">
<input type="submit" name="Re-Check Login Status" value="Re-Check Login Status">
</form>
<?php
    $OUTPUT->footer();
    return;
}

\Tsugi\Core\LTIX::getConnection();


echo("<pre>\n");

print_r($_GET);

$response = Net::doGet($openid_configuration );

echo($response);

print_r($_COOKIE);
print_r($_SESSION);

echo("\n</pre>\n");

