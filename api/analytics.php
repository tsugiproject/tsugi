<?php

require_once "../config.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Core\Rest;
use \Tsugi\Services\Analytics\AnalyticsService;

if ( Rest::preFlight() ) return;

header('Content-Type: application/json; charset=utf-8');

// LTI/cookieless endpoint: requires an LTI launch and uses the current $LINK.
$LAUNCH = LTIX::requireData();
$link_id = $LINK->id + 0;
$retval = AnalyticsService::viewModelForLink($link_id);

echo(json_encode($retval,JSON_PRETTY_PRINT));
