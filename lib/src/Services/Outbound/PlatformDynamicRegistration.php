<?php

namespace Tsugi\Services\Outbound;

use Tsugi\Core\LTIX;

/**
 * IMS Dynamic Registration with Tsugi as the platform.
 *
 * The openid configuration is one document for the install. Its URLs do not
 * contain a tool id. A course mints a one-time token, the browser opens the
 * tool, and the tool POSTs here. The registration row is created only after
 * that POST validates.
 */
class PlatformDynamicRegistration {

    const PLATFORM_CONFIGURATION = 'https://purl.imsglobal.org/spec/lti-platform-configuration';

    const BODY_LIMIT = 300000;

    const TOKEN_SECONDS = 3600;

    /**
     * Stable platform openid configuration. No client_id and no deployment_id.
     *
     * @return array<string, mixed>
     */
    public static function openIdConfiguration() {
        $root = self::root();
        return array(
            'issuer' => $root,
            'authorization_endpoint' => $root.'/lti/oidc_auth',
            'token_endpoint' => $root.'/lti/token',
            'token_endpoint_auth_methods_supported' => array('private_key_jwt'),
            'token_endpoint_auth_signing_alg_values_supported' => array('RS256'),
            'jwks_uri' => $root.'/lti/keyset',
            'registration_endpoint' => $root.'/lti/register',
            'scopes_supported' => array(
                'openid',
                ToolRegistrationDocument::SCOPE_SCORE,
                ToolRegistrationDocument::SCOPE_LINEITEM,
                ToolRegistrationDocument::SCOPE_RESULT,
                ToolRegistrationDocument::SCOPE_ROSTER,
            ),
            'response_types_supported' => array('id_token'),
            'subject_types_supported' => array('public'),
            'id_token_signing_alg_values_supported' => array('RS256'),
            'claims_supported' => array('iss', 'sub', 'name', 'given_name', 'family_name', 'email'),
            self::PLATFORM_CONFIGURATION => array(
                'product_family_code' => 'tsugi.org',
                'messages_supported' => array(
                    array('type' => 'LtiResourceLinkRequest'),
                    array('type' => 'LtiDeepLinkingRequest'),
                    array('type' => 'LtiDataPrivacyLaunchRequest'),
                ),
            ),
        );
    }

    /**
     * @return string
     */
    public static function openIdConfigurationUrl() {
        return self::root().'/lti/openid-configuration';
    }

    /**
     * Tool registration URL with the openid configuration and one-time token.
     *
     * @param string $toolUrl
     * @param string $token
     * @return string
     */
    public static function forwardUrl($toolUrl, $token) {
        $toolUrl = self::requireHttpUrl($toolUrl, 'Dynamic registration URL');
        $join = strpos($toolUrl, '?') === false ? '?' : '&';
        return $toolUrl.$join
            .'openid_configuration='.rawurlencode(self::openIdConfigurationUrl())
            .'&registration_token='.rawurlencode($token);
    }

    /**
     * Mint a one-time token for a course. No registration row is created.
     *
     * @param int $contextId
     * @param int|null $userId
     * @param string $toolUrl
     * @return string raw token
     */
    public static function startForCourse($contextId, $userId, $toolUrl) {
        $toolUrl = self::requireHttpUrl($toolUrl, 'Dynamic registration URL');
        $context = self::findContext($contextId);
        if ( $context === null ) {
            throw new \InvalidArgumentException('Course was not found.');
        }
        $token = time().':'.bin2hex(random_bytes(16));
        $createdBy = self::creatorOnKey($userId, (int) $context['key_id']);
        $PDOX = self::db();
        $p = self::prefix();
        $stmt = $PDOX->queryReturnError(
            "INSERT INTO {$p}lti_tool_registration_token
                (token_sha256, key_id, owner_org_id, owner_context_id, created_by_user_id,
                 tool_url, expires_at, created_at)
             VALUES
                (:token_sha256, :key_id, NULL, :owner_context_id, :created_by_user_id,
                 :tool_url, DATE_ADD(NOW(), INTERVAL ".self::TOKEN_SECONDS." SECOND), NOW())",
            array(
                ':token_sha256' => hash('sha256', $token),
                ':key_id' => (int) $context['key_id'],
                ':owner_context_id' => (int) $context['context_id'],
                ':created_by_user_id' => $createdBy,
                ':tool_url' => $toolUrl,
            )
        );
        if ( ! $stmt->success ) {
            $detail = isset($stmt->errorImplode) ? (string) $stmt->errorImplode : 'database error';
            throw new \RuntimeException('Could not start dynamic registration. '.$detail);
        }
        return $token;
    }

