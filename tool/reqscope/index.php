<?php

require_once __DIR__ . '/../config.php';

use Tsugi\Core\LTIX;
use Tsugi\Controllers\ReqScopeDebug;

$LAUNCH = LTIX::requireData();
ReqScopeDebug::render($LAUNCH);
