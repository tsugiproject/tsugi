<?php

namespace Tsugi\Google;

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\Core\ReqScope;
use \Tsugi\Services\Google\ClassroomQueue;

class GoogleClassroom {

    /**
     * Shown to the student. The teacher sends the queued score later.
     */
    const QUEUED_MESSAGE = 'Your score is saved here. It will be sent to Google Classroom when your instructor sends the queue.';

    /**
     * Where the Classroom controller should send the browser when getClient cannot
     * return a client. Empty when there is nothing to redirect.
     */
    public static $redirectUrl = null;

    /**
     * Flash message for that redirect. Empty when the redirect has no message.
     */
    public static $redirectError = null;

    /**
     * Returns an authorized Clasroom API client.
     *
     * On failure, returns false and sets $redirectUrl (and sometimes $redirectError).
     * The caller sends the browser there. This method does not send a response.
     *
     * @return \Google_Client|false
     */
    /**
     * JSON to store after a code exchange or a refresh, or null when the
     * blob must not replace the stored refresh token.
     *
     * @param string|null $previousJson
     * @param mixed $creds
     * @return string|null
     */
    public static function storedToken($previousJson, $creds) {
        if ( ! is_array($creds) || isset($creds['error']) || empty($creds['access_token']) ) {
            return null;
        }
        $previous = array();
        if ( is_string($previousJson) && $previousJson !== '' ) {
            $decoded = json_decode($previousJson, true);
            if ( is_array($decoded) ) {
                $previous = $decoded;
            }
        }
        if ( empty($creds['refresh_token']) && ! empty($previous['refresh_token']) ) {
            $creds['refresh_token'] = $previous['refresh_token'];
        }
        if ( empty($creds['refresh_token']) ) {
            return null;
        }
        $json = json_encode($creds);
        return is_string($json) ? $json : null;
    }

    /**
     * Short text from a failed token response. Does not include the token.
     *
     * @param mixed $creds
     */
    public static function refreshError($creds) {
        if ( ! is_array($creds) ) {
            return 'Google did not return a token.';
        }
        $error = trim((string) ($creds['error_description'] ?? $creds['error'] ?? ''));
        if ( $error === '' ) {
            return 'Google did not return a token.';
        }
        if ( strlen($error) > 400 ) {
            $error = substr($error, 0, 400);
        }
        return $error;
    }

    public static function getClient($accessTokenStr, $user_id=false) {
        global $CFG;

        self::$redirectUrl = null;
        self::$redirectError = null;

        if ( ! $user_id ) $user_id = ReqScope::loggedInUserIdLegacy();

        $held = self::lockUser($user_id);
        try {
            if ( $held === false ) {
                self::$redirectError = 'Google Classroom is busy. Try again in a moment.';
                return false;
            }
            if ( ! isset($_GET['code']) ) {
                $force = isset($_GET['consent']) && (string) $_GET['consent'] === '1';
                if ( $force ) {
                    // Reconnect must ask which Google account. The stored token
                    // may be a different person than the browser Classroom session.
                    $accessTokenStr = false;
                } else {
                    $fresh = self::readStoredToken($user_id);
                    if ( $fresh ) {
                        $accessTokenStr = $fresh;
                    }
                }
            }
            return self::clientFromToken($accessTokenStr, $user_id);
        } finally {
            if ( $held === true ) {
                self::unlockUser($user_id);
            }
        }
    }

