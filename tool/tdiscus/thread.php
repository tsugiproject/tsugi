<?php

require_once "../config.php";

use Tsugi\Controllers\DiscussionsUi;
use Tsugi\Core\LTIX;
use Tsugi\Services\Discussions\DiscussionsService;
use Tsugi\Util\U;

$LAUNCH = LTIX::requireData();

$rest_path = U::rest_path();
$thread_id = 0;
if ( isset($rest_path->action) && is_numeric($rest_path->action) ) {
    $thread_id = intval($rest_path->action);
}

DiscussionsUi::bind(DiscussionsUi::toolUrls());
$to = DiscussionsUi::threadPage($thread_id, function () use ($LAUNCH) {
    if ( intval(DiscussionsService::linkSetting('grade', '0')) > 0 && isset($LAUNCH->result) ) {
        $LAUNCH->result->gradeSend(1.0, false);
    }
});
if ( is_string($to) ) {
    header('Location: '.addSession($to));
}
