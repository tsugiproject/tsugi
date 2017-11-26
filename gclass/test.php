<?php
require_once __DIR__ . '/../vendor/autoload.php';


define('APPLICATION_NAME', 'Classroom API PHP Quickstart');
define('CREDENTIALS_PATH', '/home/ubuntu/.credentials/classroom.googleapis.com-php-quickstart.json');
define('CLIENT_SECRET_PATH', '/home/ubuntu/.credentials/client_secret.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/classroom.googleapis.com-php-quickstart.json
define('SCOPES', implode(' ', array(
  Google_Service_Classroom::CLASSROOM_COURSES_READONLY,
  Google_Service_Classroom::CLASSROOM_ROSTERS_READONLY,
  Google_Service_Classroom::CLASSROOM_PROFILE_EMAILS,
  Google_Service_Classroom::CLASSROOM_PROFILE_PHOTOS)
));

if (php_sapi_name() != 'cli') {
  throw new Exception('This application must be run on the command line.');
}

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient() {
  $client = new Google_Client();
  $client->setApplicationName(APPLICATION_NAME);
  $client->setScopes(SCOPES);
  $client->setAuthConfig(CLIENT_SECRET_PATH);
  $client->setAccessType('offline');

  // Load previously authorized credentials from a file.
  $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
  if (false && file_exists($credentialsPath)) {
    $accessToken = json_decode(file_get_contents($credentialsPath), true);
  } else {
    // Request authorization from the user.
    $authUrl = $client->createAuthUrl();
    printf("Open the following link in your browser:\n%s\n", $authUrl);
    print 'Enter verification code: ';
    $authCode = trim(fgets(STDIN));

    // Exchange authorization code for an access token.
    $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
    echo("AT=".json_encode($accessToken,JSON_PRETTY_PRINT));

    // Store the credentials to disk.
    // if(!file_exists(dirname($credentialsPath))) {
      // mkdir(dirname($credentialsPath), 0700, true);
    // }
    // file_put_contents($credentialsPath, json_encode($accessToken));
    // printf("Credentials saved to %s\n", $credentialsPath);
  }
  $client->setAccessToken($accessToken);

  // Refresh the token if it's expired.
  if ($client->isAccessTokenExpired()) {
    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    // file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
  }
  return $client;
}

/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path) {
  $homeDirectory = getenv('HOME');
  if (empty($homeDirectory)) {
    $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
  }
  return str_replace('~', realpath($homeDirectory), $path);
}

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Classroom($client);

// Print the first 10 courses the user has access to.
$optParams = array(
  'pageSize' => 10
);
$results = $service->courses->listCourses($optParams);

if (count($results->getCourses()) == 0) {
  print "No courses found.\n";
} else {
  print "Courses:\n";
  foreach ($results->getCourses() as $course) {
    printf("%s (%s)\n", $course->getName(), $course->getId());
  }
}

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
