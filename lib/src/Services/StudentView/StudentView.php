<?php

namespace Tsugi\Services\StudentView;

use Tsugi\Controllers\Courses;
use Tsugi\Controllers\Tool;
use Tsugi\Core\Cache;
use Tsugi\Core\Guid;
use Tsugi\Core\LTIX;
use Tsugi\Core\ReqScope;
use Tsugi\Core\User;
use Tsugi\UI\Output;
use Tsugi\Util\U;

/**
 * Instructor "view as student" for one manifest course.
 *
 * Four named learners live on this course's tenant key. The user_key
 * includes the course id, so Nancy Drew in another course is a different
 * account. Entering creates the user, enrolls them as a learner, and
 * swaps the session onto that account. The instructor identity stays in
 * the session so Leave Student View can put it back.
 *
 * Reset deletes that lti_user row. Foreign keys cascade the membership,
 * results, discussion posts, and the other linked rows. Badges have no
 * foreign key, so those rows are deleted first. A test student is never
 * the course owner: lti_context.user_id cascades the whole course.
 */
class StudentView {

    const SESSION_KEY = 'tsugi_student_view';

    const ROUTE = '/student';

    /**
     * @return array<string, string> slug => display name
     */
    public static function roster() {
        return array(
            'nancy-drew' => 'Nancy Drew',
            'sue-bruno' => 'Sue Bruno',
            'jane-ford' => 'Jane Ford',
            'scott-land' => 'Scott Land',
        );
    }

    /**
     * @param mixed $slug
     * @return string
     */
    public static function nameFor($slug) {
        $slug = is_string($slug) ? $slug : '';
        $roster = self::roster();
        return $roster[$slug] ?? '';
    }

    /**
     * Two-letter mark for a display name: Nancy Drew is ND.
     *
     * @param mixed $name
     * @return string
     */
    public static function initials($name) {
        $name = is_string($name) ? trim(preg_replace('/\s+/', ' ', $name)) : '';
        if ( $name === '' ) {
            return '?';
        }
        $parts = explode(' ', $name);
        $first = self::firstLetter($parts[0]);
        $last = count($parts) > 1 ? self::firstLetter($parts[count($parts) - 1]) : '';
        $initials = $first.$last;
        if ( $initials === '' ) {
            return '?';
        }
        return function_exists('mb_strtoupper') ? mb_strtoupper($initials, 'UTF-8') : strtoupper($initials);
    }

    /**
     * Circle with the student's initials on the same art as an empty course hero.
     *
     * A data URI stays inside the session, so the menu does not need another request.
     *
     * @param mixed $name
     * @param mixed $slug
     * @return string
     */
    public static function avatarDataUri($name, $slug = '') {
        $slug = is_string($slug) ? $slug : '';
        $svg = \Tsugi\Core\ContextImages::initialsAvatarSvg(self::avatarSeed($slug), self::initials($name));
        return 'data:image/svg+xml,'.rawurlencode($svg);
    }

    /**
     * @param int $contextId
     * @param string $slug
     * @return string
     */
    public static function userKey($contextId, $slug) {
        return 'student-view:'.(int) $contextId.':'.$slug;
    }

    /**
     * @param string $word
     * @return string
     */
    private static function firstLetter($word) {
        if ( function_exists('mb_substr') ) {
            return mb_substr($word, 0, 1, 'UTF-8');
        }
        return substr($word, 0, 1);
    }

    /**
     * Seeds chosen so the four students get different hero palettes and patterns.
     *
     * @param string $slug
     * @return int
     */
    private static function avatarSeed($slug) {
        $seeds = array(
            'nancy-drew' => 75,
            'sue-bruno' => 5,
            'jane-ford' => 4,
            'scott-land' => 2,
        );
        if ( isset($seeds[$slug]) ) {
            return $seeds[$slug];
        }
        return 1000 + (hexdec(substr(md5('student-view-'.$slug), 0, 6)) % 8000);
    }

    /**
     * @return bool
     */
    public static function isActive() {
        $row = self::current();
        return $row !== null;
    }

    /**
     * @return string
     */
    public static function activeName() {
        $row = self::current();
        if ( $row === null ) {
            return '';
        }
        return (string) $row['name'];
    }

