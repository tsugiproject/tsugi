<?php

require_once "../config.php";

use Tsugi\Controllers\DiscussionsUi;
use Tsugi\Core\LTIX;
use Tsugi\Util\U;

$LAUNCH = LTIX::requireData();

$rest_path = U::rest_path();
$comment_id = 0;
if ( isset($rest_path->action) && is_numeric($rest_path->action) ) {
    $comment_id = intval($rest_path->action);
}

DiscussionsUi::bind(DiscussionsUi::toolUrls());
$to = DiscussionsUi::commentRemovePage($comment_id);
if ( is_string($to) ) {
    header('Location: '.addSession($to));
}
