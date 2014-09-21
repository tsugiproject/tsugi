<?php

namespace Tsugi\Core;

/**
 * This is a class to provide access to the resource link level data.
 *
 * This data comes from the LTI launch from the LMS.
 * A resource_link may or may not be in a context.  If there
 * is a link without a context, it is a "system-wide" link
 * like "view profile" or "show all courses"
 */

class Link {
    /**
     * The integer primary key for this link in the 'lti_link' table.
     */
    public $id;

    /**
     * The string primary key for this link in the 'lti_link' table.
     */
    public $sha256;

    /**
     * The link title
     */
    public $title;

    /**
     * The result_id for the link (if set)
     *
     * This is the primary key for the lti_result row for this
     * user/link combination.  It may be false.  Is this is not
     * false, we can send a grade back to the LMS for this
     * user/link combination.
     */
    public $result_id = false;

}
