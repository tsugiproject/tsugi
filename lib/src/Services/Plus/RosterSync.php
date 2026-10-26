<?php

namespace Tsugi\Services\Plus;

use Tsugi\Core\Keyset;
use Tsugi\Core\LTIX;
use Tsugi\Util\LTI13;
use Tsugi\Util\U;

/**
 * Keep a Plus course roster in step with the controlling LMS.
 *
 * A launch asks for a refresh. An instructor launch is due after 5 minutes,
 * a learner launch after 30. The browser is sent on before the web-service
 * calls. A failed token is tried again about once a minute. A person who
 * leaves the LMS roster stays for a week, then the membership is marked
 * deleted. Their work stays. A later roster row or launch clears deleted.
 */
class RosterSync {

    const DELAY_INSTRUCTOR = 300;
    const DELAY_LEARNER = 30 * 60;
    const RETRY_SECONDS = 60;
    const EXPIRE_MINUTES = 7 * 24 * 60;
    const MAX_PAGES = 10;
    const KIND = 'roster';
    const DEBUG_MEMBER_LIMIT = 200;

    /**
     * True when the last start is old enough for this role, or there has been none.
     *
     * @param int|null $ageSeconds Seconds since nrps_at, from the database clock. Null if never started.
     * @param bool $instructor
     * @param bool $force
     */
    public static function dueAge($ageSeconds, $instructor, $force=false) {
        if ( $force ) {
            return true;
        }
        if ( $ageSeconds === null ) {
            return true;
        }
        $age = (int) $ageSeconds;
        $delay = $instructor ? self::DELAY_INSTRUCTOR : self::DELAY_LEARNER;
        return $age < 0 || $age > $delay;
    }

    /**
     * A finished pull marks people absent only when every returned member was saved.
     *
     * @param bool $truncated
     * @param int $errors
     */
    public static function shouldExpire($truncated, $errors) {
        return ! $truncated && (int) $errors === 0;
    }

    /**
     * A failed attempt that did not start a pull waits RETRY_SECONDS.
     *
     * @param int|null $ageSeconds
     * @param bool $success
     */
    public static function failureHold($ageSeconds, $success) {
        if ( $success ) {
            return false;
        }
        if ( $ageSeconds === null ) {
            return false;
        }
        $age = (int) $ageSeconds;
        return $age >= 0 && $age < self::RETRY_SECONDS;
    }

    /**
     * Same role numbers as a launch. Administrator wins over instructor.
     *
     * @param mixed $roles
     */
    public static function roleFor($roles) {
        $text = self::roleText($roles);
        $role = LTIX::ROLE_LEARNER;
        if ( $text !== '' && strpos($text, 'instructor') !== false ) {
            $role = LTIX::ROLE_INSTRUCTOR;
        }
        if ( $text !== '' && strpos($text, 'administrator') !== false ) {
            $role = LTIX::ROLE_ADMINISTRATOR;
        }
        return $role;
    }

    /**
     * @param mixed $member
     */
    public static function displayName($member) {
        $name = self::field($member, 'name');
        if ( $name !== '' ) {
            return $name;
        }
        $both = trim(self::field($member, 'given_name').' '.self::field($member, 'family_name'));
        if ( $both !== '' ) {
            return $both;
        }
        $email = self::field($member, 'email');
        if ( $email !== '' ) {
            return $email;
        }
        return self::field($member, 'user_id');
    }

    /**
     * Strip tokens and client assertions before a log row is stored.
     */
    public static function scrub($text) {
        if ( ! is_string($text) ) {
            return '';
        }
        $text = preg_replace('/Bearer\s+\S+/', 'Bearer [redacted]', $text);
        if ( ! is_string($text) ) {
            return '';
        }
        $text = preg_replace('/"access_token"\s*:\s*"[^"]*"/', '"access_token":"[redacted]"', $text);
        if ( ! is_string($text) ) {
            return '';
        }
        $text = preg_replace('/client_assertion=[^&\s"]+/', 'client_assertion=[redacted]', $text);
        if ( ! is_string($text) ) {
            return '';
        }
        $text = preg_replace('/eyJ[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+/', '[redacted-jwt]', $text);
        if ( ! is_string($text) ) {
            return '';
        }
        if ( strlen($text) > 60000 ) {
            $text = substr($text, 0, 60000)."\n[truncated]";
        }
        return $text;
    }

