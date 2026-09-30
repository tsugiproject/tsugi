<?php

namespace Tsugi\Services\Outbound;

use Tsugi\Core\LTIX;
use Tsugi\Services\Org\OrgService;

/**
 * Outbound LTI tool registration owned by one tenant key.
 *
 * org_id null means the registration may be deployed anywhere in that key.
 * A set org_id is the top of the subtree it may be deployed into. The
 * registration itself does not make the tool visible. Deployments do that.
 *
 * A registration is LTI 1.3 unless lti_version is 1.1. An LTI 1.1 registration
 * stores lti11_key, lti11_secret, and lti11_url, and leaves the LTI 1.3
 * columns empty.
 */
class ToolRegistrationService {

    /**
     * @param int $keyId tenant key
     * @param string $title
     * @param int|null $orgId null scopes the registration to the whole tenant
     * @param int|null $createdByUserId
     * @param array<string, mixed> $meta lti_version, lti11_key, lti11_secret, lti11_url, client_id, oidc_login_url, jwks_url, launch_url, redirect_uri, json
     * @return int registration_id
     */
    public static function createRegistration($keyId, $title, $orgId = null, $createdByUserId = null, array $meta = array()) {
        $PDOX = self::db();
        $p = self::prefix();
        $keyId = self::requireId($keyId, 'Tenant key');
        $title = trim((string) $title);
        if ( $title === '' ) {
            throw new \InvalidArgumentException('Registration title is required.');
        }
        if ( mb_strlen($title) > 512 ) {
            throw new \InvalidArgumentException('Registration title is too long.');
        }
        self::requireKey($keyId);

        $orgId = self::optionalOrgId($orgId);
        if ( $orgId !== null ) {
            $org = self::requireOrg($orgId);
            if ( (int) $org['key_id'] !== $keyId ) {
                throw new \InvalidArgumentException('Registration scope belongs to a different tenant.');
            }
        }

        $createdByUserId = self::optionalUserId($createdByUserId, $keyId);
        $fields = self::metaFields($meta);

        $stmt = $PDOX->queryReturnError(
            "INSERT INTO {$p}lti_tool_registration
                (key_id, org_id, created_by_user_id, title, lti_version, lti11_key, lti11_secret, lti11_url,
                 client_id, oidc_login_url, jwks_url, launch_url, redirect_uri, json, created_at)
             VALUES
                (:key_id, :org_id, :created_by_user_id, :title, :lti_version, :lti11_key, :lti11_secret, :lti11_url,
                 :client_id, :oidc_login_url, :jwks_url, :launch_url, :redirect_uri, :json, NOW())",
            array(
                ':key_id' => $keyId,
                ':org_id' => $orgId,
                ':created_by_user_id' => $createdByUserId,
                ':title' => $title,
                ':lti_version' => $fields['lti_version'],
                ':lti11_key' => $fields['lti11_key'],
                ':lti11_secret' => $fields['lti11_secret'],
                ':lti11_url' => $fields['lti11_url'],
                ':client_id' => $fields['client_id'],
                ':oidc_login_url' => $fields['oidc_login_url'],
                ':jwks_url' => $fields['jwks_url'],
                ':launch_url' => $fields['launch_url'],
                ':redirect_uri' => $fields['redirect_uri'],
                ':json' => $fields['json'],
            )
        );
        if ( ! $stmt->success ) {
            $detail = isset($stmt->errorImplode) ? (string) $stmt->errorImplode : 'database error';
            throw new \RuntimeException('Could not create tool registration. '.$detail);
        }
        $id = (int) $PDOX->lastInsertId();
        if ( $id < 1 ) {
            throw new \RuntimeException('Could not create tool registration.');
        }
        return $id;
    }

