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

    // Looks up a result for a potentially different user_id so we make
    // sure they are in the same key/ context / link as the current user
    // hence the complex query to make sure we don't cross silos
    public static function lookupResultBypass($user_id) {
        global $CFG, $PDOX, $CONTEXT, $LINK;
        $stmt = $PDOX->queryDie(
            "SELECT result_id, R.link_id AS link_id, R.user_id AS user_id,
                sourcedid, service_id, grade, note, R.json AS json
            FROM {$CFG->dbprefix}lti_result AS R
            JOIN {$CFG->dbprefix}lti_link AS L
                ON L.link_id = R.link_id AND R.link_id = :LID
            JOIN {$CFG->dbprefix}lti_user AS U
                ON U.user_id = R.user_id AND U.user_id = :UID
            JOIN {$CFG->dbprefix}lti_context AS C
                ON L.context_id = C.context_id AND C.context_id = :CID
            WHERE R.user_id = :UID AND K.key_id = :KID and U.user_id = :UID AND L.link_id = :LID",
            array(":LID" => $LINK->id,
                ":CID" => $CONTEXT->id, ":UID" => $user_id)
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row;
    }

}
