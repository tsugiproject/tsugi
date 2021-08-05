<?php
# Do not use cookie session here. We might be in an iframe and unable to establish
# a session - thanks trackers!.  Make sure the auto endpoint has the .php or
# route.php will do cookie session.

if ( isset($_COOKIE[session_name()]) ) {
   if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
}

require_once("../../config.php");
session_start();
require_once("../gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Util\LTI13;
use \Tsugi\Core\LTIX;

$openid_configuration = U::get($_REQUEST, 'openid_configuration');
$registration_token = U::get($_REQUEST, 'registration_token');
$tsugi_key = U::get($_REQUEST, 'tsugi_key');

$OUTPUT->header();
$OUTPUT->bodyStart();

// Superuser
$user_id = 0;

require_once("../../settings/key/auto_common.php");

