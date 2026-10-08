<?php

use Tsugi\Services\Outbound\PlatformDynamicRegistration;

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
echo json_encode(
    PlatformDynamicRegistration::openIdConfiguration(),
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
);