    /**
     * Token minted for this course, or null when it is not.
     *
     * @param string $token
     * @param int $contextId
     * @return array{tool_url:string, registration_id:int|null, fresh:bool, used:bool}|null
     */
    public static function courseToken($token, $contextId) {
        if ( ! is_string($token) || $token === '' ) {
            return null;
        }
        $row = self::findToken($token);
        if ( $row === null ) {
            return null;
        }
        if ( (int) $row['owner_context_id'] !== (int) $contextId || $row['owner_org_id'] !== null ) {
            return null;
        }
        return array(
            'tool_url' => (string) $row['tool_url'],
            'registration_id' => $row['registration_id'],
            'fresh' => (int) $row['fresh'] === 1 && self::prefixFresh($token),
            'used' => $row['used_at'] !== null,
        );
    }

    /**
     * Accept a tool's registration POST.
     *
     * The raw body is logged once the token names a real pending registration,
     * and before JSON parsing. An unknown token is rejected without a log row.
     *
     * @param string $authorization
     * @param string $payloadText
     * @param string|null $contentType
     * @return array{status:int, document:array<string, mixed>}
     */
    public static function accept($authorization, $payloadText, $contentType = null) {
        if ( ! is_string($payloadText) ) {
            return self::error(400, 'Could not read POST Data');
        }
        if ( strlen($payloadText) > self::BODY_LIMIT ) {
            return self::error(400, 'JSON too long');
        }
        $token = self::bearer($authorization);
        if ( $token === null ) {
            return self::error(400, 'invalid_authorization');
        }
        $row = self::findToken($token);
        if ( $row === null ) {
            return self::error(403, 'invalid_authorization');
        }
        $logId = ToolRegistrationDocument::appendLog(
            null,
            'inbound',
            'registration_request',
            $payloadText,
            is_string($contentType) ? $contentType : null,
            null
        );
        if ( ! self::usable($row, $token) ) {
            return self::error(403, 'invalid_authorization');
        }

        $decoded = json_decode($payloadText, true);
        if ( ! is_array($decoded) || json_last_error() !== JSON_ERROR_NONE || array_is_list($decoded) ) {
            return self::error(400, 'Badly formatted JSON');
        }
        try {
            $fields = self::registrationFields($decoded);
            $clientId = self::uuid();
            $deploymentId = self::uuid();
            $document = self::stamp($decoded, $clientId, $deploymentId);
        } catch ( \InvalidArgumentException $ex ) {
            return self::error(400, $ex->getMessage());
        }
        $meta = array(
            'lti_version' => '1.3',
            'lti13_client_id' => $clientId,
            'lti13_oidc_login_url' => $fields['login'],
            'lti13_jwks_url' => $fields['jwks'],
            'lti13_launch_url' => $fields['launch'],
            'lti13_redirect_uri' => $fields['redirect'],
        );

        $PDOX = self::db();
        $owns = ! $PDOX->inTransaction();
        if ( $owns ) {
            $PDOX->beginTransaction();
        }
        try {
            $locked = self::lockToken((int) $row['token_id']);
            if ( $locked === null || ! self::usable($locked, $token) ) {
                if ( $owns && $PDOX->inTransaction() ) {
                    $PDOX->rollBack();
                }
                return self::error(403, 'invalid_authorization');
            }
            if ( $locked['owner_context_id'] === null ) {
                throw new \InvalidArgumentException('Course was not found.');
            }
            $created = ToolRegistrationService::createLti13ForCourse(
                (int) $locked['owner_context_id'],
                $locked['created_by_user_id'],
                $fields['title'],
                $meta,
                $document,
                $deploymentId
            );
            ToolRegistrationDocument::attachLog($logId, $created['registration_id'], 200);
            self::consume((int) $locked['token_id'], $created['registration_id']);
            if ( $owns ) {
                $PDOX->commit();
            }
        } catch ( \InvalidArgumentException $ex ) {
            if ( $owns && $PDOX->inTransaction() ) {
                $PDOX->rollBack();
            }
            return self::error(400, $ex->getMessage());
        } catch ( \Throwable $ex ) {
            if ( $owns && $PDOX->inTransaction() ) {
                $PDOX->rollBack();
            }
            error_log('Dynamic registration failed: '.$ex->getMessage());
            return self::error(500, 'Could not store registration');
        }

        return array(
            'status' => 200,
            'document' => $document,
        );
    }

