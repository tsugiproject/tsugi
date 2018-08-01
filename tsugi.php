<?php

use \Tsugi\Core\LTIX;

define('COOKIE_SESSION', true);
require_once('config.php');

// Make PHP paths pretty .../install => install.php
$router = new Tsugi\Util\FileRouter();
$file = $router->fileCheck();
if ( $file ) {
    require_once($file);
    return;
}

$launch = LTIX::session_start();

$app = new \Tsugi\Silex\Application($launch);
$app['tsugi']->output->buffer = false;

// Hook up the Koseu and Tsugi tools
\Tsugi\Controllers\Login::routes($app);
\Tsugi\Controllers\Logout::routes($app);
if ( isset($launch->user->id) ) {
    \Tsugi\Controllers\Profile::routes($app);
    \Tsugi\Controllers\Map::routes($app);
}

$app->run();
