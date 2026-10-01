<?php

namespace Tsugi\Services\Org;

use Tsugi\Core\LTIX;

/**
 * Tenant-scoped organization tree.
 *
 * The tenant is an lti_key row. Every org stores that key_id. Hierarchy walks
 * never follow a parent in a different key.
 *
 * getAncestors() and getDescendants() include the starting org. Ancestors are
 * ordered from the org itself up to the root. Descendants are ordered from
 * the org itself downward. Those methods return rows. ancestorScope() and
 * descendantScope() return the same walk as a SQL relation, so another
 * statement can filter or join it without first loading the ids into PHP.
 *
 * moveOrg() rejects a cycle, including an org parented to itself. The parent
 * foreign key already rejects a parent from another tenant. MySQL cannot add
 * a CHECK for the self-parent case because org_id is AUTO_INCREMENT.
 */
class OrgService {

    const MAX_DEPTH = 64;

    /**
     * @param int $keyId tenant key
     * @param string $title
     * @param int|null $parentOrgId null creates a top-level org
     * @param string|null $orgType label only; the tree does not use it
     * @return int new org_id
     */
    public static function createOrg($keyId, $title, $parentOrgId = null, $orgType = null) {
        $PDOX = self::db();
        $p = self::prefix();
        $keyId = self::requireId($keyId, 'Tenant key');
        $title = self::requireTitle($title, 'Organization title');
        $orgType = self::optionalLabel($orgType, 64, 'Organization type');
        self::requireKey($keyId);

        $parentOrgId = self::optionalId($parentOrgId);
        if ( $parentOrgId !== null ) {
            $parent = self::requireOrg($parentOrgId);
            if ( (int) $parent['key_id'] !== $keyId ) {
                throw new \InvalidArgumentException('Parent organization belongs to a different tenant.');
            }
        }

        $stmt = $PDOX->queryReturnError(
            "INSERT INTO {$p}lti_org
                (key_id, parent_org_id, title, org_type, created_at)
             VALUES
                (:key_id, :parent_org_id, :title, :org_type, NOW())",
            array(
                ':key_id' => $keyId,
                ':parent_org_id' => $parentOrgId,
                ':title' => $title,
                ':org_type' => $orgType,
            )
        );
        if ( ! $stmt->success ) {
            self::fail($stmt, 'Could not create organization.');
        }
        return self::insertId();
    }

    /**
     * Move an org under another org in the same tenant, or to the tenant root.
     *
     * The cycle check and the parent update belong in one transaction. A
     * failure between them must not leave the caller holding a transaction
     * this method opened. This method opens a transaction only when the
     * caller has none, and it commits or rolls back only that transaction.
     * It does not lock the rows the cycle check read. Two simultaneous moves
     * can still each pass that check.
     *
     * @param int $orgId
     * @param int|null $parentOrgId null places the org at the tenant root
     * @return void
     */
    public static function moveOrg($orgId, $parentOrgId) {
        $orgId = self::requireId($orgId, 'Organization');
        $parentOrgId = self::optionalId($parentOrgId);

        self::runInTransaction(function () use ($orgId, $parentOrgId) {
            $org = self::requireOrg($orgId);

            if ( $parentOrgId !== null ) {
                $parent = self::requireOrg($parentOrgId);
                if ( (int) $parent['key_id'] !== (int) $org['key_id'] ) {
                    throw new \InvalidArgumentException('Parent organization belongs to a different tenant.');
                }
                if ( self::isDescendantOrSelf($parentOrgId, $orgId) ) {
                    throw new \InvalidArgumentException('An organization cannot be moved under itself or one of its descendants.');
                }
            }

            $stmt = self::db()->queryReturnError(
                "UPDATE ".self::prefix()."lti_org
                 SET parent_org_id = :parent_org_id, updated_at = NOW()
                 WHERE org_id = :org_id AND key_id = :key_id",
                array(
                    ':parent_org_id' => $parentOrgId,
                    ':org_id' => $orgId,
                    ':key_id' => (int) $org['key_id'],
                )
            );
            if ( ! $stmt->success ) {
                self::fail($stmt, 'Could not move organization.');
            }
        });
    }

