<?php

use Tsugi\Controllers\Login;
use Tsugi\Controllers\Logout;
use Tsugi\Controllers\Map;
use Tsugi\Controllers\Profile;
use Tsugi\Core\LTIX;
use Tsugi\Lumen\Application;

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

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

$app = new Application($launch);

$app['tsugi']->output->buffer = false;

// Hook up the Koseu and Tsugi tools
$app->router->group([
    'namespace' => 'Tsugi\Controllers'
], function () use ($app) {
    Login::routes($app);
    Logout::routes($app);
    Profile::routes($app);
    Map::routes($app);
});

$app->run();
