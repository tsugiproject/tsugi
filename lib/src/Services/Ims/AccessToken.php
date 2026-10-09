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
     * CURLOPT_RESOLVE entry for a tool JWKS URL, or null when the URL is refused.
     *
     * HTTPS only. Loopback, private, and reserved addresses are refused unless
     * $CFG->qa_allow_local_jwks is true. The chosen address is pinned so curl
     * cannot connect somewhere else. When local addresses are allowed, a
     * hosts-file result is used as-is so a name like local.py4e.com stays on
     * this machine.
     */
    public static function jwksFetchTarget(string $url) {
        return self::jwksFetchDecision($url)['target'];
    }

    /**
     * @return array{target:?string, log_url:string, reason:string, fix:string}
     */
    private static function jwksFetchDecision(string $url) {
        $logUrl = self::jwksUrlForLog($url);
        $parts = parse_url($url);
        if ( ! is_array($parts) ) {
            return self::jwksRefusal(
                $logUrl,
                'The jwks_uri could not be parsed as a URL.',
                'Set the tool registration jwks_uri to an absolute https URL, for example https://tools.example.edu/lti/keyset.'
            );
        }
        $scheme = isset($parts['scheme']) ? strtolower((string) $parts['scheme']) : '';
        if ( $scheme !== 'https' ) {
            $shown = $scheme === '' ? '(missing)' : $scheme;
            return self::jwksRefusal(
                $logUrl,
                'Scheme is "'.$shown.'". Only https is fetched.',
                'Change the tool jwks_uri to https and request a new token. $CFG->qa_allow_local_jwks does not allow http, including for localhost.'
            );
        }
        if ( isset($parts['user']) || isset($parts['pass']) ) {
            return self::jwksRefusal(
                $logUrl,
                'The jwks_uri contains a username or password.',
                'Remove the userinfo from the tool jwks_uri. The platform fetches that URL itself and will not send embedded credentials.'
            );
        }
        if ( ! isset($parts['host']) || ! is_string($parts['host']) || $parts['host'] === '' ) {
            return self::jwksRefusal(
                $logUrl,
                'The jwks_uri has no host.',
                'Use an absolute https URL with a host, for example https://tools.example.edu/lti/keyset.'
            );
        }
        $host = $parts['host'];
        $port = isset($parts['port']) ? (int) $parts['port'] : 443;
        if ( $port < 1 || $port > 65535 ) {
            return self::jwksRefusal(
                $logUrl,
                'Port '.$port.' is outside 1-65535.',
                'Use port 443, or another valid TCP port, on an https jwks_uri. When the URL omits the port, 443 is used.'
            );
        }
        $lookup = rtrim($host, '.');
        if ( ! filter_var($host, FILTER_VALIDATE_IP) && ! self::isDnsName($lookup) ) {
            return self::jwksRefusal(
                $logUrl,
                'Host "'.$host.'" is not a DNS name or an IP address.',
                'Use a hostname such as tools.example.edu or a dotted IP address. Decimal, hex, and other encoded hosts are refused before any connection.'
            );
        }
        $addresses = self::jwksHostAddresses($host);
        if ( count($addresses) < 1 ) {
            return self::jwksRefusal(
                $logUrl,
                'Host "'.$host.'" did not resolve to an IP address.',
                'Fix DNS or /etc/hosts on the platform server, or correct the tool jwks_uri. If this name should point at this machine, add it to /etc/hosts and set $CFG->qa_allow_local_jwks = true in config.php.'
            );
        }
        $allowLocal = self::jwksAllowLocal();
        $chosen = null;
        foreach ( $addresses as $ip ) {
            $routable = Net::isRoutable($ip) ? true : false;
            if ( ! $routable && ! $allowLocal ) {
                return self::jwksRefusal(
                    $logUrl,
                    'Resolved '.implode(', ', $addresses).'. '.$ip.' is loopback, private, or reserved, and $CFG->qa_allow_local_jwks is false.',
                    self::localJwksFix()
                );
            }
            if ( $chosen === null || ($routable && ! Net::isRoutable($chosen)) ) {
                $chosen = $ip;
            }
        }
        if ( ! is_string($chosen) || $chosen === '' ) {
            return self::jwksRefusal(
                $logUrl,
                'No address could be selected from '.implode(', ', $addresses).'.',
                self::localJwksFix()
            );
        }
        $pinned = strpos($chosen, ':') === false ? $chosen : '['.$chosen.']';
        return array(
            'target' => $host.':'.$port.':'.$pinned,
            'log_url' => $logUrl,
            'reason' => '',
            'fix' => '',
        );
    }

    /**
     * @return array{target:?string, log_url:string, reason:string, fix:string}
     */
    private static function jwksRefusal(string $logUrl, string $reason, string $fix) {
        return array(
            'target' => null,
            'log_url' => $logUrl,
            'reason' => $reason,
            'fix' => $fix,
        );
    }

    private static function localJwksFix() {
        return 'Local testing: set $CFG->qa_allow_local_jwks = true in this platform\'s config.php '
            .'(the default in config-dist.php is false), keep the jwks_uri on https, and request a new token. '
            .'A public platform should leave the flag false and register a jwks_uri that resolves to a public address.';
    }

    private static function jwksUrlForLog(string $url) {
        $parts = parse_url($url);
        if ( ! is_array($parts) ) {
            return '(unparsed url omitted)';
        }
        if ( isset($parts['user']) || isset($parts['pass']) ) {
            $scheme = isset($parts['scheme']) && is_string($parts['scheme']) ? $parts['scheme'] : '';
            $host = isset($parts['host']) && is_string($parts['host']) ? $parts['host'] : '';
            return $scheme.'://'.$host.' (userinfo removed)';
        }
        return $url;
    }

    /**
     * @param array{target:?string, log_url:string, reason:string, fix:string} $decision
     */
    private static function logJwksRejection(array $decision) {
        global $CFG;
        $wwwroot = isset($CFG->wwwroot) && is_string($CFG->wwwroot) ? $CFG->wwwroot : '';
        $flag = self::jwksAllowLocal() ? 'true' : 'false';
        error_log(
            "LTI token JWKS url rejected\n"
            .'  url='.$decision['log_url']."\n"
            .'  platform='.$wwwroot."\n"
            .'  qa_allow_local_jwks='.$flag."\n"
            .'  reason='.$decision['reason']."\n"
            .'  fix='.$decision['fix']
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function fetchJwks(string $url) {
        $decision = self::jwksFetchDecision($url);
        $resolve = $decision['target'];
        if ( $resolve === null ) {
            self::logJwksRejection($decision);
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
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_RESOLVE => array($resolve),
        );
        if ( Net::$VERIFY_PEER ) {
            $options[CURLOPT_SSL_VERIFYPEER] = true;
        }
        curl_setopt_array($ch, $options);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ( ! is_string($body) || $code < 200 || $code >= 300 || strlen($body) > 100000 ) {
            $error = curl_error($ch);
            error_log(
                "LTI token JWKS fetch failed\n"
                .'  url='.self::jwksUrlForLog($url)."\n"
                .'  resolve='.$resolve."\n"
                .'  code='.$code."\n"
                .'  err='.$error."\n"
                .'  fix=The URL was accepted, so this is not the private-address check. The key set must be HTTPS JSON with a keys array, and this PHP process must trust the server certificate.'
            );
            return null;
        }
        $decoded = json_decode($body, true);
        if ( ! is_array($decoded) || ! isset($decoded['keys']) || ! is_array($decoded['keys']) ) {
            return null;
        }
        return $decoded;
    }

    private static function jwksAllowLocal() {
        global $CFG;
        return isset($CFG->qa_allow_local_jwks) && $CFG->qa_allow_local_jwks === true;
    }

    /**
     * @return array<int, string>
     */
    private static function jwksHostAddresses(string $host) {
        if ( filter_var($host, FILTER_VALIDATE_IP) ) {
            return array($host);
        }
        $lookup = rtrim($host, '.');
        if ( ! self::isDnsName($lookup) ) {
            return array();
        }
        $ips = array();
        $v4 = gethostbynamel($lookup);
        if ( is_array($v4) ) {
            foreach ( $v4 as $ip ) {
                if ( is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ) {
                    $ips[] = $ip;
                }
            }
        }
        // A hosts-file address is enough for local testing. Extra DNS results
        // are only merged when private addresses are refused, so a public name
        // cannot hide a private one.
        if ( count($ips) === 0 || ! self::jwksAllowLocal() ) {
            foreach ( self::jwksDnsAddresses($lookup) as $ip ) {
                $ips[] = $ip;
            }
        }
        $unique = array();
        foreach ( $ips as $ip ) {
            $unique[$ip] = $ip;
        }
        return array_values($unique);
    }

    /**
     * @return array<int, string>
     */
    private static function jwksDnsAddresses(string $host) {
        if ( ! function_exists('dns_get_record') ) {
            return array();
        }
        $rows = @dns_get_record($host, DNS_A | DNS_AAAA);
        if ( ! is_array($rows) ) {
            return array();
        }
        $ips = array();
        foreach ( $rows as $row ) {
            if ( ! is_array($row) ) {
                continue;
            }
            if ( isset($row['ip']) && is_string($row['ip']) && filter_var($row['ip'], FILTER_VALIDATE_IP) ) {
                $ips[] = $row['ip'];
            }
            if ( isset($row['ipv6']) && is_string($row['ipv6']) && filter_var($row['ipv6'], FILTER_VALIDATE_IP) ) {
                $ips[] = $row['ipv6'];
            }
        }
        return $ips;
    }

    private static function isDnsName(string $host) {
        if ( $host === '' || strlen($host) > 253 ) {
            return false;
        }
        return preg_match('/\A(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\z/i', $host) === 1;
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
