<?php

namespace Tsugi\Core;

use Tsugi\Util\U;

/**
 * Request-scoped Tsugi runtime state for one PHP request.
 *
 * This is the non-LTI analogue of the objects an LTI launch leaves in
 * $USER, $CONTEXT, $LINK, and $RESULT. It is not stored in the session
 * and it does not read those globals to discover who the request is about.
 * LTIX::session_start() calls provision() before it returns. The course peel
 * calls replaceCourse() when the URL names a different course.
 */
class ReqScope {

    /** Filled from a cookie site session (COOKIE_SESSION). */
    const ORIGIN_SITE = 'site';

    /** Filled from a cookieless LTI tool session. */
    const ORIGIN_LTI = 'lti';

    /** @var self|null */
    private static $current = null;

    /**
     * Cached session-or-global user and course ids for this request.
     *
     * @var array<string,mixed>|null
     */
    private static $identity = null;

    /** @var bool */
    private static $identityLogged = false;

    /**
     * site or lti. Kept across replaceCourse() for this request.
     *
     * @var string|null
     */
    private static $storedOrigin = null;

    /**
     * Course id noted before a full hydrate, usually by the /courses/{id} peel.
     *
     * @var int
     */
    private static $notedContextId = 0;

    /**
     * launch_presentation_return_url for this request. Survives a later hydrate.
     *
     * @var string|null
     */
    private static $returnUrl = null;

    /**
     * How this request was identified. ORIGIN_SITE or ORIGIN_LTI.
     *
     * @var string|null
     */
    public $origin;

    /** @var User|null */
    public $user;

    /** @var Context|null */
    public $context;

    /** @var Membership|null */
    public $membership;

    /** @var Link|null */
    public $link;

    /** @var Result|null */
    public $result;

    /**
     * Null when this request has no link. Otherwise 0 or 1 from lti_link.published.
     *
     * @var int|null
     */
    public $published;

    /**
     * LTI launch_presentation for this request. return_url is the back link.
     *
     * @var LaunchPresentation|null
     */
    public $launchPresentation;

    /**
     * The ReqScope hydrated earlier in this request, or null.
     *
     * @return self|null
     */
    public static function current() {
        return self::$current;
    }

    /**
     * Current Tsugi user id, or 0.
     *
     * The session pair wins when $_SESSION['id'] is set. Otherwise $USER and $CONTEXT.
     *
     * @return int
     */
    public static function loggedInUserId() {
        $identity = self::identity();
        return (int) $identity['user_id'];
    }

    /**
     * Current course id, or 0, from the same source as loggedInUserId().
     *
     * @return int
     */
    public static function currentContextId() {
        $identity = self::identity();
        return (int) $identity['context_id'];
    }

    /**
     * True when loggedInUserId() is non-zero.
     *
     * A logged-in user does not need a course.
     *
     * @return bool
     */
    public static function isLoggedIn() {
        return self::loggedInUserId() !== 0;
    }

    /**
     * Temporary. Compare loggedInUserId(), currentContextId(), and isLoggedIn()
     * with the user and course on this object. Remove with ReqScopeDebug
     * around December 2026. LTI tools do not use these readers.
     *
     * @return array<int, string>
     */
    public static function legacyAccessorNotes() {
        $scope = self::current();
        $notes = array();
        $userId = self::walkerScopeId($scope, 'user');
        $contextId = self::walkerScopeId($scope, 'context');
        $readerUser = self::loggedInUserId();
        $readerContext = self::currentContextId();
        $readerIn = self::isLoggedIn();
        if ( $readerUser !== $userId ) {
            $notes[] = 'loggedInUserId(): reader '.$readerUser.' / ReqScope user.id '.$userId;
        }
        if ( $readerContext !== $contextId ) {
            $notes[] = 'currentContextId(): reader '.$readerContext.' / ReqScope context.id '.$contextId;
        }
        $scopeIn = $userId !== 0;
        if ( $readerIn !== $scopeIn ) {
            $notes[] = 'isLoggedIn(): reader '.($readerIn ? 'true' : 'false')
                .' / ReqScope '.($scopeIn ? 'true' : 'false');
        }
        return $notes;
    }

