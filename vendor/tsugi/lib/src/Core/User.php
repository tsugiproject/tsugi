<?php

namespace Tsugi\Core;

use \Tsugi\Core\Cache;

/**
 * This is a class to provide access to the resource context level data.
 *
 * This data comes from the LTI launch from the LMS.
 * A context is the equivalent of a "class" or course.   A context
 * has a roster of users and each user has a role within the context.
 * A launch may or may not contain a context.  If there
 * is a link without a context, it is a "system-wide" link
 * like "view profile" or "show all courses"
 *
 */

class User {

    // Needed to implement the Entity methods
    protected $TABLE_NAME = "lti_user";
    protected $PRIMARY_KEY = "user_id";

    // Links have settings...
    protected $ENTITY_NAME = "user";
    use JsonTrait;  // Pull in the trait

    /**
     * The integer primary key for this user in the 'lti_user' table.
     */
    public $id;

    /**
     * The user's email
     */
    public $email = null;

    /**
     * The user's display name
     */
    public $displayname = null;

    /**
     * The user's first name
     */
    public $firstname = null;

    /**
     * The user's last name
     */
    public $lastname = null;

    /**
     * The User's Locale
     */
    public $locale = null;

    /**
     * The User's Image / Avatar
     */
    public $image = null;

    /**
     * Is the user an instructor?
     */
    public $instructor = null;

    /**
     * Construct the user's name / email combination
     */
    public function getNameAndEmail() {
        $display = '';
        if ( isset($this->displayname) && strlen($this->displayname) > 0 ) {
            $display = $this->displayname;
        }
        if ( isset($this->email) && strlen($this->email) > 0 ) {
            if ( strlen($display) > 0 ) {
                $display .= ' ('.$this->email.')';
            } else {
                $display = $this->email;
            }
        }
        $display = trim($display);
        if ( strlen($display) < 1 ) return false;
        return $display;
    }


    /**
     * Get the user's first name, falling back to email
     */
    function getFirstName($displayname=null) {
        if ( $displayname === null ) $displayname = $this->getNameAndEmail();
        if ( $displayname === null ) return null;
        $pieces = explode(' ',$displayname);
        if ( count($pieces) > 0 ) return $pieces[0];
        return null;
    }

    /**
     * Load a user's info from the user_id
     *
     * We make sure that the user is a member of the current
     * context so as not to slide across silos.
     */
    public static function loadUserInfoBypass($user_id)
    {
        global $CFG, $PDOX, $CONTEXT;
        $cacheloc = 'lti_user';
        $row = Cache::check($cacheloc, $user_id);
        if ( $row != false ) return $row;
        $stmt = $PDOX->queryDie(
            "SELECT displayname, email, user_key FROM {$CFG->dbprefix}lti_user AS U
            JOIN {$CFG->dbprefix}lti_membership AS M
            ON U.user_id = M.user_id AND M.context_id = :CID
            WHERE U.user_id = :UID",
            array(":UID" => $user_id, ":CID" => $CONTEXT->id)
        );
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ( strlen($row['displayname']) < 1 && strlen($row['user_key']) > 0 ) {
            $row['displayname'] = 'user_key:'.substr($row['user_key'],0,25);
        }
        Cache::set($cacheloc, $user_id, $row);
        return $row;
    }

    /**
     * Load a user's info from the user's subbect
     *
     * We make sure that the user is a member of the current
     * context and key so as not to slide across silos.
     */
    public function loadUserInfoBypassBySubject($user_subject)
    {
        global $CFG;
        if ( ! is_string($user_subject) || strlen($user_subject) < 1 ) return null;
        if ( ! isset($this->launch->key) || ! isset($this->launch->key->id) ) return null;

        $subject_sha256 = lti_sha256($user_subject);
        $stmt = $this->launch->pdox->queryDie(
            "SELECT U.user_id AS user_id, displayname, email, user_key FROM {$CFG->dbprefix}lti_user AS U
            JOIN {$CFG->dbprefix}lti_membership AS M
            ON U.user_id = M.user_id AND M.context_id = :CID
            WHERE U.subject_sha256 = :USHA AND U.key_id = :KID",
            array(
                ":USHA" => $subject_sha256,
                ":CID" => $this->launch->context->id,
                ":KID" => $this->launch->key->id
            )
        );
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ( ! is_array($row) ) return null;
        if ( strlen($row['displayname']) < 1 && strlen($row['user_key']) > 0 ) {
            $row['displayname'] = 'user_key:'.substr($row['user_key'],0,25);
        }
        $cacheloc = 'lti_user';
        Cache::set($cacheloc, $row['user_id'], $row);
        return $row;
    }
}
