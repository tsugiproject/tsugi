<?php

namespace Tsugi\Services\Outbound;

use Tsugi\Core\LTIX;
use Tsugi\Services\Org\OrgService;

/**
 * Deploys one outbound registration to an org, a course, or the whole key.
 *
 * Both org and course empty means the tool is deployed to the key. key_id
 * stays set. A course sees that key deployment, a direct course deployment,
 * and deployments on its org and that org's ancestors. A course with no org
 * still sees the key deployment and its direct deployments.
 * That lookup is one statement: the ancestor walk is a recursive CTE inside
 * the same query, not a list of ids loaded first.
 */
class ToolDeploymentService {

    /**
     * @param int $registrationId
     * @param int|null $orgId org, course, or neither for the whole key
     * @param int|null $contextId
     * @param string|null $deploymentId future LTI deployment identifier
     * @return int tool_deployment_id
     */
    public static function createDeployment($registrationId, $orgId, $contextId, $deploymentId = null) {
        $PDOX = self::db();
        $p = self::prefix();
        $registrationId = (int) $registrationId;
        $orgId = self::optionalId($orgId);
        $contextId = self::optionalId($contextId);
        if ( $orgId !== null && $contextId !== null ) {
            throw new \InvalidArgumentException('A deployment targets an organization, a course, or the key.');
        }

        $registration = ToolRegistrationService::findRegistration($registrationId);
        if ( $registration === null ) {
            throw new \InvalidArgumentException('Tool registration was not found.');
        }

        if ( $orgId === null && $contextId === null && $registration['org_id'] !== null ) {
            throw new \InvalidArgumentException('An organization-scoped registration cannot be deployed to the whole key.');
        }
        if ( $orgId !== null && ! ToolRegistrationService::canDeployToOrg($registrationId, $orgId) ) {
            throw new \InvalidArgumentException('This registration cannot be deployed to that organization.');
        }
        if ( $contextId !== null && ! ToolRegistrationService::canDeployToContext($registrationId, $contextId) ) {
            throw new \InvalidArgumentException('This registration cannot be deployed to that course.');
        }

        $deploymentId = self::optionalDeploymentId($deploymentId);
        if ( self::findExisting($registrationId, $orgId, $contextId) !== null ) {
            throw new \InvalidArgumentException('This registration is already deployed to that target.');
        }

        $keyLevel = ($orgId === null && $contextId === null) ? 1 : null;
        $stmt = $PDOX->queryReturnError(
            "INSERT INTO {$p}lti_tool_deployment
                (registration_id, key_id, org_id, context_id, key_level, deployment_id, created_at)
             VALUES
                (:registration_id, :key_id, :org_id, :context_id, :key_level, :deployment_id, NOW())",
            array(
                ':registration_id' => $registrationId,
                ':key_id' => (int) $registration['key_id'],
                ':org_id' => $orgId,
                ':context_id' => $contextId,
                ':key_level' => $keyLevel,
                ':deployment_id' => $deploymentId,
            )
        );
        if ( ! $stmt->success ) {
            $detail = isset($stmt->errorImplode) ? (string) $stmt->errorImplode : 'database error';
            if ( stripos($detail, 'Duplicate') !== false ) {
                throw new \InvalidArgumentException('This registration is already deployed to that target.');
            }
            throw new \RuntimeException('Could not create deployment. '.$detail);
        }
        $id = (int) $PDOX->lastInsertId();
        if ( $id < 1 ) {
            throw new \RuntimeException('Could not create deployment.');
        }
        return $id;
    }

    /**
     * Deployment rows that apply to a course: a direct row, plus rows on the
     * course org and its ancestors.
     *
     * @param int $contextId
     * @return array<int, array<string, mixed>>
     */
    public static function getDeploymentsForContext($contextId) {
        return self::rowsForContext((int) $contextId, false);
    }

    /**
     * Registrations visible to a course. The same registration is returned once
     * even when both a direct deployment and an ancestor deployment exist.
     *
     * @param int $contextId
     * @return array<int, array<string, mixed>>
     */
    public static function getRegistrationsForContext($contextId) {
        return self::rowsForContext((int) $contextId, true);
    }

    /**
     * Direct course deployments, key deployments, and org deployments on the
     * course org and its ancestors. The ancestor walk is a recursive CTE in
     * this statement. A course with no org still matches a key deployment.
     *
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
        $visible = 'd.key_id = c.key_id AND (d.context_id = c.context_id OR '.$scope->in('d.org_id').' OR (d.org_id IS NULL AND d.context_id IS NULL))';

        if ( $registrations ) {
            $sql = "WITH RECURSIVE ".$scope->cte()."
                SELECT DISTINCT c.context_id AS scope_context_id,
                       r.registration_id, r.key_id, r.org_id, r.title
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
                       d.tool_deployment_id, d.registration_id, d.key_id,
                       d.org_id, d.context_id, d.deployment_id,
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
                    'org_id' => $row['org_id'] === null ? null : (int) $row['org_id'],
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
                    'org_id' => $row['org_id'] === null ? null : (int) $row['org_id'],
                    'context_id' => $row['context_id'] === null ? null : (int) $row['context_id'],
                    'deployment_id' => $row['deployment_id'] === null ? null : (string) $row['deployment_id'],
                );
            }
        }
        return $out;
    }

    /**
     * @param int $registrationId
     * @param int|null $orgId
     * @param int|null $contextId
     * @return array<string, mixed>|null
     */
    private static function findExisting($registrationId, $orgId, $contextId) {
        $p = self::prefix();
        if ( $orgId !== null ) {
            $sql = "SELECT tool_deployment_id FROM {$p}lti_tool_deployment
                WHERE registration_id = :registration_id AND org_id = :org_id";
            $params = array(':registration_id' => $registrationId, ':org_id' => $orgId);
        } else if ( $contextId !== null ) {
            $sql = "SELECT tool_deployment_id FROM {$p}lti_tool_deployment
                WHERE registration_id = :registration_id AND context_id = :context_id";
            $params = array(':registration_id' => $registrationId, ':context_id' => $contextId);
        } else {
            $sql = "SELECT tool_deployment_id FROM {$p}lti_tool_deployment
                WHERE registration_id = :registration_id
                  AND org_id IS NULL AND context_id IS NULL";
            $params = array(':registration_id' => $registrationId);
        }
        $row = self::db()->rowDie($sql, $params);
        return is_array($row) ? $row : null;
    }

    /**
     * @param mixed $id
     * @return int|null
     */
    private static function optionalId($id) {
        if ( $id === null ) {
            return null;
        }
        $id = (int) $id;
        if ( $id < 1 ) {
            throw new \InvalidArgumentException('A deployment targets an organization or a course.');
        }
        return $id;
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