    /**
     * Temporary. Compare this request with a launch and list mismatches.
     *
     * Shared slots are user, context, membership, link, and result. A slot on
     * only one side is a mismatch. When both sides have the slot, scalar
     * public fields that differ are mismatches. Remove with ReqScopeDebug
     * around December 2026.
     *
     * @param object|null $launch
     * @return array<int, string>
     */
    public static function scopeWalker($launch) {
        $scope = self::current();
        $notes = array();
        foreach ( array('user', 'context', 'membership', 'link', 'result') as $slot ) {
            $left = (is_object($scope) && isset($scope->$slot)) ? $scope->$slot : null;
            $right = (is_object($launch) && isset($launch->$slot)) ? $launch->$slot : null;
            $leftObject = is_object($left);
            $rightObject = is_object($right);
            if ( ! $leftObject && ! $rightObject ) {
                continue;
            }
            if ( ! $leftObject || ! $rightObject ) {
                $notes[] = $slot.': '
                    .( $leftObject ? 'ReqScope '.self::walkerId($left) : 'missing on ReqScope' )
                    .' / '
                    .( $rightObject ? 'Launch '.self::walkerId($right) : 'missing on Launch' );
                continue;
            }
            self::walkPair($slot, $left, $right, $notes);
        }
        return $notes;
    }

    /**
     * @param object $left
     * @param object $right
     * @param array<int, string> $notes
     */
    private static function walkPair($path, $left, $right, &$notes) {
        $leftVars = get_object_vars($left);
        $rightVars = get_object_vars($right);
        $keys = array_unique(array_merge(array_keys($leftVars), array_keys($rightVars)));
        sort($keys);
        foreach ( $keys as $key ) {
            if ( $key === 'launch' || ! is_string($key) ) {
                continue;
            }
            if ( preg_match('/secret|password|token|private/i', $key) ) {
                continue;
            }
            $hasLeft = array_key_exists($key, $leftVars);
            $hasRight = array_key_exists($key, $rightVars);
            if ( ! $hasLeft || ! $hasRight ) {
                continue;
            }
            $a = $leftVars[$key];
            $b = $rightVars[$key];
            $aComplex = is_array($a) || is_object($a);
            $bComplex = is_array($b) || is_object($b);
            if ( $aComplex && $bComplex ) {
                continue;
            }
            if ( $aComplex || $bComplex ) {
                $notes[] = $path.'.'.$key.': complex on one side';
                continue;
            }
            if ( self::walkerSame($a, $b) ) {
                continue;
            }
            $notes[] = $path.'.'.$key.': ReqScope '.self::walkerScalar($a).' / Launch '.self::walkerScalar($b);
        }
    }

    /**
     * @param mixed $a
     * @param mixed $b
     * @return bool
     */
    private static function walkerSame($a, $b) {
        if ( $a === $b ) {
            return true;
        }
        $empty = function ($value) {
            return $value === null || $value === false || $value === '';
        };
        if ( $empty($a) && $empty($b) ) {
            return true;
        }
        if ( is_numeric($a) && is_numeric($b) && (0 + $a) == (0 + $b) ) {
            return true;
        }
        return false;
    }

    /**
     * @param object|null $scope
     * @param string $slot
     * @return int
     */
    private static function walkerScopeId($scope, $slot) {
        if ( ! is_object($scope) || ! isset($scope->$slot) || ! is_object($scope->$slot) ) {
            return 0;
        }
        $id = $scope->$slot->id ?? 0;
        if ( ! is_numeric($id) || (int) $id < 1 ) {
            return 0;
        }
        return (int) $id;
    }