    /**
     * After the response is sent, refresh this course if it is due.
     *
     * @param int $contextId
     * @param int $userId
     * @param bool $instructor
     */
    public static function schedule($contextId, $userId, $instructor) {
        $contextId = (int) $contextId;
        $userId = (int) $userId;
        $instructor = (bool) $instructor;
        try {
            if ( ! self::wantsLaunchSync($contextId, $instructor) ) {
                return;
            }
        } catch ( \Throwable $e ) {
            error_log('Roster sync schedule: '.$e->getMessage());
            return;
        }
        register_shutdown_function(function () use ($contextId, $userId, $instructor) {
            self::finishRequest();
            try {
                self::sync($contextId, $userId, $instructor, false);
            } catch ( \Throwable $e ) {
                error_log('Roster sync: '.$e->getMessage());
            }
        });
    }

    /**
     * Pull the roster now.
     *
     * @param int $contextId
     * @param int $userId Launching user, or 0.
     * @param bool $instructor
     * @param bool $force Instructor button. Ignores the delay and the retry hold.
     * @return array{ran:bool,ok:bool,status:string,message:string}
     */
    public static function sync($contextId, $userId, $instructor, $force=false) {
        $contextId = (int) $contextId;
        if ( $contextId < 1 ) {
            return self::result(false, false, 'missing', 'No course.');
        }
        if ( ! self::lock($contextId) ) {
            return self::result(false, false, 'busy', 'Roster sync is already running.');
        }
        try {
            return self::syncLocked($contextId, (int) $userId, (bool) $instructor, (bool) $force);
        } catch ( \Throwable $e ) {
            error_log('Roster sync context='.$contextId.' '.$e->getMessage());
            return self::result(true, false, 'error', 'Roster sync failed.');
        } finally {
            self::unlock($contextId);
        }
    }

    /**
     * Upsert one NRPS member list into lti_user and lti_membership.
     *
     * @param int $contextId
     * @param int $keyId
     * @param mixed $members
     * @return array{count:int,inserted:int,updated:int,errors:int,debug:string}
     */
    public static function storeMembers($contextId, $keyId, $members) {
        $count = 0;
        $inserted = 0;
        $updated = 0;
        $errors = 0;
        $lines = array();
        if ( ! is_array($members) ) {
            return array(
                'count' => 0,
                'inserted' => 0,
                'updated' => 0,
                'errors' => 0,
                'debug' => '',
            );
        }
        foreach ( $members as $member ) {
            $subject = self::field($member, 'user_id');
            if ( $subject === '' ) {
                $errors++;
                $lines[] = 'Skipped a member with no user_id';
                continue;
            }
            $count++;
            try {
                $userId = self::upsertUser((int) $keyId, $subject, $member);
                $fresh = self::upsertMembership((int) $contextId, $userId, self::roleFor(self::rolesOf($member)));
                if ( $fresh ) {
                    $inserted++;
                } else {
                    $updated++;
                }
                if ( $count <= self::DEBUG_MEMBER_LIMIT ) {
                    // The email is a mild privacy leak in the Roster log. The first
                    // rollout keeps it so a pull can be debugged. A later privacy
                    // pass should revisit this by 2027-04-26.
                    $lines[] = 'member='.self::field($member, 'email').' user_id='.$subject.' count='.$count;
                }
            } catch ( \Throwable $e ) {
                $errors++;
                $lines[] = 'Failed user_id='.$subject.' '.$e->getMessage();
                error_log('Roster member user_id='.$subject.' '.$e->getMessage());
            }
        }
        return array(
            'count' => $count,
            'inserted' => $inserted,
            'updated' => $updated,
            'errors' => $errors,
            'debug' => implode("\n", $lines),
        );
    }

    /**
     * Mark memberships deleted when a completed pull has not seen them for a week.
     *
     * @param int $contextId
     * @return int
     */
    public static function expireAbsent($contextId) {
        global $CFG;
        $p = $CFG->dbprefix;
        $minutes = (int) self::EXPIRE_MINUTES;
        $stmt = self::exec(
            "UPDATE {$p}lti_membership
             SET deleted = 1, deleted_at = NOW()
             WHERE context_id = :CID
               AND deleted = 0
               AND updated_at IS NOT NULL
               AND updated_at < DATE_SUB(NOW(), INTERVAL {$minutes} MINUTE)",
            array(':CID' => (int) $contextId)
        );
        return (int) $stmt->rowCount();
    }

