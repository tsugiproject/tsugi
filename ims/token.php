<?php

// Cookieless token endpoint. Do not define COOKIE_SESSION, and do not send
// this URL through tsugi.php. OpenID configuration advertises /lti/token.
// lti/token.php is the same controller. This file is /ims/token.

require_once __DIR__ . '/../config.php';

\Tsugi\Controllers\Ims\Token::dispatch();
