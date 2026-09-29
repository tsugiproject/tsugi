<?php

require_once __DIR__ . '/../../config.php';

use Tsugi\Controllers\DiscussionsUi;
use Tsugi\Core\LTIX;
use Tsugi\Services\Discussions\DiscussionsService;
use Tsugi\Util\Net;

$LAUNCH = LTIX::requireData();

DiscussionsUi::bind(DiscussionsUi::toolUrls());
$err = DiscussionsUi::apiAddSubComment(function () use ($LAUNCH) {
    if ( intval(DiscussionsService::linkSetting('grade', '0')) > 0 && isset($LAUNCH->result) ) {
        $LAUNCH->result->gradeSend(1.0, false);
    }
});
if ( is_string($err) ) {
    Net::send400($err);
}
