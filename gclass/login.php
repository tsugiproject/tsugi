<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\Lessons;
use \Tsugi\Crypt\SecureCookie;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once __DIR__ . '/../config.php';


$PDOX = LTIX::getConnection();

session_start();

define('APPLICATION_NAME', $CFG->servicedesc);
define('SCOPES', implode(' ', array(
  Google_Service_Classroom::CLASSROOM_COURSES_READONLY,
  Google_Service_Classroom::CLASSROOM_ROSTERS_READONLY,
  Google_Service_Classroom::CLASSROOM_PROFILE_EMAILS,
  Google_Service_Classroom::CLASSROOM_PROFILE_PHOTOS,
  Google_Service_Classroom::CLASSROOM_COURSEWORK_STUDENTS)
));

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient($accessTokenStr) {
    global $CFG;
    $options = array(
        'client_id' => $CFG->google_client_id,
        'client_secret' => $CFG->google_client_secret,
        'redirect_uri' => $CFG->wwwroot . '/gclass/login'
    );
    $client = new Google_Client($options);
    $client->setApplicationName(APPLICATION_NAME);
    $client->setScopes(SCOPES);
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
        error_log("Bad accessToken, id=".$_SESSION['id']);
        error_log(json_encode($accessToken));
        $_SESSION['error'] = 'Did not get a proper Google Classroom token, either you have no access to Classroom, '.
            'or you may need to revoke the permission for this app at '.
            'https://myaccount.google.com/u/0/security?pli=1 ' .
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

    return $client;
}

if ( !isset($_SESSION['id']) ) {
    die_with_error_log('Error: Must be logged in to use Google Classroom');
}

if ( !isset($_SESSION['lti']) ) {
    $_SESSION['error'] = 'Please log out and back in.';
    header('Location: '.$CFG->apphome);
    return false;
}

if ( !isset($_SESSION['lti']['key_id']) ) {
    die_with_error_log('Error: Session is missing key_id');
}

if ( ! isset($CFG->lessons) ) {
    die_with_error_log('Cannot find lessons.json ($CFG->lessons)');
}

// Load the Lesson
$l = new Lessons($CFG->lessons);
$firstmodule = false;
if (isset($l->lessons->modules[0]->anchor) ) {
    $firstmodule = $l->lessons->modules[0]->anchor;
}

// Try access token from session when LTIX adds it.
$accessTokenStr = LTIX::decrypt_secret(LTIX::ltiParameter('gc_token', false));
if ( ! $accessTokenStr ) {
    $row = $PDOX->rowDie(
        "SELECT gc_token FROM {$CFG->dbprefix}lti_user
            WHERE user_id = :UID LIMIT 1",
        array(':UID' => $_SESSION['id'])
    );

    if ( $row != false ) {
        $accessTokenStr = $row['gc_token'];
    }
}

// Discard bad access tokens that inadvertently got stored
if ( $accessTokenStr ) {
    $accessToken = json_decode($accessTokenStr, true);
    if ( $accessToken && ! U::get($accessToken, 'refresh_token') ) {
        unset($_SESSION['gc_token']);
        $sql = "UPDATE {$CFG->dbprefix}lti_user
            SET gc_token = NULL WHERE user_id = :UID";
        $PDOX->queryDie($sql,
            array(':UID' => $_SESSION['id'])
        );
        error_log('Clearing bad access token id='.$_SESSION['id']);
        error_log($accessTokenStr);
        $accessTokenStr = false;
    }
}

// Get the API client and construct the service object.
// This will fail if our token is revoked or otherwise bad
try {
    $client = getClient($accessTokenStr);
    if ( ! $client ) return;
    $service = new Google_Service_Classroom($client);

    // Print the first 100 courses the user has access to.
    $optParams = array(
        'pageSize' => 100
    );

    $results = $service->courses->listCourses($optParams);
} catch(Exception $e) {
    // Revoked token..
    $accessTokenStr = false;
    unset($_SESSION['gc_token']);
    $sql = "UPDATE {$CFG->dbprefix}lti_user
        SET gc_token = NULL WHERE user_id = :UID";
    $PDOX->queryDie($sql,
        array(':UID' => $_SESSION['id'])
    );
    // Should redirect...
    $client = getClient($accessTokenStr);
    return;
}

// Check if we need to update/store the access token
$newAccessTokenStr = json_encode($client->getAccessToken());
if ( $newAccessTokenStr != $accessTokenStr ) {
    $sql = "UPDATE {$CFG->dbprefix}lti_user
        SET gc_token = :TOK WHERE user_id = :UID";
    $PDOX->queryDie($sql,
        array(':UID' => $_SESSION['id'], ':TOK' => $newAccessTokenStr)
    );
    error_log('Token updated user_id='.$_SESSION[ 'id'].' token='.$newAccessTokenStr);
}

// Put this in session for later
$_SESSION['gc_token'] = LTIX::encrypt_secret($newAccessTokenStr);

if (count($results->getCourses()) == 0) {
    $_SESSION['error'] = 'No Google Classroom Courses found';
    header('Location: '.$CFG->apphome);
} else {
    $_SESSION['success'] = 'Found '.count($results->getCourses()).' Google Classroom courses';
    $_SESSION['gc_courses'] = $results->getCourses();
    header('Location: '.$CFG->apphome.'/lessons/'.$firstmodule.'?nostyle=yes');
}

