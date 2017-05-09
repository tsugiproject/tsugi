<?php
use \Tsugi\Core\LTIX;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "config.php";
$LAUNCH = LTIX::session_start();

$OUTPUT->header();
