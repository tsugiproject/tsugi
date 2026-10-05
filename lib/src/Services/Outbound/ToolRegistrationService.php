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
 * Its message rows are synthesized from $meta['messages'] and
 * $meta['placements']. Name, email, grade, and roster checkboxes are
 * stored as $meta['claims'] and $meta['scopes'] on the same registration
 * document an LTI 1.3 registration uses. An LTI 1.3 registration stores lti13_
 * columns and leaves the lti11_ columns null. lti13_client_id is optional.
 * Its messages arrive later in the registration document.
 */
class ToolRegistrationService {

    /**
     * @param int $keyId tenant key
     * @param string $title
     * @param int|null $ownerOrgId null with a null course means the tenant administers it
     * @param int|null $createdByUserId
     * @param array<string, mixed> $meta lti_version, lti11_key, lti11_secret, lti11_url, messages, placements, claims, scopes, lti13_client_id, lti13_oidc_login_url, lti13_jwks_url, lti13_launch_url, lti13_redirect_uri, json
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
        $checkedPlacements = array();
        $sawPlacements = array_key_exists('placements', $meta);
        if ( $sawPlacements ) {
            $checkedPlacements = $meta['placements'];
            unset($meta['placements']);
        }
        $messageTypes = array('LtiResourceLinkRequest');
        $sawMessages = array_key_exists('messages', $meta);
        if ( $sawMessages ) {
            $messageTypes = $meta['messages'];
            unset($meta['messages']);
        }
        $claims = array();
        $sawClaims = array_key_exists('claims', $meta);
        if ( $sawClaims ) {
            $claims = $meta['claims'];
            unset($meta['claims']);
        }
        $scopes = array();
        $sawScopes = array_key_exists('scopes', $meta);
        if ( $sawScopes ) {
            $scopes = $meta['scopes'];
            unset($meta['scopes']);
        }
        $fields = self::metaFields($meta);
        $shim = $fields['lti_version'] === '1.1';
        if ( $shim ) {
            $arranged = ToolMessagePlacement::arrange($messageTypes, $checkedPlacements);
            ToolRegistrationDocument::lti11Document($fields['lti11_url'], $arranged, $claims, $scopes);
        } else if ( $sawPlacements || $sawMessages || $sawClaims || $sawScopes ) {
            throw new \InvalidArgumentException('An LTI 1.3 registration takes launches, placements, claims, and scopes from its registration document.');
        }
        $owns = $shim && ! $PDOX->inTransaction();
        if ( $owns ) {
            $PDOX->beginTransaction();
        }

        try {
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
                throw new \RuntimeException('Could not create tool registration. '.$detail);
            }
            $id = (int) $PDOX->lastInsertId();
            if ( $id < 1 ) {
                throw new \RuntimeException('Could not create tool registration.');
            }
            if ( $shim ) {
                ToolDeploymentService::createDeployment($id, null);
                ToolPlacementService::synthesizeLti11Messages($id, $messageTypes, $checkedPlacements, $claims, $scopes);
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
     * Add an LTI 1.1 tool to one course.
     *
     * The registration is owned by that course. Its one deployment is
     * assigned to that course and to no organization. Checked placements
     * are enabled on the deployment. Checked claims and scopes are both
     * requested and allowed.
     *
     * @param int $contextId
     * @param string $title
     * @param int|null $createdByUserId
     * @param array<string, mixed> $meta
     * @return int registration_id
     */
    public static function createLti11ForCourse($contextId, $title, $createdByUserId, array $meta) {
        $context = self::findContext($contextId);
        if ( $context === null ) {
            throw new \InvalidArgumentException('Course was not found.');
        }
        $meta['lti_version'] = '1.1';
        $createdByUserId = self::creatorOnKey($createdByUserId, (int) $context['key_id']);
        $PDOX = self::db();
        $owns = ! $PDOX->inTransaction();
        if ( $owns ) {
            $PDOX->beginTransaction();
        }
        try {
            $registrationId = self::createRegistration(
                (int) $context['key_id'],
                $title,
                null,
                $createdByUserId,
                $meta,
                (int) $context['context_id']
            );
            $deploymentId = ToolDeploymentService::onlyDeploymentId($registrationId);
            ToolDeploymentService::assignContext($deploymentId, (int) $context['context_id']);
            self::enableAvailablePlacements($deploymentId);
            self::allowRequestedGrants($registrationId, $deploymentId);
            if ( $owns ) {
                $PDOX->commit();
            }
            return $registrationId;
        } catch ( \Throwable $ex ) {
            if ( $owns && $PDOX->inTransaction() ) {
                $PDOX->rollBack();
            }
            throw $ex;
        }
    }

