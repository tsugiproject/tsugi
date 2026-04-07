<?php

require_once('../config.php');
require_once('util/tdiscus.php');

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tdiscus\Tdiscus;

// The path to the folder that contains this file in URL space
// /py4e/mod/tdiscus

$TOOL_ROOT = dirname($_SERVER['SCRIPT_NAME']);

$tool = new \Tsugi\Core\Tool();
$tool->run();