    /**
     * @param int $registrationId
     * @param int $orgId
     * @return bool
     */
    public static function canDeployToOrg($registrationId, $orgId) {
        $registration = self::findRegistration((int) $registrationId);
        $org = self::findOrg((int) $orgId);
        if ( $registration === null || $org === null ) {
            return false;
        }
        if ( (int) $registration['key_id'] !== (int) $org['key_id'] ) {
            return false;
        }
        if ( $registration['org_id'] === null ) {
            return true;
        }
        return OrgService::isDescendantOrSelf((int) $org['org_id'], (int) $registration['org_id']);
    }

    /**
     * A course with no org can receive a tenant-wide registration.
     * An org-scoped registration requires the course's org to be inside that subtree.
     *
     * @param int $registrationId
     * @param int $contextId
     * @return bool
     */
    public static function canDeployToContext($registrationId, $contextId) {
        $registration = self::findRegistration((int) $registrationId);
        $context = self::findContext((int) $contextId);
        if ( $registration === null || $context === null ) {
            return false;
        }
        if ( (int) $registration['key_id'] !== (int) $context['key_id'] ) {
            return false;
        }
        if ( $registration['org_id'] === null ) {
            return true;
        }
        if ( $context['org_id'] === null ) {
            return false;
        }
        return OrgService::isDescendantOrSelf((int) $context['org_id'], (int) $registration['org_id']);
    }

