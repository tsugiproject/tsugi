<?php

namespace Tsugi\Services\Outbound;

use Tsugi\Core\LTIX;
use Tsugi\Services\Org\OrgService;

/**
 * Outbound LTI tool registration inside one tenant key.
 *
 * key_id is the tenant boundary. owner_org_id and owner_context_id say who
 * administers the registration. They do not say where a deployment may be
 * used. Both null means the tenant administers it. One of them may be set.
 * The registration itself does not make the tool visible. Deployments do that.
 *
 * A registration is LTI 1.3 unless lti_version is 1.1. An LTI 1.1 registration
 * stores lti11_key, lti11_secret, and lti11_url, leaves every lti13_ column
 * null, and gets exactly one deployment whose external deployment_id is null.
 * An LTI 1.3 registration stores lti13_ columns and leaves the lti11_ columns
 * null. lti13_client_id is optional. The registration document can arrive later.
 */
class ToolRegistrationService {

    /**
     * @param int $keyId tenant key
     * @param string $title
     * @param int|null $ownerOrgId null with a null course means the tenant administers it
     * @param int|null $createdByUserId
     * @param array<string, mixed> $meta lti_version, lti11_key, lti11_secret, lti11_url, lti13_client_id, lti13_oidc_login_url, lti13_jwks_url, lti13_launch_url, lti13_redirect_uri, json
     * @param int|null $ownerContextId course administrator; mutually exclusive with $ownerOrgId
     * @return int registration_id
     */
    public static function createRegistration($keyId, $title, $ownerOrgId = null, $createdByUserId = null, array $meta = array(), $ownerContextId = null) {
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

        $ownerOrgId = self::optionalOrgId($ownerOrgId);
        $ownerContextId = self::optionalContextId($ownerContextId);
        if ( $ownerOrgId !== null && $ownerContextId !== null ) {
            throw new \InvalidArgumentException('A registration is administered by the tenant, one organization, or one course.');
        }
        if ( $ownerOrgId !== null ) {
            $org = self::requireOrg($ownerOrgId);
            if ( (int) $org['key_id'] !== $keyId ) {
                throw new \InvalidArgumentException('Registration owner belongs to a different tenant.');
            }
        }
        if ( $ownerContextId !== null ) {
            $context = self::findContext($ownerContextId);
            if ( $context === null ) {
                throw new \InvalidArgumentException('Course was not found.');
            }
            if ( (int) $context['key_id'] !== $keyId ) {
                throw new \InvalidArgumentException('Registration owner belongs to a different tenant.');
            }
        }

        $createdByUserId = self::optionalUserId($createdByUserId, $keyId);
        $fields = self::metaFields($meta);
        $shim = $fields['lti_version'] === '1.1';
        $owns = $shim && ! $PDOX->inTransaction();
        if ( $owns ) {
            $PDOX->beginTransaction();
        }

        try {
            if ( $fields['lti11_key'] !== null ) {
                $existing = $PDOX->rowDie(
                    "SELECT registration_id FROM {$p}lti_tool_registration
                     WHERE key_id = :key_id AND lti11_key = :lti11_key",
                    array(
                        ':key_id' => $keyId,
                        ':lti11_key' => $fields['lti11_key'],
                    )
                );
                if ( is_array($existing) ) {
                    throw new \InvalidArgumentException('This tenant already has that LTI 1.1 consumer key.');
                }
            }
            $stmt = $PDOX->queryReturnError(
                "INSERT INTO {$p}lti_tool_registration
                    (key_id, owner_org_id, owner_context_id, created_by_user_id, title, lti_version, lti11_key, lti11_secret, lti11_url,
                     lti13_client_id, lti13_oidc_login_url, lti13_jwks_url, lti13_launch_url, lti13_redirect_uri, json, created_at)
                 VALUES
                    (:key_id, :owner_org_id, :owner_context_id, :created_by_user_id, :title, :lti_version, :lti11_key, :lti11_secret, :lti11_url,
                     :lti13_client_id, :lti13_oidc_login_url, :lti13_jwks_url, :lti13_launch_url, :lti13_redirect_uri, :json, NOW())",
                array(
                    ':key_id' => $keyId,
                    ':owner_org_id' => $ownerOrgId,
                    ':owner_context_id' => $ownerContextId,
                    ':created_by_user_id' => $createdByUserId,
                    ':title' => $title,
                    ':lti_version' => $fields['lti_version'],
                    ':lti11_key' => $fields['lti11_key'],
                    ':lti11_secret' => $fields['lti11_secret'],
                    ':lti11_url' => $fields['lti11_url'],
                    ':lti13_client_id' => $fields['lti13_client_id'],
                    ':lti13_oidc_login_url' => $fields['lti13_oidc_login_url'],
                    ':lti13_jwks_url' => $fields['lti13_jwks_url'],
                    ':lti13_launch_url' => $fields['lti13_launch_url'],
                    ':lti13_redirect_uri' => $fields['lti13_redirect_uri'],
                    ':json' => $fields['json'],
                )
            );
            if ( ! $stmt->success ) {
                $detail = isset($stmt->errorImplode) ? (string) $stmt->errorImplode : 'database error';
                if ( $fields['lti11_key'] !== null && stripos($detail, 'Duplicate') !== false ) {
                    throw new \InvalidArgumentException('This tenant already has that LTI 1.1 consumer key.');
                }
                throw new \RuntimeException('Could not create tool registration. '.$detail);
            }
            $id = (int) $PDOX->lastInsertId();
            if ( $id < 1 ) {
                throw new \RuntimeException('Could not create tool registration.');
            }
            if ( $shim ) {
                ToolDeploymentService::createDeployment($id, null);
            }
            if ( $owns ) {
                $PDOX->commit();
            }
        } catch ( \Throwable $ex ) {
            if ( $owns && $PDOX->inTransaction() ) {
                $PDOX->rollBack();
            }
            throw $ex;
        }
        return $id;
    }

