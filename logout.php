<?php
define('COOKIE_SESSION', true);
require_once("config.php");
use \Tsugi\Crypt\SecureCookie;
session_start();
session_unset();
SecureCookie::delete();

header('Location: index.php');
