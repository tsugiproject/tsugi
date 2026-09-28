<?php

use Tsugi\Core\LTIX;

require_once __DIR__ . '/lib/include/tsugi_constants.php';

// Cookie site session, unless this request already carries a cookieless id.
// tool/reqscope/launch.php hands off with ?_LTI_TSUGI=. A ?PHPSESSID= query
// parameter is not that id: setup.php would rename the session and drop it.
if ( ! isset($_GET[TSUGI_COOKIELESS_SESSION_NAME]) ) {
    define('COOKIE_SESSION', true);
}
require_once __DIR__ . '/config.php';

$launch = LTIX::session_start();

// Make PHP paths pretty .../install => install.php
$router = new Tsugi\Util\FileRouter();
$file = $router->fileCheck();
if ( $file ) {
    require_once($file);
    return;
}

// Pull in the Tsugi LMS routes (/lessons, /discussions, /map, /badges, ...)
$app = new \Tsugi\Controllers\Tsugi($launch);

$app->run();
