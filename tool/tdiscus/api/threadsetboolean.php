<?php

require_once __DIR__ . '/../../config.php';

use Tsugi\Controllers\DiscussionsUi;
use Tsugi\Core\LTIX;
use Tsugi\Util\Net;
use Tsugi\Util\U;

$LAUNCH = LTIX::requireData();

$rest_path = U::rest_path();
$thread_id = $rest_path->action ?? null;
if ( ! isset($rest_path->parameters) || count($rest_path->parameters) != 2 ) {
    Net::send400(__('Missing required parameters'));
    return;
}

DiscussionsUi::bind(DiscussionsUi::toolUrls());
$err = DiscussionsUi::apiSetBoolean('thread', $thread_id, $rest_path->parameters[0], $rest_path->parameters[1]);
if ( is_string($err) ) {
    Net::send400($err);
}
