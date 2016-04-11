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

    // TODO: - $User->lang - The user's language choice.

    /**
     * The integer primary key for this user in the 'lti_user' table.
     */
    public $id;

    /**
     * The user's email
     */
    public $email = false;

    /**
     * The user's display name
     */
    public $displayname = false;

    /**
     * The user's first name
     */
    public $firstname = false;

    /**
     * The user's last name
     */
    public $lastname = false;

    /**
     * Is the user an instructor?
     */
    public $instructor = false;

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
    function getFirstName($displayname=false) {
        if ( $displayname === false ) $displayname = $this->getNameAndEmail();
        if ( $displayname === false ) return false;
        $pieces = explode(' ',$displayname);
        if ( count($pieces) > 0 ) return $pieces[0];
        return false;
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
}
