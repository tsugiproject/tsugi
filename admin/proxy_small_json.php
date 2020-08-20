<?php

use \Tsugi\Util\U;
use \Tsugi\Util\PS;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../config.php";

session_start();
if ( ! isset($_SESSION["admin"]) ) {
    die('Must be admin');
}

$url = U::get($_GET, 'url');
if ( ! $url ) {
    die('Missing URL parameter');
}

$url = new PS($url);
if ( ! ( $url->startswith('https://') || $url->startswith('http://') ) ) {
    die('Urls must start with https');
}

// file_get_contents ( string $filename [, bool $use_include_path = FALSE [, resource $context [, int $offset = 0 [, int $maxlen ]]]] ) : string
// Limit the length to 10000 characters

$content = file_get_contents($url, FALSE, NULL, 0, 10000);
$json = json_decode($content);

if ( ! is_object($json) ) {
    die('JSON syntax error');
}

header('Content-Type: application/json');
echo(json_encode($json,  JSON_PRETTY_PRINT));


