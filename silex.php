<?php
define('COOKIE_SESSION', true);
require_once "config.php";

use \Tsugi\Core\LTIX;

/*
session_start();
echo("<pre>\n");var_dump($_COOKIE);echo("\n<pre>\n");
echo("<pre>\n");var_dump($_SESSION);echo("\n<pre>\n");
echo(session_id());
die();
*/
$launch = LTIX::session_start();
$app = new \Tsugi\Silex\Application($launch);
$app['debug'] = true;

// echo("<pre>\n");var_dump($app);echo("\n<pre>\n");
// echo("<pre>\n");var_dump($launch);echo("\n<pre>\n");
// echo("<pre>\n");var_dump($_SESSION);echo("\n<pre>\n");
// echo(session_id());

$app->get('/dump', function() use ($app) {
    return $app['twig']->render('@Tsugi/Dump.twig');
});

$app->get('/map/json', 'Koseu\\Views\\Map::getjson');
$app->get('/map', 'Koseu\\Views\\Map::get');

$app->run();
