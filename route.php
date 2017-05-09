<?php

define('COOKIE_SESSION', true);
require_once('config.php');

$router = new Tsugi\Util\FIleRouter();

$file = $router->fileCheck();
if ( $file ) {
    require_once($file);
    return;
}

require_once "top.php";

$router->route('/lessons/{s}$', function($id){
    $view = new \Tsugi\Views\Lessons();
    $view->render($id);
 });

$router->route('/lessons$', function(){
    $view = new \Tsugi\Views\Lessons();
    $view->render();
 });


$router->execute($_SERVER['REQUEST_URI']);


