<?php

use Tsugi\Services\Outbound\PlatformDynamicRegistration;

require_once __DIR__ . '/../config.php';

$authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if ( ! is_string($authorization) ) {
    $authorization = '';
}
$payload = file_get_contents('php://input');
if ( ! is_string($payload) ) {
    $payload = '';
}
$contentType = $_SERVER['CONTENT_TYPE'] ?? null;
if ( ! is_string($contentType) ) {
    $contentType = null;
}

$result = PlatformDynamicRegistration::accept($authorization, $payload, $contentType);
http_response_code((int) $result['status']);
header('Content-Type: application/json; charset=utf-8');
$json = json_encode($result['document'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
echo is_string($json) ? $json : '{"error":"Could not store registration"}';
