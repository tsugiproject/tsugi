<?php
use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Util\LTI13;
use \Tsugi\Core\LTIX;

require_once("../../config.php");

$openid_configuration = U::get($_REQUEST, 'openid_configuration');
$registration_token = U::get($_REQUEST, 'registration_token');
$tsugi_key = U::get($_REQUEST, 'tsugi_key');
$unlock_code = U::get($_REQUEST, 'unlock_code');

$OUTPUT->header();
$OUTPUT->bodyStart();

// Superuser
$user_id = 0;

require_once("../../settings/key/auto_common.php");

