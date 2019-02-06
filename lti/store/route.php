<?php

use \Tsugi\Core\LTIX;

require_once "../../config.php";
require_once $CFG->dirroot."/admin/admin_util.php";

$local_path = route_get_local_path(__DIR__);
if ( strpos($local_path, "canvas-config.xml") === 0 ) {
    require_once("canvas-config-xml.php");
    return;
}
if ( strpos($local_path, "canvas-config.json") === 0 ) {
    require_once("canvas-config-json.php");
    return;
}

// No parameter means we require CONTEXT, USER, and LINK
$LAUNCH = LTIX::requireData(LTIX::USER);

// Make PHP paths pretty .../install => install.php
$router = new Tsugi\Util\FileRouter();
$file = $router->fileCheck();
if ( $file ) {
    require_once($file);
    return;
}

// Add 404 Handling
http_response_code(404);
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav();
echo("<h2>Page not found.</h2>\n");
$OUTPUT->footer();
