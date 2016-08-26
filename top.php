<?php
use \Tsugi\Core\LTIX;

define('COOKIE_SESSION', true);
require_once "config.php";
$LAUNCH = LTIX::session_start();

$OUTPUT->header();
