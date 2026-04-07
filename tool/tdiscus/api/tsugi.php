
<?php

require_once('../../config.php');

$TOOL_ROOT = dirname(dirname($_SERVER['SCRIPT_NAME']));


$tool = new \Tsugi\Core\Tool();
$tool->run();