    /**
     * @return int
     */
    public static function activeContextId() {
        $row = self::current();
        if ( $row === null ) {
            return 0;
        }
        return (int) $row['context_id'];
    }

    /**
     * @return string ` class="tsugi-student-view"` or empty
     */
    public static function bodyAttribute() {
        if ( ! self::isActive() ) {
            return '';
        }
        return ' class="tsugi-student-view"';
    }

    /**
     * @return string
     */
    public static function css() {
        if ( ! self::isActive() ) {
            return '';
        }
        return '<style>
body.tsugi-student-view { padding-bottom: 4.75rem; box-sizing: border-box; }
body.tsugi-student-view::before {
  content: "";
  position: fixed;
  inset: 0;
  border: 10px solid #157a3a;
  pointer-events: none;
  z-index: 2000;
}
.tsugi-student-view-bar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 2001;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem 1.5rem;
  min-height: 3.25rem;
  padding: 0.65rem 1.25rem;
  background: #157a3a;
  color: #fff;
  font-size: 15px;
}
.tsugi-student-view-bar p { margin: 0; font-weight: 600; }
.tsugi-student-view-bar form { margin: 0; }
.tsugi-student-view-leave {
  background: #fff;
  color: #146b34;
  border: 0;
  border-radius: 3px;
  padding: 0.4em 0.85em;
  font-weight: 600;
}
.tsugi-student-view-leave:hover,
.tsugi-student-view-leave:focus { background: #e8f6ee; color: #0f5428; }
</style>
';
    }

    /**
     * Bottom bar for the swapped-in student. Empty when student view is off.
     *
     * @return string
     */
    public static function barHtml() {
        if ( ! self::isActive() ) {
            return '';
        }
        $name = self::activeName();
        $cid = self::activeContextId();
        $action = U::addSession(Courses::courseUrlPrefix($cid).self::ROUTE.'/leave');
        $who = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        return '<div class="tsugi-student-view-bar" role="region" aria-label="'.htmlspecialchars(__('Student View'), ENT_QUOTES, 'UTF-8').'">'
            .'<p>'.htmlspecialchars(__('Logged in as'), ENT_QUOTES, 'UTF-8').' '.$who.'</p>'
            .'<form method="post" action="'.htmlspecialchars($action, ENT_QUOTES, 'UTF-8').'">'
            .Tool::csrfField()
            .'<button type="submit" class="tsugi-student-view-leave">'.htmlspecialchars(__('Leave Student View'), ENT_QUOTES, 'UTF-8').'</button>'
            .'</form></div>';
    }

    /**
     * One row per test student for the instructor page.
     *
     * @param int $contextId
     * @return array<int, array{slug:string,name:string,user_id:int}>
     */
    public static function rows($contextId) {
        $out = array();
        foreach ( self::roster() as $slug => $name ) {
            $user = self::findUser((int) $contextId, $slug);
            $out[] = array(
                'slug' => $slug,
                'name' => $name,
                'user_id' => is_array($user) ? (int) $user['user_id'] : 0,
            );
        }
        return $out;
    }

    /**
     * Create the learner if needed, enroll them, and swap the session.
     *
     * A nested swap is refused by restoring the instructor first, so the
     * saved identity is always the real login.
     *
     * @param int $contextId
     * @param string $slug
     * @return array{ok:bool,error?:string,name?:string}
     */
    public static function enter($contextId, $slug) {
        if ( self::isActive() ) {
            self::restoreInstructor();
        }
        $ready = self::ensureLearner((int) $contextId, $slug);
        if ( empty($ready['ok']) ) {
            return $ready;
        }
        if ( ! self::switchTo($ready) ) {
            return array('ok' => false, 'error' => 'Could not switch to that student.');
        }
        return array('ok' => true, 'name' => (string) $ready['name']);
    }

