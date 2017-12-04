<?php

namespace Tsugi\Core;

use \Tsugi\Util\LTI;
use \Tsugi\UI\Output;
use \Tsugi\Util\Net;
use \Tsugi\Google\GoogleClassroom;

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
    public $grade = null;

    // Looks up a result for a potentially different user_id so we make
    // sure they are in the same key/ context / link as the current user
    // hence the complex query to make sure we don't cross silos
    public static function lookupResultBypass($user_id) {
        global $CFG, $PDOX, $CONTEXT, $LINK;
        $stmt = $PDOX->queryDie(
            "SELECT result_id, R.link_id AS link_id, R.user_id AS user_id,
                sourcedid, service_id, grade, note, R.json AS json, R.note AS note
            FROM {$CFG->dbprefix}lti_result AS R
            JOIN {$CFG->dbprefix}lti_link AS L
                ON L.link_id = R.link_id AND R.link_id = :LID
            JOIN {$CFG->dbprefix}lti_user AS U
                ON U.user_id = R.user_id AND U.user_id = :UID
            JOIN {$CFG->dbprefix}lti_context AS C
                ON L.context_id = C.context_id AND C.context_id = :CID
            WHERE R.user_id = :UID and U.user_id = :UID AND L.link_id = :LID",
            array(":LID" => $LINK->id,
                ":CID" => $CONTEXT->id, ":UID" => $user_id)
        );
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row;
    }

    /**
     * Load the grade for a particular row and update our local copy
     *
     * Call the right LTI service to retrieve the server's grade and
     * update our local cached copy of the server_grade and the date
     * retrieved. This routine pulls the key and secret from the LTIX
     * session to avoid crossing cross tennant boundaries.
     *
     * TODO: Add LTI 2.x support for the JSON style services to this
     *
     * @param $row An optional array with the data that has the result_id, sourcedid,
     * and service (url) if this is not present, the data is pulled from the LTI
     * session for the current user/link combination.
     * @param $debug_log An (optional) array (by reference) that returns the
     * steps that were taken.
     * Each entry is an array with the [0] element a message and an optional [1]
     * element as some detail (i.e. like a POST body)
     *
     * @return mixed If this work this returns a float.  If not you get
     * a string with an error.
     *
     */
    public static function gradeGet($row=false, &$debug_log=false) {
        global $CFG;

        $PDOX = LTIX::getConnection();

        $key_key = LTIX::ltiParameter('key_key');
        $secret = LTIX::decrypt_secret(LTIX::ltiParameter('secret'));
        if ( $row !== false ) {
            $sourcedid = isset($row['sourcedid']) ? $row['sourcedid'] : false;
            $service = isset($row['service']) ? $row['service'] : false;
            // Fall back to session if it is missing
            if ( $service === false ) $service = LTIX::ltiParameter('service');
            $result_id = isset($row['result_id']) ? $row['result_id'] : false;
        } else {
            $sourcedid = LTIX::ltiParameter('sourcedid');
            $service = LTIX::ltiParameter('service');
            $result_id = LTIX::ltiParameter('result_id');
        }

        if ( $key_key == false || $secret === false ||
            $sourcedid === false || $service === false ) {
            error_log("Result::gradeGet is missing required data");
            return false;
        }

        $grade = LTI::getPOXGrade($sourcedid, $service, $key_key, $secret, $debug_log);

        if ( is_string($grade) ) return $grade;

        // UPDATE our local copy of the server's view of the grade
        if ( $result_id !== false ) {
            $stmt = $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}lti_result SET server_grade = :server_grade,
                    retrieved_at = NOW() WHERE result_id = :RID",
                array( ':server_grade' => $grade, ":RID" => $result_id)
            );
        }
        return $grade;
    }

    /**
     * Send a grade and update our local copy
     *
     * Call the right LTI service to send a new grade up to the server.
     * update our local cached copy of the server_grade and the date
     * retrieved. This routine pulls the key and secret from the LTIX
     * session to avoid crossing cross tennant boundaries.
     *
     * @param $grade A new grade - floating point number between 0.0 and 1.0
     * @param $row An optional array with the data that has the result_id, sourcedid,
     * service (url), key_key, and secret.  If these are not present, the data
     * is pulled from the LTI session for the current user/link combination.
     * @param $debug_log An (optional) array (by reference) that returns the
     * steps that were taken.
     * Each entry is an array with the [0] element a message and an optional [1]
     * element as some detail (i.e. like a POST body)
     *
     * @return mixed If this works it returns true.  If not, you get
     * a string with an error.
     *
     */
    public static function gradeSendStatic($grade, $row=false, &$debug_log=false) {
        global $CFG;
        global $LastPOXGradeResponse;
        $LastPOXGradeResponse = false;

        $PDOX = LTIX::getConnection();

        // Secret and key from session to avoid crossing tenant boundaries
        $key_key = false;
        $secret = false;
        if ( $row !== false ) {
            $result_url = isset($row['result_url']) ? $row['result_url'] : false;
            $sourcedid = isset($row['sourcedid']) ? $row['sourcedid'] : false;
            $service = isset($row['service']) ? $row['service'] : false;
            $key_key = isset($row['key_key']) ? $row['key_key'] : false;
            $secret = isset($row['secret']) ? LTIX::decrypt_secret($row['secret']) : false;
            // Fall back to session if it is missing
            if ( $service === false ) $service = LTIX::ltiParameter('service');
            $result_id = isset($row['result_id']) ? $row['result_id'] : false;
        } else {
            $result_url = LTIX::ltiParameter('result_url');
            $sourcedid = LTIX::ltiParameter('sourcedid');
            $service = LTIX::ltiParameter('service');
            $result_id = LTIX::ltiParameter('result_id');
        }


        // Secret and key from session to avoid crossing tenant boundaries
        if ( ! $key_key ) $key_key = LTIX::ltiParameter('key_key');
        if ( ! $secret ) $secret = LTIX::decrypt_secret(LTIX::ltiParameter('secret'));

        // Get the IP Address
        $ipaddr = Net::getIP();

        // Update the local copy of the grade in the lti_result table
        if ( $PDOX !== false && $result_id !== false ) {
            $stmt = $PDOX->queryReturnError(
                "UPDATE {$CFG->dbprefix}lti_result SET grade = :grade,
                    ipaddr = :IP, updated_at = NOW() WHERE result_id = :RID",
                array(
                    ':grade' => $grade,
                    ':IP' => $ipaddr,
                    ':RID' => $result_id)
            );

            if ( $stmt->success ) {
                $msg = "Grade updated result_id=".$result_id." grade=$grade";
            } else {
                $msg = "Grade NOT updated result_id=".$result_id." grade=$grade";
            }
            error_log($msg);
            if ( is_array($debug_log) )  $debug_log[] = array($msg);
        }

        // A broken launch
        if ( $key_key == false || $secret === false ) {
            error_log("Result::gradeSend stored data locally");
            return false;
        }

        // TODO: Fix this
        $comment = "";
        // Check is this is a Google Classroom Launch
        if ( isset($_SESSION['lti']) && isset($_SESSION['lti']['gc_submit_id']) ) {
            $status = GoogleClassroom::gradeSend(intval($grade*100));

        // If we have a result_url and either ($CFG->prefer_lti1_for_grade_send is false or we don't have a $service),
        // use result_url to send the grade
        } else if ( strlen($result_url) > 0 && ($CFG->prefer_lti1_for_grade_send === false || $service === false) ) {
            $status = LTI::sendJSONGrade($grade, $comment, $result_url, $key_key, $secret, $debug_log);

        // Otherwise use the more established service call
        } else if ( $sourcedid !== false && $service !== false ) {
            $status = LTI::sendPOXGrade($grade, $sourcedid, $service, $key_key, $secret, $debug_log);
        } else {
            return true;   // Local storage only
        }

        return $status;
    }

    /**
     * Send a grade and update our local copy
     *
     * Call the right LTI service to send a new grade up to the server.
     * update our local cached copy of the server_grade and the date
     * retrieved. This routine pulls the key and secret from the LTIX
     * session to avoid crossing cross tennant boundaries.
     *
     * @param $grade A new grade - floating point number between 0.0 and 1.0
     * @param $row An optional array with the data that has the result_id, sourcedid,
     * service (url), key_key, and secret.  If these are not present, the data
     * is pulled from the LTI session for the current user/link combination.
     * @param $debug_log An (optional) array (by reference) that returns the
     * steps that were taken.
     * Each entry is an array with the [0] element a message and an optional [1]
     * element as some detail (i.e. like a POST body)
     *
     * @return mixed If this works it returns true.  If not, you get
     * a string with an error.
     *
     */
    public function gradeSend($grade, $row=false, &$debug_log=false) {
        global $CFG, $USER;

        $status = self::gradeSendStatic($grade, $row, $debug_log);

        if ( $row !== false ) {
            $sourcedid = isset($row['sourcedid']) ? $row['sourcedid'] : false;
        } else {
            $sourcedid = LTIX::ltiParameter('sourcedid');
        }

        // Update the session view of the grade
        if ( $status === true ) {
            $ltidata = $this->session_get('lti');
            if ( $ltidata && $row !== false ) {
                $ltidata['grade'] = $grade;
                $this->session_put('lti', $ltidata);
            }
            $msg = 'Grade sent '.$grade.' to '.$sourcedid.' by '.$USER->id;
            if ( is_array($debug_log) )  $debug_log[] = array($msg);
            error_log($msg);
        } else {
            $msg = 'Grade failure '.$grade.' to '.$sourcedid.' by '.$USER->id;
            if ( is_array($debug_log) )  $debug_log[] = array($msg);
            error_log($msg);
            return $status;
        }

        return $status;
    }

    /**
     * Send a grade applying the due date logic and only increasing grades
     *
     * Puts messages in the session for a redirect.
     *
     * @param $gradetosend - The grade in the range 0.0 .. 1.0
     * @param $oldgrade - The previous grade in the range 0.0 .. 1.0 (optional)
     * @param $dueDate - The due date for this assignment
     */
    public function gradeSendDueDate($gradetosend, $oldgrade=false, $dueDate=false) {
        if ( $gradetosend == 1.0 ) {
            $scorestr = "Your answer is correct, score saved.";
        } else {
            $scorestr = "Your score of ".($gradetosend*100.0)."% has been saved.";
        }
        if ( $dueDate && $dueDate->penalty > 0 ) {
            $gradetosend = $gradetosend * (1.0 - $dueDate->penalty);
            $scorestr = "Effective Score = ".($gradetosend*100.0)."% after ".$dueDate->penalty*100.0." percent late penalty";
        }
        if ( $oldgrade && $oldgrade > $gradetosend ) {
            $scorestr = "New score of ".($gradetosend*100.0)."% is < than previous grade of ".($oldgrade*100.0)."%, previous grade kept";
            $gradetosend = $oldgrade;
        }

        // Use LTIX to store the grade in out database send the grade back to the LMS.
        $debug_log = array();
        $retval = $this->gradeSend($gradetosend, false, $debug_log);
        $this->session_put('debug_log', $debug_log);

        if ( $retval === true ) {
            $this->session_put('success', $scorestr);
        } else if ( $retval === false ) { // Stored locally
            $this->session_put('success', $scorestr);
        } else if ( is_string($retval) ) {
            $this->session_put('error', "Grade not sent: ".$retval);
        } else {
            $svd = Output::safe_var_dump($retval);
            error_log("Grade sending error:".$svd);
            $this->session_put('error', "Grade sending error: ".substr($svd,0,100));
        }
    }

    /**
     * Get the JSON for this result
     */
    public function getJSON()
    {
        global $CFG;
        $PDOX = $this->launch->pdox;

        $stmt = $PDOX->queryDie(
            "SELECT json FROM {$CFG->dbprefix}lti_result
                WHERE result_id = :RID",
            array(':RID' => $this->id)
        );
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row['json'];
    }

    /**
     * Set the JSON for this result
     */
    public function setJSON($json)
    {
        global $CFG;
        $PDOX = $this->launch->pdox;

        $stmt = $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}lti_result SET json = :json, updated_at = NOW()
                WHERE result_id = :RID",
            array(
                ':json' => $json,
                ':RID' => $this->id)
        );
    }

    /**
     * Get the Note for this result
     */
    public function getNote()
    {
        global $CFG;
        $PDOX = $this->launch->pdox;

        $stmt = $PDOX->queryDie(
            "SELECT note FROM {$CFG->dbprefix}lti_result
                WHERE result_id = :RID",
            array(':RID' => $this->id)
        );
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row['note'];
    }

    /**
     * Set the Note for this result
     */
    public function setNote($note)
    {
        global $CFG;
        $PDOX = $this->launch->pdox;

        $stmt = $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}lti_result SET note = :note, updated_at = NOW()
                WHERE result_id = :RID",
            array(
                ':note' => $note,
                ':RID' => $this->id)
        );
    }
}