    /**
     * @param array<string, mixed> $document
     * @return array{login:string, jwks:string, redirect:string, launch:string|null, title:string}
     */
    private static function registrationFields(array $document) {
        $login = self::httpField($document, 'initiate_login_uri', 'initiate_login_uri');
        $jwks = self::httpField($document, 'jwks_uri', 'jwks_uri');
        if ( ! isset($document['redirect_uris']) || ! is_array($document['redirect_uris']) || ! array_is_list($document['redirect_uris']) || count($document['redirect_uris']) < 1 ) {
            throw new \InvalidArgumentException('Missing initiate_login_uri, jwks_uri, redirect_uris');
        }
        $redirect = null;
        foreach ( $document['redirect_uris'] as $uri ) {
            if ( ! is_string($uri) || self::httpUrlOrNull($uri) === null ) {
                throw new \InvalidArgumentException('Missing initiate_login_uri, jwks_uri, redirect_uris');
            }
            if ( $redirect === null ) {
                $redirect = self::httpUrlOrNull($uri);
            }
        }
        $launch = self::launchUrl($document);
        return array(
            'login' => $login,
            'jwks' => $jwks,
            'redirect' => $redirect,
            'launch' => $launch,
            'title' => self::title($document, $login),
        );
    }

    /**
     * @param array<string, mixed> $document
     * @param string $key
     * @param string $label
     * @return string
     */
    private static function httpField(array $document, $key, $label) {
        if ( ! isset($document[$key]) || ! is_string($document[$key]) ) {
            throw new \InvalidArgumentException('Missing initiate_login_uri, jwks_uri, redirect_uris');
        }
        $url = self::httpUrlOrNull($document[$key]);
        if ( $url === null ) {
            throw new \InvalidArgumentException($label.' must be an http URL.');
        }
        return $url;
    }

    /**
     * @param array<string, mixed> $document
     * @return string|null
     */
    private static function launchUrl(array $document) {
        $config = self::toolConfiguration($document);
        if ( is_array($config) && isset($config['target_link_uri']) && is_string($config['target_link_uri']) ) {
            $url = self::httpUrlOrNull($config['target_link_uri']);
            if ( $url === null ) {
                throw new \InvalidArgumentException('target_link_uri must be an http URL.');
            }
            return $url;
        }
        if ( is_array($config) && isset($config['messages']) && is_array($config['messages']) ) {
            foreach ( $config['messages'] as $message ) {
                if ( is_array($message) && isset($message['target_link_uri']) && is_string($message['target_link_uri']) && $message['target_link_uri'] !== '' ) {
                    $url = self::httpUrlOrNull($message['target_link_uri']);
                    if ( $url === null ) {
                        throw new \InvalidArgumentException('target_link_uri must be an http URL.');
                    }
                    return $url;
                }
            }
        }
        return null;
    }

    /**
     * @param array<string, mixed> $document
     * @param string $login
     * @return string
     */
    private static function title(array $document, $login) {
        if ( isset($document['client_name']) && is_string($document['client_name']) ) {
            $name = trim($document['client_name']);
            if ( $name !== '' ) {
                if ( mb_strlen($name) > 512 ) {
                    throw new \InvalidArgumentException('client_name is too long.');
                }
                return $name;
            }
        }
        $parts = parse_url($login);
        if ( is_array($parts) && isset($parts['host']) && is_string($parts['host']) && $parts['host'] !== '' ) {
            $host = $parts['host'];
            if ( mb_strlen($host) <= 512 ) {
                return $host;
            }
        }
        return 'LTI tool';
    }

