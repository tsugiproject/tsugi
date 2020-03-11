<?php

namespace Tsugi\Core;

use \Tsugi\Util\U;
use \Tsugi\Util\LTI;
use \Tsugi\Util\LTI13;
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
        global $CFG, $LINK;
        global $LastPOXGradeResponse;
        $LastPOXGradeResponse = false;

        $PDOX = LTIX::getConnection();

        // Secret and key from session to avoid crossing tenant boundaries
        $key_key = false;
        $subject_key = false;
        $secret = false;
        $lti13_privkey = false;
        $lti13_pubkey = false;
        $lti13_token_audience = false;
        $lti13_issuer_client = false;
        if ( $row !== false ) {
            $result_url = isset($row['result_url']) ? $row['result_url'] : false;
            $sourcedid = isset($row['sourcedid']) ? $row['sourcedid'] : false;
            $service = isset($row['service']) ? $row['service'] : false;
            $key_key = isset($row['key_key']) ? $row['key_key'] : false;
            $subject_key = isset($row['subject_key']) ? $row['subject_key'] : false;
            $secret = isset($row['secret']) ? LTIX::decrypt_secret($row['secret']) : false;
            // Fall back to session if it is missing
            if ( $service === false ) $service = LTIX::ltiParameter('service');
            $result_id = isset($row['result_id']) ? $row['result_id'] : false;
            $lti13_privkey = isset($row['lti13_privkey']) ? LTIX::decrypt_secret($row['lti13_privkey']) : false;
            $lti13_lineitem = isset($row['lti13_lineitem']) ? $row['lti13_lineitem'] : false;
            $lti13_token_url = isset($row['lti13_token_url']) ? $row['lti13_token_url'] : false;
            $lti13_token_audience = isset($row['lti13_token_audience']) ? $row['lti13_token_audience'] : false;
            $lti13_pubkey = isset($row['lti13_pubkey']) ? $row['lti13_pubkey'] : false;
            $lti13_issuer_client = isset($row['issuer_client']) ? $row['issuer_client'] : false;
        } else {
            $result_url = LTIX::ltiParameter('result_url');
            $sourcedid = LTIX::ltiParameter('sourcedid');
            $service = LTIX::ltiParameter('service');
            $result_id = LTIX::ltiParameter('result_id');
            $lti13_lineitem = LTIX::ltiParameter('lti13_lineitem');
            $lti13_token_url = LTIX::ltiParameter('lti13_token_url');
            $lti13_token_audience = LTIX::ltiParameter('lti13_token_audience');
        }

        // Check if we are to use SHA256 as the signature
        $signature = false;
        if ( isset($LINK) ) {
            $signature = $LINK->settingsGet('oauth_signature_method');
            // error_log("Sending... sig=$signature");
        }

        // Secret and key from session to avoid crossing tenant boundaries
        if ( ! $key_key ) $key_key = LTIX::ltiParameter('key_key');
        if ( ! $subject_key ) $subject_key = LTIX::ltiParameter('subject_key');
        if ( ! $secret ) $secret = LTIX::decrypt_secret(LTIX::ltiParameter('secret'));
        if ( ! $lti13_privkey ) $lti13_privkey = LTIX::decrypt_secret(LTIX::ltiParameter('lti13_privkey'));
        if ( ! $lti13_pubkey ) $lti13_pubkey = LTIX::ltiParameter('lti13_pubkey');

        // Get the IP Address
        $ipaddr = Net::getIP();

        // Update the local copy of the grade in the lti_result table
        if ( $PDOX !== false && ! empty($result_id) ) {
            $stmt = $PDOX->queryReturnError(
                "UPDATE {$CFG->dbprefix}lti_result SET grade = :grade,
                    ipaddr = :IP, updated_at = NOW() WHERE result_id = :RID",
                array(
                    ':grade' => $grade,
                    ':IP' => $ipaddr,
                    ':RID' => $result_id)
            );

            if ( $stmt->success ) {
                $msg = "Grade stored locally result_id=".$result_id." grade=$grade";
            } else {
                $msg = "Grade NOT stored locally result_id=".$result_id." grade=$grade";
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

        // TODO: Cache the token and renew
        // LTI 1.3 grade passback - Prefer if available
        } else if ( strlen($lti13_issuer_client) > 0 && strlen($lti13_privkey) > 0 && strlen($lti13_lineitem) > 0 && strlen($lti13_token_url) > 0 ) {
            error_log("Getting token lti13_issuer_client=$lti13_issuer_client lti13_token_url=$lti13_token_url");

            $grade_token = LTI13::getGradeToken($lti13_issuer_client, $lti13_token_url, $lti13_privkey, $lti13_pubkey, $lti13_token_audience, $debug_log);

            $comment = "Sending grade $grade subject_key=$subject_key lti13_lineitem=$lti13_lineitem grade_token=$grade_token";
            error_log($comment);
            $comment = "Sending grade $grade subject_key=$subject_key";
            $status = LTI13::sendLineItemResult($subject_key, $grade, $comment, $lti13_lineitem,
                        $grade_token, $debug_log);

        // Classic POX call
        } else if ( strlen($key_key) > 0 && strlen($secret) > 0 && strlen($sourcedid) > 0 && strlen($service) > 0 ) {
            $status = LTI::sendPOXGrade($grade, $sourcedid, $service, $key_key, $secret, $debug_log, $signature);

        // LTI 2.x call - Least likely
        } else if ( strlen($key_key) > 0 && strlen($secret) > 0 && strlen($result_url) > 0 ) {
            $status = LTI::sendJSONGrade($grade, $comment, $result_url, $key_key, $secret, $debug_log, $signature);

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
            if ( strlen($sourcedid) > 0 ) {
                $msg = 'Grade sent '.$grade.' to '.$sourcedid.' by '.$USER->id;
                if ( is_array($debug_log) )  $debug_log[] = array($msg);
                error_log($msg);
            }
        } else {
            $msg = 'Grade failure '.$grade.' to '.$sourcedid.' by '.$USER->id;
            if ( is_array($debug_log) )  $debug_log[] = array($msg);
            error_log($msg);
            $svd = Output::safe_var_dump($debug_log);
            error_log("Grade falure detail:\n".$svd);
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
     *
     * TODO: Remove
     */
    public function getJSON_deprecated()
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
     *
     * TODO: Remove
     *
     */
    public function setJSON_deprecated($json)
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
     * Get a JSON for a different user
     *
     * @param $user_id The primary key of the user (instructor only)
     *
     * @return The annotation array
     */
    public function getJsonForUser($user_id) {
        global $CFG, $PDOX;
        if ( ! $this->launch->user->instructor || ! $user_id || $user_id == $this->launch->user->id ){
            $retval = $this->getJson();
            return $retval;
        } else if ( $this->launch->user->instructor ) {
            $p = $CFG->dbprefix;
            $row = $PDOX->rowDie(
                "SELECT json
                FROM {$p}lti_result AS R
                WHERE R.link_id = :LID and R.user_id = :UID",
                array(
                    ":UID" => $user_id,
                    ":LID" => $this->launch->link->id
                )
            );
            if ( ! $row ) return '';
            $json_str = $row['json'];
            return $json_str;
        } else {
            return false;
            http_response_code(403);
            die();
        }
    }

    /**
     * Get a JSON key for a result for a different user
     *
     * @param $key The key to be retrieved from the JSON
     * @param $default The default value (optional)
     * @param $user_id The primary key of the user (instructor only)
     *
     * @return The annotation array
     */
    public function getJsonKeyForUser($key, $default=false, $user_id=false) {
        global $CFG, $PDOX;
        if ( ! $this->launch->user->instructor || ! $user_id || $user_id == $this->launch->user->id ){
            $retval = $this->getJsonKey($key, $default);
            return $retval;
        } else if ( $this->launch->user->instructor ) {
            $json_str = $this->getJsonForUser($user_id);
            $json = json_decode($json_str);
            if ( isset($json->{$key}) ) return $json->{$key};
            return $default;
        } else {
            return false;
            http_response_code(403);
            die();
        }
    }

    /**
     * Set JSON for a different user
     *
     * @param $json_str The JSON String
     * @param $user_id The primary key of the user (instructor only)
     *
     * @return The annotation array
     */
    public function setJsonForUser($json_str, $user_id=false) {
        global $CFG, $PDOX;
        if ( ! $this->launch->user->instructor || ! $user_id || $user_id == $this->launch->user->id ){
            $retval = $this->setJson($json_str);
        } else if ( $this->launch->user->instructor ) {
            $p = $CFG->dbprefix;
            $stmt = $PDOX->queryDie(
                "UPDATE {$p}lti_result SET json = :JSON
                WHERE link_id = :LID and user_id = :UID",
                array(
                    ":JSON" => $json_str,
                    ":UID" => $user_id,
                    ":LID" => $this->launch->link->id
                )
            );
        } else {
            http_response_code(403);
            die();
        }
    }

    /**
     * Set a JSON key for a result for a different user
     *
     * @param $key The key to be retrieved from the JSON
     * @param $value The default value (optional)
     * @param $user_id The primary key of the user (instructor only)
     *
     * @return The annotation array
     */
    public function setJsonKeyForUser($key, $value, $user_id=false) {
        global $CFG, $PDOX;

        $old = $this->getJsonForUser($user_id);
        $old_json = json_decode($old);
        if ( $old_json == null ) $old_json = new \stdClass();
        $old_json->{$key} = $value;
        $new = json_encode($old_json);
        $this->setJsonForUser($new, $user_id);
    }

    /**
     * Get a Note 
     *
     * @param $user_id The primary key of the user (instructor only)
     *
     * @return The annotation array
     */
    public function getNote($user_id=false) {
        global $CFG;

        $PDOX = $this->launch->pdox;
        if ( ! $this->launch->user->instructor || ! $user_id || $user_id == $this->launch->user->id ){
            $stmt = $PDOX->queryDie(
                "SELECT note FROM {$CFG->dbprefix}lti_result
                    WHERE result_id = :RID",
                array(':RID' => $this->id)
            );
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row['note'];
        } else if ( $this->launch->user->instructor ) {
            $p = $CFG->dbprefix;
            $row = $PDOX->rowDie(
                "SELECT note
                FROM {$p}lti_result AS R
                WHERE R.link_id = :LID and R.user_id = :UID",
                array(
                    ":UID" => $user_id,
                    ":LID" => $this->launch->link->id
                )
            );
            $note_str = $row['note'];
            return $note_str;
        } else {
            return false;
            http_response_code(403);
            die();
        }
    }

    /**
     * Set the Note for this result
     *
     * @param $json_str The Note String
     * @param $user_id The primary key of the user (instructor only)
     *
     * @return The annotation array
     */
    public function setNote($note_str, $user_id=false) {
        global $CFG;
        $PDOX = $this->launch->pdox;
        if ( ! $this->launch->user->instructor || ! $user_id || $user_id == $this->launch->user->id ){
            $retval = $this->setNote($json_str);
            $stmt = $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}lti_result SET note = :note, updated_at = NOW()
                    WHERE result_id = :RID",
                array(
                    ':note' => $note,
                    ':RID' => $this->id)
            );
        } else if ( $this->launch->user->instructor ) {
            $p = $CFG->dbprefix;
            $stmt = $PDOX->queryDie(
                "UPDATE {$p}lti_result SET note = :NOTE
                WHERE link_id = :LID and user_id = :UID",
                array(
                    ":NOTE" => $note_str,
                    ":UID" => $user_id,
                    ":LID" => $this->launch->link->id
                )
            );
        } else {
            http_response_code(403);
            die();
        }
    }
}
