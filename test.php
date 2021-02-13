<?php
use \Tsugi\Core\LTIX;

require_once "config.php";

if ( ! \Tsugi\Util\U::isCLI() ) die('Can only be run from the command line');

$CFG = new \Tsugi\Config\ConfigInfo(basename(__FILE__),'http://localhost');


$LAUNCH = new \Tsugi\Core\LTIX();

$OUTPUT = new \Tsugi\UI\Output();

$router = new Tsugi\Util\FileRouter();

$set = new \Tsugi\UI\MenuSet();

$submenu = new \Tsugi\UI\Menu();

$cc_dom = new \Tsugi\Util\CC();

$client = new \Google_Client();
$link = new \Google_Service_Classroom_Link();
$materials = new \Google_Service_Classroom_Material();
$cw = new \Google_Service_Classroom_CourseWork();

echo("Test complete\n");

