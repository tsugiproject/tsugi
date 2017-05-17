<?php
if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("config.php");
use \Tsugi\Crypt\SecureCookie;
session_start();
session_unset();
SecureCookie::delete();

header('Location: '.$CFG->apphome);
