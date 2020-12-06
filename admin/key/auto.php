<?php
// In the top frame, we use cookies for session.
if (!defined('COOKIE_SESSION')) define('COOKIE_SESSION', true);
require_once("../../config.php");

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Util\LTI13;
use \Tsugi\Core\LTIX;

$openid_configuration = U::get($_REQUEST, 'openid_configuration');
$registration_token = U::get($_REQUEST, 'registration_token');
$tsugi_key = U::get($_REQUEST, 'tsugi_key');

session_start();

$OUTPUT->header();
$OUTPUT->bodyStart();

if ( ! isset($_SESSION['id']) ) {
    $_SESSION['login_return'] = LTIX::curPageUrlFolder();
    header('Location: '.$CFG->wwwroot.'/login');
    return;
}

$user_id = $_SESSION['id'];

require_once("../../settings/key/auto_common.php");

