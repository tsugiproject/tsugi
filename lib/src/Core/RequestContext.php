<?php

namespace Tsugi\Core;

use Tsugi\Util\U;

/**
 * Request-scoped Tsugi runtime state for one PHP request.
 *
 * This is the non-LTI analogue of the objects an LTI launch leaves in
 * $USER, $CONTEXT, $LINK, and $RESULT. It is not stored in the session
 * and it does not read those globals to discover who the request is about.
 * Call fromInternalActivity() (or hydrate()) before current().
 */
class RequestContext {

    /** @var self|null */
    private static $current = null;

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

    /** @var User */
    public $user;

    /** @var Context */
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
     * The RequestContext hydrated earlier in this request, or null.
     *
     * @return self|null
     */
    public static function current() {
        return self::$current;
    }

    /**
     * Drop the request-scoped object and the globals it installed.
     * Tests use this. A normal request does not.
     */
    public static function reset() {
        self::$current = null;
        self::$notedContextId = 0;
        self::$returnUrl = null;
        self::assignGlobals(null);
    }

    /**
     * Remember the course for this request.
     *
     * A course already on the current RequestContext is left as it is.
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
     * @throws RequestContextException
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
     * @throws RequestContextException
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
        $sessionUser = (int) U::loggedInUserId();
        $sessionContext = (int) U::currentContextId();
        $passed = (int) $context_id;
        if ( ! $rc || ! $rc->user || ! $rc->context ) {
            error_log('RequestContext drift: not established; session user='.$sessionUser.' context='.$sessionContext.' passed='.$passed);
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
            error_log('RequestContext drift: '.implode('; ', $drifts));
        }
    }

    /**
     * Attach the link and result for the user already on this request.
     *
     * @param int|null $link_id
     * @return self
     * @throws RequestContextException
     */
    public static function setLink($link_id) {
        global $CFG, $PDOX;

        $rc = self::current();
        if ( ! $rc || ! $rc->user || ! $rc->context || (int) $rc->context->id < 1 || (int) $rc->user->id < 1 ) {
            throw new RequestContextException('Request context is not established.', 500);
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
            throw new RequestContextException('Link not found in this context.', 404);
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
            throw new RequestContextException('User and context are required.', 404);
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
            throw new RequestContextException('User not found.', 404);
        }

        $contextRow = $PDOX->rowDie(
            "SELECT context_id, context_key, title
             FROM {$p}lti_context
             WHERE context_id = :CID AND (deleted IS NULL OR deleted = 0)",
            array(':CID' => $context_id)
        );
        if ( ! $contextRow ) {
            throw new RequestContextException('Context not found.', 404);
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
            throw new RequestContextException('User is not a member of this context.', 403);
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

        $context = new Context();
        $context->id = (int) $contextRow['context_id'];
        $context->title = $contextRow['title'];
        $context->context_id = $contextRow['context_key'];

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
                throw new RequestContextException('Link not found in this context.', 404);
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
        return (int) U::currentContextId();
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
     * First course id for this request is kept. A later different id is logged and ignored.
     *
     * @param int $existing
     * @param int $passed
     */
    private static function logContextChange($existing, $passed) {
        if ( $passed < 1 || $passed === $existing ) {
            return;
        }
        error_log('RequestContext refused to change context_id from '.$existing.' to '.$passed);
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
            throw new RequestContextException('Could not load result.', 500);
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
