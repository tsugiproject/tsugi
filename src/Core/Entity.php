<?php

namespace Tsugi\Core;

use \Tsugi\Core\LTIX;

/**
 * This is a class holding commmon functionality to be extended by various other classes.
 */

class Entity {

    /**
     * All extending classes must define these member variables
     */

    // protected $TABLE_NAME = "lti_result";
    // protected $PRIMARY_KEY = "result_id";
    // public $id = ...;

    /**
     * A reference to our containing launch
     */
    public $launch;

    // Pull in all the session access functions
    use SessionTrait;

    // Pull in all the json access functions
    use JsonTrait;
}
