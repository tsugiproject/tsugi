<?php

require_once __DIR__ . '../lib/include/tsugi_constants.php';

if ( isset($_GET[session_name()]) || isset($_GET[TSUGI_COOKIELESS_SESSION_NAME]) ) {
    $cookie = false;
} else {
    define('COOKIE_SESSION', true);
    $cookie = true;
}
require_once('../config.php');

// Make PHP paths pretty .../install => install.php
$router = new Tsugi\Util\FileRouter();
$file = $router->fileCheck();
if ( $file ) {
    require_once($file);
    return;
}

// Add 404 Handling
http_response_code(404);
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
echo("<h2>Page not found.</h2>\n");
$OUTPUT->footer();
