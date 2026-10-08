<?php

namespace Tsugi\Services\Ims;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Tsugi\Core\Keyset;
use Tsugi\Core\LTIX;
use Tsugi\Services\Outbound\PlatformDynamicRegistration;
use Tsugi\Services\Outbound\ToolDeploymentGrant;
use Tsugi\Util\LTI13;
use Tsugi\Util\Net;

/**
 * OAuth 2 client-credentials tokens for LTI Advantage services.
 *
 * The tool signs a private_key_jwt. Scopes come from the deployment, not from
 * the registration request. One deployment is that deployment's allow list.
 * When the assertion names no deployment and the tool has several, a scope is
 * granted when any of those deployments allows it. The service call still
 * checks the deployment that covers the course.
 */
class AccessToken {

    public const ASSERTION_TYPE = 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer';

    public const EXPIRES = 3600;

    private const ASSERTION_MAX_SECONDS = 300;

    /**
     * @param array<string, mixed> $post
     * @param array<int, string> $audiences token endpoint URLs this assertion may name
     * @param array<string, mixed>|null $jwks tool key set; null fetches lti13_jwks_url
     * @return array{status:int, body:array<string, mixed>}
     */
    public static function grant(array $post, array $audiences, ?array $jwks = null) {
        $grantType = self::postString($post, 'grant_type');
        if ( $grantType === '' ) {
            return self::error(400, 'invalid_request');
        }
        if ( $grantType !== 'client_credentials' ) {
            return self::error(400, 'unsupported_grant_type');
        }
        if ( self::postString($post, 'client_assertion_type') !== self::ASSERTION_TYPE ) {
            return self::error(400, 'invalid_request');
        }
        $assertion = self::postString($post, 'client_assertion');
        if ( $assertion === '' ) {
            return self::error(400, 'invalid_request');
        }
        $requested = self::scopeList(self::postString($post, 'scope'));
        if ( count($requested) < 1 ) {
            return self::error(400, 'invalid_scope');
        }

        $unverified = LTI13::parse_jwt($assertion, false);
        if ( ! is_object($unverified) || ! isset($unverified->body->iss) || ! is_string($unverified->body->iss) ) {
            return self::error(401, 'invalid_client');
        }
        $registration = self::registrationByClient(trim($unverified->body->iss));
        if ( $registration === null ) {
            return self::error(401, 'invalid_client');
        }
        if ( $jwks === null ) {
            $jwks = self::fetchJwks($registration['jwks_url']);
        }
        if ( $jwks === null ) {
            return self::error(401, 'invalid_client');
        }

        $verified = self::decodeAssertion($assertion, $jwks);
        if ( $verified === null ) {
            return self::error(401, 'invalid_client');
        }
        $clientId = isset($verified->iss) && is_string($verified->iss) ? trim($verified->iss) : '';
        $subject = isset($verified->sub) && is_string($verified->sub) ? trim($verified->sub) : '';
        if ( $clientId === '' || $clientId !== $subject || $clientId !== $registration['client_id'] ) {
            return self::error(401, 'invalid_client');
        }
        if ( ! self::audienceMatches(isset($verified->aud) ? $verified->aud : null, $audiences) ) {
            return self::error(400, 'invalid_grant');
        }
        if ( ! self::assertionTimeOk($verified) ) {
            return self::error(400, 'invalid_grant');
        }
        $jti = isset($verified->jti) && is_string($verified->jti) ? trim($verified->jti) : '';
        if ( $jti === '' || strlen($jti) > 255 ) {
            return self::error(400, 'invalid_grant');
        }

        $namedDeployment = null;
        if ( isset($verified->{LTI13::DEPLOYMENT_ID_CLAIM}) ) {
            if ( ! is_string($verified->{LTI13::DEPLOYMENT_ID_CLAIM}) ) {
                return self::error(400, 'invalid_grant');
            }
            $namedDeployment = trim($verified->{LTI13::DEPLOYMENT_ID_CLAIM});
            if ( $namedDeployment === '' ) {
                return self::error(400, 'invalid_grant');
            }
        }

        $deployments = self::deployments($registration['registration_id'], $registration['key_id']);
        if ( count($deployments) < 1 ) {
            return self::error(400, 'invalid_scope');
        }
        $pin = null;
        if ( $namedDeployment !== null ) {
            foreach ( $deployments as $deployment ) {
                if ( $deployment['deployment_id'] === $namedDeployment ) {
                    $pin = $deployment;
                    break;
                }
            }
            if ( $pin === null ) {
                return self::error(400, 'invalid_grant');
            }
            $allowed = ToolDeploymentGrant::allowedScopes($pin['tool_deployment_id']);
        } else if ( count($deployments) === 1 ) {
            $pin = $deployments[0];
            $allowed = ToolDeploymentGrant::allowedScopes($pin['tool_deployment_id']);
        } else {
            $allowed = array();
            foreach ( $deployments as $deployment ) {
                foreach ( ToolDeploymentGrant::allowedScopes($deployment['tool_deployment_id']) as $scope ) {
                    if ( ! in_array($scope, $allowed, true) ) {
                        $allowed[] = $scope;
                    }
                }
            }
        }

        $granted = array();
        foreach ( $requested as $scope ) {
            if ( in_array($scope, $allowed, true) && ! in_array($scope, $granted, true) ) {
                $granted[] = $scope;
            }
        }
        if ( count($granted) < 1 ) {
            return self::error(400, 'invalid_scope');
        }

        $token = self::sign($registration, $granted, $pin === null ? null : $pin['deployment_id']);
        if ( $token === null ) {
            return self::error(500, 'server_error');
        }
        return array(
            'status' => 200,
            'body' => array(
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => self::EXPIRES,
                'scope' => implode(' ', $granted),
            ),
        );
    }