    /**
     * Put the instructor session back. False when student view is not on.
     *
     * @return bool
     */
    public static function restoreInstructor() {
        $row = self::current();
        if ( $row === null ) {
            return false;
        }
        $saved = $row['saved'];
        if ( ! is_array($saved) ) {
            unset($_SESSION[self::SESSION_KEY]);
            return false;
        }
        self::applyKeys($_SESSION, $saved['session'] ?? array());
        $ltiKey = self::ltiKey();
        if ( ! isset($_SESSION[$ltiKey]) || ! is_array($_SESSION[$ltiKey]) ) {
            $_SESSION[$ltiKey] = array();
        }
        self::applyKeys($_SESSION[$ltiKey], $saved['lti'] ?? array());
        if ( ! empty($saved['admin_set']) ) {
            $_SESSION['admin'] = $saved['admin'];
        } else {
            unset($_SESSION['admin']);
        }
        unset($_SESSION[self::SESSION_KEY]);
        Cache::clearAllSessionCaches();
        Output::clearTopNavSession();
        ReqScope::resetIdentity();
        return true;
    }

    /**
     * Delete the test student. Linked rows cascade with the user.
     *
     * @param int $contextId
     * @param string $slug
     * @return array{ok:bool,error?:string,name?:string}
     */
    public static function reset($contextId, $slug) {
        $name = self::nameFor($slug);
        if ( $name === '' ) {
            return array('ok' => false, 'error' => 'That student is not one of the test students.');
        }
        $contextId = (int) $contextId;
        $user = self::findUser($contextId, $slug);
        if ( ! is_array($user) ) {
            return array('ok' => false, 'error' => $name.' is not in this course.');
        }
        $userId = (int) $user['user_id'];
        $active = self::current();
        if ( $active !== null && (int) $active['user_id'] === $userId ) {
            if ( ! self::restoreInstructor() ) {
                return array('ok' => false, 'error' => 'Leave student view before resetting this student.');
            }
        }
        if ( self::ownsCourseOrKey($userId) ) {
            return array('ok' => false, 'error' => $name.' owns a course or key, so that account was not deleted.');
        }

        global $CFG, $PDOX;
        $PDOX = LTIX::getConnection();
        $p = $CFG->dbprefix;
        $PDOX->queryReturnError(
            "DELETE FROM {$p}badges WHERE user_id = :UID",
            array(':UID' => $userId),
            false
        );
        $stmt = $PDOX->queryReturnError(
            "DELETE FROM {$p}lti_user WHERE user_id = :UID AND key_id = :KID AND user_sha256 = :SHA",
            array(
                ':UID' => $userId,
                ':KID' => (int) $user['key_id'],
                ':SHA' => (string) $user['user_sha256'],
            )
        );
        if ( ! is_object($stmt) || empty($stmt->success) ) {
            return array('ok' => false, 'error' => 'Could not delete '.$name.'.');
        }
        return array('ok' => true, 'name' => $name);
    }

    /**
     * @param array{user_id:int,user_key:string,email:string,name:string,membership_id:int,context_id:int,slug:string} $ready
     * @return bool
     */
    public static function switchTo(array $ready) {
        if ( self::isActive() ) {
            return false;
        }
        $userId = (int) ($ready['user_id'] ?? 0);
        $name = (string) ($ready['name'] ?? '');
        $contextId = (int) ($ready['context_id'] ?? 0);
        if ( $userId < 1 || $name === '' || $contextId < 1 ) {
            return false;
        }

        $ltiKey = self::ltiKey();
        $lti = (isset($_SESSION[$ltiKey]) && is_array($_SESSION[$ltiKey])) ? $_SESSION[$ltiKey] : array();
        $saved = array(
            'session' => self::captureKeys($_SESSION, self::sessionIdentityKeys()),
            'lti' => self::captureKeys($lti, self::ltiIdentityKeys()),
            'admin_set' => array_key_exists('admin', $_SESSION),
            'admin' => $_SESSION['admin'] ?? null,
        );

        $email = (string) ($ready['email'] ?? '');
        $userKey = (string) ($ready['user_key'] ?? '');
        $membershipId = (int) ($ready['membership_id'] ?? 0);

        $_SESSION['id'] = $userId;
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_key'] = $userKey;
        $_SESSION['email'] = $email;
        $_SESSION['displayname'] = $name;
        $avatar = self::avatarDataUri($name, (string) ($ready['slug'] ?? ''));
        $_SESSION['avatar'] = $avatar;
        $_SESSION['image'] = $avatar;
        unset($_SESSION['profile_id']);
        $_SESSION['isinstructor'] = false;
        $_SESSION['instructor'] = false;
        if ( $membershipId > 0 ) {
            $_SESSION['membership_id'] = $membershipId;
        } else {
            unset($_SESSION['membership_id']);
        }
        $_SESSION[User::SESSION_CREATE_COURSES] = 0;
        unset($_SESSION['admin']);

        $lti['user_id'] = $userId;
        $lti['user_key'] = $userKey;
        $lti['email'] = $email;
        $lti['displayname'] = $name;
        $lti['user_email'] = $email;
        $lti['user_displayname'] = $name;
        $lti['image'] = $avatar;
        $lti['user_image'] = $avatar;
        unset($lti['profile_id']);
        $lti['role'] = LTIX::ROLE_LEARNER;
        if ( $membershipId > 0 ) {
            $lti['membership_id'] = $membershipId;
        } else {
            unset($lti['membership_id']);
        }
        $_SESSION[$ltiKey] = $lti;

        $_SESSION[self::SESSION_KEY] = array(
            'context_id' => $contextId,
            'slug' => (string) ($ready['slug'] ?? ''),
            'name' => $name,
            'user_id' => $userId,
            'saved' => $saved,
        );

        Cache::clearAllSessionCaches();
        Output::clearTopNavSession();
        ReqScope::resetIdentity();
        return true;
    }

