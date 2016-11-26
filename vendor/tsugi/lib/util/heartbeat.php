<?php

if ( isset($_GET[session_name()]) ) {
    $cookie = false;
} else {
    define('COOKIE_SESSION', true);
    $cookie = true;
}

require_once "../../../../config.php";

\Tsugi\UI\Output::handleHeartBeat($cookie);