    /**
     * @param object $value
     * @return string
     */
    private static function walkerId($value) {
        $id = (isset($value->id) && is_scalar($value->id)) ? $value->id : '?';
        return get_class($value).' id='.$id;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private static function walkerScalar($value) {
        if ( $value === null ) {
            return 'null';
        }
        if ( $value === true ) {
            return 'true';
        }
        if ( $value === false ) {
            return 'false';
        }
        return (string) $value;
    }

    /**
     * Drop the cached user and course ids so the next read sees the session again.
     *
     * A course switch writes $_SESSION['context_id'] and then calls this.
     */
    public static function resetIdentity() {
        self::$identity = null;
        self::$identityLogged = false;
    }

    /**
     * Drop the request-scoped object and the globals it installed.
     * Tests use this. A normal request does not.
     */
    public static function reset() {
        self::resetIdentity();
        self::$current = null;
        self::$notedContextId = 0;
        self::$returnUrl = null;
        self::$storedOrigin = null;
        self::assignGlobals(null);
    }

    /**
     * Session pair when a session user id is present, otherwise the global pair.
     *
     * @return array<string,mixed>
     */
    private static function identity() {
        global $USER, $CONTEXT, $DETAIL_LOG;
        if ( is_array(self::$identity) ) {
            return self::$identity;
        }

        $sid = 0;
        $scid = 0;
        $uid = 0;
        $cid = 0;

        if ( isset($_SESSION) && is_array($_SESSION) ) {
            if ( array_key_exists('id', $_SESSION) ) {
                $sid = self::normalizePositiveId($_SESSION['id']);
            }
            if ( array_key_exists('context_id', $_SESSION) ) {
                $scid = self::normalizePositiveId($_SESSION['context_id']);
            }
        }

        if ( isset($USER) && $USER !== false && $USER !== null && is_object($USER)
            && property_exists($USER, 'id') ) {
            $uid = self::normalizePositiveId($USER->id);
        }
        if ( isset($CONTEXT) && $CONTEXT !== false && $CONTEXT !== null && is_object($CONTEXT)
            && property_exists($CONTEXT, 'id') ) {
            $cid = self::normalizePositiveId($CONTEXT->id);
        }

        $errors = array();
        if ( $sid > 0 && $uid > 0 && $sid !== $uid ) {
            $errors[] = 'SID/UID mismatch sid='.$sid.' uid='.$uid;
        }
        if ( $scid > 0 && $cid > 0 && $scid !== $cid ) {
            $errors[] = 'SCID/CID mismatch scid='.$scid.' cid='.$cid;
        }

        $source = null;
        $user_id = 0;
        $context_id = 0;
        if ( $sid > 0 ) {
            $source = 'session';
            $user_id = $sid;
            $context_id = $scid;
        } else if ( $uid > 0 ) {
            $source = 'global';
            $user_id = $uid;
            $context_id = $cid;
        }

        self::$identity = array(
            'source' => $source,
            'user_id' => $user_id,
            'context_id' => $context_id,
            'sid' => $sid,
            'scid' => $scid,
            'uid' => $uid,
            'cid' => $cid,
            'errors' => $errors,
        );

        if ( ! self::$identityLogged && ! empty($errors) && ! empty($DETAIL_LOG) ) {
            foreach ( $errors as $error ) {
                error_log('LMS ID SANITY: '.$error);
            }
            self::$identityLogged = true;
        }

        return self::$identity;
    }

    /**
     * @param mixed $value
     * @return int 0 for missing, invalid, or non-positive values.
     */
    private static function normalizePositiveId($value) {
        if ( $value === null || $value === false || $value === '' || is_bool($value) ) {
            return 0;
        }
        if ( ! is_numeric($value) ) {
            return 0;
        }
        $intval = (int) $value;
        if ( $intval <= 0 ) {
            return 0;
        }
        return $intval;
    }

    /**
     * Record as much identity as the caller already has.
     *
     * A missing user or a missing course is stored as null. Both ids load
     * membership, role, and instructor. $link_id also loads the link and result.
     * Does not replace the LTI launch globals. A failure is logged and the
     * request keeps a partial object instead of dying.
     *
     * @param int $user_id
     * @param int $context_id
     * @param int|null $link_id
     * @param string|null $origin ORIGIN_SITE or ORIGIN_LTI
     * @return self
     */
    public static function provision($user_id, $context_id, $link_id = null, $origin = null) {
        $user_id = (int) $user_id;
        $context_id = (int) $context_id;
        if ( is_string($origin) && $origin !== '' ) {
            self::$storedOrigin = $origin;
        }
        $link_id = ($link_id === null) ? null : (int) $link_id;
        if ( $link_id !== null && $link_id < 1 ) {
            $link_id = null;
        }

        if ( $user_id < 1 && $context_id < 1 ) {
            return self::place(null, null, null, null, null, null);
        }

        // Drop a course noted earlier so fromInternalActivity uses $context_id.
        self::$current = null;
        self::$notedContextId = 0;

        if ( $user_id >= 1 && $context_id < 1 ) {
            return self::place(self::loadUser($user_id), null, null, null, null, null);
        }
        if ( $user_id < 1 && $context_id >= 1 ) {
            return self::place(null, self::loadContext($context_id), null, null, null, null);
        }

        try {
            return self::fromInternalActivity($user_id, $context_id, $link_id, false);
        } catch ( ReqScopeException $ex ) {
            error_log('ReqScope provision: '.$ex->getMessage());
            self::$current = null;
            self::$notedContextId = 0;
            return self::place(self::loadUser($user_id), null, null, null, null, null);
        }
    }

    /**
     * Keep the user session_start already recorded and point this request at $context_id.
     *
     * The same course is left as it is. A different course reloads membership,
     * role, and instructor. No user means the call changes nothing.
     *
     * @param int $context_id
     * @return self|null
     */
    public static function replaceCourse($context_id) {
        $context_id = (int) $context_id;
        if ( $context_id < 1 ) {
            return self::$current;
        }
        $user_id = 0;
        if ( self::$current && self::$current->user && (int) self::$current->user->id > 0 ) {
            $user_id = (int) self::$current->user->id;
        }
        if ( $user_id < 1 ) {
            $user_id = (int) self::loggedInUserId();
        }
        if ( $user_id < 1 ) {
            return self::$current;
        }
        $have = 0;
        if ( self::$current && self::$current->context ) {
            $have = (int) self::$current->context->id;
        }
        if ( $have === $context_id ) {
            return self::$current;
        }
        $origin = self::$storedOrigin;
        if ( self::$current && is_string(self::$current->origin) && self::$current->origin !== '' ) {
            $origin = self::$current->origin;
        }
        return self::provision($user_id, $context_id, null, $origin);
    }

    /**
     * Remember the course for this request.
     *
     * A course already on the current ReqScope is left as it is.
     *
     * @param int $context_id
     */
    public static function noteContext($context_id) {
        $context_id = (int) $context_id;
        if ( $context_id < 1 ) {
            return;
        }
        $existing = self::existingContextId();
        if ( $existing > 0 ) {
            self::logContextChange($existing, $context_id);
            return;
        }
        self::$notedContextId = $context_id;
    }

    /**
     * Remember launch_presentation_return_url. Applied to the current object
     * and to the next hydrate in this request.
     *
     * @param string|null $url
     */
    public static function setReturnUrl($url) {
        $url = is_string($url) ? trim($url) : '';
        self::$returnUrl = $url === '' ? null : $url;
        if ( self::$current ) {
            self::attachReturnUrl(self::$current);
        }
    }

    /**
     * Load user, context, membership, and (when $link_id is set) link and result.
     *
     * $link_id null means the activity has never been published: user and context
     * are still loaded, and link and result stay null. Does not write the session.
     *
     * @param int $user_id
     * @param int $context_id
     * @param int|null $link_id
     * @return self
     * @throws ReqScopeException
     */
    /**
     * Load the user, course, and role onto this request. No link.
     *
     * The course peel calls this. A request that already has a user and a
     * course is left as it is. Does not replace the LTI launch globals.
     *
     * @param int $user_id
     * @param int $context_id
     * @return self
     * @throws ReqScopeException
     */
    public static function establish($user_id, $context_id) {
        $current = self::current();
        if ( $current && $current->user && (int) $current->user->id > 0
            && $current->context && (int) $current->context->id > 0 ) {
            self::logContextChange((int) $current->context->id, (int) $context_id);
            return $current;
        }
        return self::fromInternalActivity($user_id, $context_id, null, false);
    }

    /**
     * Temporary. Compare this request with the session user and course, and with
     * the course id the caller would have passed to fromInternalActivity.
     * Logs a drift and does not change the request. Remove once the peel is trusted.
     *
     * @param int $context_id
     */
    public static function logSessionDrift($context_id) {
        $rc = self::current();
        $sessionUser = (int) self::loggedInUserId();
        $sessionContext = (int) self::currentContextId();
        $passed = (int) $context_id;
        if ( ! $rc || ! $rc->user || ! $rc->context ) {
            error_log('ReqScope drift: not established; session user='.$sessionUser.' context='.$sessionContext.' passed='.$passed);
            return;
        }
        $drifts = array();
        if ( (int) $rc->user->id !== $sessionUser ) {
            $drifts[] = 'user_id request='.(int) $rc->user->id.' session='.$sessionUser;
        }
        if ( (int) $rc->context->id !== $sessionContext ) {
            $drifts[] = 'context_id request='.(int) $rc->context->id.' session='.$sessionContext;
        }
        if ( $passed > 0 && (int) $rc->context->id !== $passed ) {
            $drifts[] = 'context_id request='.(int) $rc->context->id.' passed='.$passed;
        }
        $derivedContext = $passed > 0 ? $passed : $sessionContext;
        if ( $sessionUser > 0 && $derivedContext > 0
            && (int) $rc->user->id === $sessionUser
            && (int) $rc->context->id === $derivedContext ) {
            $derived = self::membershipSnapshot($sessionUser, $derivedContext);
            $haveId = $rc->membership ? (int) $rc->membership->id : 0;
            $haveRole = $rc->membership ? (int) $rc->membership->role : 0;
            if ( $derived['id'] !== $haveId || $derived['role'] !== $haveRole ) {
                $drifts[] = 'membership request='.$haveId.' role='.$haveRole
                    .' derived='.$derived['id'].' role='.$derived['role'];
            }
            if ( $derived['instructor'] !== (bool) $rc->user->instructor ) {
                $drifts[] = 'instructor request='.($rc->user->instructor ? '1' : '0')
                    .' derived='.($derived['instructor'] ? '1' : '0');
            }
        }
        if ( count($drifts) > 0 ) {
            error_log('ReqScope drift: '.implode('; ', $drifts));
        }
    }

    /**
     * Attach the link and result for the user already on this request.
     *
     * @param int|null $link_id
     * @return self
     * @throws ReqScopeException
     */
    public static function setLink($link_id) {
        global $CFG, $PDOX;

        $rc = self::current();
        if ( ! $rc || ! $rc->user || ! $rc->context || (int) $rc->context->id < 1 || (int) $rc->user->id < 1 ) {
            throw new ReqScopeException('Request scope is not established.', 500);
        }
        $link_id = ($link_id === null) ? 0 : (int) $link_id;
        if ( $link_id < 1 ) {
            $rc->link = null;
            $rc->result = null;
            $rc->published = null;
            return $rc;
        }
        if ( $rc->link && (int) $rc->link->id === $link_id && $rc->result ) {
            return $rc;
        }

        LTIX::getConnection();
        $p = $CFG->dbprefix;
        $linkRow = $PDOX->rowDie(
            "SELECT link_id, link_key, title, published
             FROM {$p}lti_link
             WHERE link_id = :LID AND context_id = :CID
               AND (deleted IS NULL OR deleted = 0)",
            array(':LID' => $link_id, ':CID' => (int) $rc->context->id)
        );
        if ( ! $linkRow ) {
            throw new ReqScopeException('Link not found in this context.', 404);
        }
        $link = new Link();
        $link->id = (int) $linkRow['link_id'];
        $link->title = $linkRow['title'];
        $result = self::ensureResult($link->id, (int) $rc->user->id);
        $link->result_id = $result->id;
        if ( $result->grade !== null && $result->grade !== false ) {
            $link->grade = $result->grade;
        }
        $rc->link = $link;
        $rc->result = $result;
        $rc->published = (int) $linkRow['published'];
        return $rc;
    }

    public static function fromInternalActivity($user_id, $context_id, $link_id = null, $installGlobals = true) {
        global $CFG, $PDOX;

        $user_id = (int) $user_id;
        $context_id = self::resolveContextId($context_id);
        $link_id = ($link_id === null) ? null : (int) $link_id;

        if ( $user_id < 1 || $context_id < 1 ) {
            throw new ReqScopeException('User and context are required.', 404);
        }

        LTIX::getConnection();
        $p = $CFG->dbprefix;

        $userRow = $PDOX->rowDie(
            "SELECT user_id, user_key, displayname, email, locale, image
             FROM {$p}lti_user
             WHERE user_id = :UID AND (deleted IS NULL OR deleted = 0)",
            array(':UID' => $user_id)
        );
        if ( ! $userRow ) {
            throw new ReqScopeException('User not found.', 404);
        }

        $contextRow = $PDOX->rowDie(
            "SELECT c.context_id, c.context_key, c.title, k.key_key
             FROM {$p}lti_context AS c
             LEFT JOIN {$p}lti_key AS k ON c.key_id = k.key_id
             WHERE c.context_id = :CID AND (c.deleted IS NULL OR c.deleted = 0)",
            array(':CID' => $context_id)
        );
        if ( ! $contextRow ) {
            throw new ReqScopeException('Context not found.', 404);
        }

        $memberRow = $PDOX->rowDie(
            "SELECT membership_id, role, role_override
             FROM {$p}lti_membership
             WHERE context_id = :CID AND user_id = :UID
               AND (deleted IS NULL OR deleted = 0)",
            array(':CID' => $context_id, ':UID' => $user_id)
        );
        $siteAdmin = isset($_SESSION['admin']) && $_SESSION['admin'] == 'yes';
        $owner = self::userOwnsContext($context_id, $user_id);
        if ( ! $memberRow && ! $siteAdmin && ! $owner ) {
            throw new ReqScopeException('User is not a member of this context.', 403);
        }

        $effective = 0;
        if ( $memberRow ) {
            $role = (int) ($memberRow['role'] ?? 0);
            $role_override = (int) ($memberRow['role_override'] ?? 0);
            $effective = max($role, $role_override);
        }

        $user = new User();
        $user->id = (int) $userRow['user_id'];
        $user->key = $userRow['user_key'];
        $user->displayname = $userRow['displayname'];
        $user->email = $userRow['email'];
        $user->locale = $userRow['locale'];
        $user->image = $userRow['image'];
        if ( is_string($user->displayname) && $user->displayname !== '' ) {
            $pieces = explode(' ', $user->displayname);
            $user->firstname = $pieces[0];
            if ( count($pieces) > 1 ) {
                $user->lastname = $pieces[count($pieces) - 1];
            }
        }
        $user->instructor = $effective >= LTIX::ROLE_INSTRUCTOR || $siteAdmin || $owner;
        $user->admin = $effective >= LTIX::ROLE_ADMINISTRATOR || $siteAdmin;

        $context = self::contextFromRow($contextRow);

        $membership = null;
        if ( $memberRow ) {
            $membership = new Membership();
            $membership->id = (int) $memberRow['membership_id'];
            $membership->role = $effective;
        }

        $link = null;
        $result = null;
        $published = null;
        if ( $link_id !== null && $link_id > 0 ) {
            $linkRow = $PDOX->rowDie(
                "SELECT link_id, link_key, title, published
                 FROM {$p}lti_link
                 WHERE link_id = :LID AND context_id = :CID
                   AND (deleted IS NULL OR deleted = 0)",
                array(':LID' => $link_id, ':CID' => $context_id)
            );
            if ( ! $linkRow ) {
                throw new ReqScopeException('Link not found in this context.', 404);
            }
            $link = new Link();
            $link->id = (int) $linkRow['link_id'];
            $link->title = $linkRow['title'];
            $published = (int) $linkRow['published'];
            $result = self::ensureResult($link->id, $user->id);
            $link->result_id = $result->id;
            if ( $result->grade !== null && $result->grade !== false ) {
                $link->grade = $result->grade;
            }
        }

        return self::hydrate($user, $context, $link, $result, $membership, $published, $installGlobals);
    }

    /**
     * Install an already-built set of model objects as this request's context.
     *
     * The globals $USER, $CONTEXT, $LINK, and $RESULT point at these same objects.
     *
     * @param User $user
     * @param Context $context
     * @param Link|null $link
     * @param Result|null $result
     * @param Membership|null $membership
     * @param int|null $published
     * @return self
     */
    public static function hydrate(User $user, Context $context, $link = null, $result = null, $membership = null, $published = null, $installGlobals = true) {
        $rc = new self();
        $rc->user = $user;
        $rc->context = $context;
        $rc->membership = $membership;
        $rc->link = $link;
        $rc->result = $result;
        $rc->published = $published === null ? null : (int) $published;
        self::attachReturnUrl($rc);
        self::$current = $rc;
        self::$notedContextId = (int) $context->id;
        $rc->origin = self::$storedOrigin;
        if ( $installGlobals ) {
            self::assignGlobals($rc);
        }
        return $rc;
    }

    /**
     * Course already on this request wins. Otherwise the id noted by the
     * course peel, then the caller's id, then the session course.
     *
     * @param int $passed
     * @return int
     */
    private static function resolveContextId($passed) {
        $passed = (int) $passed;
        $existing = self::existingContextId();
        if ( $existing > 0 ) {
            self::logContextChange($existing, $passed);
            return $existing;
        }
        if ( $passed > 0 ) {
            return $passed;
        }
        return (int) self::currentContextId();
    }

    /**
     * Membership and role fromInternalActivity would store for this pair.
     * Does not change the current request.
     *
     * @param int $user_id
     * @param int $context_id
     * @return array{id:int,role:int,instructor:bool}
     */
    private static function membershipSnapshot($user_id, $context_id) {
        global $CFG, $PDOX;

        LTIX::getConnection();
        $p = $CFG->dbprefix;
        $memberRow = $PDOX->rowDie(
            "SELECT membership_id, role, role_override
             FROM {$p}lti_membership
             WHERE context_id = :CID AND user_id = :UID
               AND (deleted IS NULL OR deleted = 0)",
            array(':CID' => $context_id, ':UID' => $user_id)
        );
        $role = 0;
        $id = 0;
        if ( $memberRow ) {
            $id = (int) $memberRow['membership_id'];
            $role = max((int) ($memberRow['role'] ?? 0), (int) ($memberRow['role_override'] ?? 0));
        }
        $siteAdmin = isset($_SESSION['admin']) && $_SESSION['admin'] == 'yes';
        $owner = self::userOwnsContext($context_id, $user_id);
        return array(
            'id' => $id,
            'role' => $role,
            'instructor' => $role >= LTIX::ROLE_INSTRUCTOR || $siteAdmin || $owner,
        );
    }

    /**
     * Course owner or the owner of the course's key. Same check as Membership::ensureInSession().
     *
     * @param int $context_id
     * @param int $user_id
     * @return bool
     */
    private static function userOwnsContext($context_id, $user_id) {
        global $CFG, $PDOX;

        $p = $CFG->dbprefix;
        $row = $PDOX->rowDie(
            "SELECT context_id FROM {$p}lti_context
             WHERE context_id = :CID AND (
                 key_id IN (SELECT key_id FROM {$p}lti_key WHERE user_id = :UID)
                 OR user_id = :UID
             ) AND (deleted IS NULL OR deleted = 0)",
            array(':CID' => $context_id, ':UID' => $user_id)
        );
        return (bool) $row;
    }

    /**
     * @return int
     */
    private static function existingContextId() {
        if ( self::$current && self::$current->context && (int) self::$current->context->id > 0 ) {
            return (int) self::$current->context->id;
        }
        if ( self::$notedContextId > 0 ) {
            return self::$notedContextId;
        }
        return 0;
    }

    /**
     * @param User|null $user
     * @param Context|null $context
     * @param Link|null $link
     * @param Result|null $result
     * @param Membership|null $membership
     * @param int|null $published
     * @return self
     */
    private static function place($user, $context, $link, $result, $membership, $published) {
        $rc = new self();
        $rc->user = $user;
        $rc->context = $context;
        $rc->membership = $membership;
        $rc->link = $link;
        $rc->result = $result;
        $rc->published = $published === null ? null : (int) $published;
        $rc->origin = self::$storedOrigin;
        self::attachReturnUrl($rc);
        self::$current = $rc;
        self::$notedContextId = ($context && (int) $context->id > 0) ? (int) $context->id : 0;
        return $rc;
    }

    /**
     * @param int $user_id
     * @return User|null
     */
    private static function loadUser($user_id) {
        global $CFG, $PDOX;

        if ( (int) $user_id < 1 ) {
            return null;
        }
        LTIX::getConnection();
        $userRow = $PDOX->rowDie(
            "SELECT user_id, user_key, displayname, email, locale, image
             FROM {$CFG->dbprefix}lti_user
             WHERE user_id = :UID AND (deleted IS NULL OR deleted = 0)",
            array(':UID' => (int) $user_id)
        );
        if ( ! is_array($userRow) ) {
            return null;
        }
        $user = new User();
        $user->id = (int) $userRow['user_id'];
        $user->key = $userRow['user_key'];
        $user->displayname = $userRow['displayname'];
        $user->email = $userRow['email'];
        $user->locale = $userRow['locale'];
        $user->image = $userRow['image'];
        $user->instructor = false;
        $user->admin = false;
        if ( is_string($user->displayname) && $user->displayname !== '' ) {
            $pieces = explode(' ', $user->displayname);
            $user->firstname = $pieces[0];
            if ( count($pieces) > 1 ) {
                $user->lastname = $pieces[count($pieces) - 1];
            }
        }
        return $user;
    }

    /**
     * @param int $context_id
     * @return Context|null
     */
    private static function loadContext($context_id) {
        global $CFG, $PDOX;

        if ( (int) $context_id < 1 ) {
            return null;
        }
        LTIX::getConnection();
        $p = $CFG->dbprefix;
        $row = $PDOX->rowDie(
            "SELECT c.context_id, c.context_key, c.title, k.key_key
             FROM {$p}lti_context AS c
             LEFT JOIN {$p}lti_key AS k ON c.key_id = k.key_id
             WHERE c.context_id = :CID AND (c.deleted IS NULL OR c.deleted = 0)",
            array(':CID' => (int) $context_id)
        );
        if ( ! is_array($row) ) {
            return null;
        }
        return self::contextFromRow($row);
    }

    /**
     * Course row plus the tenant key Launch stores on context.key.
     *
     * @param array<string,mixed> $row
     * @return Context
     */
    private static function contextFromRow(array $row) {
        $context = new Context();
        $context->id = (int) $row['context_id'];
        $context->title = $row['title'];
        $context->context_id = $row['context_key'];
        if ( isset($row['key_key']) && $row['key_key'] !== null && $row['key_key'] !== '' ) {
            $context->key = $row['key_key'];
        }
        return $context;
    }

    /**
     * First course id for this request is kept. A later different id is logged and ignored.
     *
     * @param int $existing
     * @param int $passed
     */
    private static function logContextChange($existing, $passed) {
        if ( $passed < 1 || $passed === $existing ) {
            return;
        }
        error_log('ReqScope refused to change context_id from '.$existing.' to '.$passed);
    }

    /**
     * @param self $rc
     */
    private static function attachReturnUrl(self $rc) {
        if ( self::$returnUrl === null ) {
            return;
        }
        if ( ! $rc->launchPresentation ) {
            $rc->launchPresentation = new LaunchPresentation();
        }
        $rc->launchPresentation->return_url = self::$returnUrl;
    }

    /**
     * Same insert launch uses: one lti_result row per (link_id, user_id).
     *
     * @param int $link_id
     * @param int $user_id
     * @return Result
     */
    private static function ensureResult($link_id, $user_id) {
        global $CFG, $PDOX;

        $p = $CFG->dbprefix;
        $row = $PDOX->rowDie(
            "SELECT result_id, grade
             FROM {$p}lti_result
             WHERE link_id = :LID AND user_id = :UID
               AND (deleted IS NULL OR deleted = 0)",
            array(':LID' => $link_id, ':UID' => $user_id)
        );
        if ( ! $row ) {
            $PDOX->queryDie(
                "INSERT INTO {$p}lti_result
                    (link_id, user_id, created_at, updated_at)
                 VALUES (:LID, :UID, NOW(), NOW())",
                array(':LID' => $link_id, ':UID' => $user_id)
            );
            $row = $PDOX->rowDie(
                "SELECT result_id, grade
                 FROM {$p}lti_result
                 WHERE link_id = :LID AND user_id = :UID
                   AND (deleted IS NULL OR deleted = 0)",
                array(':LID' => $link_id, ':UID' => $user_id)
            );
        }
        if ( ! $row ) {
            throw new ReqScopeException('Could not load result.', 500);
        }

        $result = new Result();
        $result->id = (int) $row['result_id'];
        $result->grade = $row['grade'];
        return $result;
    }

    /**
     * @param self|null $rc
     */
    private static function assignGlobals($rc) {
        global $USER, $CONTEXT, $LINK, $RESULT;

        if ( $rc === null ) {
            $USER = null;
            $CONTEXT = null;
            $LINK = null;
            $RESULT = null;
            return;
        }

        $USER = $rc->user;
        $CONTEXT = $rc->context;
        $LINK = $rc->link;
        $RESULT = $rc->result;
    }
}
