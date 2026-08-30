<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Core\LTIX;
use \Tdiscus\Threads;

require_once "../util/threads.php";

// No parameter means we require CONTEXT, USER, and LINK
$LTI = LTIX::requireData();

$THREADS = new Threads();

if ( ! \Tsugi\Controllers\Tool::csrfOk() ) {
    Net::send400('Missing or invalid CSRF token');
    return;
}

$rest_path = U::rest_path();
$thread_id = $rest_path->action;
if ( count($rest_path->parameters) != 2 ) {
    Net::send400(__('Missing required parameters'));
    return;
}

$column = $rest_path->parameters[0];
$value = $rest_path->parameters[1];

// error_log("threadUserSetBoolean $thread_id $column $value");

$retval = $THREADS->threadUserSetBoolean($thread_id, $column, $value);
if ( is_string($retval) ) {
    Net::send400($retval);
    return;
}