    /**
     * @param int $registrationId
     * @return array<string, mixed>|null
     */
    public static function findRegistration($registrationId) {
        if ( (int) $registrationId < 1 ) {
            return null;
        }
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT registration_id, key_id, org_id, title
             FROM {$p}lti_tool_registration
             WHERE registration_id = :registration_id",
            array(':registration_id' => (int) $registrationId)
        );
        if ( ! is_array($row) ) {
            return null;
        }
        return array(
            'registration_id' => (int) $row['registration_id'],
            'key_id' => (int) $row['key_id'],
            'org_id' => $row['org_id'] === null ? null : (int) $row['org_id'],
            'title' => (string) $row['title'],
        );
    }

    /**
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    private static function metaFields(array $meta) {
        $allowed = array(
            'lti_version', 'lti11_key', 'lti11_secret', 'lti11_url',
            'client_id', 'oidc_login_url', 'jwks_url', 'launch_url', 'redirect_uri', 'json',
        );
        foreach ( $meta as $key => $_value ) {
            if ( ! in_array($key, $allowed, true) ) {
                throw new \InvalidArgumentException('Unknown registration field.');
            }
        }
        $out = array();
        foreach ( $allowed as $key ) {
            if ( ! array_key_exists($key, $meta) || $meta[$key] === null || $meta[$key] === '' ) {
                $out[$key] = null;
                continue;
            }
            if ( $key === 'json' && (is_array($meta[$key]) || is_object($meta[$key])) ) {
                $encoded = json_encode($meta[$key]);
                if ( ! is_string($encoded) ) {
                    throw new \InvalidArgumentException('Registration json could not be encoded.');
                }
                $out[$key] = $encoded;
                continue;
            }
            if ( $key === 'client_id' || $key === 'lti11_key' ) {
                $client = trim((string) $meta[$key]);
                if ( strlen($client) > 255 ) {
                    throw new \InvalidArgumentException('Registration '.$key.' is too long.');
                }
                $out[$key] = $client === '' ? null : $client;
                continue;
            }
            $out[$key] = trim((string) $meta[$key]);
            if ( $out[$key] === '' ) {
                $out[$key] = null;
            }
        }
        return self::requireOneProtocol($out);
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    private static function requireOneProtocol(array $fields) {
        $version = $fields['lti_version'] === null ? '1.3' : (string) $fields['lti_version'];
        if ( $version !== '1.1' && $version !== '1.3' ) {
            throw new \InvalidArgumentException('Registration lti_version must be 1.1 or 1.3.');
        }
        $fields['lti_version'] = $version;
        $lti11 = array('lti11_key', 'lti11_secret', 'lti11_url');
        $lti13 = array('client_id', 'oidc_login_url', 'jwks_url', 'launch_url', 'redirect_uri');
        if ( $version === '1.1' ) {
            foreach ( $lti11 as $key ) {
                if ( $fields[$key] === null ) {
                    throw new \InvalidArgumentException('An LTI 1.1 registration requires a key, secret, and URL.');
                }
            }
            foreach ( $lti13 as $key ) {
                if ( $fields[$key] !== null ) {
                    throw new \InvalidArgumentException('An LTI 1.1 registration does not store LTI 1.3 fields.');
                }
            }
            return $fields;
        }
        foreach ( $lti11 as $key ) {
            if ( $fields[$key] !== null ) {
                throw new \InvalidArgumentException('An LTI 1.3 registration does not store an LTI 1.1 key, secret, or URL.');
            }
        }
        return $fields;
    }

    /**
     * @param int $orgId
     * @return array<string, mixed>
     */
    private static function requireOrg($orgId) {
        $org = self::findOrg($orgId);
        if ( $org === null ) {
            throw new \InvalidArgumentException('Organization was not found.');
        }
        return $org;
    }

    /**
     * @param int $orgId
     * @return array<string, mixed>|null
     */
    private static function findOrg($orgId) {
        if ( (int) $orgId < 1 ) {
            return null;
        }
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT org_id, key_id FROM {$p}lti_org WHERE org_id = :org_id",
            array(':org_id' => (int) $orgId)
        );
        if ( ! is_array($row) ) {
            return null;
        }
        return array(
            'org_id' => (int) $row['org_id'],
            'key_id' => (int) $row['key_id'],
        );
    }

    /**
     * @param int $contextId
     * @return array<string, mixed>|null
     */
    private static function findContext($contextId) {
        if ( (int) $contextId < 1 ) {
            return null;
        }
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT context_id, key_id, org_id FROM {$p}lti_context WHERE context_id = :context_id",
            array(':context_id' => (int) $contextId)
        );
        if ( ! is_array($row) ) {
            return null;
        }
        return array(
            'context_id' => (int) $row['context_id'],
            'key_id' => (int) $row['key_id'],
            'org_id' => $row['org_id'] === null ? null : (int) $row['org_id'],
        );
    }

    /**
     * @param int $keyId
     * @return void
     */
    private static function requireKey($keyId) {
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT key_id FROM {$p}lti_key WHERE key_id = :key_id",
            array(':key_id' => (int) $keyId)
        );
        if ( ! is_array($row) ) {
            throw new \InvalidArgumentException('Tenant key was not found.');
        }
    }

    /**
     * @param mixed $userId
     * @param int $keyId
     * @return int|null
     */
    private static function optionalUserId($userId, $keyId) {
        if ( $userId === null ) {
            return null;
        }
        $userId = (int) $userId;
        if ( $userId < 1 ) {
            throw new \InvalidArgumentException('User was not found.');
        }
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT user_id, key_id FROM {$p}lti_user WHERE user_id = :user_id",
            array(':user_id' => $userId)
        );
        if ( ! is_array($row) ) {
            throw new \InvalidArgumentException('User was not found.');
        }
        if ( (int) $row['key_id'] !== (int) $keyId ) {
            throw new \InvalidArgumentException('User belongs to a different tenant.');
        }
        return $userId;
    }

    /**
     * @param mixed $orgId
     * @return int|null
     */
    private static function optionalOrgId($orgId) {
        if ( $orgId === null ) {
            return null;
        }
        $orgId = (int) $orgId;
        if ( $orgId < 1 ) {
            throw new \InvalidArgumentException('Organization was not found.');
        }
        return $orgId;
    }

    /**
     * @param mixed $id
     * @param string $label
     * @return int
     */
    private static function requireId($id, $label) {
        $id = (int) $id;
        if ( $id < 1 ) {
            throw new \InvalidArgumentException($label.' is required.');
        }
        return $id;
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
