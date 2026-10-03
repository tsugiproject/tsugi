<?php

namespace Tsugi\Services\Outbound;

use Tsugi\Core\LTIX;
use Tsugi\Services\Org\OrgService;

/**
 * One LTI deployment of an outbound registration, and where it may be used.
 *
 * The deployment row is the LTI deployment_id. It is not owned by one org or
 * one course. Scope is many-to-many: lti_tool_deployment_org and
 * lti_tool_deployment_context. Assigning an organization makes the deployment
 * available to courses in that organization and below it. That lookup is one
 * statement. The ancestor walk is a recursive CTE inside the same query.
 *
 * A deployment with no scope rows is not visible. Blank does not mean the
 * whole tenant. key_id stays on the deployment and on every assignment so a
 * scope cannot leave the tenant.
 *
 * LTI 1.3 may have many deployments, each with its own external deployment_id.
 * LTI 1.1 has exactly one deployment and that deployment_id is null. The row
 * exists so scope lookup is the same for both protocols. The unique key on
 * (registration_id, deployment_id) does not enforce that, because MySQL allows
 * many null deployment ids. createDeployment() does.
 */
class ToolDeploymentService {

    /**
     * @param int $registrationId
     * @param string|null $deploymentId external LTI deployment_id
     * @return int tool_deployment_id
     */
    public static function createDeployment($registrationId, $deploymentId = null) {
        $PDOX = self::db();
        $p = self::prefix();
        $registration = self::requireRegistration($registrationId);
        $deploymentId = self::optionalDeploymentId($deploymentId);
        $lti11 = $registration['lti_version'] === '1.1';
        if ( $lti11 && $deploymentId !== null ) {
            throw new \InvalidArgumentException('An LTI 1.1 deployment has no external deployment id.');
        }

        // Lock the registration so two requests cannot both see zero
        // deployments and insert a second null row. The unique key cannot
        // see that collision.
        $owns = $lti11 && ! $PDOX->inTransaction();
        if ( $owns ) {
            $PDOX->beginTransaction();
        }
        try {
            if ( $lti11 ) {
                self::lockRegistration((int) $registration['registration_id']);
                if ( self::deploymentCount((int) $registration['registration_id']) > 0 ) {
                    throw new \InvalidArgumentException('An LTI 1.1 registration has one deployment.');
                }
            }
            $stmt = $PDOX->queryReturnError(
                "INSERT INTO {$p}lti_tool_deployment
                    (registration_id, key_id, deployment_id, created_at)
                 VALUES
                    (:registration_id, :key_id, :deployment_id, NOW())",
                array(
                    ':registration_id' => (int) $registration['registration_id'],
                    ':key_id' => (int) $registration['key_id'],
                    ':deployment_id' => $deploymentId,
                )
            );
            if ( ! $stmt->success ) {
                $detail = isset($stmt->errorImplode) ? (string) $stmt->errorImplode : 'database error';
                if ( stripos($detail, 'Duplicate') !== false ) {
                    throw new \InvalidArgumentException('This registration already has that deployment id.');
                }
                throw new \RuntimeException('Could not create deployment. '.$detail);
            }
            $id = (int) $PDOX->lastInsertId();
            if ( $id < 1 ) {
                throw new \RuntimeException('Could not create deployment.');
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
     * The registration and its one deployment for an LTI 1.1 key and secret.
     *
     * lti11_key is a stored field, not an identifier. Several registrations in
     * one tenant may share it. The secret picks among those rows. A wrong
     * secret, an unknown key, or more than one row with that key and secret
     * is the same result.
     *
     * @param string $consumerKey
     * @param string $sharedSecret
     * @param int $keyId tenant key
     * @return array{registration_id:int, key_id:int, lti11_key:string, tool_deployment_id:int, deployment_id:null}
     */
    public static function resolveLti11($consumerKey, $sharedSecret, $keyId) {
        $consumerKey = trim((string) $consumerKey);
        if ( $consumerKey === '' ) {
            throw new \InvalidArgumentException('LTI 1.1 consumer key is required.');
        }
        $keyId = (int) $keyId;
        if ( $keyId < 1 ) {
            throw new \InvalidArgumentException('Tenant key is required.');
        }
        $p = self::prefix();
        $rows = self::db()->allRowsDie(
            "SELECT r.registration_id, r.key_id, r.lti11_key, r.lti11_secret,
                    d.tool_deployment_id, d.deployment_id
             FROM {$p}lti_tool_registration r
             INNER JOIN {$p}lti_tool_deployment d
                ON d.registration_id = r.registration_id AND d.key_id = r.key_id
             WHERE r.lti_version = '1.1' AND r.key_id = :key_id AND r.lti11_key = :lti11_key",
            array(
                ':key_id' => $keyId,
                ':lti11_key' => $consumerKey,
            )
        );
        $secret = (string) $sharedSecret;
        $match = null;
        foreach ( $rows as $row ) {
            if ( ! hash_equals((string) $row['lti11_secret'], $secret) ) {
                continue;
            }
            if ( $match !== null ) {
                throw new \InvalidArgumentException('LTI 1.1 registration was not found.');
            }
            $match = $row;
        }
        if ( ! is_array($match) ) {
            throw new \InvalidArgumentException('LTI 1.1 registration was not found.');
        }
        if ( $match['deployment_id'] !== null ) {
            throw new \RuntimeException('An LTI 1.1 deployment must not have an external deployment id.');
        }
        return array(
            'registration_id' => (int) $match['registration_id'],
            'key_id' => (int) $match['key_id'],
            'lti11_key' => (string) $match['lti11_key'],
            'tool_deployment_id' => (int) $match['tool_deployment_id'],
            'deployment_id' => null,
        );
    }

    /**
     * Give each LTI 1.1 registration the one deployment it is missing.
     *
     * Does not change consumer keys, secrets, launch URLs, or an existing
     * deployment_id. A registration that already has a deployment is left
     * alone, including one that already has its single null deployment.
     * Calling this again inserts nothing.
     *
     * @return int number of deployment rows inserted
     */
    public static function ensureLti11Deployments() {
        $p = self::prefix();
        $rows = self::db()->allRowsDie(
            "SELECT r.registration_id
             FROM {$p}lti_tool_registration r
             LEFT JOIN {$p}lti_tool_deployment d
                ON d.registration_id = r.registration_id
             WHERE r.lti_version = '1.1' AND d.tool_deployment_id IS NULL",
            array()
        );
        if ( ! is_array($rows) ) {
            return 0;
        }
        $added = 0;
        foreach ( $rows as $row ) {
            self::createDeployment((int) $row['registration_id'], null);
            $added++;
        }
        return $added;
    }

    /**
     * @param int $toolDeploymentId
     * @param int $orgId
     * @return void
     */
    public static function assignOrg($toolDeploymentId, $orgId) {
        $deployment = self::requireDeployment($toolDeploymentId);
        $orgId = self::requireId($orgId, 'Organization');
        $org = self::findOrg($orgId);
        if ( $org === null ) {
            throw new \InvalidArgumentException('Organization was not found.');
        }
        if ( (int) $org['key_id'] !== (int) $deployment['key_id'] ) {
            throw new \InvalidArgumentException('That organization is in a different tenant.');
        }
        self::insertScope(
            'lti_tool_deployment_org',
            'org_id',
            (int) $deployment['tool_deployment_id'],
            $orgId,
            (int) $deployment['key_id'],
            'This deployment is already assigned to that organization.'
        );
    }

    /**
     * @param int $toolDeploymentId
     * @param int $contextId
     * @return void
     */
    public static function assignContext($toolDeploymentId, $contextId) {
        $deployment = self::requireDeployment($toolDeploymentId);
        $contextId = self::requireId($contextId, 'Course');
        $context = self::findContext($contextId);
        if ( $context === null ) {
            throw new \InvalidArgumentException('Course was not found.');
        }
        if ( (int) $context['key_id'] !== (int) $deployment['key_id'] ) {
            throw new \InvalidArgumentException('That course is in a different tenant.');
        }
        self::insertScope(
            'lti_tool_deployment_context',
            'context_id',
            (int) $deployment['tool_deployment_id'],
            $contextId,
            (int) $deployment['key_id'],
            'This deployment is already assigned to that course.'
        );
    }

    /**
     * The single deployment on a registration.
     *
     * An LTI 1.1 registration has exactly one. Callers that just created
     * that registration use this to assign the course and its grants.
     *
     * @param int $registrationId
     * @return int tool_deployment_id
     */
    public static function onlyDeploymentId($registrationId) {
        $registrationId = (int) $registrationId;
        if ( $registrationId < 1 ) {
            throw new \InvalidArgumentException('Tool registration was not found.');
        }
        $rows = self::db()->allRowsDie(
            "SELECT tool_deployment_id FROM ".self::prefix()."lti_tool_deployment
             WHERE registration_id = :registration_id",
            array(':registration_id' => $registrationId)
        );
        if ( ! is_array($rows) || count($rows) !== 1 ) {
            throw new \RuntimeException('This registration does not have exactly one deployment.');
        }
        return (int) $rows[0]['tool_deployment_id'];
    }

    /**
     * Deployment rows available to a course: an explicit course assignment, or
     * an assignment to the course's organization or one of its ancestors.
     *
     * @param int $contextId
     * @return array<int, array<string, mixed>>
     */
    public static function getDeploymentsForContext($contextId) {
        return self::rowsForContext((int) $contextId, false);
    }

    /**
     * Registrations visible to a course. The same registration is returned once
     * even when more than one of its deployments apply.
     *
     * @param int $contextId
     * @return array<int, array<string, mixed>>
     */
    public static function getRegistrationsForContext($contextId) {
        return self::rowsForContext((int) $contextId, true);
    }

    /**
     * @param int $contextId
     * @param bool $registrations
     * @return array<int, array<string, mixed>>
     */
    private static function rowsForContext($contextId, $registrations) {
        if ( (int) $contextId < 1 ) {
            throw new \InvalidArgumentException('Course is required.');
        }
        $contextId = (int) $contextId;
        $scope = OrgService::contextAncestorScope($contextId);
        $p = self::prefix();
        $params = $scope->params();
        $params[':context_id'] = $contextId;
        $visible = 'd.key_id = c.key_id AND (
            EXISTS (
                SELECT 1 FROM '.$p.'lti_tool_deployment_context scope_context
                WHERE scope_context.tool_deployment_id = d.tool_deployment_id
                  AND scope_context.context_id = c.context_id
                  AND scope_context.key_id = c.key_id
            )
            OR EXISTS (
                SELECT 1 FROM '.$p.'lti_tool_deployment_org scope_org
                WHERE scope_org.tool_deployment_id = d.tool_deployment_id
                  AND scope_org.key_id = c.key_id
                  AND '.$scope->in('scope_org.org_id').'
            )
        )';

        if ( $registrations ) {
            $sql = "WITH RECURSIVE ".$scope->cte()."
                SELECT DISTINCT c.context_id AS scope_context_id,
                       r.registration_id, r.key_id, r.owner_org_id, r.owner_context_id, r.title
                FROM {$p}lti_context c
                LEFT JOIN {$p}lti_tool_deployment d
                    ON {$visible}
                LEFT JOIN {$p}lti_tool_registration r
                    ON r.registration_id = d.registration_id
                   AND r.key_id = c.key_id
                WHERE c.context_id = :context_id
                ORDER BY r.title ASC, r.registration_id ASC";
        } else {
            $sql = "WITH RECURSIVE ".$scope->cte()."
                SELECT c.context_id AS scope_context_id,
                       d.tool_deployment_id, d.registration_id, d.key_id, d.deployment_id,
                       r.registration_id AS matched_registration_id
                FROM {$p}lti_context c
                LEFT JOIN {$p}lti_tool_deployment d
                    ON {$visible}
                LEFT JOIN {$p}lti_tool_registration r
                    ON r.registration_id = d.registration_id
                   AND r.key_id = c.key_id
                WHERE c.context_id = :context_id
                ORDER BY d.tool_deployment_id ASC";
        }

        $rows = self::db()->allRowsDie($sql, $params);
        if ( ! is_array($rows) || count($rows) < 1 ) {
            throw new \InvalidArgumentException('Course was not found.');
        }

        $out = array();
        foreach ( $rows as $row ) {
            if ( $registrations ) {
                if ( $row['registration_id'] === null ) {
                    continue;
                }
                $out[] = array(
                    'registration_id' => (int) $row['registration_id'],
                    'key_id' => (int) $row['key_id'],
                    'owner_org_id' => $row['owner_org_id'] === null ? null : (int) $row['owner_org_id'],
                    'owner_context_id' => $row['owner_context_id'] === null ? null : (int) $row['owner_context_id'],
                    'title' => (string) $row['title'],
                );
            } else {
                if ( $row['tool_deployment_id'] === null || $row['matched_registration_id'] === null ) {
                    continue;
                }
                $out[] = array(
                    'tool_deployment_id' => (int) $row['tool_deployment_id'],
                    'registration_id' => (int) $row['registration_id'],
                    'key_id' => (int) $row['key_id'],
                    'deployment_id' => $row['deployment_id'] === null ? null : (string) $row['deployment_id'],
                );
            }
        }
        return $out;
    }

    /**
     * @param int $registrationId
     * @return array<string, mixed>
     */
    private static function requireRegistration($registrationId) {
        $registration = ToolRegistrationService::findRegistration((int) $registrationId);
        if ( $registration === null ) {
            throw new \InvalidArgumentException('Tool registration was not found.');
        }
        return $registration;
    }

    /**
     * @param int $toolDeploymentId
     * @return array<string, mixed>
     */
    private static function requireDeployment($toolDeploymentId) {
        $toolDeploymentId = self::requireId($toolDeploymentId, 'Deployment');
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT tool_deployment_id, registration_id, key_id, deployment_id
             FROM {$p}lti_tool_deployment
             WHERE tool_deployment_id = :tool_deployment_id",
            array(':tool_deployment_id' => $toolDeploymentId)
        );
        if ( ! is_array($row) ) {
            throw new \InvalidArgumentException('Deployment was not found.');
        }
        return array(
            'tool_deployment_id' => (int) $row['tool_deployment_id'],
            'registration_id' => (int) $row['registration_id'],
            'key_id' => (int) $row['key_id'],
            'deployment_id' => $row['deployment_id'] === null ? null : (string) $row['deployment_id'],
        );
    }

    /**
     * @param int $registrationId
     * @return void
     */
    private static function lockRegistration($registrationId) {
        $p = self::prefix();
        self::db()->rowDie(
            "SELECT registration_id FROM {$p}lti_tool_registration
             WHERE registration_id = :registration_id
             FOR UPDATE",
            array(':registration_id' => $registrationId)
        );
    }

    /**
     * @param int $registrationId
     * @return int
     */
    private static function deploymentCount($registrationId) {
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT COUNT(*) AS n FROM {$p}lti_tool_deployment
             WHERE registration_id = :registration_id",
            array(':registration_id' => $registrationId)
        );
        return is_array($row) ? (int) $row['n'] : 0;
    }