    /**
     * Audiences a tool may put in the client assertion.
     *
     * OpenID configuration publishes /lti/token. /ims/token is the same controller.
     *
     * @return array<int, string>
     */
    public static function audiences() {
        global $CFG;
        $root = rtrim((string) $CFG->wwwroot, '/');
        $configured = PlatformDynamicRegistration::openIdConfiguration()['token_endpoint'];
        $out = array();
        foreach ( array($configured, $root.'/lti/token', $root.'/ims/token') as $audience ) {
            if ( is_string($audience) && $audience !== '' && ! in_array($audience, $out, true) ) {
                $out[] = $audience;
            }
        }
        return $out;
    }

    /**
     * @return array{client_id:string, registration_id:int, key_id:int, scopes:array<int, string>, deployment_id:?string}|null
     */
    public static function verify(string $token) {
        if ( $token === '' ) {
            return null;
        }
        $body = self::decodePlatform($token);
        if ( $body === null ) {
            return null;
        }
        $issuer = PlatformDynamicRegistration::openIdConfiguration()['issuer'];
        $iss = isset($body->iss) && is_string($body->iss) ? $body->iss : '';
        $clientId = isset($body->sub) && is_string($body->sub) ? trim($body->sub) : '';
        if ( $iss !== $issuer || $clientId === '' ) {
            return null;
        }
        $registration = self::registrationByClient($clientId);
        if ( $registration === null ) {
            return null;
        }
        $registrationId = isset($body->registration_id) ? (int) $body->registration_id : 0;
        if ( $registrationId !== $registration['registration_id'] ) {
            return null;
        }
        $scopes = self::scopeList(isset($body->scope) && is_string($body->scope) ? $body->scope : '');
        if ( count($scopes) < 1 ) {
            return null;
        }
        $deploymentId = null;
        if ( isset($body->{LTI13::DEPLOYMENT_ID_CLAIM}) ) {
            if ( ! is_string($body->{LTI13::DEPLOYMENT_ID_CLAIM}) ) {
                return null;
            }
            $deploymentId = trim($body->{LTI13::DEPLOYMENT_ID_CLAIM});
            if ( $deploymentId === '' ) {
                return null;
            }
        }
        return array(
            'client_id' => $clientId,
            'registration_id' => $registration['registration_id'],
            'key_id' => $registration['key_id'],
            'scopes' => $scopes,
            'deployment_id' => $deploymentId,
        );
    }