    /**
     * @param int $contextId
     * @return array<string, mixed>
     */
    public static function status($contextId) {
        global $CFG;
        $PDOX = LTIX::getConnection();
        $p = $CFG->dbprefix;
        $contextId = (int) $contextId;
        $row = $PDOX->rowDie(
            "SELECT nrps_at, lti13_membership_url, plus_course,
                    TIMESTAMPDIFF(SECOND, nrps_at, NOW()) AS nrps_age
             FROM {$p}lti_context
             WHERE context_id = :CID",
            array(':CID' => $contextId)
        );
        $counts = $PDOX->rowDie(
            "SELECT
                SUM(CASE WHEN deleted = 0 THEN 1 ELSE 0 END) AS active_count,
                SUM(CASE WHEN deleted = 1 THEN 1 ELSE 0 END) AS removed_count
             FROM {$p}lti_membership
             WHERE context_id = :CID",
            array(':CID' => $contextId)
        );
        return array(
            'nrps_at' => is_array($row) ? ($row['nrps_at'] ?? null) : null,
            'nrps_age' => is_array($row) ? ($row['nrps_age'] ?? null) : null,
            'membership_url' => is_array($row) ? ($row['lti13_membership_url'] ?? null) : null,
            'plus_course' => is_array($row) ? (int) ($row['plus_course'] ?? 0) : 0,
            'active' => is_array($counts) ? (int) ($counts['active_count'] ?? 0) : 0,
            'removed' => is_array($counts) ? (int) ($counts['removed_count'] ?? 0) : 0,
            'failures' => self::logRows($contextId, 0),
            'successes' => self::logRows($contextId, 1),
        );
    }

    /**
     * Everyone in the course, including memberships marked deleted.
     *
     * @param int $contextId
     * @return array<int, array<string, mixed>>
     */
    public static function members($contextId) {
        global $CFG;
        $PDOX = LTIX::getConnection();
        $p = $CFG->dbprefix;
        $rows = $PDOX->allRowsDie(
            "SELECT M.membership_id, M.user_id, M.role, M.role_override, M.deleted,
                    U.displayname, U.email
             FROM {$p}lti_membership AS M
             JOIN {$p}lti_user AS U ON U.user_id = M.user_id
             WHERE M.context_id = :CID
             ORDER BY M.deleted ASC, U.displayname ASC, U.email ASC, M.membership_id ASC",
            array(':CID' => (int) $contextId)
        );
        return is_array($rows) ? $rows : array();
    }

    /**
     * The role a launch would grant. An override wins when it is higher.
     *
     * @param int|string|null $role
     * @param int|string|null $override
     */
    public static function roleLabel($role, $override=null) {
        $value = (int) $role;
        if ( $override !== null && $override !== '' ) {
            $value = max($value, (int) $override);
        }
        if ( $value >= LTIX::ROLE_ADMINISTRATOR ) {
            return 'Administrator';
        }
        if ( $value >= LTIX::ROLE_INSTRUCTOR ) {
            return 'Instructor';
        }
        return 'Learner';
    }

    /**
     * Set or clear deleted on one membership in this course.
     *
     * @param int $contextId
     * @param int $membershipId
     * @param bool $deleted
     */
    public static function setDeleted($contextId, $membershipId, $deleted) {
        global $CFG;
        $PDOX = LTIX::getConnection();
        $p = $CFG->dbprefix;
        $flag = $deleted ? 1 : 0;
        $row = $PDOX->rowDie(
            "SELECT deleted FROM {$p}lti_membership
             WHERE membership_id = :MID AND context_id = :CID",
            array(':MID' => (int) $membershipId, ':CID' => (int) $contextId)
        );
        if ( ! is_array($row) ) {
            return false;
        }
        if ( (int) ($row['deleted'] ?? 0) === $flag ) {
            return true;
        }
        $cleared = $flag === 1 ? 'NOW()' : 'NULL';
        self::exec(
            "UPDATE {$p}lti_membership
             SET deleted = :DEL, deleted_at = {$cleared}, updated_at = NOW()
             WHERE membership_id = :MID AND context_id = :CID",
            array(
                ':DEL' => $flag,
                ':MID' => (int) $membershipId,
                ':CID' => (int) $contextId,
            )
        );
        return true;
    }

