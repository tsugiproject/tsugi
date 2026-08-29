<?php

use Tsugi\Core\LTIX;

define('COOKIE_SESSION', true);
require_once('config.php');

$launch = LTIX::session_start();

// Make PHP paths pretty .../install => install.php
$router = new Tsugi\Util\FileRouter();
$file = $router->fileCheck();
if ( $file ) {
    require_once($file);
    return;
}

// Pull in the Tsugi LMS routes (/lessons, /discussions, /map, /badges, ...)
$app = new \Tsugi\Controllers\Tsugi($launch);

$app->run();