    /**
     * @param string|false $accessTokenStr
     * @param int $user_id
     * @return \Google_Client|false
     */
    private static function clientFromToken($accessTokenStr, $user_id) {
        global $CFG;

        $options = array(
            'client_id' => $CFG->google_client_id,
            'client_secret' => $CFG->google_client_secret,
            'redirect_uri' => $CFG->wwwroot . '/gclass/login'
        );
        $client = new \Google_Client($options);
        $client->setApplicationName($CFG->servicedesc);
        $client->setScopes(implode(' ', array(
            \Google_Service_Classroom::CLASSROOM_COURSES_READONLY,
            \Google_Service_Classroom::CLASSROOM_ROSTERS_READONLY,
            \Google_Service_Classroom::CLASSROOM_PROFILE_EMAILS,
            \Google_Service_Classroom::CLASSROOM_PROFILE_PHOTOS,
            \Google_Service_Classroom::CLASSROOM_COURSEWORK_STUDENTS
            ))
        );
        $client->setAccessType('offline');

        $accessToken = false;
        if ( isset($_GET['code']) ) {
            $accessToken = $client->fetchAccessTokenWithAuthCode($_GET['code']);
            $store = self::storedToken(is_string($accessTokenStr) ? $accessTokenStr : null, $accessToken);
            if ( $store === null ) {
                $message = self::refreshError($accessToken);
                ClassroomQueue::connection($user_id, $message);
                $keys = is_array($accessToken) ? implode(',', array_keys($accessToken)) : 'none';
                error_log('Classroom token exchange rejected user='.$user_id.' keys='.$keys);
                $granted = is_array($accessToken) && ! empty($accessToken['access_token']) && empty($accessToken['error']);
                if ( $granted ) {
                    self::authRedirect($client);
                    return false;
                }
                $app = self::appName();
                self::$redirectError = 'Google Classroom did not connect ('.$message.'). '.
                    'If this keeps happening, revoke '.$app.' at '.
                    'https://myaccount.google.com/permissions and connect again.';
                self::$redirectUrl = $CFG->apphome;
                return false;
            }
            $decoded = json_decode($store, true);
            $client->setAccessToken(is_array($decoded) ? $decoded : $accessToken);
            self::saveToken($user_id, $store);
            ClassroomQueue::connection($user_id, null);
            return $client;
        }

        if ( $accessTokenStr ) {
            $accessToken = json_decode($accessTokenStr, true);
        }

        if ( ! is_array($accessToken) || ! U::get($accessToken, 'refresh_token') ) {
            self::authRedirect($client);
            return false;
        }

        $client->setAccessToken($accessToken);
        if ( $client->isAccessTokenExpired() ) {
            try {
                $creds = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } catch ( \Throwable $e ) {
                ClassroomQueue::connection($user_id, $e->getMessage());
                self::$redirectError = 'Google Classroom needs to be reconnected.';
                self::authRedirect($client);
                return false;
            }
            $store = self::storedToken($accessTokenStr, $creds);
            if ( $store === null ) {
                $message = self::refreshError($creds);
                ClassroomQueue::connection($user_id, $message);
                self::$redirectError = 'Google Classroom needs to be reconnected. '.$message;
                self::authRedirect($client);
                return false;
            }
            $decoded = json_decode($store, true);
            if ( is_array($decoded) ) {
                $client->setAccessToken($decoded);
            }
            if ( $store !== $accessTokenStr ) {
                self::saveToken($user_id, $store);
            }
        }

        ClassroomQueue::connection($user_id, null);
        return $client;
    }

    /**
     * @param \Google_Client $client
     */
    /**
     * Send the browser to Google. There is no stored refresh token yet, so
     * consent is required or Google returns an access token and nothing else.
     *
     * @param \Google_Client $client
     */
    private static function authRedirect($client) {
        // select_account or Google reuses whichever account last approved this
        // client. That account is not always the one signed in to Classroom.
        $client->setPrompt('select_account consent');
        self::$redirectUrl = $client->createAuthUrl();
    }

    /**
     * Name used in a connect error. servicedesc is often unset.
     */
    private static function appName() {
        global $CFG;
        if ( is_string($CFG->servicedesc) && $CFG->servicedesc !== '' ) {
            return $CFG->servicedesc;
        }
        if ( is_string($CFG->servicename) && $CFG->servicename !== '' ) {
            return $CFG->servicename;
        }
        return 'this application';
    }

    /**
     * @param int $user_id
     * @param string $tokenJson
     */
    private static function saveToken($user_id, $tokenJson) {
        global $CFG, $PDOX;
        $sql = "UPDATE {$CFG->dbprefix}lti_user
            SET gc_token = :TOK WHERE user_id = :UID";
        $PDOX->queryDie($sql, array(':UID' => $user_id, ':TOK' => $tokenJson));
        error_log('Classroom token updated user_id='.$user_id);
    }

    /**
     * True when this request holds the lock, false when another request holds
     * it, null when the database cannot lock.
     *
     * @param int $user_id
     * @return bool|null
     */
    private static function lockUser($user_id) {
        if ( (int) $user_id < 1 ) {
            return null;
        }
        try {
            $PDOX = LTIX::getConnection();
            $row = $PDOX->rowDie(
                "SELECT GET_LOCK(:NAME, 15) AS locked",
                array(':NAME' => 'tsugi_gclass_'.(int) $user_id)
            );
        } catch ( \Throwable $e ) {
            error_log('Classroom token lock user='.(int) $user_id.' '.$e->getMessage());
            return null;
        }
        if ( ! is_array($row) || ! array_key_exists('locked', $row) || $row['locked'] === null ) {
            return null;
        }
        return (int) $row['locked'] === 1;
    }

    /**
     * @param int $user_id
     */
    private static function unlockUser($user_id) {
        try {
            $PDOX = LTIX::getConnection();
            $PDOX->rowDie(
                "SELECT RELEASE_LOCK(:NAME) AS released",
                array(':NAME' => 'tsugi_gclass_'.(int) $user_id)
            );
        } catch ( \Throwable $e ) {
            error_log('Classroom token unlock user='.(int) $user_id.' '.$e->getMessage());
        }
    }


    public static function retrieve_instructor_token($user_id=false) {
        if ( ! $user_id ) $user_id = ReqScope::loggedInUserIdLegacy();
        $accessTokenStr = self::readStoredToken($user_id);
        if ( $accessTokenStr ) {
            return $accessTokenStr;
        }
        $sessionToken = LTIX::decrypt_secret(LTIX::ltiParameter('gc_token', false));
        if ( ! is_string($sessionToken) || $sessionToken === '' ) {
            return false;
        }
        $accessToken = json_decode($sessionToken, true);
        if ( ! is_array($accessToken) || ! U::get($accessToken, 'refresh_token') ) {
            return false;
        }
        return $sessionToken;
    }