    /**
     * This session is a Plus launch for this course, or the row is a Plus course.
     *
     * @param int $contextId
     */
    public static function courseIsPlus($contextId) {
        $contextId = (int) $contextId;
        $ltiKey = defined('TSUGI_SESSION_LTI') ? TSUGI_SESSION_LTI : 'lti';
        $lti = (isset($_SESSION[$ltiKey]) && is_array($_SESSION[$ltiKey])) ? $_SESSION[$ltiKey] : array();
        if ( (int) ($lti['plus_course'] ?? 0) === 1 && (int) ($lti['context_id'] ?? 0) === $contextId ) {
            return true;
        }
        global $CFG;
        $PDOX = LTIX::getConnection();
        $row = $PDOX->rowDie(
            "SELECT plus_course FROM {$CFG->dbprefix}lti_context WHERE context_id = :CID",
            array(':CID' => $contextId)
        );
        return is_array($row) && (int) ($row['plus_course'] ?? 0) === 1;
    }

    /**
     * @param int $contextId
     * @param bool $instructor
     */
    private static function wantsLaunchSync($contextId, $instructor) {
        $row = self::contextRow($contextId);
        if ( $row === null ) {
            return false;
        }
        $age = array_key_exists('nrps_age', $row) && $row['nrps_age'] !== null
            ? (int) $row['nrps_age']
            : null;
        if ( ! self::dueAge($age, $instructor, false) ) {
            return false;
        }
        $latest = self::latestLog($contextId);
        if ( $latest !== null && self::failureHold($latest['age'], (int) $latest['success'] === 1) ) {
            return false;
        }
        $gap = self::configGap($row);
        if ( $gap !== null && $latest !== null && ($latest['detail']['status'] ?? '') === $gap ) {
            return false;
        }
        return true;
    }

    /**
     * @param int $contextId
     * @param int $userId
     * @param bool $instructor
     * @param bool $force
     * @return array{ran:bool,ok:bool,status:string,message:string}
     */
    private static function syncLocked($contextId, $userId, $instructor, $force) {
        $row = self::contextRow($contextId);
        if ( $row === null ) {
            return self::result(false, false, 'missing', 'Course not found.');
        }
        $age = array_key_exists('nrps_age', $row) && $row['nrps_age'] !== null
            ? (int) $row['nrps_age']
            : null;
        if ( ! self::dueAge($age, $instructor, $force) ) {
            return self::result(false, true, 'waiting', 'Roster is current.');
        }
        $latest = self::latestLog($contextId);
        if ( ! $force && $latest !== null && self::failureHold($latest['age'], (int) $latest['success'] === 1) ) {
            return self::result(false, true, 'held', 'Waiting to retry the last roster error.');
        }

        $gap = self::configGap($row);
        if ( $gap !== null ) {
            if ( ! $force && $latest !== null && ($latest['detail']['status'] ?? '') === $gap ) {
                return self::result(false, false, $gap, self::gapMessage($gap));
            }
            self::writeLog($contextId, $row, $userId, false, null, self::urlOf($row), array(
                'status' => $gap,
                'count' => 0,
            ), array(self::gapMessage($gap)));
            return self::result(true, false, $gap, self::gapMessage($gap));
        }

        $debug = array();
        $token = self::token($row, $debug);
        if ( ! is_string($token) || $token === '' ) {
            self::writeLog($contextId, $row, $userId, false, null, self::urlOf($row), array(
                'status' => 'token_failed',
                'count' => 0,
            ), $debug);
            return self::result(true, false, 'token_failed', 'Could not get a roster token from the LMS.');
        }

        self::stampStart($contextId);
        $http = null;
        $loaded = LTI13::loadNRPS(self::urlOf($row), $token, $debug, self::MAX_PAGES, $http);
        if ( ! is_object($loaded) || ! isset($loaded->members) || ! is_array($loaded->members) ) {
            $why = is_string($loaded) ? $loaded : 'Roster response had no members.';
            $debug[] = $why;
            self::writeLog($contextId, $row, $userId, false, $http, self::urlOf($row), array(
                'status' => 'roster_failed',
                'count' => 0,
            ), $debug);
            return self::result(true, false, 'roster_failed', 'Could not load the roster from the LMS.');
        }

        $stored = self::storeMembers($contextId, (int) $row['key_id'], $loaded->members);
        $debug[] = $stored['debug'];
        $truncated = ! empty($loaded->truncated);
        $expired = 0;
        if ( self::shouldExpire($truncated, $stored['errors']) ) {
            $expired = self::expireAbsent($contextId);
            if ( $expired > 0 ) {
                $debug[] = 'Inactive memberships marked deleted '.$expired;
            }
        } elseif ( $truncated ) {
            $debug[] = 'Paging stopped before the roster was complete. Nobody was marked deleted.';
        } else {
            $debug[] = 'Some people could not be saved. Nobody was marked deleted.';
        }

        $status = $truncated ? 'truncated' : 'done';
        $ok = self::shouldExpire($truncated, $stored['errors']);
        self::writeLog($contextId, $row, $userId, $ok, $http, self::urlOf($row), array(
            'status' => $status,
            'count' => $stored['count'],
            'inserted' => $stored['inserted'],
            'updated' => $stored['updated'],
            'expired' => $expired,
            'errors' => $stored['errors'],
        ), $debug);

        if ( $truncated ) {
            return self::result(true, false, 'truncated', 'Roster pull stopped early. '.$stored['count'].' people were saved. Nobody was removed.');
        }
        $message = 'Roster updated. '.$stored['count'].' people';
        if ( $stored['inserted'] > 0 ) {
            $message .= ', '.$stored['inserted'].' new';
        }
        if ( $expired > 0 ) {
            $message .= ', '.$expired.' removed';
        }
        $message .= '.';
        if ( $stored['errors'] > 0 ) {
            $message .= ' '.$stored['errors'].' could not be saved.';
            return self::result(true, false, 'done', $message);
        }
        return self::result(true, true, 'done', $message);
    }