    /**
     * @param array{registration_id:int, key_id:int, client_id:string, jwks_url:string} $registration
     * @param array<int, string> $scopes
     */
    private static function sign(array $registration, array $scopes, ?string $deploymentId) {
        $privkey = null;
        $kid = null;
        $ok = Keyset::getSigning($privkey, $kid);
        if ( $ok !== true || ! is_string($privkey) || $privkey === '' || ! is_string($kid) || $kid === '' ) {
            return null;
        }
        $now = time();
        $issuer = PlatformDynamicRegistration::openIdConfiguration()['issuer'];
        $claims = array(
            'iss' => $issuer,
            'sub' => $registration['client_id'],
            'aud' => $issuer,
            'iat' => $now,
            'exp' => $now + self::EXPIRES,
            'scope' => implode(' ', $scopes),
            'registration_id' => $registration['registration_id'],
        );
        if ( $deploymentId !== null && $deploymentId !== '' ) {
            $claims[LTI13::DEPLOYMENT_ID_CLAIM] = $deploymentId;
        }
        return LTI13::encode_jwt($claims, $privkey, $kid);
    }

    /**
     * @return object|null
     */
    private static function decodePlatform(string $token) {
        // Keyset reads the global connection. The membership route does not
        // open one before it checks the access token.
        LTIX::getConnection();
        JWT::$leeway = 60;
        foreach ( Keyset::getCurrentKeys() as $row ) {
            if ( ! isset($row['pubkey']) || ! is_string($row['pubkey']) || $row['pubkey'] === '' ) {
                continue;
            }
            try {
                $decoded = JWT::decode($token, new Key($row['pubkey'], 'RS256'));
            } catch ( \Throwable $ex ) {
                continue;
            }
            if ( is_object($decoded) ) {
                return $decoded;
            }
        }
        return null;
    }

    /**
     * @param array<string, mixed> $jwks
     * @return object|null
     */
    private static function decodeAssertion(string $jwt, array $jwks) {
        try {
            $keys = JWK::parseKeySet($jwks);
        } catch ( \Throwable $ex ) {
            return null;
        }
        if ( ! is_array($keys) || count($keys) < 1 ) {
            return null;
        }
        JWT::$leeway = 60;
        $parsed = LTI13::parse_jwt($jwt, false);
        $kid = is_object($parsed) && isset($parsed->header->kid) && is_string($parsed->header->kid)
            ? $parsed->header->kid
            : '';
        try {
            if ( $kid === '' && count($keys) === 1 ) {
                $only = array_values($keys);
                $decoded = JWT::decode($jwt, $only[0]);
            } else {
                $decoded = JWT::decode($jwt, $keys);
            }
        } catch ( \Throwable $ex ) {
            return null;
        }
        return is_object($decoded) ? $decoded : null;
    }

    /**
     * @param object $verified
     */
    private static function assertionTimeOk($verified) {
        $iat = isset($verified->iat) ? (int) $verified->iat : 0;
        $exp = isset($verified->exp) ? (int) $verified->exp : 0;
        if ( $iat < 1 || $exp <= $iat ) {
            return false;
        }
        if ( ($exp - $iat) > self::ASSERTION_MAX_SECONDS ) {
            return false;
        }
        $now = time();
        if ( $iat > $now + 60 ) {
            return false;
        }
        return true;
    }

