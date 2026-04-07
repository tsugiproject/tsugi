<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\Settings;
use \Tdiscus\Tdiscus;
use \Tdiscus\Threads;

require_once "../util/threads.php";
require_once "../util/tdiscus.php";

// No parameter means we require CONTEXT, USER, and LINK
$LTI = LTIX::requireData();

$THREADS = new Threads();

$thread_id = U::get($_POST, 'thread_id');
$comment_id = U::get($_POST, 'comment_id');
$comment = U::get($_POST, 'comment');

$retval = $THREADS->commentAddSubComment($thread_id, $comment_id, $comment);
if ( is_string($retval) ) {
    Net::send400($retval);
    return;
}

if ( Settings::linkGet('grade') > 0 ) {
    $LTI->result->gradeSend(1.0, false);
}

$comment = $THREADS->commentLoad($retval);

Tdiscus::renderComment($LTI, intval($thread_id), $comment);

