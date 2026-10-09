<?php

namespace Tsugi\Controllers\Ims;

use Tsugi\Services\Ims\NamesRoles;
use Tsugi\Util\LTI13;

/**
 * GET /ims/nrps/context/{contextId}/memberships
 */
class Nrps {

    public static function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ( $method !== 'GET' && $method !== 'HEAD' ) {
            self::send(405, array('error' => 'invalid_request'), 'application/json; charset=utf-8', array('Allow' => 'GET'));
            return;
        }
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url(is_string($uri) ? $uri : '', PHP_URL_PATH);
        $contextId = is_string($path) ? NamesRoles::contextIdFromPath($path) : null;
        if ( $contextId === null ) {
            self::send(404, array('error' => 'not_found'), 'application/json; charset=utf-8', array());
            return;
        }
        if ( ! self::acceptsMembership() ) {
            self::send(406, array('error' => 'not_acceptable'), 'application/json; charset=utf-8', array());
            return;
        }
        $token = self::bearer();
        if ( $token === null ) {
            self::send(401, array('error' => 'invalid_token'), 'application/json; charset=utf-8', array(
                'WWW-Authenticate' => 'Bearer',
            ));
            return;
        }
        $result = NamesRoles::read($token, $contextId, $_GET);
        $headers = array();
        if ( is_string($result['next']) ) {
            $headers['Link'] = '<'.$result['next'].'>; rel="next"';
        }
        if ( $result['status'] === 401 ) {
            $headers['WWW-Authenticate'] = 'Bearer';
        }
        $type = $result['status'] === 200
            ? LTI13::MEDIA_TYPE_MEMBERSHIPS.'; charset=utf-8'
            : 'application/json; charset=utf-8';
        if ( $method === 'HEAD' ) {
            self::send($result['status'], null, $type, $headers);
            return;
        }
        self::send($result['status'], $result['body'], $type, $headers);
    }

    private static function acceptsMembership(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if ( ! is_string($accept) || trim($accept) === '' ) {
            return true;
        }
        if ( str_contains($accept, LTI13::MEDIA_TYPE_MEMBERSHIPS) ) {
            return true;
        }
        if ( str_contains($accept, 'application/json') ) {
            return true;
        }
        if ( str_contains($accept, '*/*') ) {
            return true;
        }
        return false;
    }

    private static function bearer(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if ( ( ! is_string($header) || $header === '') && function_exists('apache_request_headers') ) {
            $headers = apache_request_headers();
            if ( is_array($headers) ) {
                foreach ( $headers as $name => $value ) {
                    if ( is_string($name) && strcasecmp($name, 'Authorization') === 0 && is_string($value) ) {
                        $header = $value;
                        break;
                    }
                }
            }
        }
        if ( ! is_string($header) || ! preg_match('/Bearer\s+(\S+)/i', $header, $match) ) {
            return null;
        }
        return $match[1];
    }

    /**
     * @param array<string, mixed>|null $body
     * @param array<string, string> $headers
     */
    private static function send(int $status, ?array $body, string $contentType, array $headers): void
    {
        http_response_code($status);
        header('Content-Type: '.$contentType);
        header('Cache-Control: no-store');
        foreach ( $headers as $name => $value ) {
            header($name.': '.$value);
        }
        if ( $body === null ) {
            return;
        }
        echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
