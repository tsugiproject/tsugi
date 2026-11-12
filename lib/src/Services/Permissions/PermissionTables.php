<?php

namespace Tsugi\Services\Permissions;

use Tsugi\Core\LTIX;
use Tsugi\Core\Mail;
use Tsugi\Services\Courses\CoursesService;
use Tsugi\Services\Permission;
use Tsugi\Util\NotificationsService;
use Tsugi\Util\U;

/**
 * Admin reads and writes for the permission tables.
 *
 * Grants are the rows an administrator hands out. Revoke keeps the row.
 * A second active grant for the same user, system scope, and permission
 * is rejected. Applications are listed and can be removed. A user has
 * at most one application in progress for a permission, either pending
 * or sent back for revision. The system scope row is read-only here.
 */
class PermissionTables {

    const SYSTEM_SCOPE_ID = 1;

    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_DENIED = 'denied';

    /** Sent back so the applicant can edit the same row and resubmit. */
    const STATUS_REVISION = 'revision';

    /** Grant row written when an application is approved. */
    const SOURCE_APPLICATION = 'application';

    /** Justification column is TEXT. This is the form limit. */
    const JUSTIFICATION_MAX = 4000;

    /** Admin list page size. One extra row is fetched to know if another page exists. */
    const PAGE_LENGTH = 20;

    /**
     * A grant still allows the permission.
     *
     * @param array<string, mixed> $row
     * @param int|null $now Unix time. Tests pass this.
     */
    public static function isActive(array $row, $now = null) {
        if ( ! empty($row['revoked_at']) ) {
            return false;
        }
        if ( empty($row['expires_at']) ) {
            return true;
        }
        $expires = strtotime((string) $row['expires_at']);
        if ( $expires === false ) {
            return false;
        }
        $t = $now === null ? time() : (int) $now;
        return $expires > $t;
    }

