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

class Context extends Entity {

    // TODO: - $Context->lang - The context language choice.

    // Needed to implement the Entity methods
    protected $TABLE_NAME = "lti_context";
    protected $PRIMARY_KEY = "context_id";

    // Contexts have settings...
    protected $ENTITY_NAME = "context";
    use SettingsTrait;  // Pull in the trait

    /**
     * The integer primary key for this context in the 'lti_context' table.
     */
    public $id;

    /**
     * The context title
     */
    public $title;
}