    /**
     * @param int $contextId
     * @param string $slug
     * @return array{ok:bool,error?:string,user_id?:int,user_key?:string,email?:string,name?:string,membership_id?:int,context_id?:int,slug?:string}
     */
    private static function ensureLearner($contextId, $slug) {
        $name = self::nameFor($slug);
        if ( $name === '' ) {
            return array('ok' => false, 'error' => 'That student is not one of the test students.');
        }
        $course = self::courseRow($contextId);
        if ( ! is_array($course) ) {
            return array('ok' => false, 'error' => 'Course not found.');
        }
        if ( (int) ($course['manifest_id'] ?? 0) < 1 ) {
            return array('ok' => false, 'error' => 'Student view is for courses that use a manifest.');
        }
        $keyId = (int) $course['key_id'];
        if ( $keyId < 1 ) {
            return array('ok' => false, 'error' => 'This course has no key.');
        }

        $userKey = self::userKey($contextId, $slug);
        $email = $slug.'@student-view.test';
        $user = self::findUser($contextId, $slug);
        if ( ! is_array($user) ) {
            $user = self::insertUser($keyId, $userKey, $name, $email);
        } else {
            self::refreshUser((int) $user['user_id'], $name, $email);
        }
        if ( ! is_array($user) ) {
            $user = self::findUser($contextId, $slug);
        }
        if ( ! is_array($user) || (int) ($user['user_id'] ?? 0) < 1 ) {
            return array('ok' => false, 'error' => 'Could not create '.$name.'.');
        }
        $userId = (int) $user['user_id'];
        $membershipId = self::enrollLearner($contextId, $userId);
        if ( $membershipId < 1 ) {
            return array('ok' => false, 'error' => 'Could not enroll '.$name.'.');
        }
        return array(
            'ok' => true,
            'user_id' => $userId,
            'user_key' => $userKey,
            'email' => $email,
            'name' => $name,
            'membership_id' => $membershipId,
            'context_id' => (int) $contextId,
            'slug' => $slug,
        );
    }

    /**
     * @param int $contextId
     * @return array{key_id:int,manifest_id:int}|null
     */
    private static function courseRow($contextId) {
        global $CFG, $PDOX;
        $cid = (int) $contextId;
        if ( $cid < 1 ) {
            return null;
        }
        $PDOX = LTIX::getConnection();
        $row = $PDOX->rowDie(
            "SELECT key_id, manifest_id FROM {$CFG->dbprefix}lti_context
             WHERE context_id = :CID AND (deleted IS NULL OR deleted = 0)",
            array(':CID' => $cid)
        );
        if ( ! is_array($row) ) {
            return null;
        }
        return array(
            'key_id' => (int) ($row['key_id'] ?? 0),
            'manifest_id' => (int) ($row['manifest_id'] ?? 0),
        );
    }

