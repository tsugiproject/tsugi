<?php

use \Tsugi\Util\Net;
use \Tsugi\Core\LTIX;
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
  Google_Service_Classroom::CLASSROOM_PROFILE_PHOTOS)
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

    if ( $accessToken ) {
        $client->setAccessToken($accessToken);
    } else {
        // Request authorization from the user.
        $authUrl = $client->createAuthUrl();
        header('Location: '.$authUrl);
        return;
    }

    // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    }

    return $client;
}

if ( !isset($_SESSION['id']) ) {
    die_with_error_log('Error: Must be logged in to use Google Classroom');
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

// Get the API client and construct the service object.
$client = getClient($accessTokenStr);

// Check if we need to update/store the access token
$newAccessTokenStr = json_encode($client->getAccessToken());
if ( $newAccessTokenStr != $accessTokenStr ) {
    $sql = "UPDATE {$CFG->dbprefix}lti_user
        SET gc_token = :TOK WHERE user_id = :UID";
    $PDOX->queryDie($sql,
        array(':UID' => $_SESSION['id'], ':TOK' => $newAccessTokenStr)
    );
    if ( isset($_SESSION['lti']) ) {
        $_SESSION['lti']['gc_token'] = LTIX::encrypt_secret($newAccessTokenStr);
    }
    error_log('Token updated user_id='.$_SESSION['id']);
}

// Lets talk to Google...
$service = new Google_Service_Classroom($client);

// Print the first 10 courses the user has access to.
$optParams = array(
  'pageSize' => 10
);
$results = $service->courses->listCourses($optParams);

if (count($results->getCourses()) == 0) {
    $_SESSION['error'] = 'No Google Classroom Courses found';
    //print "No courses found.\n";
} else {
    $_SESSION['success'] = 'Found '.count($results->getCourses()).' Google Classroom courses';
    $_SESSION['gc_courses'] = $results->getCourses();
/*
    print "Courses:\n";
    foreach ($results->getCourses() as $course) {
        printf("%s (%s)\n", $course->getName(), $course->getId());
    }
*/
}

header('Location: '.$CFG->apphome);
return;

/*
$prof = $service->userProfiles->get('me');
var_dump($prof);
*/

$x = $client->getAccessToken();
var_dump($x);
$access_token = $x['access_token'];
echo("AT=$access_token \n");


// v1/userProfiles/{userId}
$user_info_url = "https://classroom.googleapis.com/v1/userProfiles/me" ."?alt=json&access_token=" .
           $access_token;

echo("UIU=".$user_info_url."\n");
$response = \Tsugi\Util\Net::doGet($user_info_url);
echo($response."\n");
$user = json_decode($response);
var_dump($user);