    /**
     * Remove an organization without deleting the courses, tools, or child
     * orgs that pointed at it.
     *
     * The database cannot express "move this up to the parent, or else to the
     * key." Foreign keys can only cascade, restrict, or null a column. A
     * composite ON DELETE SET NULL would also null key_id, and key_id is the
     * tenant. Those foreign keys are therefore ON DELETE RESTRICT. This method
     * walks the rows off the org, then deletes the org. A raw DELETE of an org
     * that still has children, registrations, or deployments fails.
     *
     * The transaction is required because the steps are only safe together.
     * Stopping after any one of them leaves tools, scope, or the tree in the
     * wrong place, or it deletes rows the later step was supposed to keep.
     * Do not reorder these steps and do not delete the org from anywhere else.
     *
     * 1. Reparent child orgs onto this org's parent, or make them roots when
     *    this org is a root. This has to happen first. The parent foreign key
     *    would otherwise refuse the delete, and the old CASCADE behavior
     *    deleted the whole subtree.
     * 2. Clear registration.org_id for registrations scoped to this org. They
     *    become key-wide and keep key_id. This has to happen before the org
     *    row goes away. Deleting the registration would cascade into every
     *    deployment of that registration, including deployments this method
     *    is about to keep.
     * 3. Retarget deployments that point at this org. If the parent org
     *    already has that registration, drop this deployment. Otherwise point
     *    it at the parent. A root has no parent, so the deployment becomes a
     *    key deployment: org_id and context_id null, key_id unchanged. Drop
     *    it instead when that registration already has a key deployment.
     *    This has to happen before the org delete. The deployment foreign key
     *    would otherwise refuse the delete, and CASCADE would remove the row.
     * 4. Delete the org. Courses placed directly in it are left in place by
     *    the database: lti_context.org_id is a single-column foreign key with
     *    ON DELETE SET NULL, so the course keeps key_id and loses only the
     *    org. Courses placed in a child stay on that child, because the child
     *    was reparented in step 1.
     *
     * If this method opened the transaction, a failure rolls it back and
     * closes it. A caller that already has a transaction keeps that
     * transaction; this method does not commit or roll back someone else's
     * work.
     *
     * @param int $orgId
     * @return void
     */
    public static function deleteOrg($orgId) {
        $orgId = self::requireId($orgId, 'Organization');
        $org = self::requireOrg($orgId);
        $keyId = (int) $org['key_id'];
        $parentOrgId = $org['parent_org_id'];

        self::runInTransaction(function () use ($orgId, $keyId, $parentOrgId) {
            self::reparentChildren($orgId, $keyId, $parentOrgId);
            self::releaseRegistrations($orgId, $keyId);
            self::bubbleDeployments($orgId, $keyId, $parentOrgId);
            self::removeOrgRow($orgId, $keyId);
        });
    }

    /**
     * Rows sitting directly on this org.
     *
     * A later delete screen uses this to say how many courses, deployments,
     * child orgs, and registrations are still here, and to list them so a
     * person can move each one. These are direct rows only. A course placed
     * in a child belongs to that child. A deployment on a course, on an
     * ancestor, or on the key is not a holding of this org.
     *
     * empty is true only when all four lists are empty. That is when a screen
     * may call deleteOrg() without it choosing destinations. deleteOrg()
     * still repairs a non-empty org. That path is for unattended removal.
     *
     * @param int $orgId
     * @return array{courses: array<int, array<string, mixed>>, deployments: array<int, array<string, mixed>>, children: array<int, array<string, mixed>>, registrations: array<int, array<string, mixed>>, counts: array<string, int>, empty: bool}
     */
    public static function directHoldings($orgId) {
        $orgId = self::requireId($orgId, 'Organization');
        $org = self::requireOrg($orgId);
        $keyId = (int) $org['key_id'];

        $courses = self::coursesOnOrg($orgId, $keyId);
        $deployments = self::deploymentsOnOrg($orgId, $keyId);
        $children = self::childOrgs($orgId, $keyId);
        $registrations = self::registrationsOnOrg($orgId, $keyId);
        $counts = array(
            'courses' => count($courses),
            'deployments' => count($deployments),
            'children' => count($children),
            'registrations' => count($registrations),
        );

        return array(
            'courses' => $courses,
            'deployments' => $deployments,
            'children' => $children,
            'registrations' => $registrations,
            'counts' => $counts,
            'empty' => array_sum($counts) === 0,
        );
    }

