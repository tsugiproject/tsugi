<?php

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once("../config.php");
session_start();
require_once("gate.php");
if ( $REDIRECTED === true || ! isset($_SESSION["admin"]) ) return;

use \Tsugi\Util\U;

// https://stackoverflow.com/questions/7160899/websocket-client-in-php/16608429#16608429
// https://www.cubewebsites.com/blog/development/php/how-to-catch-php-errors-and-warnings/

function errorHandler($errno,$errmsg,$errfile) {        
    // echo("Error $errno $errmsg\r\n");
    throw new \Exception("$errno $errmsg");
}

echo("<pre>\n");

if ( ! isset($CFG->websocket_url) ) {
    echo('$CFG->websocket_url is not set'."\r\n");
    return;
}

echo("Checking $CFG->websocket_url  \r\n");
$pieces = parse_url($CFG->websocket_url);
// print_r($pieces);
$host = U::get($pieces,'host');
$port = U::get($pieces,'port');
if ( !$host || !$port ) {
    echo("Missing port and/or host from URL\r\n");
    return;
}
echo("Connecting to $host on $port\r\n");
$local = "http://localhost:8888";  //url where this script run
$data = 'Yada';
$token = 'xyzzy';

$head = "GET /notify?token=$token HTTP/1.1"."\r\n".
        "Upgrade: WebSocket"."\r\n".
        "Connection: Upgrade"."\r\n".
        "Origin: $local"."\r\n".
        "Host: $host:$port"."\r\n".
        "Sec-WebSocket-Extensions: permessage-deflate; client_max_window_bits"."\r\n".
        "Sec-WebSocket-Key: PPFJBM3NioHgtAfKzvN/dA=="."\r\n".
        "Sec-WebSocket-Version: 13"."\r\n".
        "Content-Length: ".strlen($data)."\r\n"."\r\n";
echo("\r\nSending:\r\n");
echo($head);

//WebSocket handshake
set_error_handler("errorHandler");
try {
    $sock = fsockopen($host, $port, $errno, $errstr, 2);
} catch(\Exception $e) {
    echo("Socket could not be opened: ".$e->getMessage()."\r\n");
    exit();
}

if ( $sock === false ) {
    echo("Socket could not be opened!\r\n");
    exit();
}
fwrite($sock, $head ) or die('error:'.$errno.':'.$errstr);
$headers = fread($sock, 2000);
echo("Received:\r\n");
echo $headers;
echo("Socket test success\r\n");
fclose($sock);
