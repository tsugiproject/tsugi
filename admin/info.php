<?php

use \Tsugi\Util\U;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../config.php");
session_start();
require_once("gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

ob_start();
phpinfo();
$output = ob_get_clean();
$btn = '<p style="margin:1em;overflow:auto;"><a href="index.php" style="float:right;display:inline-block;padding:.5em 1em;background:#337ab7;color:#fff;text-decoration:none;border-radius:4px;font-family:sans-serif;font-size:14px;">← Back to Admin</a></p>';
echo str_replace('<body>', '<body>' . $btn, $output);