    /**
     * @param array<string, mixed> $document
     * @param string $clientId
     * @param string $deploymentId
     * @return array<string, mixed>
     */
    private static function stamp(array $document, $clientId, $deploymentId) {
        $key = ToolRegistrationDocument::TOOL_CONFIGURATION;
        if ( ! array_key_exists($key, $document) || $document[$key] === null ) {
            $document[$key] = array();
            if ( isset($document['messages']) && is_array($document['messages']) ) {
                $document[$key]['messages'] = $document['messages'];
            }
        }
        // json_decode turns {} into []. An empty list is that object.
        // A non-empty list was a JSON array.
        if ( ! is_array($document[$key]) || (array_is_list($document[$key]) && $document[$key] !== array()) ) {
            throw new \InvalidArgumentException('LTI tool configuration must be a JSON object.');
        }
        $document['client_id'] = $clientId;
        $document[$key]['deployment_id'] = $deploymentId;
        return $document;
    }

    /**
     * @param array<string, mixed> $document
     * @return array<string, mixed>|null
     */
    private static function toolConfiguration(array $document) {
        $key = ToolRegistrationDocument::TOOL_CONFIGURATION;
        if ( ! isset($document[$key]) || ! is_array($document[$key]) || array_is_list($document[$key]) ) {
            return null;
        }
        return $document[$key];
    }

    /**
     * @param mixed $authorization
     * @return string|null
     */
    private static function bearer($authorization) {
        if ( ! is_string($authorization) ) {
            return null;
        }
        if ( ! preg_match('/^Bearer\s+(\S+)$/i', trim($authorization), $match) ) {
            return null;
        }
        $token = $match[1];
        if ( strlen($token) > 255 ) {
            return null;
        }
        return $token;
    }

