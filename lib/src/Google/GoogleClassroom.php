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

    public static function getClient($accessTokenStr, $user_id=false, $useAuthCode=true, $lockWait=15, $httpTimeout=null) {
        global $CFG;

        self::$redirectUrl = null;
        self::$redirectError = null;

        if ( ! $user_id ) $user_id = ReqScope::loggedInUserIdLegacy();

        $held = self::lockUser($user_id, $lockWait);
        try {
            if ( $held === false ) {
                self::$redirectError = 'Google Classroom is busy. Try again in a moment.';
                return false;
            }
            if ( ! $useAuthCode || ! isset($_GET['code']) ) {
                $force = $useAuthCode && isset($_GET['consent']) && (string) $_GET['consent'] === '1';
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
            return self::clientFromToken($accessTokenStr, $user_id, $useAuthCode, $httpTimeout);
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
    private static function clientFromToken($accessTokenStr, $user_id, $useAuthCode=true, $httpTimeout=null) {
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
            \Google_Service_Classroom::CLASSROOM_COURSEWORK_STUDENTS,
            \Google_Service_Classroom::CLASSROOM_COURSEWORKMATERIALS
            ))
        );
        $client->setAccessType('offline');
        $client->setIncludeGrantedScopes(true);
        if ( is_numeric($httpTimeout) && (float) $httpTimeout > 0 ) {
            $seconds = (float) $httpTimeout;
            $client->setHttpClient(new \GuzzleHttp\Client(array(
                'timeout' => $seconds,
                'connect_timeout' => min(2, $seconds),
            )));
        }

        $accessToken = false;
        $code = $useAuthCode ? self::classroomLoginCode($user_id) : false;
        if ( is_string($code) ) {
            $accessToken = $client->fetchAccessTokenWithAuthCode($code);
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
                error_log('Classroom token refresh unavailable user='.$user_id);
                self::$redirectError = 'Google Classroom is temporarily unavailable.';
                return false;
            }
            $store = self::storedToken($accessTokenStr, $creds);
            if ( $store === null ) {
                $message = self::refreshError($creds);
                if ( ! self::credentialRejected($creds) ) {
                    error_log('Classroom token refresh unavailable user='.$user_id);
                    self::$redirectError = 'Google Classroom is temporarily unavailable.';
                    return false;
                }
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
        if ( session_id() == '' ) {
            session_start();
        }
        $state = bin2hex(random_bytes(16));
        $_SESSION['gc_oauth_state'] = $state;
        $_SESSION['gc_oauth_user'] = (int) ReqScope::loggedInUserIdLegacy();
        $client->setState($state);
        $client->setPrompt('select_account consent');
        self::$redirectUrl = $client->createAuthUrl();
    }

    /**
     * Authorization codes are exchanged only on /gclass/login, and only for
     * the browser session that started that connect.
     *
     * @param int $user_id
     * @return string|false
     */
    private static function classroomLoginCode($user_id) {
        if ( ! self::isClassroomLoginRequest() ) {
            return false;
        }
        $state = (string) ($_GET['state'] ?? '');
        $saved = (string) ($_SESSION['gc_oauth_state'] ?? '');
        $savedUser = (int) ($_SESSION['gc_oauth_user'] ?? 0);
        unset($_SESSION['gc_oauth_state'], $_SESSION['gc_oauth_user']);
        if ( $state === '' || $saved === '' || ! hash_equals($saved, $state) ) {
            return false;
        }
        if ( $savedUser < 1 || $savedUser !== (int) $user_id ) {
            return false;
        }
        $code = $_GET['code'] ?? '';
        return is_string($code) && $code !== '' ? $code : false;
    }

    private static function isClassroomLoginRequest() {
        global $CFG;
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        $expected = parse_url(rtrim((string) $CFG->wwwroot, '/').'/gclass/login', PHP_URL_PATH);
        if ( ! is_string($path) || ! is_string($expected) ) {
            return false;
        }
        return rtrim($path, '/') === rtrim($expected, '/');
    }

    /**
     * Google refused the stored refresh token. A network failure is not this.
     *
     * @param mixed $creds
     */
    private static function credentialRejected($creds) {
        if ( ! is_array($creds) ) {
            return false;
        }
        $error = (string) ($creds['error'] ?? '');
        return $error === 'invalid_grant' || $error === 'invalid_client' || $error === 'unauthorized_client';
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
    private static function lockUser($user_id, $wait=15) {
        if ( (int) $user_id < 1 ) {
            return null;
        }
        $wait = (int) $wait;
        if ( $wait < 0 ) {
            $wait = 0;
        }
        try {
            $PDOX = LTIX::getConnection();
            $row = $PDOX->rowDie(
                "SELECT GET_LOCK(:NAME, ".$wait.") AS locked",
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


    /**
     * Whether this stored token lists a scope. Null when the token has no
     * scope list, so the caller has to try the call.
     *
     * @param string|false $accessTokenStr
     * @param string $scope
     * @return bool|null
     */
    public static function grantsScope($accessTokenStr, $scope) {
        if ( ! is_string($accessTokenStr) || $accessTokenStr === '' ) {
            return null;
        }
        $decoded = json_decode($accessTokenStr, true);
        if ( ! is_array($decoded) ) {
            return null;
        }
        $granted = trim((string) ($decoded['scope'] ?? ''));
        if ( $granted === '' ) {
            return null;
        }
        foreach ( preg_split('/\s+/', $granted) as $one ) {
            if ( $one === $scope ) {
                return true;
            }
        }
        return false;
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
    /**
     * Send a stored result when the quiz page is the site login.
     * Null means this result has no Classroom submission.
     *
     * @param int $resultId
     * @param float $fraction
     * @return true|string|null
     */
    public static function deliverStored($resultId, $fraction) {
        $target = self::storedTarget((int) $resultId);
        if ( $target === null ) {
            return null;
        }
        $target['grade'] = $fraction;
        return self::deliver($target);
    }

    /**
     * @param int $resultId
     * @return array<string,mixed>|null
     */
    private static function storedTarget($resultId) {
        if ( $resultId < 1 ) {
            return null;
        }
        global $CFG;
        $PDOX = LTIX::getConnection();
        $row = $PDOX->rowDie(
            "SELECT R.result_id AS result_id, R.user_id AS user_id, R.link_id AS link_id,
                    R.gc_submit_id AS gc_submit_id,
                    L.gc_coursework AS gc_coursework, L.gc_course AS gc_course, L.link_key AS link_key,
                    C.context_id AS context_id, C.user_id AS owner_id
             FROM {$CFG->dbprefix}lti_result AS R
             JOIN {$CFG->dbprefix}lti_link AS L ON L.link_id = R.link_id
             JOIN {$CFG->dbprefix}lti_context AS C ON C.context_id = L.context_id
             WHERE R.result_id = :RID AND (R.deleted IS NULL OR R.deleted = 0)
             LIMIT 1",
            array(':RID' => $resultId)
        );
        if ( ! is_array($row) ) {
            return null;
        }
        $submit = trim((string) ($row['gc_submit_id'] ?? ''));
        if ( $submit === '' ) {
            return null;
        }
        $work = trim((string) ($row['gc_coursework'] ?? ''));
        $key = trim((string) ($row['link_key'] ?? ''));
        if ( $work === '' && $key !== '' && ctype_digit($key) ) {
            $work = $key;
        }
        return array(
            'context_id' => (int) ($row['context_id'] ?? 0),
            'link_id' => (int) ($row['link_id'] ?? 0),
            'user_id' => (int) ($row['user_id'] ?? 0),
            'result_id' => (int) ($row['result_id'] ?? 0),
            'owner_id' => (int) ($row['owner_id'] ?? 0),
            'gc_course' => trim((string) ($row['gc_course'] ?? '')),
            'gc_coursework' => $work,
            'gc_submit_id' => $submit,
        );
    }

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

    /**
     * Send grades already stored on this link to a coursework just created.
     * A student with no Classroom submission yet is left for their first open.
     *
     * @param int $contextId
     * @param int $linkId
     * @param int $ownerId
     * @param string $course
     * @param string $coursework
     * @return array{sent:int,queued:int,waiting:int}
     */
    public static function pushStoredGrades($contextId, $linkId, $ownerId, $course, $coursework) {
        $counts = array('sent' => 0, 'queued' => 0, 'waiting' => 0);
        $contextId = (int) $contextId;
        $linkId = (int) $linkId;
        $ownerId = (int) $ownerId;
        $course = trim((string) $course);
        $coursework = trim((string) $coursework);
        if ( $contextId < 1 || $linkId < 1 || $ownerId < 1 || $course === '' || $coursework === '' ) {
            return $counts;
        }
        $grades = self::storedGrades($linkId);
        if ( count($grades) < 1 ) {
            return $counts;
        }

        $accessTokenStr = self::retrieve_instructor_token($ownerId);
        $client = $accessTokenStr ? self::getClient($accessTokenStr, $ownerId) : false;
        if ( ! $client ) {
            error_log('Classroom stored grades not sent; teacher not connected owner='.$ownerId);
            $counts['waiting'] = count($grades);
            return $counts;
        }

        try {
            $service = new \Google_Service_Classroom($client);
            $byEmail = self::classroomStudentsByEmail($service, $course);
            $byUser = self::classroomSubmissionsByUser($service, $course, $coursework);
            if ( count($byEmail) > 0 && count($byUser) < 1 ) {
                sleep(1);
                $byUser = self::classroomSubmissionsByUser($service, $course, $coursework);
            }
        } catch ( \Throwable $e ) {
            error_log('Classroom stored grades list failed owner='.$ownerId);
            $counts['waiting'] = count($grades);
            return $counts;
        }

        foreach ( $grades as $grade ) {
            $email = strtolower(trim((string) ($grade['email'] ?? '')));
            $googleUser = ($email !== '' && isset($byEmail[$email])) ? $byEmail[$email] : '';
            $submitId = ($googleUser !== '' && isset($byUser[$googleUser])) ? $byUser[$googleUser] : '';
            if ( $submitId === '' ) {
                $counts['waiting']++;
                continue;
            }
            self::storeSubmitId((int) $grade['result_id'], $submitId);
            $status = self::deliver(array(
                'context_id' => $contextId,
                'link_id' => $linkId,
                'user_id' => (int) ($grade['user_id'] ?? 0),
                'result_id' => (int) ($grade['result_id'] ?? 0),
                'owner_id' => $ownerId,
                'gc_course' => $course,
                'gc_coursework' => $coursework,
                'gc_submit_id' => $submitId,
                'grade' => (float) ($grade['grade'] ?? 0),
            ));
            if ( $status === true ) {
                $counts['sent']++;
            } else {
                $counts['queued']++;
            }
        }
        return $counts;
    }

    /**
     * @param int $linkId
     * @return array<int,array<string,mixed>>
     */
    private static function storedGrades($linkId) {
        global $CFG;
        $PDOX = LTIX::getConnection();
        $rows = $PDOX->allRowsDie(
            "SELECT R.result_id AS result_id, R.user_id AS user_id, R.grade AS grade, U.email AS email
             FROM {$CFG->dbprefix}lti_result AS R
             JOIN {$CFG->dbprefix}lti_user AS U ON U.user_id = R.user_id
             WHERE R.link_id = :LID AND R.grade IS NOT NULL
               AND (R.deleted IS NULL OR R.deleted = 0)",
            array(':LID' => (int) $linkId)
        );
        return is_array($rows) ? $rows : array();
    }

    /**
     * @param int $resultId
     * @param string $submitId
     */
    private static function storeSubmitId($resultId, $submitId) {
        if ( $resultId < 1 || $submitId === '' ) {
            return;
        }
        global $CFG;
        $PDOX = LTIX::getConnection();
        $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}lti_result
             SET gc_submit_id = :SUB, updated_at = NOW()
             WHERE result_id = :RID",
            array(':SUB' => $submitId, ':RID' => $resultId)
        );
    }

    /**
     * @param \Google_Service_Classroom $service
     * @param string $course
     * @return array<string,string> lowercase email to Classroom user id
     */
    private static function classroomStudentsByEmail($service, $course) {
        $found = array();
        $token = '';
        do {
            $opt = array('pageSize' => 100);
            if ( $token !== '' ) {
                $opt['pageToken'] = $token;
            }
            $page = $service->courses_students->listCoursesStudents($course, $opt);
            $students = $page->getStudents();
            if ( is_array($students) ) {
                foreach ( $students as $student ) {
                    $profile = $student->getProfile();
                    $email = is_object($profile) ? strtolower(trim((string) $profile->getEmailAddress())) : '';
                    $uid = trim((string) $student->getUserId());
                    if ( $email !== '' && $uid !== '' ) {
                        $found[$email] = $uid;
                    }
                }
            }
            $token = trim((string) $page->getNextPageToken());
        } while ( $token !== '' );
        return $found;
    }

    /**
     * @param \Google_Service_Classroom $service
     * @param string $course
     * @param string $coursework
     * @return array<string,string> Classroom user id to submission id
     */
    private static function classroomSubmissionsByUser($service, $course, $coursework) {
        $found = array();
        $token = '';
        do {
            $opt = array('pageSize' => 100);
            if ( $token !== '' ) {
                $opt['pageToken'] = $token;
            }
            $page = $service->courses_courseWork_studentSubmissions->listCoursesCourseWorkStudentSubmissions(
                $course,
                $coursework,
                $opt
            );
            $subs = $page->getStudentSubmissions();
            if ( is_array($subs) ) {
                foreach ( $subs as $sub ) {
                    $uid = trim((string) $sub->getUserId());
                    $sid = trim((string) $sub->getId());
                    if ( $uid !== '' && $sid !== '' && ! isset($found[$uid]) ) {
                        $found[$uid] = $sid;
                    }
                }
            }
            $token = trim((string) $page->getNextPageToken());
        } while ( $token !== '' );
        return $found;
    }

    /**
     * Copy a Tsugi end-of-day due onto a Classroom assignment.
     * An empty value leaves the assignment without a due date.
     *
     * @param \Google_Service_Classroom_CourseWork $courseWork
     * @param mixed $mysql
     */
    public static function fillDueDate($courseWork, $mysql) {
        $parts = self::dueParts($mysql);
        if ( $parts === null ) {
            return;
        }
        $date = new \Google_Service_Classroom_Date();
        $date->setYear($parts['year']);
        $date->setMonth($parts['month']);
        $date->setDay($parts['day']);
        $time = new \Google_Service_Classroom_TimeOfDay();
        $time->setHours($parts['hours']);
        $time->setMinutes($parts['minutes']);
        $time->setSeconds($parts['seconds']);
        $courseWork->setDueDate($date);
        $courseWork->setDueTime($time);
    }

    /**
     * Patch the Classroom assignment for this link after a due date save.
     * A link that is not mirrored is left alone.
     *
     * @param int $contextId
     * @param string $linkKey
     * @return array{ok:bool,status:string,message?:string}
     */
    public static function pushDueDateForKey($contextId, $linkKey) {
        $contextId = (int) $contextId;
        $linkKey = trim((string) $linkKey);
        if ( $contextId < 1 || $linkKey === '' ) {
            return array('ok' => true, 'status' => 'not_classroom');
        }
        global $CFG;
        $PDOX = LTIX::getConnection();
        $row = $PDOX->rowDie(
            "SELECT L.link_id AS link_id, L.gc_course AS gc_course, L.gc_coursework AS gc_coursework,
                    L.link_key AS link_key, L.end_datetime AS end_datetime,
                    C.user_id AS owner_id
             FROM {$CFG->dbprefix}lti_link AS L
             JOIN {$CFG->dbprefix}lti_context AS C ON C.context_id = L.context_id
             WHERE L.context_id = :CID AND L.link_key = :LK
               AND (L.deleted IS NULL OR L.deleted = 0)
             LIMIT 1",
            array(':CID' => $contextId, ':LK' => $linkKey)
        );
        if ( ! is_array($row) ) {
            return array('ok' => true, 'status' => 'not_classroom');
        }
        $course = trim((string) ($row['gc_course'] ?? ''));
        $work = trim((string) ($row['gc_coursework'] ?? ''));
        $key = trim((string) ($row['link_key'] ?? ''));
        if ( $work === '' && $course !== '' && $key !== '' && ctype_digit($key) ) {
            $work = $key;
        }
        $ownerId = (int) ($row['owner_id'] ?? 0);
        if ( $course === '' || $work === '' || $ownerId < 1 ) {
            return array('ok' => true, 'status' => 'not_classroom');
        }

        $linkId = (int) ($row['link_id'] ?? 0);
        $parts = self::dueParts($row['end_datetime'] ?? null);
        $url = 'courses/'.$course.'/courseWork/'.$work;
        $request = json_encode(array(
            'updateMask' => 'dueDate,dueTime',
            'dueDate' => $parts === null ? null : array(
                'year' => $parts['year'],
                'month' => $parts['month'],
                'day' => $parts['day'],
            ),
            'dueTime' => $parts === null ? null : array(
                'hours' => $parts['hours'],
                'minutes' => $parts['minutes'],
                'seconds' => $parts['seconds'],
            ),
        ));
        $detail = $parts === null ? 'Due date cleared' : 'Due date set';

        $accessTokenStr = self::retrieve_instructor_token($ownerId);
        $client = $accessTokenStr ? self::getClient($accessTokenStr, $ownerId) : false;
        if ( ! $client ) {
            ClassroomQueue::note($contextId, $linkId, $ownerId, ClassroomQueue::KIND_DUE, false, 'Not connected to Google Classroom.', null, $url, $request);
            return array(
                'ok' => false,
                'status' => 'error',
                'message' => 'Not connected to Google Classroom.',
            );
        }

        try {
            $service = new \Google_Service_Classroom($client);
            $cw = new \Google_Service_Classroom_CourseWork();
            self::fillDueDate($cw, $row['end_datetime'] ?? null);
            $service->courses_courseWork->patch(
                $course,
                $work,
                $cw,
                array('updateMask' => 'dueDate,dueTime')
            );
        } catch ( \Throwable $e ) {
            $message = ClassroomQueue::scrub($e->getMessage());
            if ( strlen($message) > 400 ) {
                $message = substr($message, 0, 400);
            }
            if ( $message === '' ) {
                $message = 'A due date was not updated in Google Classroom.';
            }
            $http = $e instanceof \Google\Service\Exception ? (int) $e->getCode() : null;
            ClassroomQueue::note($contextId, $linkId, $ownerId, ClassroomQueue::KIND_DUE, false, $message, $http, $url, $request);
            error_log('Classroom due date not updated owner='.$ownerId.' link='.$linkKey);
            return array('ok' => false, 'status' => 'error', 'message' => $message);
        }
        ClassroomQueue::note($contextId, $linkId, $ownerId, ClassroomQueue::KIND_DUE, true, $detail, 200, $url, $request);
        return array('ok' => true, 'status' => 'updated');
    }

    /**
     * Calendar day and clock time Classroom should show.
     *
     * The stored value is 23:59:59 on the day the teacher picked. The site
     * zone is west of the teacher, so converting that instant to UTC makes
     * Classroom show the next morning. These fields are the calendar day
     * and 23:59:59 themselves.
     *
     * @param mixed $mysql
     * @return array{year:int,month:int,day:int,hours:int,minutes:int,seconds:int}|null
     */
    private static function dueParts($mysql) {
        $raw = trim((string) $mysql);
        if ( $raw === '' || strpos($raw, '0000-00-00') === 0 ) {
            return null;
        }
        $raw = str_replace('T', ' ', $raw);
        if ( ! preg_match('/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $raw, $match) ) {
            return null;
        }
        if ( ! checkdate((int) $match[2], (int) $match[3], (int) $match[1]) ) {
            return null;
        }
        return array(
            'year' => (int) $match[1],
            'month' => (int) $match[2],
            'day' => (int) $match[3],
            'hours' => (int) $match[4],
            'minutes' => (int) $match[5],
            'seconds' => (int) $match[6],
        );
    }

}
