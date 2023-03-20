<?php

namespace Tsugi\Core;

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;
use \Tsugi\Util\LTI13;

/**
 * This is a class to provide access to the resource key level data.
 *
 * This data comes from the LTI launch from the LMS.
 * A context is the equivalent of a "client" or customer.   A key
 * has has security arrangements and possibly billing arrangements.
 * Everything in Tsugi flows from a key. If you delete a key,
 * all contexts and users are also deleted.
 *
 */

class Key extends Entity {

    // Needed to implement the Entity methods
    protected $TABLE_NAME = "lti_key";
    protected $PRIMARY_KEY = "key_id";

    // Contexts have settings...
    protected $ENTITY_NAME = "key";
    use SettingsTrait;  // Pull in the trait

    /**
     * The integer primary key for this context in the 'lti_key' table.
     */
    public $id;

    /**
     * The key title
     */
    public $title;

}
