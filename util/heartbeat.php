<?php

if ( ! isset($CFG) ) return; // Only from within tsugi.php

$cookie = defined('COOKIE_SESSION');

$retval = \Tsugi\UI\Output::handleHeartBeat($cookie);

echo(\Tsugi\Util\LTI::jsonIndent(json_encode($retval)));