    /**
     * Replace an LTI 1.1 tool this course owns.
     *
     * The course assignment stays. Message rows are rebuilt, so placement
     * enablement is written again and grants follow the new request.
     *
     * @param int $contextId
     * @param int $registrationId
     * @param string $title
     * @param array<string, mixed> $meta
     * @return void
     */
    public static function updateLti11ForCourse($contextId, $registrationId, $title, array $meta) {
        $owned = self::requireOwnedCourseLti11($contextId, $registrationId);
        $title = trim((string) $title);
        if ( $title === '' ) {
            throw new \InvalidArgumentException('Registration title is required.');
        }
        if ( mb_strlen($title) > 512 ) {
            throw new \InvalidArgumentException('Registration title is too long.');
        }
        $meta['lti_version'] = '1.1';
        $split = self::splitCheckboxMeta($meta);
        $fields = $split['fields'];
        if ( $fields['lti_version'] !== '1.1' ) {
            throw new \InvalidArgumentException('This course does not own that tool.');
        }
        $arranged = ToolMessagePlacement::arrange($split['messageTypes'], $split['placements']);
        ToolRegistrationDocument::lti11Document($fields['lti11_url'], $arranged, $split['claims'], $split['scopes']);

        $PDOX = self::db();
        $p = self::prefix();
        $owns = ! $PDOX->inTransaction();
        if ( $owns ) {
            $PDOX->beginTransaction();
        }
        try {
            $stmt = $PDOX->queryReturnError(
                "UPDATE {$p}lti_tool_registration
                 SET title = :title, lti11_key = :lti11_key, lti11_secret = :lti11_secret,
                     lti11_url = :lti11_url, updated_at = NOW()
                 WHERE registration_id = :registration_id
                   AND owner_context_id = :owner_context_id
                   AND lti_version = '1.1'",
                array(
                    ':title' => $title,
                    ':lti11_key' => $fields['lti11_key'],
                    ':lti11_secret' => $fields['lti11_secret'],
                    ':lti11_url' => $fields['lti11_url'],
                    ':registration_id' => $owned['registration_id'],
                    ':owner_context_id' => $owned['owner_context_id'],
                )
            );
            if ( ! $stmt->success ) {
                $detail = isset($stmt->errorImplode) ? (string) $stmt->errorImplode : 'database error';
                throw new \RuntimeException('Could not update tool registration. '.$detail);
            }
            ToolPlacementService::synthesizeLti11Messages(
                $owned['registration_id'],
                $split['messageTypes'],
                $split['placements'],
                $split['claims'],
                $split['scopes']
            );
            $deploymentId = ToolDeploymentService::onlyDeploymentId($owned['registration_id']);
            self::enableAvailablePlacements($deploymentId);
            self::allowRequestedGrants($owned['registration_id'], $deploymentId);
            if ( $owns ) {
                $PDOX->commit();
            }
        } catch ( \Throwable $ex ) {
            if ( $owns && $PDOX->inTransaction() ) {
                $PDOX->rollBack();
            }
            throw $ex;
        }
    }