    /**
     * Stored token for this teacher. A blob with no refresh token is left in
     * place and treated as missing, so a student request cannot wipe it.
     *
     * @param int $user_id
     * @return string|false
     */
    private static function readStoredToken($user_id) {
        global $PDOX, $CFG;
        $row = $PDOX->rowDie(
            "SELECT gc_token FROM {$CFG->dbprefix}lti_user
                WHERE user_id = :UID LIMIT 1",
            array(':UID' => $user_id)
        );
        if ( $row == false ) {
            return false;
        }
        $accessTokenStr = $row['gc_token'];
        if ( ! is_string($accessTokenStr) || $accessTokenStr === '' ) {
            return false;
        }
        $accessToken = json_decode($accessTokenStr, true);
        if ( ! is_array($accessToken) || ! U::get($accessToken, 'refresh_token') ) {
            return false;
        }
        return $accessTokenStr;
    }

    public static function destroy_instructor_token() {
        global $PDOX, $CFG;
        unset($_SESSION['gc_token']);
        $sql = "UPDATE {$CFG->dbprefix}lti_user
            SET gc_token = NULL WHERE user_id = :UID";
        $PDOX->queryDie($sql,
            array(':UID' => ReqScope::loggedInUserIdLegacy())
        );
    }

    /**
     * Send the current launch's 0–1 grade. A failure is queued for the teacher.
     *
     * @param float $fraction
     * @return true|string
     */
    public static function gradeSend($fraction) {
        $lti = U::get($_SESSION, TSUGI_SESSION_LTI);
        if ( ! is_array($lti) ) {
            return 'Google Classroom session is missing.';
        }
        return self::deliver(array(
            'context_id' => U::get($lti, 'context_id'),
            'link_id' => U::get($lti, 'link_id'),
            'user_id' => U::get($lti, 'user_id'),
            'result_id' => U::get($lti, 'result_id'),
            'owner_id' => U::get($lti, 'gc_owner_id'),
            'gc_course' => U::get($lti, 'gc_course'),
            'gc_coursework' => U::get($lti, 'gc_coursework'),
            'gc_submit_id' => U::get($lti, 'gc_submit_id'),
            'grade' => $fraction,
        ));
    }

    /**
     * Patch one Classroom submission. $row is the session fields or a queue row.
     *
     * @param array<string,mixed> $row
     * @return true|string
     */
    public static function deliver(array $row) {
        $ownerId = (int) ($row['owner_id'] ?? 0);
        $course = trim((string) ($row['gc_course'] ?? ''));
        $coursework = trim((string) ($row['gc_coursework'] ?? ''));
        $submitId = trim((string) ($row['gc_submit_id'] ?? ''));
        $fraction = (float) ($row['grade'] ?? 0);
        $points = (int) ($fraction * 100);
        if ( $points < 0 ) {
            $points = 0;
        }
        if ( $points > 100 ) {
            $points = 100;
        }
        $url = 'courses/'.$course.'/courseWork/'.$coursework.'/studentSubmissions/'.$submitId;
        $request = json_encode(array(
            'assignedGrade' => $points,
            'draftGrade' => $points,
        ));

        if ( $course === '' || $coursework === '' || $submitId === '' || $ownerId < 1 ) {
            $detail = 'Missing Classroom course, coursework, or submission.';
            ClassroomQueue::fail($row, $detail, null, $url, $request);
            return self::QUEUED_MESSAGE;
        }

        $accessTokenStr = self::retrieve_instructor_token($ownerId);
        $client = $accessTokenStr ? self::getClient($accessTokenStr, $ownerId) : false;
        if ( ! $client ) {
            $detail = self::$redirectError;
            if ( ! is_string($detail) || $detail === '' ) {
                $detail = 'Not connected to Google Classroom.';
            }
            ClassroomQueue::fail($row, $detail, null, $url, $request);
            error_log('Classroom grade queued owner='.$ownerId.' result='.(int) ($row['result_id'] ?? 0));
            return self::QUEUED_MESSAGE;
        }

        try {
            $service = new \Google_Service_Classroom($client);
            $sub = new \Google_Service_Classroom_StudentSubmission();
            $sub->setAssignedGrade($points);
            $sub->setDraftGrade($points);
            $service->courses_courseWork_studentSubmissions->patch(
                $course,
                $coursework,
                $submitId,
                $sub,
                array('updateMask' => 'assignedGrade,draftGrade')
            );
        } catch ( \Throwable $e ) {
            $http = $e instanceof \Google\Service\Exception ? (int) $e->getCode() : null;
            if ( $http === 401 || $http === 403 ) {
                ClassroomQueue::connection($ownerId, $e->getMessage());
            }
            ClassroomQueue::fail($row, $e->getMessage(), $http, $url, $request);
            error_log('Classroom grade queued owner='.$ownerId.' result='.(int) ($row['result_id'] ?? 0));
            return self::QUEUED_MESSAGE;
        }

        ClassroomQueue::succeed($row, 200, $url, $request);
        return true;
    }

}