    /**
     * Name, email, and user id for an application or grant row.
     *
     * @param array<string, mixed> $row
     */
    public static function userLabel(array $row): string {
        $name = trim((string) ($row['displayname'] ?? ''));
        $email = trim((string) ($row['email'] ?? ''));
        $id = (int) ($row['user_id'] ?? 0);
        if ( $name === '' ) {
            $label = $email;
        } else if ( $email === '' || strcasecmp($name, $email) === 0 ) {
            $label = $name;
        } else {
            $label = $name.' ('.$email.')';
        }
        if ( $label === '' ) {
            return (string) $id;
        }
        return $label.' ('.$id.')';
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function registry() {
        return Permission::registry();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function scopes() {
        global $CFG, $PDOX;
        self::connection();
        return $PDOX->allRowsDie(
            "SELECT scope_id, code, title, created_at
             FROM {$CFG->dbprefix}site_scope
             ORDER BY scope_id"
        );
    }

    /**
     * One page of applications, newest first.
     *
     * @param int $start
     * @return array{rows: array<int, array<string, mixed>>, start: int, more: bool, length: int}
     */
    public static function applications($start = 0) {
        global $CFG, $PDOX;
        self::connection();
        $p = $CFG->dbprefix;
        $start = max(0, (int) $start);
        $rows = $PDOX->allRowsDie(
            "SELECT A.application_id, A.user_id, A.scope_id, A.permission, A.status,
                    A.justification, A.response, A.created_at, A.reviewed_at,
                    U.displayname, U.email,
                    R.displayname AS reviewer_name
             FROM {$p}site_application AS A
             JOIN {$p}lti_user AS U ON U.user_id = A.user_id
             LEFT JOIN {$p}lti_user AS R ON R.user_id = A.reviewer_id
             ORDER BY A.application_id DESC
             LIMIT {$start}, ".(self::PAGE_LENGTH + 1)
        );
        return self::pageResult($rows, $start);
    }

    /**
     * One page of grants, newest first.
     *
     * @param int $start
     * @return array{rows: array<int, array<string, mixed>>, start: int, more: bool, length: int}
     */
    public static function grants($start = 0) {
        global $CFG, $PDOX;
        self::connection();
        $p = $CFG->dbprefix;
        $start = max(0, (int) $start);
        $rows = $PDOX->allRowsDie(
            "SELECT G.grant_id, G.user_id, G.scope_id, G.permission, G.source,
                    G.granted_at, G.expires_at, G.revoked_at, G.grantor_id,
                    U.displayname, U.email, U.create_courses,
                    GU.displayname AS grantor_name
             FROM {$p}site_grant AS G
             JOIN {$p}lti_user AS U ON U.user_id = G.user_id
             LEFT JOIN {$p}lti_user AS GU ON GU.user_id = G.grantor_id
             ORDER BY G.grant_id DESC
             LIMIT {$start}, ".(self::PAGE_LENGTH + 1)
        );
        return self::pageResult($rows, $start);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param int $start
     * @return array{rows: array<int, array<string, mixed>>, start: int, more: bool, length: int}
     */
    private static function pageResult(array $rows, $start) {
        $more = count($rows) > self::PAGE_LENGTH;
        if ( $more ) {
            $rows = array_slice($rows, 0, self::PAGE_LENGTH);
        }
        return array(
            'rows' => $rows,
            'start' => max(0, (int) $start),
            'more' => $more,
            'length' => self::PAGE_LENGTH,
        );
    }

    /**
     * Hand out a system-scope grant. No application row.
     *
     * @param string $userLookup User id or email.
     * @param string $permission
     * @param string $expiresRaw Empty, or a datetime-local value.
     * @param int $grantorId Logged-in admin, or 0.
     * @return array{ok: true, grant_id: int}|array{ok: false, error: string}
     */
    public static function handGrant($userLookup, $permission, $expiresRaw, $grantorId) {
        global $CFG, $PDOX;
        self::connection();

        $permission = trim((string) $permission);
        $registry = self::registry();
        if ( ! isset($registry[$permission]) ) {
            return array('ok' => false, 'error' => 'Unknown permission.');
        }

        $user = self::findUser($userLookup);
        if ( isset($user['error']) ) {
            return array('ok' => false, 'error' => $user['error']);
        }
        $uid = (int) $user['user_id'];

        $expires = self::parseExpires($expiresRaw);
        if ( isset($expires['error']) ) {
            return array('ok' => false, 'error' => $expires['error']);
        }

        $p = $CFG->dbprefix;
        $active = $PDOX->rowDie(
            "SELECT grant_id FROM {$p}site_grant
             WHERE user_id = :UID AND scope_id = :SID AND permission = :PERM
               AND revoked_at IS NULL
               AND (expires_at IS NULL OR expires_at > NOW())
             LIMIT 1",
            array(
                ':UID' => $uid,
                ':SID' => self::SYSTEM_SCOPE_ID,
                ':PERM' => $permission,
            )
        );
        if ( is_array($active) ) {
            return array('ok' => false, 'error' => 'That user already has an active grant for this permission.');
        }

        $grantId = self::saveGrant(
            $uid,
            $permission,
            'admin',
            (int) $grantorId,
            $expires['at']
        );
        return array('ok' => true, 'grant_id' => $grantId);
    }

    /**
     * Insert one system-scope grant, or refresh the row that is already there.
     *
     * The unique key is (user_id, scope_id, permission). A second insert for
     * that triple updates the same row and clears revocation.
     *
     * @param int $uid
     * @param string $permission
     * @param string $source
     * @param int $grantorId
     * @param string|null $expiresAt
     * @param int|null $applicationId
     * @param bool $dieOnError
     * @return int Grant id, or 0 when $dieOnError is false and the write failed.
     */
    private static function saveGrant($uid, $permission, $source, $grantorId, $expiresAt, $applicationId = null, $dieOnError = true) {
        global $CFG, $PDOX;
        $p = $CFG->dbprefix;
        $appId = ((int) $applicationId) > 0 ? (int) $applicationId : null;
        $stmt = $PDOX->queryReturnError(
            "INSERT INTO {$p}site_grant
                (user_id, scope_id, permission, source, application_id, grantor_id, granted_at, expires_at)
             VALUES (:UID, :SID, :PERM, :SRC, :AID, :GID, NOW(), :EXP)
             ON DUPLICATE KEY UPDATE
                grant_id = LAST_INSERT_ID(grant_id),
                source = VALUES(source),
                application_id = COALESCE(VALUES(application_id), application_id),
                grantor_id = VALUES(grantor_id),
                granted_at = NOW(),
                expires_at = VALUES(expires_at),
                revoked_at = NULL,
                revoked_by = NULL",
            array(
                ':UID' => (int) $uid,
                ':SID' => self::SYSTEM_SCOPE_ID,
                ':PERM' => $permission,
                ':SRC' => $source,
                ':AID' => $appId,
                ':GID' => ((int) $grantorId) > 0 ? (int) $grantorId : null,
                ':EXP' => $expiresAt,
            )
        );
        if ( ! $stmt->success ) {
            error_log('Sql Failure:'.$stmt->errorImplode);
            if ( $dieOnError ) {
                if ( ! empty($CFG->DEVELOPER) ) {
                    die($stmt->errorImplode);
                }
                die('Internal database error');
            }
            return 0;
        }
        return (int) $PDOX->lastInsertId();
    }

    /**
     * Keep a course.create grant in step with lti_user.create_courses.
     *
     * Turning the flag on adds a system-scope grant when the user has no
     * active one. Turning it off revokes grants that came from that flag.
     * A hand grant is left alone.
     *
     * @param int $userId
     * @param bool $enabled
     * @param bool $dieOnError When false, a failed write returns false instead of stopping the request.
     * @return bool False only when a write was attempted and failed and $dieOnError is false.
     */
    public static function syncCourseCreateFlag($userId, $enabled, $dieOnError = true) {
        global $CFG, $PDOX;
        self::connection();
        $uid = (int) $userId;
        if ( $uid < 1 ) {
            return true;
        }
        $p = $CFG->dbprefix;
        if ( $enabled ) {
            $active = $PDOX->rowDie(
                "SELECT grant_id FROM {$p}site_grant
                 WHERE user_id = :UID AND scope_id = :SID AND permission = :PERM
                   AND revoked_at IS NULL
                   AND (expires_at IS NULL OR expires_at > NOW())
                 LIMIT 1",
                array(
                    ':UID' => $uid,
                    ':SID' => self::SYSTEM_SCOPE_ID,
                    ':PERM' => CoursesService::COURSE_CREATE,
                )
            );
            if ( is_array($active) ) {
                return true;
            }
            $grantId = self::saveGrant(
                $uid,
                CoursesService::COURSE_CREATE,
                'create_courses',
                0,
                null,
                null,
                $dieOnError
            );
            return $dieOnError || $grantId > 0;
        }
        $stmt = $PDOX->queryReturnError(
            "UPDATE {$p}site_grant
             SET revoked_at = NOW()
             WHERE user_id = :UID AND scope_id = :SID AND permission = :PERM
               AND source = :SRC AND revoked_at IS NULL",
            array(
                ':UID' => $uid,
                ':SID' => self::SYSTEM_SCOPE_ID,
                ':PERM' => CoursesService::COURSE_CREATE,
                ':SRC' => 'create_courses',
            )
        );
        if ( $stmt->success ) {
            return true;
        }
        error_log('Sql Failure:'.$stmt->errorImplode);
        if ( $dieOnError ) {
            if ( ! empty($CFG->DEVELOPER) ) {
                die($stmt->errorImplode);
            }
            die('Internal database error');
        }
        return false;
    }

    /**
     * @param int $grantId
     * @param int $revokedBy
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public static function revokeGrant($grantId, $revokedBy) {
        global $CFG, $PDOX;
        self::connection();
        $id = (int) $grantId;
        if ( $id < 1 ) {
            return array('ok' => false, 'error' => 'Grant not found.');
        }
        $p = $CFG->dbprefix;
        $row = $PDOX->rowDie(
            "SELECT grant_id, revoked_at FROM {$p}site_grant WHERE grant_id = :ID",
            array(':ID' => $id)
        );
        if ( ! is_array($row) ) {
            return array('ok' => false, 'error' => 'Grant not found.');
        }
        if ( ! empty($row['revoked_at']) ) {
            return array('ok' => false, 'error' => 'That grant is already revoked.');
        }
        $by = (int) $revokedBy;
        if ( $by > 0 ) {
            $PDOX->queryDie(
                "UPDATE {$p}site_grant
                 SET revoked_at = NOW(), revoked_by = :BY
                 WHERE grant_id = :ID AND revoked_at IS NULL",
                array(':BY' => $by, ':ID' => $id)
            );
        } else {
            $PDOX->queryDie(
                "UPDATE {$p}site_grant
                 SET revoked_at = NOW()
                 WHERE grant_id = :ID AND revoked_at IS NULL",
                array(':ID' => $id)
            );
        }
        return array('ok' => true);
    }

    /**
     * @param int $grantId
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public static function deleteGrant($grantId) {
        global $CFG, $PDOX;
        self::connection();
        $id = (int) $grantId;
        if ( $id < 1 ) {
            return array('ok' => false, 'error' => 'Grant not found.');
        }
        $stmt = $PDOX->queryDie(
            "DELETE FROM {$CFG->dbprefix}site_grant WHERE grant_id = :ID",
            array(':ID' => $id)
        );
        if ( $stmt->rowCount() < 1 ) {
            return array('ok' => false, 'error' => 'Grant not found.');
        }
        return array('ok' => true);
    }

    /**
     * @param int $applicationId
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public static function deleteApplication($applicationId) {
        global $CFG, $PDOX;
        self::connection();
        $id = (int) $applicationId;
        if ( $id < 1 ) {
            return array('ok' => false, 'error' => 'Application not found.');
        }
        $stmt = $PDOX->queryDie(
            "DELETE FROM {$CFG->dbprefix}site_application WHERE application_id = :ID",
            array(':ID' => $id)
        );
        if ( $stmt->rowCount() < 1 ) {
            return array('ok' => false, 'error' => 'Application not found.');
        }
        return array('ok' => true);
    }

    /**
     * The user's pending application for this permission, or false.
     *
     * @param int $userId
     * @param string $permission
     * @return array<string, mixed>|false
     */
    public static function pendingApplication($userId, $permission) {
        global $CFG, $PDOX;
        self::connection();
        $uid = (int) $userId;
        if ( $uid < 1 ) {
            return false;
        }
        $row = $PDOX->rowDie(
            "SELECT application_id, user_id, scope_id, permission, justification,
                    status, response, created_at, updated_at
             FROM {$CFG->dbprefix}site_application
             WHERE user_id = :UID AND scope_id = :SID AND permission = :PERM
               AND status = :STATUS
             ORDER BY application_id DESC
             LIMIT 1",
            array(
                ':UID' => $uid,
                ':SID' => self::SYSTEM_SCOPE_ID,
                ':PERM' => trim((string) $permission),
                ':STATUS' => self::STATUS_PENDING,
            )
        );
        return is_array($row) ? $row : false;
    }

    /**
     * The application still in progress: pending, or sent back for revision.
     *
     * @param int $userId
     * @param string $permission
     * @return array<string, mixed>|false
     */
    public static function openApplication($userId, $permission) {
        global $CFG, $PDOX;
        self::connection();
        $uid = (int) $userId;
        if ( $uid < 1 ) {
            return false;
        }
        $row = $PDOX->rowDie(
            "SELECT application_id, user_id, scope_id, permission, justification,
                    status, response, created_at, updated_at
             FROM {$CFG->dbprefix}site_application
             WHERE user_id = :UID AND scope_id = :SID AND permission = :PERM
               AND status IN (:PENDING, :REVISION)
             ORDER BY application_id DESC
             LIMIT 1",
            array(
                ':UID' => $uid,
                ':SID' => self::SYSTEM_SCOPE_ID,
                ':PERM' => trim((string) $permission),
                ':PENDING' => self::STATUS_PENDING,
                ':REVISION' => self::STATUS_REVISION,
            )
        );
        return is_array($row) ? $row : false;
    }

    /**
     * Store a pending application and tell the admin emails.
     *
     * A denied or approved row stays. A second in-progress row is refused.
     * A racing second insert updates that same row. The open_slot unique
     * key makes the later write win instead of adding another request.
     *
     * @param int $userId
     * @param string $permission
     * @param string $justification
     * @return array{ok: true, application_id: int}|array{ok: false, error: string}
     */
    public static function submitApplication($userId, $permission, $justification) {
        global $CFG, $PDOX;
        self::connection();

        $uid = (int) $userId;
        if ( $uid < 1 ) {
            return array('ok' => false, 'error' => 'You must be logged in.');
        }

        $permission = trim((string) $permission);
        $registry = self::registry();
        if ( ! isset($registry[$permission]) || empty($registry[$permission]['requestable']) ) {
            return array('ok' => false, 'error' => 'That permission cannot be requested.');
        }

        $justification = trim((string) $justification);
        if ( $justification === '' ) {
            return array('ok' => false, 'error' => 'Tell us why you want this permission.');
        }
        if ( U::strlen($justification) > self::JUSTIFICATION_MAX ) {
            return array('ok' => false, 'error' => 'Keep the request under '.self::JUSTIFICATION_MAX.' characters.');
        }

        if ( is_array(self::openApplication($uid, $permission)) ) {
            return array('ok' => false, 'error' => 'You already have an application in progress.');
        }

        $p = $CFG->dbprefix;
        $PDOX->queryDie(
            "INSERT INTO {$p}site_application
                (user_id, scope_id, permission, justification, status, open_slot, created_at, updated_at)
             VALUES (:UID, :SID, :PERM, :WHY, :STATUS, :SLOT, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                application_id = LAST_INSERT_ID(application_id),
                justification = VALUES(justification),
                status = VALUES(status),
                open_slot = VALUES(open_slot),
                updated_at = NOW()",
            array(
                ':UID' => $uid,
                ':SID' => self::SYSTEM_SCOPE_ID,
                ':PERM' => $permission,
                ':WHY' => $justification,
                ':STATUS' => self::STATUS_PENDING,
                ':SLOT' => self::openSlot($uid, $permission),
            )
        );
        $id = (int) $PDOX->lastInsertId();
        $row = self::pendingApplication($uid, $permission);
        if ( ! is_array($row) ) {
            $row = array(
                'application_id' => $id,
                'user_id' => $uid,
                'permission' => $permission,
                'justification' => $justification,
                'status' => self::STATUS_PENDING,
            );
        }

        $user = $PDOX->rowDie(
            "SELECT user_id, displayname, email
             FROM {$p}lti_user
             WHERE user_id = :UID",
            array(':UID' => $uid)
        );
        self::notifyAdmins($row, is_array($user) ? $user : array('user_id' => $uid));
        return array('ok' => true, 'application_id' => (int) $row['application_id']);
    }

    /**
     * Put a sent-back application back in the queue with a new justification.
     *
     * @param int $userId
     * @param int $applicationId
     * @param string $justification
     * @return array{ok: true, application_id: int}|array{ok: false, error: string}
     */
    public static function resubmitApplication($userId, $applicationId, $justification) {
        global $CFG, $PDOX;
        self::connection();

        $uid = (int) $userId;
        $id = (int) $applicationId;
        if ( $uid < 1 || $id < 1 ) {
            return array('ok' => false, 'error' => 'Application not found.');
        }

        $justification = trim((string) $justification);
        if ( $justification === '' ) {
            return array('ok' => false, 'error' => 'Tell us why you want this permission.');
        }
        if ( U::strlen($justification) > self::JUSTIFICATION_MAX ) {
            return array('ok' => false, 'error' => 'Keep the request under '.self::JUSTIFICATION_MAX.' characters.');
        }

        $p = $CFG->dbprefix;
        $current = $PDOX->rowDie(
            "SELECT permission FROM {$p}site_application
             WHERE application_id = :ID AND user_id = :UID AND status = :REVISION",
            array(
                ':ID' => $id,
                ':UID' => $uid,
                ':REVISION' => self::STATUS_REVISION,
            )
        );
        if ( ! is_array($current) ) {
            return array('ok' => false, 'error' => 'That application is not waiting for a revision.');
        }
        $stmt = $PDOX->queryDie(
            "UPDATE {$p}site_application
             SET justification = :WHY, status = :PENDING, open_slot = :SLOT
             WHERE application_id = :ID AND user_id = :UID AND status = :REVISION",
            array(
                ':WHY' => $justification,
                ':PENDING' => self::STATUS_PENDING,
                ':SLOT' => self::openSlot($uid, (string) $current['permission']),
                ':ID' => $id,
                ':UID' => $uid,
                ':REVISION' => self::STATUS_REVISION,
            )
        );
        if ( $stmt->rowCount() < 1 ) {
            return array('ok' => false, 'error' => 'That application is not waiting for a revision.');
        }

        $row = $PDOX->rowDie(
            "SELECT application_id, user_id, scope_id, permission, justification,
                    status, response
             FROM {$p}site_application
             WHERE application_id = :ID",
            array(':ID' => $id)
        );
        $user = $PDOX->rowDie(
            "SELECT user_id, displayname, email
             FROM {$p}lti_user
             WHERE user_id = :UID",
            array(':UID' => $uid)
        );
        if ( is_array($row) ) {
            self::notifyAdmins($row, is_array($user) ? $user : array('user_id' => $uid), true);
        }
        return array('ok' => true, 'application_id' => $id);
    }

    /**
     * Delete the user's own in-progress application.
     *
     * @param int $userId
     * @param int $applicationId
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public static function withdrawApplication($userId, $applicationId) {
        global $CFG, $PDOX;
        self::connection();
        $uid = (int) $userId;
        $id = (int) $applicationId;
        if ( $uid < 1 || $id < 1 ) {
            return array('ok' => false, 'error' => 'Application not found.');
        }
        $stmt = $PDOX->queryDie(
            "DELETE FROM {$CFG->dbprefix}site_application
             WHERE application_id = :ID AND user_id = :UID
               AND status IN (:PENDING, :REVISION)",
            array(
                ':ID' => $id,
                ':UID' => $uid,
                ':PENDING' => self::STATUS_PENDING,
                ':REVISION' => self::STATUS_REVISION,
            )
        );
        if ( $stmt->rowCount() < 1 ) {
            return array('ok' => false, 'error' => 'Application not found.');
        }
        return array('ok' => true);
    }

    /**
     * Newest application for this user and permission, any status.
     *
     * @param int $userId
     * @param string $permission
     * @return array<string, mixed>|false
     */
    public static function latestApplication($userId, $permission) {
        global $CFG, $PDOX;
        self::connection();
        $uid = (int) $userId;
        if ( $uid < 1 ) {
            return false;
        }
        $row = $PDOX->rowDie(
            "SELECT application_id, user_id, scope_id, permission, justification,
                    status, response, reviewed_at, created_at, updated_at
             FROM {$CFG->dbprefix}site_application
             WHERE user_id = :UID AND scope_id = :SID AND permission = :PERM
             ORDER BY application_id DESC
             LIMIT 1",
            array(
                ':UID' => $uid,
                ':SID' => self::SYSTEM_SCOPE_ID,
                ':PERM' => trim((string) $permission),
            )
        );
        return is_array($row) ? $row : false;
    }

    /**
     * Approve, reject, or send back a pending application.
     *
     * Approval writes the grant in the same transaction as the status
     * change. Sending it back keeps the same row so the applicant can
     * edit and resubmit. A reply is required for that. Each decision
     * notifies the applicant. A second review of the same row is refused.
     *
     * @param int $applicationId
     * @param string $decision approved, denied, or revision
     * @param int $reviewerId
     * @param string $response Note included in the notification. Required for revision.
     * @return array{ok: true, status: string}|array{ok: false, error: string}
     */
    public static function reviewApplication($applicationId, $decision, $reviewerId, $response = '') {
        global $CFG, $PDOX;
        self::connection();

        $id = (int) $applicationId;
        if ( $id < 1 ) {
            return array('ok' => false, 'error' => 'Application not found.');
        }
        $decision = strtolower(trim((string) $decision));
        if ( $decision === 'approve' || $decision === self::STATUS_APPROVED ) {
            $status = self::STATUS_APPROVED;
        } else if ( $decision === 'reject' || $decision === self::STATUS_DENIED ) {
            $status = self::STATUS_DENIED;
        } else if ( $decision === 'revise' || $decision === self::STATUS_REVISION ) {
            $status = self::STATUS_REVISION;
        } else {
            return array('ok' => false, 'error' => 'Choose approve, reject, or send back.');
        }

        $response = trim((string) $response);
        if ( $status === self::STATUS_REVISION && $response === '' ) {
            return array('ok' => false, 'error' => 'Write a reply before sending this back.');
        }
        if ( U::strlen($response) > self::JUSTIFICATION_MAX ) {
            return array('ok' => false, 'error' => 'Keep the reply under '.self::JUSTIFICATION_MAX.' characters.');
        }

        $p = $CFG->dbprefix;
        $row = $PDOX->rowDie(
            "SELECT application_id, user_id, scope_id, permission, justification, status
             FROM {$p}site_application
             WHERE application_id = :ID",
            array(':ID' => $id)
        );
        if ( ! is_array($row) ) {
            return array('ok' => false, 'error' => 'Application not found.');
        }
        if ( (string) $row['status'] !== self::STATUS_PENDING ) {
            return array('ok' => false, 'error' => 'That application has already been reviewed.');
        }

        $reviewer = (int) $reviewerId;
        $PDOX->beginTransaction();
        $slot = $status === self::STATUS_REVISION
            ? self::openSlot((int) $row['user_id'], (string) $row['permission'])
            : null;
        $updated = $PDOX->queryReturnError(
            "UPDATE {$p}site_application
             SET status = :STATUS, reviewer_id = :RID, response = :RESP,
                 open_slot = :SLOT, reviewed_at = NOW()
             WHERE application_id = :ID AND status = :PENDING",
            array(
                ':STATUS' => $status,
                ':RID' => $reviewer > 0 ? $reviewer : null,
                ':RESP' => $response === '' ? null : $response,
                ':SLOT' => $slot,
                ':ID' => $id,
                ':PENDING' => self::STATUS_PENDING,
            )
        );
        if ( ! $updated->success || $updated->rowCount() < 1 ) {
            if ( $PDOX->inTransaction() ) {
                $PDOX->rollBack();
            }
            return array('ok' => false, 'error' => 'That application has already been reviewed.');
        }

        if ( $status === self::STATUS_APPROVED ) {
            $grantId = self::saveGrant(
                (int) $row['user_id'],
                (string) $row['permission'],
                self::SOURCE_APPLICATION,
                $reviewer,
                null,
                $id,
                false
            );
            if ( $grantId < 1 ) {
                if ( $PDOX->inTransaction() ) {
                    $PDOX->rollBack();
                }
                return array('ok' => false, 'error' => 'Could not save the grant.');
            }
        }

        $PDOX->commit();

        $row['status'] = $status;
        $row['response'] = $response;
        self::notifyApplicant($row, $status);
        return array('ok' => true, 'status' => $status);
    }

    /**
     * Title, text, and link for the applicant's decision notice.
     *
     * @param array<string, mixed> $application
     * @param string $status approved, denied, or revision
     * @return array{title: string, text: string, url: string}
     */
    public static function decisionNotice(array $application, $status): array {
        global $CFG;
        $permission = (string) ($application['permission'] ?? '');
        $registry = Permission::registry();
        $permTitle = isset($registry[$permission]['title']) ? (string) $registry[$permission]['title'] : '';
        $label = $permTitle !== '' ? $permTitle : $permission;
        $response = trim((string) ($application['response'] ?? ''));
        $status = (string) $status;
        $root = (isset($CFG) && isset($CFG->wwwroot)) ? rtrim((string) $CFG->wwwroot, '/') : '';
        if ( $status === self::STATUS_APPROVED ) {
            $title = 'Instructor application approved';
            $text = 'Your application for '.$label.' was approved.';
            if ( $permission === CoursesService::COURSE_CREATE ) {
                $text .= "\nYou can create courses now.";
            }
            $url = $root.'/instructor/';
        } else if ( $status === self::STATUS_REVISION ) {
            $title = 'Instructor application sent back for revisions';
            $text = 'Your instructor application was sent back for revisions.';
            $url = $root.'/instructor/apply';
        } else {
            $title = 'Instructor application not approved';
            $text = 'Your application for '.$label.' was not approved.';
            $url = $root.'/instructor/apply';
        }
        if ( $response !== '' ) {
            $text .= "\n\n".$response;
        }
        return array(
            'title' => $title,
            'text' => $text,
            'url' => $url,
        );
    }

    /**
     * In-app notice to the applicant. A failed send leaves the decision in place.
     *
     * @param array<string, mixed> $application
     * @param string $status
     */
    private static function notifyApplicant(array $application, $status) {
        $userId = (int) ($application['user_id'] ?? 0);
        if ( $userId < 1 ) {
            return;
        }
        $notice = self::decisionNotice($application, $status);
        $dedupe = 'site_application_decision_'.(int) ($application['application_id'] ?? 0);
        try {
            NotificationsService::create(
                $userId,
                $notice['title'],
                $notice['text'],
                $notice['url'],
                array(
                    'application_id' => (int) ($application['application_id'] ?? 0),
                    'status' => (string) $status,
                ),
                $dedupe
            );
        } catch ( \Throwable $e ) {
            error_log('Application decision notification failed for user '.$userId.': '.$e->getMessage());
        }
    }

    /**
     * Addresses in $CFG->adminemails, lowercased, with blanks dropped.
     *
     * @return list<string>
     */
    public static function adminEmails(): array {
        global $CFG;
        if ( ! isset($CFG) || ! is_object($CFG) ) {
            return array();
        }
        $allowed = $CFG->adminemails ?? false;
        if ( is_string($allowed) ) {
            $allowed = array($allowed);
        }
        if ( ! is_array($allowed) ) {
            return array();
        }
        $out = array();
        foreach ( $allowed as $one ) {
            $email = strtolower(trim((string) $one));
            if ( $email === '' || isset($out[$email]) ) {
                continue;
            }
            if ( ! filter_var($email, FILTER_VALIDATE_EMAIL) ) {
                continue;
            }
            $out[$email] = $email;
        }
        return array_values($out);
    }

    /**
     * Subject and body for an admin notice about a new application.
     *
     * @param array<string, mixed> $application
     * @param array<string, mixed> $applicant
     * @param bool $resubmit True after the applicant revises a sent-back request.
     * @return array{subject: string, message: string, title: string, text: string}
     */
    public static function applicationNotice(array $application, array $applicant, $resubmit = false): array {
        global $CFG;
        $name = trim((string) ($applicant['displayname'] ?? ''));
        $email = trim((string) ($applicant['email'] ?? ''));
        $uid = (int) ($application['user_id'] ?? $applicant['user_id'] ?? 0);
        if ( $name !== '' ) {
            $who = $name;
        } else if ( $email !== '' ) {
            $who = $email;
        } else {
            $who = 'User '.$uid;
        }
        $permission = (string) ($application['permission'] ?? '');
        $registry = Permission::registry();
        $permTitle = isset($registry[$permission]['title']) ? (string) $registry[$permission]['title'] : '';
        $justification = trim((string) ($application['justification'] ?? ''));
        $service = (isset($CFG) && isset($CFG->servicename)) ? (string) $CFG->servicename : 'Tsugi';
        $root = (isset($CFG) && isset($CFG->wwwroot)) ? rtrim((string) $CFG->wwwroot, '/') : '';
        $link = $root.'/admin/permissions/';
        $resubmit = (bool) $resubmit;
        $subject = $resubmit
            ? 'Instructor application resubmitted by '.$who
            : 'Instructor application from '.$who;
        $whoLine = $who;
        if ( $email !== '' && strcasecmp($who, $email) !== 0 ) {
            $whoLine .= ' ('.$email.')';
        }
        $whoLine .= ' ('.$uid.')';
        $permLine = $permission;
        if ( $permTitle !== '' && $permTitle !== $permission ) {
            $permLine .= ' — '.$permTitle;
        }
        $heading = $resubmit
            ? "Instructor application resubmitted on {$service}"
            : "Instructor application on {$service}";
        $reply = trim((string) ($application['response'] ?? ''));
        $message = "{$heading}\n\n"
            ."Applicant: {$whoLine}\n"
            ."Permission: {$permLine}\n\n";
        if ( $resubmit && $reply !== '' ) {
            $message .= "Previous reply:\n{$reply}\n\n";
        }
        $message .= "Justification:\n{$justification}\n\n"
            ."Review: {$link}\n";
        return array(
            'subject' => $subject,
            'message' => $message,
            'title' => $subject,
            'text' => $message,
        );
    }

    /**
     * Email every admin address, and notify each matching user account.
     *
     * Mail is skipped when $CFG->OFFLINE is true, or when mail is not
     * configured. A failed send does not undo the application.
     *
     * A resubmit uses its own notice so it does not replace the original one.
     *
     * @param array<string, mixed> $application
     * @param array<string, mixed> $applicant
     * @param bool $resubmit
     */
    public static function notifyAdmins(array $application, array $applicant, $resubmit = false) {
        global $CFG;
        $emails = self::adminEmails();
        if ( count($emails) < 1 ) {
            return;
        }
        $resubmit = (bool) $resubmit;
        $notice = self::applicationNotice($application, $applicant, $resubmit);
        $offline = isset($CFG->OFFLINE) && $CFG->OFFLINE === true;
        if ( ! $offline ) {
            foreach ( $emails as $email ) {
                try {
                    Mail::sendTransactional($email, $notice['subject'], $notice['message']);
                } catch ( \Throwable $e ) {
                    error_log('Application mail failed for '.$email.': '.$e->getMessage());
                }
            }
        }
        $url = rtrim((string) ($CFG->wwwroot ?? ''), '/').'/admin/permissions/';
        $id = (int) ($application['application_id'] ?? 0);
        $dedupe = $resubmit ? 'site_application_resubmit_'.$id : 'site_application_'.$id;
        foreach ( self::usersByEmail($emails) as $userId ) {
            try {
                NotificationsService::create(
                    $userId,
                    $notice['title'],
                    $notice['text'],
                    $url,
                    array('application_id' => (int) ($application['application_id'] ?? 0)),
                    $dedupe
                );
            } catch ( \Throwable $e ) {
                error_log('Application notification failed for user '.$userId.': '.$e->getMessage());
            }
        }
    }

    /**
     * Site-login accounts (the google.com key) whose email is in the list.
     *
     * An LMS launch can store the same address on another key. Those rows
     * are not notified.
     *
     * @param list<string> $emails Lowercased addresses.
     * @return list<int>
     */
    private static function usersByEmail(array $emails): array {
        global $CFG, $PDOX;
        if ( count($emails) < 1 ) {
            return array();
        }
        self::connection();
        $params = array(':SHA' => lti_sha256(CoursesService::GOOGLE_KEY));
        $holders = array();
        foreach ( array_values($emails) as $i => $email ) {
            $key = ':E'.$i;
            $holders[] = $key;
            $params[$key] = $email;
        }
        $p = $CFG->dbprefix;
        $rows = $PDOX->allRowsDie(
            "SELECT U.user_id
             FROM {$p}lti_user AS U
             JOIN {$p}lti_key AS K ON K.key_id = U.key_id
             WHERE U.deleted = 0
               AND K.key_sha256 = :SHA
               AND LOWER(U.email) IN (".implode(', ', $holders).")",
            $params
        );
        $ids = array();
        foreach ( $rows as $row ) {
            $id = (int) ($row['user_id'] ?? 0);
            if ( $id > 0 ) {
                $ids[$id] = $id;
            }
        }
        return array_values($ids);
    }

    /**
     * @param string $lookup
     * @return array{user_id: int}|array{error: string}
     */
    public static function findUser($lookup) {
        global $CFG, $PDOX;
        self::connection();
        $lookup = trim((string) $lookup);
        if ( $lookup === '' ) {
            return array('error' => 'Enter a user id or email.');
        }
        $p = $CFG->dbprefix;
        if ( ctype_digit($lookup) ) {
            $rows = $PDOX->allRowsDie(
                "SELECT user_id FROM {$p}lti_user
                 WHERE user_id = :ID AND deleted = 0",
                array(':ID' => (int) $lookup)
            );
        } else {
            $rows = $PDOX->allRowsDie(
                "SELECT user_id FROM {$p}lti_user
                 WHERE email = :EMAIL AND deleted = 0",
                array(':EMAIL' => $lookup)
            );
        }
        if ( count($rows) < 1 ) {
            return array('error' => 'No user found.');
        }
        if ( count($rows) > 1 ) {
            return array('error' => 'More than one user has that email.');
        }
        return array('user_id' => (int) $rows[0]['user_id']);
    }

    /**
     * @param string $raw
     * @return array{at: string|null}|array{error: string}
     */
    public static function parseExpires($raw) {
        $raw = trim((string) $raw);
        if ( $raw === '' ) {
            return array('at' => null);
        }
        $raw = str_replace('T', ' ', $raw);
        if ( preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $raw) ) {
            $raw .= ':00';
        }
        $ts = strtotime($raw);
        if ( $ts === false ) {
            return array('error' => 'Expiration is not a valid date.');
        }
        return array('at' => date('Y-m-d H:i:s', $ts));
    }

    /**
     * Unique while a request is in progress. Cleared after approve or reject.
     *
     * @param int $userId
     * @param string $permission
     */
    private static function openSlot($userId, $permission) {
        return ((int) $userId).':'.self::SYSTEM_SCOPE_ID.':'.trim((string) $permission);
    }

    private static function connection() {
        global $PDOX;
        if ( $PDOX === null || $PDOX === false ) {
            $PDOX = LTIX::getConnection();
        }
    }
}