    /**
     * Delete an LTI 1.1 tool this course owns.
     *
     * Deployments, messages, placements, and grants go with the registration.
     *
     * @param int $contextId
     * @param int $registrationId
     * @return void
     */
    public static function deleteLti11ForCourse($contextId, $registrationId) {
        $owned = self::requireOwnedCourseLti11($contextId, $registrationId);
        $p = self::prefix();
        $stmt = self::db()->queryReturnError(
            "DELETE FROM {$p}lti_tool_registration
             WHERE registration_id = :registration_id
               AND owner_context_id = :owner_context_id
               AND lti_version = '1.1'",
            array(
                ':registration_id' => $owned['registration_id'],
                ':owner_context_id' => $owned['owner_context_id'],
            )
        );
        if ( ! $stmt->success ) {
            $detail = isset($stmt->errorImplode) ? (string) $stmt->errorImplode : 'database error';
            throw new \RuntimeException('Could not delete tool registration. '.$detail);
        }
    }

    /**
     * The stored LTI 1.1 fields for a tool this course owns.
     *
     * @param int $contextId
     * @param int $registrationId
     * @return array{registration_id:int, key_id:int, owner_context_id:int, title:string, lti11_key:string, lti11_secret:string, lti11_url:string}
     */
    public static function courseLti11($contextId, $registrationId) {
        return self::requireOwnedCourseLti11($contextId, $registrationId);
    }

    /**
     * An LTI 1.1 registration this course can launch.
     *
     * The course does not have to own it. A tool shared into the course is included.
     *
     * @param int $contextId
     * @param int $registrationId
     * @return array{registration_id:int, key_id:int, title:string, lti11_key:string, lti11_secret:string, lti11_url:string}
     */
    public static function visibleLti11($contextId, $registrationId) {
        $registrationId = (int) $registrationId;
        if ( $registrationId < 1 ) {
            throw new \InvalidArgumentException('This course does not have that tool.');
        }
        $visible = false;
        foreach ( ToolDeploymentService::getRegistrationsForContext((int) $contextId) as $tool ) {
            if ( (int) $tool['registration_id'] === $registrationId ) {
                $visible = true;
                break;
            }
        }
        if ( ! $visible ) {
            throw new \InvalidArgumentException('This course does not have that tool.');
        }
        return self::lti11LaunchFields($registrationId);
    }

    /**
     * An LTI 1.1 deployment this course can launch.
     *
     * A lesson item stores tool_deployment_id. The deployment is in scope for
     * the course. Claims and scopes are that deployment's grants.
     *
     * @param int $contextId
     * @param int $toolDeploymentId
     * @return array{registration_id:int, key_id:int, title:string, lti11_key:string, lti11_secret:string, lti11_url:string, tool_deployment_id:int}
     */
    public static function visibleLti11Deployment($contextId, $toolDeploymentId) {
        $toolDeploymentId = (int) $toolDeploymentId;
        if ( $toolDeploymentId < 1 ) {
            throw new \InvalidArgumentException('This course does not have that deployment.');
        }
        $found = null;
        foreach ( ToolDeploymentService::getDeploymentsForContext((int) $contextId) as $row ) {
            if ( (int) $row['tool_deployment_id'] === $toolDeploymentId ) {
                $found = $row;
                break;
            }
        }
        if ( $found === null ) {
            throw new \InvalidArgumentException('This course does not have that deployment.');
        }
        $tool = self::lti11LaunchFields((int) $found['registration_id']);
        $tool['tool_deployment_id'] = $toolDeploymentId;
        return $tool;
    }