    /**
     * @param int $contextId
     * @param string $slug
     * @return array{user_id:int,key_id:int,user_sha256:string,user_key:string}|null
     */
    private static function findUser($contextId, $slug) {
        if ( self::nameFor($slug) === '' ) {
            return null;
        }
        $course = self::courseRow($contextId);
        if ( ! is_array($course) || (int) $course['key_id'] < 1 ) {
            return null;
        }
        $userKey = self::userKey($contextId, $slug);
        $sha = U::lti_sha256($userKey);
        if ( ! is_string($sha) || $sha === '' ) {
            return null;
        }
        global $CFG, $PDOX;
        $PDOX = LTIX::getConnection();
        $row = $PDOX->rowDie(
            "SELECT user_id, key_id, user_key, user_sha256 FROM {$CFG->dbprefix}lti_user
             WHERE key_id = :KID AND user_sha256 = :SHA
             LIMIT 1",
            array(':KID' => (int) $course['key_id'], ':SHA' => $sha)
        );
        if ( ! is_array($row) ) {
            return null;
        }
        if ( (string) ($row['user_key'] ?? '') !== $userKey ) {
            return null;
        }
        return array(
            'user_id' => (int) $row['user_id'],
            'key_id' => (int) $row['key_id'],
            'user_sha256' => (string) $row['user_sha256'],
            'user_key' => $userKey,
        );
    }

    /**
     * @param int $keyId
     * @param string $userKey
     * @param string $name
     * @param string $email
     * @return array{user_id:int,key_id:int,user_sha256:string,user_key:string}|null
     */
    private static function insertUser($keyId, $userKey, $name, $email) {
        global $CFG, $PDOX;
        $PDOX = LTIX::getConnection();
        $sha = U::lti_sha256($userKey);
        $stmt = $PDOX->queryReturnError(
            "INSERT INTO {$CFG->dbprefix}lti_user
                (guid, user_sha256, user_key, key_id, email, displayname, created_at, updated_at, login_at)
             VALUES
                (UNHEX(:GUID), :SHA, :UKEY, :KEY, :EMAIL, :DN, NOW(), NOW(), NOW())",
            array(
                ':GUID' => Guid::uuid7(),
                ':SHA' => $sha,
                ':UKEY' => $userKey,
                ':KEY' => (int) $keyId,
                ':EMAIL' => $email,
                ':DN' => $name,
            )
        );
        if ( ! is_object($stmt) || empty($stmt->success) ) {
            return null;
        }
        $userId = (int) $PDOX->lastInsertId();
        if ( $userId < 1 ) {
            return null;
        }
        return array(
            'user_id' => $userId,
            'key_id' => (int) $keyId,
            'user_sha256' => (string) $sha,
            'user_key' => $userKey,
        );
    }

    /**
     * @param int $userId
     * @param string $name
     * @param string $email
     */
    private static function refreshUser($userId, $name, $email) {
        global $CFG, $PDOX;
        $PDOX = LTIX::getConnection();
        $PDOX->queryReturnError(
            "UPDATE {$CFG->dbprefix}lti_user
             SET displayname = :DN, email = :EMAIL, deleted = 0, deleted_at = NULL,
                 login_at = NOW(), updated_at = NOW()
             WHERE user_id = :UID",
            array(':DN' => $name, ':EMAIL' => $email, ':UID' => (int) $userId)
        );
    }

