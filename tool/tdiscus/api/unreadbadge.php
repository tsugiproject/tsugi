<?php

require_once __DIR__ . '/../../config.php';

use Tsugi\Controllers\DiscussionsUi;
use Tsugi\Core\LTIX;
use Tsugi\Services\Discussions\DiscussionsService;

$LAUNCH = LTIX::requireData();

$counts = DiscussionsService::unreadBadgeCounts();
$response = array(
    "badge" => array(
        "personal" => intval($counts['personal']),
        "participating" => intval($counts['participating']),
        "global" => intval($counts['global']),
    ),
    "main_badge" => DiscussionsService::mainBadgeCount($counts),
    "config" => array(
        "include_participating_in_main_badge" => DiscussionsService::includeParticipatingInMainBadge() ? 1 : 0,
        "include_participation_as_personal" => DiscussionsService::includeParticipationAsPersonal() ? 1 : 0,
    ),
);

header('Content-Type: application/json; charset=utf-8');
echo(json_encode($response));
