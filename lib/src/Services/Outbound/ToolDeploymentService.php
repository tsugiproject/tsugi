<?php

namespace Tsugi\Services\Outbound;

use Tsugi\Core\LTIX;
use Tsugi\Services\Org\OrgService;

/**
 * Deploys one outbound registration to exactly one org or exactly one course.
 *
 * A course sees the union of its direct deployments and deployments on its
 * org and that org's ancestors. A course with no org sees only direct deployments.
 */
class ToolDeploymentService {

    /**
     * @param int $registrationId
     * @param int|null $orgId set this or $contextId, never both
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
        if ( ($orgId === null) === ($contextId === null) ) {
            throw new \InvalidArgumentException('A deployment targets an organization or a course.');
        }

        $registration = ToolRegistrationService::findRegistration($registrationId);
        if ( $registration === null ) {
            throw new \InvalidArgumentException('Tool registration was not found.');
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

        $stmt = $PDOX->queryReturnError(
            "INSERT INTO {$p}lti_tool_deployment
                (registration_id, key_id, org_id, context_id, deployment_id, created_at)
             VALUES
                (:registration_id, :key_id, :org_id, :context_id, :deployment_id, NOW())",
            array(
                ':registration_id' => $registrationId,
                ':key_id' => (int) $registration['key_id'],
                ':org_id' => $orgId,
                ':context_id' => $contextId,
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
        $context = self::requireContext((int) $contextId);
        return self::rowsForContext($context, false);
    }

    /**
     * Registrations visible to a course. The same registration is returned once
     * even when both a direct deployment and an ancestor deployment exist.
     *
     * @param int $contextId
     * @return array<int, array<string, mixed>>
     */
    public static function getRegistrationsForContext($contextId) {
        $context = self::requireContext((int) $contextId);
        return self::rowsForContext($context, true);
    }

    /**
     * @param array<string, mixed> $context
     * @param bool $registrations
     * @return array<int, array<string, mixed>>
     */
    private static function rowsForContext(array $context, $registrations) {
        $PDOX = self::db();
        $p = self::prefix();
        $params = array(
            ':context_id' => (int) $context['context_id'],
            ':key_id' => (int) $context['key_id'],
        );
        $orgClause = '';
        $orgIds = self::ancestorOrgIds($context);
        if ( count($orgIds) > 0 ) {
            $holders = array();
            foreach ( $orgIds as $i => $orgId ) {
                $name = ':org_'.$i;
                $holders[] = $name;
                $params[$name] = (int) $orgId;
            }
            $orgClause = ' OR d.org_id IN ('.implode(', ', $holders).')';
        }

        if ( $registrations ) {
            $sql = "SELECT DISTINCT r.registration_id, r.key_id, r.org_id, r.title
                FROM {$p}lti_tool_registration r
                INNER JOIN {$p}lti_tool_deployment d ON d.registration_id = r.registration_id
                WHERE r.key_id = :key_id
                  AND (d.context_id = :context_id{$orgClause})
                ORDER BY r.title ASC, r.registration_id ASC";
        } else {
            $sql = "SELECT d.tool_deployment_id, d.registration_id, d.key_id, d.org_id, d.context_id, d.deployment_id
                FROM {$p}lti_tool_deployment d
                INNER JOIN {$p}lti_tool_registration r ON r.registration_id = d.registration_id
                WHERE r.key_id = :key_id
                  AND (d.context_id = :context_id{$orgClause})
                ORDER BY d.tool_deployment_id ASC";
        }

        $rows = $PDOX->allRowsDie($sql, $params);
        $out = array();
        foreach ( $rows as $row ) {
            if ( $registrations ) {
                $out[] = array(
                    'registration_id' => (int) $row['registration_id'],
                    'key_id' => (int) $row['key_id'],
                    'org_id' => $row['org_id'] === null ? null : (int) $row['org_id'],
                    'title' => (string) $row['title'],
                );
            } else {
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
     * @param array<string, mixed> $context
     * @return array<int, int>
     */
    private static function ancestorOrgIds(array $context) {
        if ( $context['org_id'] === null ) {
            return array();
        }
        $ids = array();
        foreach ( OrgService::getAncestors((int) $context['org_id']) as $org ) {
            if ( (int) $org['key_id'] !== (int) $context['key_id'] ) {
                continue;
            }
            $ids[] = (int) $org['org_id'];
        }
        return $ids;
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
        } else {
            $sql = "SELECT tool_deployment_id FROM {$p}lti_tool_deployment
                WHERE registration_id = :registration_id AND context_id = :context_id";
            $params = array(':registration_id' => $registrationId, ':context_id' => $contextId);
        }
        $row = self::db()->rowDie($sql, $params);
        return is_array($row) ? $row : null;
    }

    /**
     * @param int $contextId
     * @return array<string, mixed>
     */
    private static function requireContext($contextId) {
        if ( (int) $contextId < 1 ) {
            throw new \InvalidArgumentException('Course is required.');
        }
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT context_id, key_id, org_id FROM {$p}lti_context WHERE context_id = :context_id",
            array(':context_id' => (int) $contextId)
        );
        if ( ! is_array($row) ) {
            throw new \InvalidArgumentException('Course was not found.');
        }
        return array(
            'context_id' => (int) $row['context_id'],
            'key_id' => (int) $row['key_id'],
            'org_id' => $row['org_id'] === null ? null : (int) $row['org_id'],
        );
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
