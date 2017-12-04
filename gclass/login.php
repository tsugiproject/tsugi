<?php

use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\Lessons;
use \Tsugi\Crypt\SecureCookie;
use \Tsugi\Google\GoogleClassroom;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once __DIR__ . '/../config.php';

require_once "util.php";

$PDOX = LTIX::getConnection();

session_start();

if ( ! sanity_check() ) return;

// Load the Lesson
$l = new Lessons($CFG->lessons);
$firstmodule = false;
if (isset($l->lessons->modules[0]->anchor) ) {
    $firstmodule = $l->lessons->modules[0]->anchor;
}

// Try access token from session when LTIX adds it.
$accessTokenStr = GoogleClassroom::retrieve_instructor_token();

// Get the API client and construct the service object.
// This will fail if our token is revoked or otherwise bad
try {
    $client = GoogleClassroom::getClient($accessTokenStr);
    if ( ! $client ) return;
    $service = new Google_Service_Classroom($client);

    // Print the first 100 courses the user has access to.
    $optParams = array(
        'pageSize' => 100
    );

    $results = $service->courses->listCourses($optParams);
    $newAccessTokenStr = json_encode($client->getAccessToken(), true);

    // Put this in session for later
    $_SESSION['gc_token'] = LTIX::encrypt_secret($newAccessTokenStr);
} catch(Exception $e) {
    // Revoked token..
    GoogleClassroom::destroy_instructor_token();
    $accessTokenStr = false;
    // Should redirect...
    $client = GoogleClassroom::getClient($accessTokenStr);
    return;
}


if (count($results->getCourses()) == 0) {
    $_SESSION['error'] = 'No Google Classroom Courses found';
    header('Location: '.$CFG->apphome);
} else {
    $_SESSION['success'] = 'Found '.count($results->getCourses()).' Google Classroom courses. '.
        'Use the icon by each link to install links / assignments into your Google Classroom.';
    $_SESSION['gc_courses'] = $results->getCourses();
    header('Location: '.$CFG->apphome.'/lessons/'.$firstmodule.'?nostyle=yes');
}