    /**
     * Learner membership. A roster soft-delete is cleared so Enter puts them back.
     *
     * @param int $contextId
     * @param int $userId
     * @return int membership_id, or 0
     */
    private static function enrollLearner($contextId, $userId) {
        global $CFG, $PDOX;
        $PDOX = LTIX::getConnection();
        $p = $CFG->dbprefix;
        $row = $PDOX->rowDie(
            "SELECT membership_id FROM {$p}lti_membership
             WHERE context_id = :CID AND user_id = :UID",
            array(':CID' => (int) $contextId, ':UID' => (int) $userId)
        );
        if ( is_array($row) && (int) ($row['membership_id'] ?? 0) > 0 ) {
            $mid = (int) $row['membership_id'];
            $PDOX->queryDie(
                "UPDATE {$p}lti_membership
                 SET role = :ROLE, role_override = NULL, deleted = 0, deleted_at = NULL, updated_at = NOW()
                 WHERE membership_id = :MID",
                array(':ROLE' => LTIX::ROLE_LEARNER, ':MID' => $mid)
            );
            return $mid;
        }
        $stmt = $PDOX->queryReturnError(
            "INSERT INTO {$p}lti_membership
                (context_id, user_id, role, created_at, updated_at)
             VALUES
                (:CID, :UID, :ROLE, NOW(), NOW())",
            array(
                ':CID' => (int) $contextId,
                ':UID' => (int) $userId,
                ':ROLE' => LTIX::ROLE_LEARNER,
            )
        );
        $mid = (is_object($stmt) && ! empty($stmt->success)) ? (int) $PDOX->lastInsertId() : 0;
        if ( $mid > 0 ) {
            return $mid;
        }
        $again = $PDOX->rowDie(
            "SELECT membership_id FROM {$p}lti_membership
             WHERE context_id = :CID AND user_id = :UID",
            array(':CID' => (int) $contextId, ':UID' => (int) $userId)
        );
        return is_array($again) ? (int) ($again['membership_id'] ?? 0) : 0;
    }

    /**
     * True when deleting this user would take a course or tenant key with it.
     *
     * @param int $userId
     * @return bool
     */
    private static function ownsCourseOrKey($userId) {
        global $CFG, $PDOX;
        $PDOX = LTIX::getConnection();
        $p = $CFG->dbprefix;
        $uid = (int) $userId;
        $course = $PDOX->rowDie(
            "SELECT context_id FROM {$p}lti_context WHERE user_id = :UID LIMIT 1",
            array(':UID' => $uid)
        );
        if ( is_array($course) ) {
            return true;
        }
        $key = $PDOX->rowDie(
            "SELECT key_id FROM {$p}lti_key WHERE user_id = :UID LIMIT 1",
            array(':UID' => $uid)
        );
        return is_array($key);
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function current() {
        if ( ! isset($_SESSION[self::SESSION_KEY]) || ! is_array($_SESSION[self::SESSION_KEY]) ) {
            return null;
        }
        $row = $_SESSION[self::SESSION_KEY];
        if ( (int) ($row['context_id'] ?? 0) < 1 || (int) ($row['user_id'] ?? 0) < 1 ) {
            return null;
        }
        if ( ! is_string($row['name'] ?? null) || $row['name'] === '' ) {
            return null;
        }
        return $row;
    }

    /**
     * @return string
     */
    private static function ltiKey() {
        return defined('TSUGI_SESSION_LTI') ? TSUGI_SESSION_LTI : 'lti';
    }

    /**
     * @return array<int, string>
     */
    private static function sessionIdentityKeys() {
        return array(
            'id', 'user_id', 'user_key', 'email', 'displayname', 'avatar', 'image',
            'profile_id', 'isinstructor', 'instructor', 'membership_id',
            User::SESSION_CREATE_COURSES,
        );
    }

    /**
     * @return array<int, string>
     */
    private static function ltiIdentityKeys() {
        return array(
            'user_id', 'user_key', 'email', 'displayname', 'user_email', 'user_displayname',
            'image', 'user_image', 'profile_id', 'role', 'membership_id',
        );
    }

    /**
     * @param array<string, mixed> $source
     * @param array<int, string> $keys
     * @return array<string, array{set:bool,value?:mixed}>
     */
    private static function captureKeys(array $source, array $keys) {
        $out = array();
        foreach ( $keys as $key ) {
            if ( array_key_exists($key, $source) ) {
                $out[$key] = array('set' => true, 'value' => $source[$key]);
            } else {
                $out[$key] = array('set' => false);
            }
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $target
     * @param array<string, array{set:bool,value?:mixed}> $captured
     */
    private static function applyKeys(array &$target, array $captured) {
        foreach ( $captured as $key => $row ) {
            if ( ! is_array($row) ) {
                continue;
            }
            if ( ! empty($row['set']) ) {
                $target[$key] = $row['value'] ?? null;
            } else {
                unset($target[$key]);
            }
        }
    }
}