    /**
     * Starting org, then its parent, then that parent, up to the root.
     *
     * @param int $orgId
     * @return array<int, array<string, mixed>>
     */
    public static function getAncestors($orgId) {
        return self::walkRows($orgId, true);
    }

    /**
     * Starting org, then every org below it in the same tenant.
     *
     * @param int $orgId
     * @return array<int, array<string, mixed>>
     */
    public static function getDescendants($orgId) {
        return self::walkRows($orgId, false);
    }

    /**
     * Ancestors of one org, including itself, as a composable SQL set.
     *
     * The tenant is an lti_key. Both the anchor and the recursive step require
     * that key_id, so the walk stays inside the tenant.
     *
     * @param int $keyId tenant key
     * @param int $orgId starting organization
     * @param string $name CTE name, unique within the statement
     * @return OrgScope
     */
    public static function ancestorScope($keyId, $orgId, $name = 'org_ancestors') {
        return self::orgScope($keyId, $orgId, $name, true);
    }

    /**
     * Descendants of one org, including itself, as a composable SQL set.
     *
     * @param int $keyId tenant key
     * @param int $orgId starting organization
     * @param string $name CTE name, unique within the statement
     * @return OrgScope
     */
    public static function descendantScope($keyId, $orgId, $name = 'org_descendants') {
        return self::orgScope($keyId, $orgId, $name, false);
    }

    /**
     * Ancestors of a course's organization, including that organization.
     *
     * A course with no organization produces an empty set. A caller can still
     * put the scope in one statement and match direct course rows beside it.
     *
     * @param int $contextId
     * @param string $name CTE name, unique within the statement
     * @return OrgScope
     */
    public static function contextAncestorScope($contextId, $name = 'org_ancestors') {
        $contextId = self::requireId($contextId, 'Course');
        $name = OrgScope::checkName($name);
        $p = self::prefix();
        $param = ':'.$name.'_context_id';
        $anchor = "SELECT o.org_id, o.parent_org_id, o.key_id, 0 AS depth
    FROM {$p}lti_context c
    INNER JOIN {$p}lti_org o
        ON o.org_id = c.org_id
       AND o.key_id = c.key_id
    WHERE c.context_id = {$param}";

        return new OrgScope(
            $name,
            $anchor,
            true,
            $p.'lti_org',
            self::MAX_DEPTH,
            array($param => $contextId)
        );
    }

    /**
     * True when $orgId is $ancestorOrgId or sits below it in the same tenant.
     *
     * isDescendantOrSelf(Computer Science, Engineering) is true.
     * isDescendantOrSelf(Engineering, Engineering) is true.
     * isDescendantOrSelf(Mechanical Engineering, Computer Science) is false.
     *
     * @param int $orgId
     * @param int $ancestorOrgId
     * @return bool
     */
    public static function isDescendantOrSelf($orgId, $ancestorOrgId) {
        $orgId = (int) $orgId;
        $ancestorOrgId = (int) $ancestorOrgId;
        if ( $orgId < 1 || $ancestorOrgId < 1 ) {
            return false;
        }
        $org = self::findOrg($orgId);
        if ( $org === null ) {
            return false;
        }
        $scope = self::ancestorScope((int) $org['key_id'], $orgId);
        $params = $scope->params();
        $params[':ancestor_org_id'] = $ancestorOrgId;
        $row = self::db()->rowDie(
            "WITH RECURSIVE ".$scope->cte()."
             SELECT MAX(depth) AS max_depth,
                    MAX(CASE WHEN org_id = :ancestor_org_id THEN 1 ELSE 0 END) AS found
             FROM ".$scope->name(),
            $params
        );
        if ( ! is_array($row) || $row['max_depth'] === null ) {
            return false;
        }
        if ( (int) $row['max_depth'] >= self::MAX_DEPTH ) {
            throw new \RuntimeException('Organization hierarchy is too deep or cyclic.');
        }
        return (int) $row['found'] === 1;
    }

