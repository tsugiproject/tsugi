<?php

require_once "top.php";

$router = new Tsugi\Util\UrlRouter();

$router->route('/tsugi\/lessons\/([^\/]*)$/', function($id){
    $view = new \Tsugi\Views\Lessons();
    $view->render($id);
 });

$router->route('/~\/lessons$/', function(){
    $view = new \Tsugi\Views\Lessons();
    $view->render();
 });

$router->execute($_SERVER['REQUEST_URI']);


