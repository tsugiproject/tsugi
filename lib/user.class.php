<?php

namespace Tsugi\Core;

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
     * The string primary key for this user in the 'lti_user' table.
     */
    public $sha256;

    /**
     * The user's email
     */
    public $email;

    /**
     * The user's display name
     */
    public $displayname;

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
     * Ge tthe user's first name, falling back to email
     */
    function getFirstName($displayname=false) {
        if ( $displayname === false ) $displayname = $this->getNameAndEmail();
        if ( $displayname === false ) return false;
        $pieces = explode(' ',$displayname);
        if ( count($pieces) > 0 ) return $pieces[0];
        return false;
    }
}