    /**
     * @param int $registrationId
     * @return array{registration_id:int, key_id:int, title:string, lti11_key:string, lti11_secret:string, lti11_url:string}
     */
    private static function lti11LaunchFields($registrationId) {
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT registration_id, key_id, title, lti_version, lti11_key, lti11_secret, lti11_url
             FROM {$p}lti_tool_registration
             WHERE registration_id = :registration_id",
            array(':registration_id' => (int) $registrationId)
        );
        if ( ! is_array($row) || (string) $row['lti_version'] !== '1.1' ) {
            throw new \InvalidArgumentException('This test sends an LTI 1.1 launch.');
        }
        return array(
            'registration_id' => (int) $row['registration_id'],
            'key_id' => (int) $row['key_id'],
            'title' => (string) $row['title'],
            'lti11_key' => (string) $row['lti11_key'],
            'lti11_secret' => (string) $row['lti11_secret'],
            'lti11_url' => (string) $row['lti11_url'],
        );
    }

    /**
     * How many other LTI 1.1 registrations in this course's tenant store this launch URL.
     *
     * The URL is not an identifier. This count is only a note.
     *
     * @param int $contextId
     * @param string $url
     * @param int $exceptRegistrationId the tool being edited, so it is not counted as another tool
     * @return int
     */
    public static function otherLaunchUrlCount($contextId, $url, $exceptRegistrationId = 0) {
        $context = self::findContext($contextId);
        if ( $context === null ) {
            throw new \InvalidArgumentException('Course was not found.');
        }
        $url = trim((string) $url);
        if ( $url === '' ) {
            return 0;
        }
        $p = self::prefix();
        $params = array(
            ':key_id' => (int) $context['key_id'],
            ':lti11_url' => $url,
        );
        $except = '';
        $exceptRegistrationId = (int) $exceptRegistrationId;
        if ( $exceptRegistrationId > 0 ) {
            $except = ' AND registration_id <> :registration_id';
            $params[':registration_id'] = $exceptRegistrationId;
        }
        $row = self::db()->rowDie(
            "SELECT COUNT(*) AS n
             FROM {$p}lti_tool_registration
             WHERE key_id = :key_id AND lti_version = '1.1' AND lti11_url = :lti11_url".$except,
            $params
        );
        return is_array($row) ? (int) $row['n'] : 0;
    }

    /**
     * Other LTI 1.1 tools in the same tenant that store each registration's launch URL.
     *
     * @param array<int, int> $registrationIds
     * @return array<int, int> registration_id => count of other tools
     */
    public static function otherLaunchUrlCounts(array $registrationIds) {
        $ids = array();
        foreach ( $registrationIds as $registrationId ) {
            $registrationId = (int) $registrationId;
            if ( $registrationId > 0 ) {
                $ids[$registrationId] = $registrationId;
            }
        }
        if ( count($ids) === 0 ) {
            return array();
        }
        $p = self::prefix();
        $params = array();
        $holders = array();
        $i = 0;
        foreach ( $ids as $registrationId ) {
            $key = ':id'.$i;
            $holders[] = $key;
            $params[$key] = $registrationId;
            $i++;
        }
        $rows = self::db()->allRowsDie(
            "SELECT r.registration_id,
                    (SELECT COUNT(*)
                     FROM {$p}lti_tool_registration o
                     WHERE o.key_id = r.key_id
                       AND o.lti_version = '1.1'
                       AND o.lti11_url = r.lti11_url
                       AND o.registration_id <> r.registration_id) AS other_tools
             FROM {$p}lti_tool_registration r
             WHERE r.registration_id IN (".implode(', ', $holders).")",
            $params
        );
        $out = array();
        foreach ( $ids as $registrationId ) {
            $out[$registrationId] = 0;
        }
        foreach ( $rows as $row ) {
            $out[(int) $row['registration_id']] = (int) $row['other_tools'];
        }
        return $out;
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
     * @param int $contextId
     * @param int $registrationId
     * @return array{registration_id:int, key_id:int, owner_context_id:int, title:string, lti11_key:string, lti11_secret:string, lti11_url:string, context_id:int}
     */
    private static function requireOwnedCourseLti11($contextId, $registrationId) {
        $context = self::findContext($contextId);
        if ( $context === null ) {
            throw new \InvalidArgumentException('Course was not found.');
        }
        $registrationId = (int) $registrationId;
        if ( $registrationId < 1 ) {
            throw new \InvalidArgumentException('This course does not own that tool.');
        }
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT registration_id, key_id, owner_context_id, title, lti_version, lti11_key, lti11_secret, lti11_url
             FROM {$p}lti_tool_registration
             WHERE registration_id = :registration_id",
            array(':registration_id' => $registrationId)
        );
        if ( ! is_array($row)
            || (string) $row['lti_version'] !== '1.1'
            || $row['owner_context_id'] === null
            || (int) $row['owner_context_id'] !== (int) $context['context_id']
            || (int) $row['key_id'] !== (int) $context['key_id'] ) {
            throw new \InvalidArgumentException('This course does not own that tool.');
        }
        return array(
            'registration_id' => (int) $row['registration_id'],
            'key_id' => (int) $row['key_id'],
            'owner_context_id' => (int) $row['owner_context_id'],
            'title' => (string) $row['title'],
            'lti11_key' => (string) $row['lti11_key'],
            'lti11_secret' => (string) $row['lti11_secret'],
            'lti11_url' => (string) $row['lti11_url'],
            'context_id' => (int) $context['context_id'],
        );
    }

    /**
     * @param array<string, mixed> $meta
     * @return array{fields:array<string, mixed>, messageTypes:array<int, string>, placements:array<int, string>, claims:array<int, string>, scopes:array<int, string>}
     */
    private static function splitCheckboxMeta(array $meta) {
        $placements = array();
        if ( array_key_exists('placements', $meta) ) {
            $placements = $meta['placements'];
            unset($meta['placements']);
        }
        $messageTypes = array('LtiResourceLinkRequest');
        if ( array_key_exists('messages', $meta) ) {
            $messageTypes = $meta['messages'];
            unset($meta['messages']);
        }
        $claims = array();
        if ( array_key_exists('claims', $meta) ) {
            $claims = $meta['claims'];
            unset($meta['claims']);
        }
        $scopes = array();
        if ( array_key_exists('scopes', $meta) ) {
            $scopes = $meta['scopes'];
            unset($meta['scopes']);
        }
        return array(
            'fields' => self::metaFields($meta),
            'messageTypes' => $messageTypes,
            'placements' => $placements,
            'claims' => $claims,
            'scopes' => $scopes,
        );
    }

    /**
     * @param int $deploymentId
     * @return void
     */
    private static function enableAvailablePlacements($deploymentId) {
        foreach ( ToolPlacementService::getAvailablePlacementsForDeployment($deploymentId) as $row ) {
            ToolPlacementService::enablePlacementForDeployment($deploymentId, (int) $row['message_placement_id']);
        }
    }

    /**
     * Allow each claim and scope the registration asks for that is not already allowed.
     *
     * @param int $registrationId
     * @param int $deploymentId
     * @return void
     */
    private static function allowRequestedGrants($registrationId, $deploymentId) {
        $document = ToolRegistrationDocument::registrationDocument($registrationId);
        if ( ! is_array($document) ) {
            return;
        }
        $haveClaims = ToolDeploymentGrant::allowedClaims($deploymentId);
        foreach ( ToolRegistrationDocument::requestedClaims($document) as $claim ) {
            if ( ! in_array($claim, $haveClaims, true) ) {
                ToolDeploymentGrant::allowClaim($deploymentId, $claim);
            }
        }
        $haveScopes = ToolDeploymentGrant::allowedScopes($deploymentId);
        foreach ( ToolRegistrationDocument::requestedScopes($document) as $scope ) {
            if ( ! in_array($scope, $haveScopes, true) ) {
                ToolDeploymentGrant::allowScope($deploymentId, $scope);
            }
        }
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
    /**
     * Record the signed-in user when they belong to this course's tenant.
     * A site login on another key still adds the tool; created_by stays empty.
     *
     * @param mixed $userId
     * @param int $keyId
     * @return int|null
     */
    private static function creatorOnKey($userId, $keyId) {
        try {
            return self::optionalUserId($userId, $keyId);
        } catch ( \InvalidArgumentException $ex ) {
            return null;
        }
    }

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
