<?php

namespace Tsugi\Controllers\Ims;

use Tsugi\Services\Ims\AccessToken;

/**
 * POST /lti/token and POST /ims/token.
 *
 * OpenID configuration advertises /lti/token. /ims/token is the same controller.
 */
class Token {

    public static function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ( $method !== 'POST' ) {
            self::json(405, array('error' => 'invalid_request'), array('Allow' => 'POST'));
            return;
        }
        $result = AccessToken::grant($_POST, AccessToken::audiences(), null);
        $headers = array();
        if ( $result['status'] === 401 ) {
            $headers['WWW-Authenticate'] = 'Bearer';
        }
        self::json($result['status'], $result['body'], $headers);
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, string> $headers
     */
    private static function json(int $status, array $body, array $headers): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        header('Pragma: no-cache');
        foreach ( $headers as $name => $value ) {
            header($name.': '.$value);
        }
        echo json_encode($body, JSON_UNESCAPED_SLASHES);
    }
}
