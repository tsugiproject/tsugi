<?php

namespace Tsugi\Google;

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;

class GoogleClassroom {

    /**
     * Returns an authorized Clasroom API client.
     * @return Google_Client the authorized client object
     */
    public static function getClient($accessTokenStr, $user_id=false) {
        global $CFG, $PDOX;

        if ( ! $user_id ) $user_id = $_SESSION['id'];

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
            // Google_Service_Classroom::CLASSROOM_COURSEWORK_ME
            ))
        );
        $client->setAccessType('offline');

        $accessToken = false;
        // Are we coming back from an authorization?
        if ( isset($_GET['code']) ) {
            $authCode = $_GET['code'];
            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
        } else if ( $accessTokenStr ) {
            $accessToken = json_decode($accessTokenStr, true);
        }

        // Check if we have a bad access token
        if ( $accessToken && ! U::get($accessToken, 'refresh_token') ) {
            error_log("Bad accessToken");
            error_log(json_encode($accessToken));
            $_SESSION['error'] = 'Did not get a proper Google Classroom token, '.
                'either you have no access to Classroom, '.
                'or you may need to revoke the permission for this app '.
                '(' . $CFG->servicedesc . ') ' .
                'at https://myaccount.google.com/u/0/security?pli=1 ' .
                'and re-establish your connection to Classroom.'.
            header('Location: '.$CFG->apphome);
    	return false;
        }

        if ( $accessToken ) {
            $client->setAccessToken($accessToken);
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            header('Location: '.$authUrl);
            return false;
        }

        // Refresh the token if it's expired.
        // https://stackoverflow.com/questions/10827920/not-receiving-google-oauth-refresh-token
        // The refresh_token is only provided on the first authorization from the user.
        // To fix - revoke app access - https://myaccount.google.com/u/0/security?pli=1
        if ($client->isAccessTokenExpired()) {
            error_log("Expired=".json_encode($client->getAccessToken()));
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        }

        $newAccessToken = $client->getAccessToken();
        $newAccessTokenStr = json_encode($client->getAccessToken());

        // Check if we need to erase/update/store the access token
        if ( $accessToken && ! U::get($accessToken, 'refresh_token') ) {
            unset($_SESSION['gc_token']);
            $sql = "UPDATE {$CFG->dbprefix}lti_user
                SET gc_token = NULL WHERE user_id = :UID";
            $PDOX->queryDie($sql,
                array(':UID' => $_SESSION['id'])
            );
            error_log('Clearing bad access token id='.$_SESSION['id']);
            error_log($accessTokenStr);
            $newAccessTokenStr = false;
        }

        if ( $newAccessTokenStr && $newAccessTokenStr != $accessTokenStr ) {
            $sql = "UPDATE {$CFG->dbprefix}lti_user
                SET gc_token = :TOK WHERE user_id = :UID";
            $PDOX->queryDie($sql,
                array(':UID' => $user_id, ':TOK' => $newAccessTokenStr)
            );
            error_log('Token updated user_id='.$user_id.' token='.$newAccessTokenStr);
        }

        return $client;
    }


    public static function retrieve_instructor_token($user_id=false) {
        global $PDOX, $CFG;

        if ( ! $user_id ) $user_id = $_SESSION['id'];
        // Try access token from session when LTIX adds it.
        $accessTokenStr = LTIX::decrypt_secret(LTIX::ltiParameter('gc_token', false));
        if ( ! $accessTokenStr ) {
            $row = $PDOX->rowDie(
                "SELECT gc_token FROM {$CFG->dbprefix}lti_user
                    WHERE user_id = :UID LIMIT 1",
                array(':UID' => $user_id)
            );

            if ( $row != false ) {
                $accessTokenStr = $row['gc_token'];
            }
        }

        // Discard bad access tokens that inadvertently got stored
        if ( $accessTokenStr ) {
            $accessToken = json_decode($accessTokenStr, true);
            if ( $accessToken && ! U::get($accessToken, 'refresh_token') ) {
                destroy_access_token();
                error_log('Clearing bad access token id='.$_SESSION['id']);
                error_log($accessTokenStr);
                $accessTokenStr = false;
            }
        }
        return $accessTokenStr;
    }

    public static function destroy_instructor_token() {
        global $PDOX, $CFG;
        unset($_SESSION['gc_token']);
        $sql = "UPDATE {$CFG->dbprefix}lti_user
            SET gc_token = NULL WHERE user_id = :UID";
        $PDOX->queryDie($sql,
            array(':UID' => $_SESSION['id'])
        );
    }

    public static function gradeSend($grade) {
        $lti = U::get($_SESSION, 'lti');
        if ( ! is_array($lti) ) return "GoogleClassroom::gradeSend - LTI Session not setup";

        $gc_course = U::get($lti, 'gc_course');
        $gc_coursework = U::get($lti, 'gc_coursework');
        $gc_submit_id = U::get($lti, 'gc_submit_id');
        $gc_owner_id = U::get($lti, 'gc_owner_id');

        if ( ! $gc_course ) return 'GoogleClassroom::gradeSend - missing gc_course';
        if ( ! $gc_coursework ) return 'GoogleClassroom::gradeSend - missing gc_coursework';
        if ( ! $gc_submit_id ) return 'GoogleClassroom::gradeSend - missing gc_submit_id';
        if ( ! $gc_owner_id ) return 'GoogleClassroom::gradeSend - missing gc_owner_id';

        // Try access token from session when LTIX adds it.
        $accessTokenStr = self::retrieve_instructor_token($gc_owner_id);
        if ( ! $accessTokenStr ) {
            error_log('Classroom connection failed id='.$gc_owner_id);
            error_log($accessTokenStr);
            return 'GoogleClassroom::gradeSend - not connected to Google Classroom, contact your instructor';
        }

        // Get the API client and construct the service object.
        $client = self::getClient($accessTokenStr, $gc_owner_id);
        if ( ! $client ) {
            error_log('Classroom connection failed id='.$gc_owner_id);
            error_log($accessTokenStr);
            return 'GoogleClassroom::gradeSend - cannot connect to Google, contact your instructor';
        }

        // Lets talk to Google
        $service = new \Google_Service_Classroom($client);
        $studentSubmissions = $service->courses_courseWork_studentSubmissions;

        $sub = new \Google_Service_Classroom_StudentSubmission();
        $sub->setAssignedGrade($grade);
        $sub->setDraftGrade($grade);
        $sub->setState('TURNED_IN');
        $opt = array('updateMask' => 'assignedGrade,draftGrade');
        $retval = $studentSubmissions->patch($gc_course, $gc_coursework, $gc_submit_id, $sub, $opt);
        return true;
    }

}
