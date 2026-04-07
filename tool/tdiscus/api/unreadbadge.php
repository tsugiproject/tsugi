<?php

use \Tsugi\Core\LTIX;
use \Tdiscus\Threads;

require_once "../util/threads.php";

$LTI = LTIX::requireData();
$THREADS = new Threads();

$counts = $THREADS->unreadBadgeCounts();
$response = array(
    "badge" => array(
        "personal" => intval($counts['personal']),
        "participating" => intval($counts['participating']),
        "global" => intval($counts['global']),
    ),
    "main_badge" => $THREADS->mainBadgeCount($counts),
    "config" => array(
        "include_participating_in_main_badge" => Threads::includeParticipatingInMainBadge() ? 1 : 0,
        "include_participation_as_personal" => Threads::includeParticipationAsPersonal() ? 1 : 0,
    ),
);

header('Content-Type: application/json; charset=utf-8');
echo(json_encode($response));