    /**
     * @param int $orgId
     * @param bool $upward
     * @return array<int, array<string, mixed>>
     */
    private static function walkRows($orgId, $upward) {
        $orgId = self::requireId($orgId, 'Organization');
        $org = self::findOrg($orgId);
        if ( $org === null ) {
            return array();
        }
        $scope = $upward
            ? self::ancestorScope((int) $org['key_id'], $orgId)
            : self::descendantScope((int) $org['key_id'], $orgId);
        $p = self::prefix();
        $name = $scope->name();
        $sql = "WITH RECURSIVE ".$scope->cte()."
            SELECT o.org_id, o.key_id, o.parent_org_id, o.title, o.org_type, {$name}.depth
            FROM {$name}
            INNER JOIN {$p}lti_org o
                ON o.org_id = {$name}.org_id
               AND o.key_id = {$name}.key_id
            ORDER BY {$name}.depth ASC, o.org_id ASC";

        return self::finishWalk(self::rows($sql, $scope->params()));
    }

    /**
     * @param int $keyId
     * @param int $orgId
     * @param string $name
     * @param bool $upward
     * @return OrgScope
     */
    private static function orgScope($keyId, $orgId, $name, $upward) {
        $keyId = self::requireId($keyId, 'Tenant key');
        $orgId = self::requireId($orgId, 'Organization');
        $name = OrgScope::checkName($name);
        $p = self::prefix();
        $keyParam = ':'.$name.'_key_id';
        $orgParam = ':'.$name.'_org_id';
        $anchor = "SELECT org_id, parent_org_id, key_id, 0 AS depth
    FROM {$p}lti_org
    WHERE key_id = {$keyParam}
      AND org_id = {$orgParam}";

        return new OrgScope(
            $name,
            $anchor,
            $upward,
            $p.'lti_org',
            self::MAX_DEPTH,
            array(
                $keyParam => $keyId,
                $orgParam => $orgId,
            )
        );
    }

