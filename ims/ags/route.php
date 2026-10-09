<?php

// Cookieless LTI service. Do not define COOKIE_SESSION, and do not send
// this URL through tsugi.php. A browser session must not start.

require_once __DIR__ . '/../../config.php';

\Tsugi\Controllers\Ims\Ags::dispatch();