    /**
     * @param string $token
     * @return array<string, mixed>|null
     */
    private static function findToken($token) {
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT token_id, key_id, owner_org_id, owner_context_id, created_by_user_id,
                    tool_url, used_at, registration_id,
                    CASE WHEN expires_at > NOW() THEN 1 ELSE 0 END AS fresh
             FROM {$p}lti_tool_registration_token
             WHERE token_sha256 = :token_sha256",
            array(':token_sha256' => hash('sha256', $token))
        );
        return self::tokenRow($row);
    }

    /**
     * @param int $tokenId
     * @return array<string, mixed>|null
     */
    private static function lockToken($tokenId) {
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT token_id, key_id, owner_org_id, owner_context_id, created_by_user_id,
                    tool_url, used_at, registration_id,
                    CASE WHEN expires_at > NOW() THEN 1 ELSE 0 END AS fresh
             FROM {$p}lti_tool_registration_token
             WHERE token_id = :token_id
             FOR UPDATE",
            array(':token_id' => (int) $tokenId)
        );
        return self::tokenRow($row);
    }

    /**
     * @param mixed $row
     * @return array<string, mixed>|null
     */
    private static function tokenRow($row) {
        if ( ! is_array($row) ) {
            return null;
        }
        return array(
            'token_id' => (int) $row['token_id'],
            'key_id' => (int) $row['key_id'],
            'owner_org_id' => $row['owner_org_id'] === null ? null : (int) $row['owner_org_id'],
            'owner_context_id' => $row['owner_context_id'] === null ? null : (int) $row['owner_context_id'],
            'created_by_user_id' => $row['created_by_user_id'] === null ? null : (int) $row['created_by_user_id'],
            'tool_url' => (string) $row['tool_url'],
            'used_at' => $row['used_at'],
            'registration_id' => $row['registration_id'] === null ? null : (int) $row['registration_id'],
            'fresh' => (int) $row['fresh'],
        );
    }

    /**
     * @param array<string, mixed> $row
     * @param string $token
     * @return bool
     */
    private static function usable(array $row, $token) {
        if ( (int) $row['fresh'] !== 1 || $row['used_at'] !== null ) {
            return false;
        }
        return self::prefixFresh($token);
    }

    /**
     * Sakai stamps the token as unix-time, a colon, then the secret.
     *
     * @param string $token
     * @return bool
     */
    private static function prefixFresh($token) {
        $parts = explode(':', $token, 2);
        if ( count($parts) < 2 || ! ctype_digit($parts[0]) ) {
            return false;
        }
        $delta = time() - (int) $parts[0];
        return $delta >= 0 && $delta <= self::TOKEN_SECONDS;
    }

    /**
     * @param int $tokenId
     * @param int $registrationId
     * @return void
     */
    private static function consume($tokenId, $registrationId) {
        $p = self::prefix();
        $stmt = self::db()->queryReturnError(
            "UPDATE {$p}lti_tool_registration_token
             SET used_at = NOW(), registration_id = :registration_id
             WHERE token_id = :token_id AND used_at IS NULL",
            array(
                ':registration_id' => (int) $registrationId,
                ':token_id' => (int) $tokenId,
            )
        );
        if ( ! $stmt->success || $stmt->rowCount() < 1 ) {
            throw new \RuntimeException('Could not consume registration token.');
        }
    }

    /**
     * @param int $status
     * @param string $message
     * @return array{status:int, document:array<string, mixed>}
     */
    private static function error($status, $message) {
        return array(
            'status' => (int) $status,
            'document' => array('error' => $message),
        );
    }

    /**
     * @param string $url
     * @param string $label
     * @return string
     */
    private static function requireHttpUrl($url, $label) {
        $checked = self::httpUrlOrNull($url);
        if ( $checked === null ) {
            throw new \InvalidArgumentException($label.' must be an http URL.');
        }
        return $checked;
    }

    /**
     * @param mixed $url
     * @return string|null
     */
    private static function httpUrlOrNull($url) {
        if ( ! is_string($url) ) {
            return null;
        }
        $url = trim($url);
        if ( $url === '' || strlen($url) > 2048 ) {
            return null;
        }
        if ( ! preg_match('#^https?://#i', $url) ) {
            return null;
        }
        if ( preg_match('/[[:cntrl:][:space:]"\'<>`\\\\]/', $url) ) {
            return null;
        }
        $parts = parse_url($url);
        if ( ! is_array($parts) || ! isset($parts['host']) || ! is_string($parts['host']) || $parts['host'] === '' ) {
            return null;
        }
        return $url;
    }

    /**
     * @return string
     */
    private static function uuid() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        $hex = bin2hex($data);
        return substr($hex, 0, 8).'-'.substr($hex, 8, 4).'-'.substr($hex, 12, 4).'-'.substr($hex, 16, 4).'-'.substr($hex, 20, 12);
    }

    /**
     * @param int $contextId
     * @return array{context_id:int, key_id:int}|null
     */
    private static function findContext($contextId) {
        if ( (int) $contextId < 1 ) {
            return null;
        }
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT context_id, key_id FROM {$p}lti_context WHERE context_id = :context_id",
            array(':context_id' => (int) $contextId)
        );
        if ( ! is_array($row) ) {
            return null;
        }
        return array(
            'context_id' => (int) $row['context_id'],
            'key_id' => (int) $row['key_id'],
        );
    }

    /**
     * @param mixed $userId
     * @param int $keyId
     * @return int|null
     */
    private static function creatorOnKey($userId, $keyId) {
        $userId = (int) $userId;
        if ( $userId < 1 ) {
            return null;
        }
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT user_id, key_id FROM {$p}lti_user WHERE user_id = :user_id",
            array(':user_id' => $userId)
        );
        if ( ! is_array($row) || (int) $row['key_id'] !== (int) $keyId ) {
            return null;
        }
        return $userId;
    }

    /**
     * @return string
     */
    private static function root() {
        global $CFG;
        return rtrim((string) $CFG->wwwroot, '/');
    }

    /**
     * @return \Tsugi\Util\PDOX
     */
    private static function db() {
        global $CFG, $PDOX;
        LTIX::getConnection();
        if ( ! isset($CFG) || ! is_object($CFG) || ! isset($PDOX) || ! is_object($PDOX) ) {
            throw new \RuntimeException('Database is not available.');
        }
        return $PDOX;
    }

    /**
     * @return string
     */
    private static function prefix() {
        global $CFG;
        return (string) $CFG->dbprefix;
    }
}