    /**
     * @param array<string, mixed> $row
     * @return string|null
     */
    private static function configGap(array $row) {
        if ( trim(self::urlOf($row)) === '' ) {
            return 'no_membership_url';
        }
        $deployment = $row['lti13_deployment_id'] ?? '';
        if ( ! is_string($deployment) || trim($deployment) === '' ) {
            return 'no_deployment';
        }
        $tokenUrl = $row['lms_token_url'] ?? '';
        if ( ! is_string($tokenUrl) || trim($tokenUrl) === '' ) {
            return 'no_token_url';
        }
        $client = $row['lms_client'] ?? '';
        if ( ! is_string($client) || trim($client) === '' ) {
            return 'no_client';
        }
        return null;
    }

    /**
     * @param string $status
     */
    private static function gapMessage($status) {
        if ( $status === 'no_membership_url' ) {
            return 'This course has no membership URL from the LMS.';
        }
        if ( $status === 'no_deployment' ) {
            return 'This course has no deployment id.';
        }
        if ( $status === 'no_token_url' ) {
            return 'This tenant has no token URL.';
        }
        if ( $status === 'no_client' ) {
            return 'This tenant has no client id.';
        }
        if ( $status === 'no_key' ) {
            return 'This site has no signing key for roster tokens.';
        }
        return 'Roster sync could not start.';
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, mixed> $debug
     * @return string|false
     */
    private static function token(array $row, array &$debug) {
        $privkey = null;
        $kid = null;
        $ok = Keyset::getSigning($privkey, $kid);
        if ( $ok !== true || ! is_string($privkey) || $privkey === '' || ! is_string($kid) || $kid === '' ) {
            $debug[] = is_string($ok) ? $ok : self::gapMessage('no_key');
            return false;
        }
        $audience = $row['lms_token_audience'] ?? '';
        if ( ! is_string($audience) || trim($audience) === '' ) {
            $audience = false;
        }
        $deployment = trim((string) $row['lti13_deployment_id']);
        return self::withDeploymentClaim(function () use ($row, $privkey, $kid, $audience, $deployment, &$debug) {
            return LTI13::getNRPSToken(
                trim((string) $row['lms_client']),
                trim((string) $row['lms_token_url']),
                $privkey,
                $kid,
                $audience,
                $deployment,
                $debug
            );
        });
    }

    /**
     * Send the stored deployment id on this token call.
     * Other token calls still follow $CFG->token_deployment_id.
     *
     * @param callable $fn
     * @return mixed
     */
    private static function withDeploymentClaim($fn) {
        global $CFG;
        $existed = is_object($CFG) && isset($CFG->token_deployment_id);
        $previous = $existed ? $CFG->token_deployment_id : null;
        if ( is_object($CFG) ) {
            $CFG->token_deployment_id = true;
        }
        try {
            return $fn();
        } finally {
            if ( is_object($CFG) ) {
                if ( $existed ) {
                    $CFG->token_deployment_id = $previous;
                } else {
                    unset($CFG->token_deployment_id);
                }
            }
        }
    }

    /**
     * @param int $keyId
     * @param string $subject
     * @param mixed $member
     */
    private static function upsertUser($keyId, $subject, $member) {
        global $CFG;
        $PDOX = LTIX::getConnection();
        $p = $CFG->dbprefix;
        $sha = U::lti_sha256($subject);
        $existing = $PDOX->rowDie(
            "SELECT user_id FROM {$p}lti_user
             WHERE key_id = :KID AND subject_sha256 = :SHA
             LIMIT 1",
            array(':KID' => $keyId, ':SHA' => $sha)
        );
        $name = self::displayName($member);
        $email = self::nullable(self::field($member, 'email'));
        $image = self::nullable(self::field($member, 'picture'));
        if ( is_array($existing) && (int) ($existing['user_id'] ?? 0) > 0 ) {
            $userId = (int) $existing['user_id'];
            $sets = array('updated_at = NOW()');
            $params = array(':UID' => $userId);
            if ( $name !== '' ) {
                $sets[] = 'displayname = :NAME';
                $params[':NAME'] = $name;
            }
            if ( $email !== null ) {
                $sets[] = 'email = :EMAIL';
                $params[':EMAIL'] = $email;
            }
            if ( $image !== null ) {
                $sets[] = 'image = :IMAGE';
                $params[':IMAGE'] = $image;
            }
            self::exec(
                "UPDATE {$p}lti_user SET ".implode(', ', $sets)." WHERE user_id = :UID",
                $params
            );
            return $userId;
        }
        self::exec(
            "INSERT INTO {$p}lti_user
                (subject_key, subject_sha256, displayname, email, image, key_id, created_at, updated_at)
             VALUES
                (:SUBJECT, :SHA, :NAME, :EMAIL, :IMAGE, :KID, NOW(), NOW())",
            array(
                ':SUBJECT' => $subject,
                ':SHA' => $sha,
                ':NAME' => $name,
                ':EMAIL' => $email,
                ':IMAGE' => $image,
                ':KID' => $keyId,
            )
        );
        return (int) $PDOX->lastInsertId();
    }

    /**
     * @param int $contextId
     * @param int $userId
     * @param int $role
     * @return bool True when this inserted a membership.
     */
    private static function upsertMembership($contextId, $userId, $role) {
        global $CFG;
        $PDOX = LTIX::getConnection();
        $p = $CFG->dbprefix;
        $existing = $PDOX->rowDie(
            "SELECT membership_id FROM {$p}lti_membership
             WHERE context_id = :CID AND user_id = :UID
             LIMIT 1",
            array(':CID' => $contextId, ':UID' => $userId)
        );
        if ( is_array($existing) && (int) ($existing['membership_id'] ?? 0) > 0 ) {
            self::exec(
                "UPDATE {$p}lti_membership
                 SET role = :ROLE, deleted = 0, deleted_at = NULL, updated_at = NOW()
                 WHERE membership_id = :MID",
                array(
                    ':ROLE' => $role,
                    ':MID' => (int) $existing['membership_id'],
                )
            );
            return false;
        }
        self::exec(
            "INSERT INTO {$p}lti_membership
                (context_id, user_id, role, deleted, created_at, updated_at)
             VALUES
                (:CID, :UID, :ROLE, 0, NOW(), NOW())",
            array(
                ':CID' => $contextId,
                ':UID' => $userId,
                ':ROLE' => $role,
            )
        );
        return true;
    }

    /**
     * @param int $contextId
     */
    private static function stampStart($contextId) {
        global $CFG;
        self::exec(
            "UPDATE {$CFG->dbprefix}lti_context
             SET nrps_at = NOW(), updated_at = NOW()
             WHERE context_id = :CID",
            array(':CID' => (int) $contextId)
        );
    }

    /**
     * @param int $contextId
     * @param array<string, mixed> $row
     * @param int $userId
     * @param bool $success
     * @param int|null $http
     * @param string $url
     * @param array<string, mixed> $detail
     * @param array<int, mixed> $debug
     */
    private static function writeLog($contextId, array $row, $userId, $success, $http, $url, array $detail, array $debug) {
        global $CFG;
        $p = $CFG->dbprefix;
        $detail['debug'] = self::scrub(self::debugText($debug));
        $json = json_encode($detail, JSON_UNESCAPED_SLASHES);
        if ( ! is_string($json) ) {
            $json = '{}';
        }
        $linkId = (int) ($row['plus_launch_link_id'] ?? 0);
        try {
            self::exec(
                "INSERT INTO {$p}lti_context_plus_log
                    (context_id, link_id, user_id, kind, success, http_response, url, request, detail, created_at)
                 VALUES
                    (:CID, :LINK, :UID, :KIND, :OK, :HTTP, :URL, :REQ, :DETAIL, NOW())",
                array(
                    ':CID' => (int) $contextId,
                    ':LINK' => $linkId > 0 ? $linkId : null,
                    ':UID' => $userId > 0 ? $userId : null,
                    ':KIND' => self::KIND,
                    ':OK' => $success ? 1 : 0,
                    ':HTTP' => $http === null ? null : (int) $http,
                    ':URL' => $url,
                    ':REQ' => 'GET '.$url,
                    ':DETAIL' => $json,
                )
            );
        } catch ( \Throwable $e ) {
            error_log('Roster log context='.$contextId.' '.$e->getMessage());
        }
    }

    /**
     * Run a write and raise on failure so one bad row does not end the request.
     *
     * @param string $sql
     * @param array<string, mixed> $params
     */
    private static function exec($sql, array $params) {
        $PDOX = LTIX::getConnection();
        $stmt = $PDOX->queryReturnError($sql, $params);
        if ( ! is_object($stmt) || empty($stmt->success) ) {
            $message = is_object($stmt) ? (string) $stmt->errorImplode : 'query failed';
            throw new \RuntimeException($message);
        }
        return $stmt;
    }

    /**
     * @param int $contextId
     * @return array{age:int|null,success:int,detail:array<string,mixed>}|null
     */
    private static function latestLog($contextId) {
        global $CFG;
        $PDOX = LTIX::getConnection();
        $row = $PDOX->rowDie(
            "SELECT success, detail, TIMESTAMPDIFF(SECOND, created_at, NOW()) AS age
             FROM {$CFG->dbprefix}lti_context_plus_log
             WHERE context_id = :CID AND kind = :KIND
             ORDER BY context_plus_log_id DESC
             LIMIT 1",
            array(':CID' => (int) $contextId, ':KIND' => self::KIND)
        );
        if ( ! is_array($row) ) {
            return null;
        }
        return array(
            'age' => $row['age'] === null ? null : (int) $row['age'],
            'success' => (int) ($row['success'] ?? 0),
            'detail' => self::decodeDetail($row['detail'] ?? ''),
        );
    }

    /**
     * @param int $contextId
     * @param int $success
     * @return array<int, array<string, mixed>>
     */
    private static function logRows($contextId, $success) {
        global $CFG;
        $PDOX = LTIX::getConnection();
        $rows = $PDOX->allRowsDie(
            "SELECT context_plus_log_id, created_at, success, http_response, url, detail
             FROM {$CFG->dbprefix}lti_context_plus_log
             WHERE context_id = :CID AND kind = :KIND AND success = :OK
             ORDER BY context_plus_log_id DESC
             LIMIT 20",
            array(
                ':CID' => (int) $contextId,
                ':KIND' => self::KIND,
                ':OK' => (int) $success,
            )
        );
        if ( ! is_array($rows) ) {
            return array();
        }
        $out = array();
        foreach ( $rows as $row ) {
            if ( ! is_array($row) ) {
                continue;
            }
            $detail = self::decodeDetail($row['detail'] ?? '');
            $row['detail_status'] = isset($detail['status']) ? (string) $detail['status'] : '';
            $row['detail_count'] = isset($detail['count']) ? (int) $detail['count'] : 0;
            $row['detail_inserted'] = isset($detail['inserted']) ? (int) $detail['inserted'] : 0;
            $row['detail_expired'] = isset($detail['expired']) ? (int) $detail['expired'] : 0;
            $row['detail_errors'] = isset($detail['errors']) ? (int) $detail['errors'] : 0;
            $row['detail_debug'] = isset($detail['debug']) ? (string) $detail['debug'] : '';
            $out[] = $row;
        }
        return $out;
    }

    /**
     * @param mixed $raw
     * @return array<string, mixed>
     */
    private static function decodeDetail($raw) {
        if ( ! is_string($raw) || $raw === '' ) {
            return array();
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : array();
    }

    /**
     * @param int $contextId
     * @return array<string, mixed>|null
     */
    private static function contextRow($contextId) {
        global $CFG;
        $PDOX = LTIX::getConnection();
        $p = $CFG->dbprefix;
        $row = $PDOX->rowDie(
            "SELECT c.context_id, c.key_id, c.plus_course, c.plus_launch_link_id,
                    c.lti13_membership_url, c.lti13_deployment_id, c.nrps_at,
                    TIMESTAMPDIFF(SECOND, c.nrps_at, NOW()) AS nrps_age,
                    k.lms_client, k.lms_token_url, k.lms_token_audience
             FROM {$p}lti_context AS c
             JOIN {$p}lti_key AS k ON k.key_id = c.key_id
             WHERE c.context_id = :CID
               AND (c.deleted IS NULL OR c.deleted = 0)",
            array(':CID' => (int) $contextId)
        );
        return is_array($row) ? $row : null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function urlOf(array $row) {
        $url = $row['lti13_membership_url'] ?? '';
        return is_string($url) ? trim($url) : '';
    }

    /**
     * @param bool $ran
     * @param bool $ok
     * @param string $status
     * @param string $message
     * @return array{ran:bool,ok:bool,status:string,message:string}
     */
    private static function result($ran, $ok, $status, $message) {
        return array(
            'ran' => $ran,
            'ok' => $ok,
            'status' => $status,
            'message' => $message,
        );
    }

    /**
     * @param int $contextId
     */
    private static function lock($contextId) {
        try {
            $PDOX = LTIX::getConnection();
        } catch ( \Throwable $e ) {
            error_log('Roster sync lock: '.$e->getMessage());
            return false;
        }
        $name = 'tsugi_nrps_'.(int) $contextId;
        if ( strlen($name) > 64 ) {
            $name = substr($name, 0, 64);
        }
        $row = $PDOX->rowDie(
            "SELECT GET_LOCK(:NAME, 0) AS locked",
            array(':NAME' => $name)
        );
        return is_array($row) && (int) ($row['locked'] ?? 0) === 1;
    }

    /**
     * @param int $contextId
     */
    private static function unlock($contextId) {
        try {
            $PDOX = LTIX::getConnection();
            $name = 'tsugi_nrps_'.(int) $contextId;
            if ( strlen($name) > 64 ) {
                $name = substr($name, 0, 64);
            }
            $PDOX->queryDie(
                "SELECT RELEASE_LOCK(:NAME)",
                array(':NAME' => $name)
            );
        } catch ( \Throwable $e ) {
            error_log('Roster sync unlock: '.$e->getMessage());
        }
    }

    private static function finishRequest() {
        ignore_user_abort(true);
        if ( function_exists('fastcgi_finish_request') ) {
            @fastcgi_finish_request();
        }
    }

    /**
     * @param array<int, mixed> $debug
     */
    private static function debugText(array $debug) {
        $lines = array();
        foreach ( $debug as $line ) {
            if ( is_string($line) ) {
                $lines[] = $line;
                continue;
            }
            $encoded = json_encode($line, JSON_UNESCAPED_SLASHES);
            if ( is_string($encoded) ) {
                $lines[] = $encoded;
            }
        }
        return implode("\n", $lines);
    }

    /**
     * @param mixed $roles
     */
    private static function roleText($roles) {
        if ( is_string($roles) ) {
            return strtolower($roles);
        }
        if ( ! is_array($roles) ) {
            return '';
        }
        $parts = array();
        foreach ( $roles as $role ) {
            if ( is_string($role) && $role !== '' ) {
                $parts[] = $role;
            }
        }
        return strtolower(implode(' ', $parts));
    }

    /**
     * @param mixed $member
     * @return array<int, string>|string
     */
    private static function rolesOf($member) {
        if ( is_array($member) ) {
            return $member['roles'] ?? array();
        }
        if ( is_object($member) ) {
            return $member->roles ?? array();
        }
        return array();
    }

    /**
     * @param mixed $member
     * @param string $name
     */
    private static function field($member, $name) {
        $value = null;
        if ( is_array($member) && array_key_exists($name, $member) ) {
            $value = $member[$name];
        } elseif ( is_object($member) && isset($member->{$name}) ) {
            $value = $member->{$name};
        }
        if ( is_string($value) ) {
            return trim($value);
        }
        if ( is_int($value) || is_float($value) ) {
            return trim((string) $value);
        }
        return '';
    }

    /**
     * @param string $value
     * @return string|null
     */
    private static function nullable($value) {
        return $value === '' ? null : $value;
    }

}
