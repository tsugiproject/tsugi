<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Core\LTIX;

session_start();

\Tsugi\Core\LTIX::getConnection();

$openid_configuration = U::get($_GET, 'openid_configuration');
$registration_token = U::get($_GET, 'registration_token');

echo("<pre>\n");

print_r($_GET);

$response = Net::doGet($openid_configuration );

echo($response);

print_r($_COOKIE);
print_r($_SESSION);

echo("\n</pre>\n");

