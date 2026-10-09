<?php

namespace Tsugi\Controllers\Ims;

use Tsugi\Services\Ims\AssignmentsGrades;
use Tsugi\Util\LTI13;

/**
 * Assignments and Grades for one course.
 *
 * GET/POST  /ims/ags/context/{contextId}/lineitems
 * GET/PUT/DELETE /ims/ags/context/{contextId}/lineitems/{linkId}
 * GET  /ims/ags/context/{contextId}/lineitems/{linkId}/results
 * POST /ims/ags/context/{contextId}/lineitems/{linkId}/scores
 *
 * /lti/ags is the same controller.
 */
class Ags {

    public static function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url(is_string($uri) ? $uri : '', PHP_URL_PATH);
        $parsed = is_string($path) ? AssignmentsGrades::parsePath($path) : null;
        if ( $parsed === null ) {
            self::send(404, array('error' => 'not_found'), 'application/json; charset=utf-8', array());
            return;
        }

        $action = $parsed['action'];
        if ( $action === 'container' ) {
            self::container($method, $parsed);
            return;
        }
        if ( $action === 'item' ) {
            self::item($method, $parsed);
            return;
        }
        if ( $action === 'results' ) {
            self::results($method, $parsed);
            return;
        }
        self::scores($method, $parsed);
    }

    /**
     * @param array{context_id:int, link_id:?int, user_id:?string, action:string} $parsed
     */
    private static function container(string $method, array $parsed): void
    {
        if ( $method !== 'GET' && $method !== 'HEAD' && $method !== 'POST' ) {
            self::send(405, array('error' => 'invalid_request'), 'application/json; charset=utf-8', array(
                'Allow' => 'GET, POST',
            ));
            return;
        }
        if ( $method === 'POST' ) {
            if ( ! self::contentType(LTI13::MEDIA_TYPE_LINEITEM) ) {
                self::send(415, array('error' => 'unsupported_media_type'), 'application/json; charset=utf-8', array());
                return;
            }
            $body = self::jsonBody();
            if ( ! is_array($body) ) {
                self::send(400, array('error' => 'invalid_request'), 'application/json; charset=utf-8', array());
                return;
            }
            $token = self::requireBearer();
            if ( $token === null ) {
                return;
            }
            $result = AssignmentsGrades::create($token, $parsed['context_id'], $body);
            self::finish($result, LTI13::MEDIA_TYPE_LINEITEM);
            return;
        }
        if ( ! self::accepts(LTI13::MEDIA_TYPE_LINEITEMS) ) {
            self::send(406, array('error' => 'not_acceptable'), 'application/json; charset=utf-8', array());
            return;
        }
        $token = self::requireBearer();
        if ( $token === null ) {
            return;
        }
        $result = AssignmentsGrades::listItems($token, $parsed['context_id'], $_GET);
        self::finish($result, LTI13::MEDIA_TYPE_LINEITEMS, $method === 'HEAD');
    }

    /**
     * @param array{context_id:int, link_id:?int, user_id:?string, action:string} $parsed
     */
    private static function item(string $method, array $parsed): void
    {
        if ( $method !== 'GET' && $method !== 'HEAD' && $method !== 'PUT' && $method !== 'DELETE' ) {
            self::send(405, array('error' => 'invalid_request'), 'application/json; charset=utf-8', array(
                'Allow' => 'GET, PUT, DELETE',
            ));
            return;
        }
        $linkId = (int) $parsed['link_id'];
        if ( $method === 'DELETE' ) {
            $token = self::requireBearer();
            if ( $token === null ) {
                return;
            }
            $result = AssignmentsGrades::delete($token, $parsed['context_id'], $linkId);
            self::finish($result, LTI13::MEDIA_TYPE_LINEITEM);
            return;
        }
        if ( $method === 'PUT' ) {
            if ( ! self::contentType(LTI13::MEDIA_TYPE_LINEITEM) ) {
                self::send(415, array('error' => 'unsupported_media_type'), 'application/json; charset=utf-8', array());
                return;
            }
            $body = self::jsonBody();
            if ( ! is_array($body) ) {
                self::send(400, array('error' => 'invalid_request'), 'application/json; charset=utf-8', array());
                return;
            }
            $token = self::requireBearer();
            if ( $token === null ) {
                return;
            }
            $result = AssignmentsGrades::update($token, $parsed['context_id'], $linkId, $body);
            self::finish($result, LTI13::MEDIA_TYPE_LINEITEM);
            return;
        }
        if ( ! self::accepts(LTI13::MEDIA_TYPE_LINEITEM) ) {
            self::send(406, array('error' => 'not_acceptable'), 'application/json; charset=utf-8', array());
            return;
        }
        $token = self::requireBearer();
        if ( $token === null ) {
            return;
        }
        $result = AssignmentsGrades::readItem($token, $parsed['context_id'], $linkId);
        self::finish($result, LTI13::MEDIA_TYPE_LINEITEM, $method === 'HEAD');
    }

    /**
     * @param array{context_id:int, link_id:?int, user_id:?string, action:string} $parsed
     */
    private static function results(string $method, array $parsed): void
    {
        if ( $method !== 'GET' && $method !== 'HEAD' ) {
            self::send(405, array('error' => 'invalid_request'), 'application/json; charset=utf-8', array(
                'Allow' => 'GET',
            ));
            return;
        }
        if ( ! self::accepts(LTI13::RESULTS_TYPE) ) {
            self::send(406, array('error' => 'not_acceptable'), 'application/json; charset=utf-8', array());
            return;
        }
        $token = self::requireBearer();
        if ( $token === null ) {
            return;
        }
        $result = AssignmentsGrades::results($token, $parsed['context_id'], (int) $parsed['link_id'], $_GET, $parsed['user_id']);
        self::finish($result, LTI13::RESULTS_TYPE, $method === 'HEAD');
    }

    /**
     * @param array{context_id:int, link_id:?int, user_id:?string, action:string} $parsed
     */
    private static function scores(string $method, array $parsed): void
    {
        if ( $method !== 'POST' ) {
            self::send(405, array('error' => 'invalid_request'), 'application/json; charset=utf-8', array(
                'Allow' => 'POST',
            ));
            return;
        }
        if ( ! self::contentType(LTI13::SCORE_TYPE) ) {
            self::send(415, array('error' => 'unsupported_media_type'), 'application/json; charset=utf-8', array());
            return;
        }
        $body = self::jsonBody();
        if ( ! is_array($body) ) {
            self::send(400, array('error' => 'invalid_request'), 'application/json; charset=utf-8', array());
            return;
        }
        $token = self::requireBearer();
        if ( $token === null ) {
            return;
        }
        $result = AssignmentsGrades::score($token, $parsed['context_id'], (int) $parsed['link_id'], $body);
        self::finish($result, LTI13::SCORE_TYPE);
    }

    /**
     * @param array{status:int, body:array<int|string, mixed>|null, next:?string, location:?string} $result
     */
    private static function finish(array $result, string $mediaType, bool $head = false): void
    {
        $headers = array();
        if ( is_string($result['next']) ) {
            $headers['Link'] = '<'.$result['next'].'>; rel="next"';
        }
        if ( is_string($result['location']) ) {
            $headers['Location'] = $result['location'];
        }
        if ( $result['status'] === 401 ) {
            $headers['WWW-Authenticate'] = 'Bearer';
        }
        if ( $result['status'] === 204 ) {
            self::send(204, null, '', $headers);
            return;
        }
        $type = $result['status'] === 200 || $result['status'] === 201
            ? $mediaType.'; charset=utf-8'
            : 'application/json; charset=utf-8';
        self::send($result['status'], $head ? null : $result['body'], $type, $headers);
    }

    private static function requireBearer(): ?string
    {
        $token = self::bearer();
        if ( $token === null ) {
            self::send(401, array('error' => 'invalid_token'), 'application/json; charset=utf-8', array(
                'WWW-Authenticate' => 'Bearer',
            ));
            return null;
        }
        return $token;
    }

    private static function accepts(string $mediaType): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if ( ! is_string($accept) || trim($accept) === '' ) {
            return true;
        }
        if ( str_contains($accept, $mediaType) ) {
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

    private static function contentType(string $mediaType): bool
    {
        $type = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        return is_string($type) && str_contains($type, $mediaType);
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function jsonBody(): ?array
    {
        $raw = file_get_contents('php://input');
        if ( ! is_string($raw) || trim($raw) === '' ) {
            return null;
        }
        if ( strlen($raw) > 1000000 ) {
            return null;
        }
        $decoded = json_decode($raw, true);
        if ( ! is_array($decoded) || array_is_list($decoded) ) {
            return null;
        }
        return $decoded;
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
     * @param array<string, mixed>|array<int, mixed>|null $body
     * @param array<string, string> $headers
     */
    private static function send(int $status, ?array $body, string $contentType, array $headers): void
    {
        http_response_code($status);
        if ( $contentType !== '' ) {
            header('Content-Type: '.$contentType);
        }
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