    /**
     * Whether this actor may edit the registration.
     *
     * This is not a new permissions table. The actor is a scope Tsugi already
     * has: the system administrator, one tenant key, one organization, or one
     * course. A system administrator may manage every registration. A tenant
     * administrator may manage every registration on that key, including
     * org-owned and course-owned ones. An organization administrator may
     * manage a registration owned by that organization or an organization
     * below it. A course administrator may manage a registration owned by
     * that course.
     *
     * @param int $registrationId
     * @param array{system?:bool, key_id?:int, org_id?:int, context_id?:int} $actor
     * @return bool
     */
    public static function mayAdminister($registrationId, array $actor) {
        $registration = self::findRegistration((int) $registrationId);
        if ( $registration === null ) {
            return false;
        }
        if ( ! empty($actor['system']) ) {
            return true;
        }
        $keyId = isset($actor['key_id']) ? (int) $actor['key_id'] : 0;
        if ( $keyId > 0 && $keyId === (int) $registration['key_id'] ) {
            return true;
        }
        $orgId = isset($actor['org_id']) ? (int) $actor['org_id'] : 0;
        if ( $orgId > 0 && $registration['owner_org_id'] !== null ) {
            $org = self::findOrg($orgId);
            if ( $org !== null && (int) $org['key_id'] === (int) $registration['key_id']
                && OrgService::isDescendantOrSelf((int) $registration['owner_org_id'], $orgId) ) {
                return true;
            }
        }
        $contextId = isset($actor['context_id']) ? (int) $actor['context_id'] : 0;
        if ( $contextId > 0 && $registration['owner_context_id'] !== null
            && $contextId === (int) $registration['owner_context_id'] ) {
            $context = self::findContext($contextId);
            if ( $context !== null && (int) $context['key_id'] === (int) $registration['key_id'] ) {
                return true;
            }
        }
        return false;
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
            "SELECT registration_id, key_id, owner_org_id, owner_context_id, title, lti_version
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
            'owner_org_id' => $row['owner_org_id'] === null ? null : (int) $row['owner_org_id'],
            'owner_context_id' => $row['owner_context_id'] === null ? null : (int) $row['owner_context_id'],
            'title' => (string) $row['title'],
            'lti_version' => (string) $row['lti_version'],
        );
    }

    /**
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    private static function metaFields(array $meta) {
        $allowed = array(
            'lti_version', 'lti11_key', 'lti11_secret', 'lti11_url',
            'lti13_client_id', 'lti13_oidc_login_url', 'lti13_jwks_url', 'lti13_launch_url', 'lti13_redirect_uri', 'json',
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
            if ( $key === 'lti13_client_id' || $key === 'lti11_key' ) {
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
        $lti13 = array('lti13_client_id', 'lti13_oidc_login_url', 'lti13_jwks_url', 'lti13_launch_url', 'lti13_redirect_uri');
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
     * @param mixed $contextId
     * @return int|null
     */
    private static function optionalContextId($contextId) {
        if ( $contextId === null ) {
            return null;
        }
        $contextId = (int) $contextId;
        if ( $contextId < 1 ) {
            throw new \InvalidArgumentException('Course was not found.');
        }
        return $contextId;
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
