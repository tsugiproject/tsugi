<?php

namespace Tsugi\Services\Permissions;

use Tsugi\Core\LTIX;
use Tsugi\Services\Permission;

/**
 * Admin reads and writes for the permission tables.
 *
 * Grants are the rows an administrator hands out. Revoke keeps the row.
 * A second active grant for the same user, system scope, and permission
 * is rejected. Applications are listed and can be removed. The system
 * scope row is read-only here.
 */
class PermissionTables {

    const SYSTEM_SCOPE_ID = 1;

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
     * @return array<int, array<string, mixed>>
     */
    public static function applications() {
        global $CFG, $PDOX;
        self::connection();
        $p = $CFG->dbprefix;
        return $PDOX->allRowsDie(
            "SELECT A.application_id, A.user_id, A.scope_id, A.permission, A.status,
                    A.justification, A.response, A.created_at, A.reviewed_at,
                    U.displayname, U.email,
                    R.displayname AS reviewer_name
             FROM {$p}site_application AS A
             JOIN {$p}lti_user AS U ON U.user_id = A.user_id
             LEFT JOIN {$p}lti_user AS R ON R.user_id = A.reviewer_id
             ORDER BY A.application_id DESC
             LIMIT 500"
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function grants() {
        global $CFG, $PDOX;
        self::connection();
        $p = $CFG->dbprefix;
        return $PDOX->allRowsDie(
            "SELECT G.grant_id, G.user_id, G.scope_id, G.permission, G.source,
                    G.granted_at, G.expires_at, G.revoked_at, G.grantor_id,
                    U.displayname, U.email, U.create_courses,
                    GU.displayname AS grantor_name
             FROM {$p}site_grant AS G
             JOIN {$p}lti_user AS U ON U.user_id = G.user_id
             LEFT JOIN {$p}lti_user AS GU ON GU.user_id = G.grantor_id
             ORDER BY G.grant_id DESC
             LIMIT 500"
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

        $cols = 'user_id, scope_id, permission, source, granted_at';
        $vals = ':UID, :SID, :PERM, :SRC, NOW()';
        $params = array(
            ':UID' => $uid,
            ':SID' => self::SYSTEM_SCOPE_ID,
            ':PERM' => $permission,
            ':SRC' => 'admin',
        );
        $grantor = (int) $grantorId;
        if ( $grantor > 0 ) {
            $cols .= ', grantor_id';
            $vals .= ', :GID';
            $params[':GID'] = $grantor;
        }
        if ( $expires['at'] !== null ) {
            $cols .= ', expires_at';
            $vals .= ', :EXP';
            $params[':EXP'] = $expires['at'];
        }

        $PDOX->queryDie(
            "INSERT INTO {$p}site_grant ({$cols}) VALUES ({$vals})",
            $params
        );
        return array('ok' => true, 'grant_id' => (int) $PDOX->lastInsertId());
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

    private static function connection() {
        global $PDOX;
        if ( $PDOX === null || $PDOX === false ) {
            $PDOX = LTIX::getConnection();
        }
    }
}
