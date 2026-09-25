<?php

namespace Tsugi\Core;

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
        self::assignGlobals(null);
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
    public static function fromInternalActivity($user_id, $context_id, $link_id = null) {
        global $CFG, $PDOX;

        $user_id = (int) $user_id;
        $context_id = (int) $context_id;
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
        if ( ! $memberRow ) {
            throw new RequestContextException('User is not a member of this context.', 403);
        }

        $role = (int) ($memberRow['role'] ?? 0);
        $role_override = (int) ($memberRow['role_override'] ?? 0);
        $effective = max($role, $role_override);

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
        $user->instructor = $effective >= LTIX::ROLE_INSTRUCTOR;
        $user->admin = $effective >= LTIX::ROLE_ADMINISTRATOR;

        $context = new Context();
        $context->id = (int) $contextRow['context_id'];
        $context->title = $contextRow['title'];
        $context->context_id = $contextRow['context_key'];

        $membership = new Membership();
        $membership->id = (int) $memberRow['membership_id'];
        $membership->role = $effective;

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

        return self::hydrate($user, $context, $link, $result, $membership, $published);
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
    public static function hydrate(User $user, Context $context, $link = null, $result = null, $membership = null, $published = null) {
        $rc = new self();
        $rc->user = $user;
        $rc->context = $context;
        $rc->membership = $membership;
        $rc->link = $link;
        $rc->result = $result;
        $rc->published = $published === null ? null : (int) $published;
        self::$current = $rc;
        self::assignGlobals($rc);
        return $rc;
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
