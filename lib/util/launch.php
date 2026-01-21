<?php

require_once "../../../../config.php";
require_once $CFG->vendorinclude . "/lms_lib.php";

use \Tsugi\Core\LTIX;

if ( isset($_GET['endpoint']) && isset($_GET['debug']) ) {
    // All good
} else {
    die('endpoint and debug are required');
}

$endpoint = $_GET['endpoint'];
$debug = $_GET['debug'] == 0;

// Grab the session
$LAUNCH = LTIX::requireData();

$debug = true;

$content = LTIX::getLaunchContent($endpoint, $debug);

echo($content);