    /**
     * @param string $table unprefixed table name
     * @param string $targetColumn org_id or context_id
     * @param int $toolDeploymentId
     * @param int $targetId
     * @param int $keyId copied from the deployment, not from the caller
     * @param string $duplicate
     * @return void
     */
    private static function insertScope($table, $targetColumn, $toolDeploymentId, $targetId, $keyId, $duplicate) {
        $p = self::prefix();
        $existing = self::db()->rowDie(
            "SELECT tool_deployment_id FROM {$p}{$table}
             WHERE tool_deployment_id = :tool_deployment_id AND {$targetColumn} = :target_id",
            array(
                ':tool_deployment_id' => $toolDeploymentId,
                ':target_id' => $targetId,
            )
        );
        if ( is_array($existing) ) {
            throw new \InvalidArgumentException($duplicate);
        }
        $stmt = self::db()->queryReturnError(
            "INSERT INTO {$p}{$table}
                (tool_deployment_id, {$targetColumn}, key_id, created_at)
             VALUES
                (:tool_deployment_id, :target_id, :key_id, NOW())",
            array(
                ':tool_deployment_id' => $toolDeploymentId,
                ':target_id' => $targetId,
                ':key_id' => $keyId,
            )
        );
        if ( $stmt->success ) {
            return;
        }
        $detail = isset($stmt->errorImplode) ? (string) $stmt->errorImplode : 'database error';
        if ( stripos($detail, 'Duplicate') !== false ) {
            throw new \InvalidArgumentException($duplicate);
        }
        throw new \RuntimeException('Could not assign deployment. '.$detail);
    }

    /**
     * @param int $orgId
     * @return array<string, mixed>|null
     */
    private static function findOrg($orgId) {
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
     * @param mixed $deploymentId
     * @return string|null
     */
    private static function optionalDeploymentId($deploymentId) {
        if ( $deploymentId === null ) {
            return null;
        }
        $deploymentId = trim((string) $deploymentId);
        if ( $deploymentId === '' ) {
            return null;
        }
        if ( strlen($deploymentId) > 255 ) {
            throw new \InvalidArgumentException('Deployment id is too long.');
        }
        return $deploymentId;
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
