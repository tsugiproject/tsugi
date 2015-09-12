<?php

namespace Tsugi\Core;

/**
 * This is a class to provide access to the user's result data.
 *
 * This data comes from the LTI launch from the LMS.
 */

class Result extends Entity {

    // Needed to implement the Entity methods
    protected $TABLE_NAME = "lti_result";
    protected $PRIMARY_KEY = "result_id";

    /**
     * The integer primary key for this link in the 'lti_result' table.
     */
    public $id;

    /**
     * The current grade for the user
     *
     * If there is a current grade (float between 0.0 and 1.0)
     * it is in this variable.  If there is not yet a grade for
     * this user/link combination, this will be false.
     */
    public $grade = false;

}
