<?php

namespace Tsugi\Core;

use \Tsugi\Core\Cache;

/**
 * This is a class to provide access to the resource link level data.
 *
 * This data comes from the LTI launch from the LMS.
 * A resource_link may or may not be in a context.  If there
 * is a link without a context, it is a "system-wide" link
 * like "view profile" or "show all courses"
 */

class Link extends Entity {
    // Needed to implement the Entity methods
    protected $TABLE_NAME = "lti_link";
    protected $PRIMARY_KEY = "link_id";

    /**
     * The integer primary key for this link in the 'lti_link' table.
     */
    public $id;

    /**
     * The link title
     */
    public $title;

    /**
     * The current grade for the user
     *
     * If there is a current grade (float between 0.0 and 1.0)
     * it is in this variable.  If there is not yet a grade for
     * this user/link combination, this will be false.
     */
    public $grade = false;

    /**
     * The result_id for the link (if set)
     *
     * This is the primary key for the lti_result row for this
     * user/link combination.  It may be false.  Is this is not
     * false, we can send a grade back to the LMS for this
     * user/link combination.
     */
    public $result_id = false;

    /**
     * Load link information for a different link than current
     *
     * Make sure not to cross Context silos.
     */
    public static function loadLinkInfo($link_id)
    {
        global $CFG, $PDOX, $CONTEXT;

        $cacheloc = 'lti_link';
        $row = Cache::check($cacheloc, $link_id);
        if ( $row != false ) return $row;
        $stmt = $PDOX->queryDie(
            "SELECT title FROM {$CFG->dbprefix}lti_link
                WHERE link_id = :LID AND context_id = :CID",
            array(":LID" => $link_id, ":CID" => $CONTEXT->id)
        );
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        Cache::set($cacheloc, $link_id, $row);
        return $row;
    }

}
