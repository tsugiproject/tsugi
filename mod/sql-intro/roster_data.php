<?php

require_once "../../config.php";
require_once $CFG->dirroot."/pdo.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Util\LTI;
use \Tsugi\Util\Mersenne_Twister;

require_once "sql_util.php";

$LTI = LTIX::requireData();

// Compute the stuff for the output
$code = $USER->id+$LINK->id+$CONTEXT->id;

header('Content-Type: application/json; charset=utf-8');
$roster = makeRoster($code);
echo(jsonIndent(json_encode($roster)));
