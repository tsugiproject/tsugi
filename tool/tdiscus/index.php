<?php

require_once "../config.php";

use Tsugi\Controllers\DiscussionsUi;
use Tsugi\Core\LTIX;

$LAUNCH = LTIX::requireData();

DiscussionsUi::bind(DiscussionsUi::toolUrls());
$to = DiscussionsUi::threadListPage();
if ( is_string($to) ) {
    header('Location: '.addSession($to));
}