    /**
     * Place a course in an org, or clear that placement.
     *
     * The org must belong to the course's tenant key. A null org leaves the
     * course unplaced, which is how existing courses already work.
     *
     * @param int $contextId
     * @param int|null $orgId
     * @return void
     */
    public static function placeContext($contextId, $orgId) {
        $PDOX = self::db();
        $p = self::prefix();
        $contextId = self::requireId($contextId, 'Course');
        $context = self::requireContext($contextId);
        $orgId = self::optionalId($orgId);

        if ( $orgId !== null ) {
            $org = self::requireOrg($orgId);
            if ( (int) $org['key_id'] !== (int) $context['key_id'] ) {
                throw new \InvalidArgumentException('Course and organization belong to different tenants.');
            }
        }

        $stmt = $PDOX->queryReturnError(
            "UPDATE {$p}lti_context
             SET org_id = :org_id, updated_at = NOW()
             WHERE context_id = :context_id AND key_id = :key_id",
            array(
                ':org_id' => $orgId,
                ':context_id' => $contextId,
                ':key_id' => (int) $context['key_id'],
            )
        );
        if ( ! $stmt->success ) {
            self::fail($stmt, 'Could not place course.');
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private static function finishWalk(array $rows) {
        if ( count($rows) > 0 && (int) $rows[count($rows) - 1]['depth'] >= self::MAX_DEPTH ) {
            throw new \RuntimeException('Organization hierarchy is too deep or cyclic.');
        }
        $out = array();
        foreach ( $rows as $row ) {
            $out[] = self::orgRow($row);
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function orgRow(array $row) {
        return array(
            'org_id' => (int) $row['org_id'],
            'key_id' => (int) $row['key_id'],
            'parent_org_id' => $row['parent_org_id'] === null ? null : (int) $row['parent_org_id'],
            'title' => (string) $row['title'],
            'org_type' => $row['org_type'] === null ? null : (string) $row['org_type'],
            'depth' => isset($row['depth']) ? (int) $row['depth'] : 0,
        );
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
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT org_id, key_id, parent_org_id, title, org_type
             FROM {$p}lti_org WHERE org_id = :org_id",
            array(':org_id' => (int) $orgId)
        );
        if ( ! is_array($row) ) {
            return null;
        }
        return self::orgRow($row);
    }

    /**
     * @param int $contextId
     * @return array<string, mixed>
     */
    private static function requireContext($contextId) {
        $p = self::prefix();
        $row = self::db()->rowDie(
            "SELECT context_id, key_id, org_id, title
             FROM {$p}lti_context WHERE context_id = :context_id",
            array(':context_id' => (int) $contextId)
        );
        if ( ! is_array($row) ) {
            throw new \InvalidArgumentException('Course was not found.');
        }
        return array(
            'context_id' => (int) $row['context_id'],
            'key_id' => (int) $row['key_id'],
            'org_id' => $row['org_id'] === null ? null : (int) $row['org_id'],
            'title' => $row['title'] === null ? null : (string) $row['title'],
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
     * @param string $sql
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    private static function rows($sql, array $params) {
        $rows = self::db()->allRowsDie($sql, $params);
        return is_array($rows) ? $rows : array();
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
     * @param mixed $id
     * @return int|null
     */
    private static function optionalId($id) {
        if ( $id === null ) {
            return null;
        }
        $id = (int) $id;
        if ( $id < 1 ) {
            throw new \InvalidArgumentException('Organization was not found.');
        }
        return $id;
    }

    /**
     * @param mixed $title
     * @param string $label
     * @return string
     */
    private static function requireTitle($title, $label) {
        $title = trim((string) $title);
        if ( $title === '' ) {
            throw new \InvalidArgumentException($label.' is required.');
        }
        if ( mb_strlen($title) > 512 ) {
            throw new \InvalidArgumentException($label.' is too long.');
        }
        return $title;
    }

    /**
     * @param mixed $value
     * @param int $max
     * @param string $label
     * @return string|null
     */
    private static function optionalLabel($value, $max, $label) {
        if ( $value === null ) {
            return null;
        }
        $value = trim((string) $value);
        if ( $value === '' ) {
            return null;
        }
        if ( mb_strlen($value) > $max ) {
            throw new \InvalidArgumentException($label.' is too long.');
        }
        return $value;
    }

    /**
     * @return int
     */
    private static function insertId() {
        $id = (int) self::db()->lastInsertId();
        if ( $id < 1 ) {
            throw new \RuntimeException('Could not create organization.');
        }
        return $id;
    }

    /**
     * Run $work in a transaction this method owns.
     *
     * A caller that already opened a transaction keeps it. Beginning another
     * one fails on MySQL, and committing or rolling back that outer
     * transaction would discard the caller's other work. When this method
     * opens the transaction, it closes it: commit after $work, or roll back
     * when $work throws. A rollback failure is reported with the original
     * error so the connection is not left inside an open transaction without
     * a trace.
     *
     * @param callable $work
     * @return void
     */
    private static function runInTransaction($work) {
        $PDOX = self::db();
        $owns = ! $PDOX->inTransaction();
        if ( $owns ) {
            $PDOX->beginTransaction();
        }
        try {
            $work();
            if ( $owns ) {
                $PDOX->commit();
            }
        } catch ( \Throwable $ex ) {
            if ( $owns && $PDOX->inTransaction() ) {
                try {
                    $PDOX->rollBack();
                } catch ( \Throwable $rollback ) {
                    throw new \RuntimeException(
                        'The organization change failed, and the transaction could not be rolled back. '.$ex->getMessage(),
                        0,
                        $rollback
                    );
                }
            }
            throw $ex;
        }
    }

    /**
     * @param int $orgId
     * @param int $keyId
     * @param int|null $parentOrgId
     * @return void
     */
    private static function reparentChildren($orgId, $keyId, $parentOrgId) {
        $stmt = self::db()->queryReturnError(
            "UPDATE ".self::prefix()."lti_org
             SET parent_org_id = :parent_org_id, updated_at = NOW()
             WHERE parent_org_id = :org_id AND key_id = :key_id",
            array(
                ':parent_org_id' => $parentOrgId,
                ':org_id' => $orgId,
                ':key_id' => $keyId,
            )
        );
        if ( ! $stmt->success ) {
            self::fail($stmt, 'Could not reparent child organizations.');
        }
    }

    /**
     * @param int $orgId
     * @param int $keyId
     * @return void
     */
    private static function releaseRegistrations($orgId, $keyId) {
        $stmt = self::db()->queryReturnError(
            "UPDATE ".self::prefix()."lti_tool_registration
             SET org_id = NULL, updated_at = NOW()
             WHERE org_id = :org_id AND key_id = :key_id",
            array(
                ':org_id' => $orgId,
                ':key_id' => $keyId,
            )
        );
        if ( ! $stmt->success ) {
            self::fail($stmt, 'Could not release tool registrations from the organization.');
        }
    }

    /**
     * @param int $orgId
     * @param int $keyId
     * @param int|null $parentOrgId
     * @return void
     */
    private static function bubbleDeployments($orgId, $keyId, $parentOrgId) {
        $p = self::prefix();
        $PDOX = self::db();
        if ( $parentOrgId !== null ) {
            $drop = $PDOX->queryReturnError(
                "DELETE d FROM {$p}lti_tool_deployment d
                 INNER JOIN {$p}lti_tool_deployment kept
                    ON kept.registration_id = d.registration_id
                   AND kept.org_id = :parent_org_id
                   AND kept.tool_deployment_id <> d.tool_deployment_id
                 WHERE d.org_id = :org_id AND d.key_id = :key_id",
                array(
                    ':parent_org_id' => $parentOrgId,
                    ':org_id' => $orgId,
                    ':key_id' => $keyId,
                )
            );
            if ( ! $drop->success ) {
                self::fail($drop, 'Could not drop deployments already covered by the parent organization.');
            }
            $move = $PDOX->queryReturnError(
                "UPDATE {$p}lti_tool_deployment
                 SET org_id = :parent_org_id, updated_at = NOW()
                 WHERE org_id = :org_id AND key_id = :key_id",
                array(
                    ':parent_org_id' => $parentOrgId,
                    ':org_id' => $orgId,
                    ':key_id' => $keyId,
                )
            );
            if ( ! $move->success ) {
                self::fail($move, 'Could not move deployments to the parent organization.');
            }
            return;
        }

        $drop = $PDOX->queryReturnError(
            "DELETE d FROM {$p}lti_tool_deployment d
             INNER JOIN {$p}lti_tool_deployment kept
                ON kept.registration_id = d.registration_id
               AND kept.key_id = d.key_id
               AND kept.org_id IS NULL
               AND kept.context_id IS NULL
               AND kept.tool_deployment_id <> d.tool_deployment_id
             WHERE d.org_id = :org_id AND d.key_id = :key_id",
            array(
                ':org_id' => $orgId,
                ':key_id' => $keyId,
            )
        );
        if ( ! $drop->success ) {
            self::fail($drop, 'Could not drop deployments already covered by the key.');
        }
        $move = $PDOX->queryReturnError(
            "UPDATE {$p}lti_tool_deployment
             SET org_id = NULL, key_level = 1, updated_at = NOW()
             WHERE org_id = :org_id AND key_id = :key_id",
            array(
                ':org_id' => $orgId,
                ':key_id' => $keyId,
            )
        );
        if ( ! $move->success ) {
            self::fail($move, 'Could not move deployments to the key.');
        }
    }

    /**
     * @param int $orgId
     * @param int $keyId
     * @return array<int, array<string, mixed>>
     */
    private static function coursesOnOrg($orgId, $keyId) {
        $rows = self::rows(
            "SELECT context_id, key_id, title
             FROM ".self::prefix()."lti_context
             WHERE org_id = :org_id AND key_id = :key_id
             ORDER BY title ASC, context_id ASC",
            array(':org_id' => $orgId, ':key_id' => $keyId)
        );
        $out = array();
        foreach ( $rows as $row ) {
            $out[] = array(
                'context_id' => (int) $row['context_id'],
                'key_id' => (int) $row['key_id'],
                'title' => $row['title'] === null ? null : (string) $row['title'],
            );
        }
        return $out;
    }

    /**
     * @param int $orgId
     * @param int $keyId
     * @return array<int, array<string, mixed>>
     */
    private static function deploymentsOnOrg($orgId, $keyId) {
        $p = self::prefix();
        $rows = self::rows(
            "SELECT d.tool_deployment_id, d.registration_id, d.key_id, d.deployment_id, r.title
             FROM {$p}lti_tool_deployment d
             INNER JOIN {$p}lti_tool_registration r
                ON r.registration_id = d.registration_id AND r.key_id = d.key_id
             WHERE d.org_id = :org_id AND d.key_id = :key_id
             ORDER BY r.title ASC, d.tool_deployment_id ASC",
            array(':org_id' => $orgId, ':key_id' => $keyId)
        );
        $out = array();
        foreach ( $rows as $row ) {
            $out[] = array(
                'tool_deployment_id' => (int) $row['tool_deployment_id'],
                'registration_id' => (int) $row['registration_id'],
                'key_id' => (int) $row['key_id'],
                'deployment_id' => $row['deployment_id'] === null ? null : (string) $row['deployment_id'],
                'title' => (string) $row['title'],
            );
        }
        return $out;
    }

    /**
     * @param int $orgId
     * @param int $keyId
     * @return array<int, array<string, mixed>>
     */
    private static function childOrgs($orgId, $keyId) {
        $rows = self::rows(
            "SELECT org_id, key_id, parent_org_id, title, org_type
             FROM ".self::prefix()."lti_org
             WHERE parent_org_id = :org_id AND key_id = :key_id
             ORDER BY title ASC, org_id ASC",
            array(':org_id' => $orgId, ':key_id' => $keyId)
        );
        $out = array();
        foreach ( $rows as $row ) {
            $out[] = self::orgRow($row);
        }
        return $out;
    }

    /**
     * @param int $orgId
     * @param int $keyId
     * @return array<int, array<string, mixed>>
     */
    private static function registrationsOnOrg($orgId, $keyId) {
        $rows = self::rows(
            "SELECT registration_id, key_id, org_id, title
             FROM ".self::prefix()."lti_tool_registration
             WHERE org_id = :org_id AND key_id = :key_id
             ORDER BY title ASC, registration_id ASC",
            array(':org_id' => $orgId, ':key_id' => $keyId)
        );
        $out = array();
        foreach ( $rows as $row ) {
            $out[] = array(
                'registration_id' => (int) $row['registration_id'],
                'key_id' => (int) $row['key_id'],
                'org_id' => (int) $row['org_id'],
                'title' => (string) $row['title'],
            );
        }
        return $out;
    }

    /**
     * @param int $orgId
     * @param int $keyId
     * @return void
     */
    private static function removeOrgRow($orgId, $keyId) {
        $stmt = self::db()->queryReturnError(
            "DELETE FROM ".self::prefix()."lti_org
             WHERE org_id = :org_id AND key_id = :key_id",
            array(
                ':org_id' => $orgId,
                ':key_id' => $keyId,
            )
        );
        if ( ! $stmt->success ) {
            self::fail($stmt, 'Could not delete organization.');
        }
    }

    /**
     * @param object $stmt
     * @param string $message
     * @return void
     */
    private static function fail($stmt, $message) {
        $detail = isset($stmt->errorImplode) ? (string) $stmt->errorImplode : 'database error';
        throw new \RuntimeException($message.' '.$detail);
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