    /**
     * @param mixed $aud
     * @param array<int, string> $expected
     */
    private static function audienceMatches($aud, array $expected) {
        $got = array();
        if ( is_string($aud) ) {
            $got[] = $aud;
        } else if ( is_array($aud) ) {
            foreach ( $aud as $one ) {
                if ( is_string($one) ) {
                    $got[] = $one;
                }
            }
        }
        foreach ( $got as $one ) {
            $one = rtrim($one, '/');
            foreach ( $expected as $want ) {
                if ( $one === rtrim($want, '/') ) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @return array<int, string>
     */
    private static function scopeList(string $scope) {
        $parts = preg_split('/\s+/', trim($scope));
        if ( ! is_array($parts) ) {
            return array();
        }
        $out = array();
        foreach ( $parts as $part ) {
            if ( $part !== '' && ! in_array($part, $out, true) ) {
                $out[] = $part;
            }
        }
        return $out;
    }

    /**
     * @return array{registration_id:int, key_id:int, client_id:string, jwks_url:string}|null
     */
    private static function registrationByClient(string $clientId) {
        if ( $clientId === '' || strlen($clientId) > 255 ) {
            return null;
        }
        $p = self::prefix();
        $rows = self::db()->allRowsDie(
            "SELECT registration_id, key_id, lti13_client_id, lti13_jwks_url
             FROM {$p}lti_tool_registration
             WHERE lti13_client_id = :client_id AND lti_version = '1.3'",
            array(':client_id' => $clientId)
        );
        if ( ! is_array($rows) || count($rows) !== 1 ) {
            return null;
        }
        $row = $rows[0];
        $jwks = isset($row['lti13_jwks_url']) && is_string($row['lti13_jwks_url']) ? trim($row['lti13_jwks_url']) : '';
        return array(
            'registration_id' => (int) $row['registration_id'],
            'key_id' => (int) $row['key_id'],
            'client_id' => (string) $row['lti13_client_id'],
            'jwks_url' => $jwks,
        );
    }

    /**
     * @return array<int, array{tool_deployment_id:int, deployment_id:string}>
     */
    private static function deployments(int $registrationId, int $keyId) {
        $p = self::prefix();
        $rows = self::db()->allRowsDie(
            "SELECT tool_deployment_id, deployment_id
             FROM {$p}lti_tool_deployment
             WHERE registration_id = :registration_id
               AND key_id = :key_id
               AND deployment_id IS NOT NULL
               AND deployment_id <> ''
             ORDER BY tool_deployment_id ASC",
            array(
                ':registration_id' => $registrationId,
                ':key_id' => $keyId,
            )
        );
        if ( ! is_array($rows) ) {
            return array();
        }
        $out = array();
        foreach ( $rows as $row ) {
            if ( ! isset($row['deployment_id']) || ! is_string($row['deployment_id']) || $row['deployment_id'] === '' ) {
                continue;
            }
            $out[] = array(
                'tool_deployment_id' => (int) $row['tool_deployment_id'],
                'deployment_id' => $row['deployment_id'],
            );
        }
        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function fetchJwks(string $url) {
        if ( ! preg_match('#^https?://#i', $url) ) {
            return null;
        }
        $ch = curl_init($url);
        if ( $ch === false ) {
            return null;
        }
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        );
        if ( Net::$VERIFY_PEER ) {
            $options[CURLOPT_SSL_VERIFYPEER] = true;
        }
        curl_setopt_array($ch, $options);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ( ! is_string($body) || $code < 200 || $code >= 300 || strlen($body) > 100000 ) {
            $error = curl_error($ch);
            error_log('LTI token JWKS fetch failed url='.$url.' code='.$code.' err='.$error);
            return null;
        }
        $decoded = json_decode($body, true);
        if ( ! is_array($decoded) || ! isset($decoded['keys']) || ! is_array($decoded['keys']) ) {
            return null;
        }
        return $decoded;
    }

    /**
     * @param array<string, mixed> $post
     */
    private static function postString(array $post, string $key) {
        if ( ! isset($post[$key]) || ! is_string($post[$key]) ) {
            return '';
        }
        return trim($post[$key]);
    }

    /**
     * @return array{status:int, body:array<string, mixed>}
     */
    private static function error(int $status, string $error) {
        return array(
            'status' => $status,
            'body' => array('error' => $error),
        );
    }

    /**
     * @return \Tsugi\Util\PDOX
     */
    private static function db() {
        $PDOX = LTIX::getConnection();
        if ( ! $PDOX ) {
            throw new \RuntimeException('Database connection is not available.');
        }
        return $PDOX;
    }

    private static function prefix() {
        global $CFG;
        return isset($CFG->dbprefix) ? (string) $CFG->dbprefix : '';
    }
}
