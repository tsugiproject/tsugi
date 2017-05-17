<?php

define('COOKIE_SESSION', true);
require_once('config.php');

// Make PHP paths pretty .../install => install.php
$router = new Tsugi\Util\FileRouter();
$file = $router->fileCheck();
if ( $file ) {
    require_once($file);
    return;
}

$router = new Tsugi\Util\UrlRouter();

$router->route('/lessons/{s}$', function($id){
    require_once "top.php";
    $view = new \Tsugi\Views\Lessons();
    $view->render($id);
 });

$router->route('/lessons$', function(){
    require_once "top.php";
    $view = new \Tsugi\Views\Lessons();
    $view->render();
 });

$router->execute($_SERVER['REQUEST_URI']);

// Add 404 Handling
http_response_code(404);
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
echo("<h2>Page not found.</h2>\n");
$OUTPUT->footer();
