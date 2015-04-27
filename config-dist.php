<?php

// Configuration file - copy from config-dist.php to config.php
// and then edit.  Since config.php has passwords and other secrets
// never check config.php into a source repository

// This is the URL where the software is hosted
// Do not add a trailing slash to this string
$wwwroot = 'http://localhost/tsugi';  /// For normal
// $wwwroot = 'http://localhost:8888/tsugi';   // For MAMP

$dirroot = realpath(dirname(__FILE__));
require_once($dirroot."/lib/vendor/Tsugi/Config/ConfigInfo.php");

// We store the configuration in a global object
// Additional documentation on these fields is 
// available in that class or in the PHPDoc for that class
unset($CFG);
global $CFG;
$CFG = new \Tsugi\Config\ConfigInfo($dirroot, $wwwroot);
unset($wwwroot);
unset($dirroot);

// Set to true to redirect to the upgrading.php script
// Also copy upgrading-dist.php to upgrading.php and add your message
$CFG->upgrading = false;

// This is how the system will refer to itself.
$CFG->servicename = 'TSUGI';
$CFG->servicedesc = false;

// Information on the owner of this system
$CFG->ownername = false;  // 'Charles Severance'
$CFG->owneremail = false; // 'csev@example.com'
$CFG->providekeys = false;  // true

// Set these to your API key for your Google Sign on
// https://console.developers.google.com/
$CFG->google_client_id = false; // '96041-nljpjj8jlv4.apps.googleusercontent.com';
$CFG->google_client_secret = false; // '6Q7w_x4ESrl29a';

// Database connection information to configure the PDO connection
// You need to point this at a database with am account and password
// that can create tables.   To make the initial tables go into Admin
// to run the upgrade.php script which auto-creates the tables.
$CFG->pdo       = 'mysql:host=127.0.0.1;dbname=tsugi';
// $CFG->pdo       = 'mysql:host=127.0.0.1;port=8889;dbname=tsugi'; // MAMP
$CFG->dbuser    = 'ltiuser';
$CFG->dbpass    = 'ltipassword';

// The dbprefix allows you to give all the tables a prefix
// in case your hosting only gives you one database.  This
// can be short like "t_" and can even be an empty string if you
// can make a separate database for each instance of TSUGI.
// This allows you to host multiple instances of TSUGI in a
// single database if your hosting choices are limited.
$CFG->dbprefix  = '';

// This is the PW that you need to access the Administration
// features of this application.
$CFG->adminpw = 'warning:please-change-adminpw-89b543!';

// From LTI 2.0 spec: A globally unique identifier for the service provider. 
// As a best practice, this value should match an Internet domain name 
// assigned by ICANN, but any globally unique identifier is acceptable.
$CFG->product_instance_guid = 'lti2.example.com';

// When this is true it enables a Developer test harness that can launch
// tools using LTI.  It allows quick testing without setting up an LMS
// course, etc.
$CFG->DEVELOPER = true;

// This allows you to make your own tool folders.  These are scanned
// for database.php and index.php files to do automatic table creation
// as well as making lists of tools in various UI places.
$CFG->tool_folders = array("core", "mod", "samples", "exercises");

// These values configure the cookie used to record the overall
// login in a long-lived encrypted cookie.   Look at the library
// code createSecureCookie() for more detail on how these operate.
$CFG->cookiesecret = 'warning:please-change-cookie-secret-a289b543';
$CFG->cookiename = 'TSUGIAUTO';
$CFG->cookiepad = '390b246ea9';

// Where the bulk mail comes from - should be a real address with a wildcard box you check
$CFG->maildomain = false; // 'mail.example.com';
$CFG->mailsecret = 'warning:please-change-mailsecret-92ds29';
$CFG->maileol = "\n";  // Depends on your mailer - may need to be \r\n

// Set the nonce clearing factor and expiry time
$CFG->noncecheck = 100;
$CFG->noncetime = 1800;

// This is used to make sure that our constructed session ids
// based on resource_link_id, oauth_consumer_key, etc are not
// predictable or guessable.   Just make this a long random string.
// See LTIX::getCompositeKey() for detail on how this operates.
$CFG->sessionsalt = "warning:please-change-sessionsalt-89b543";

// Timezone
$CFG->timezone = 'Pacific/Honolulu'; // Nice for due dates

// Old analytics
$CFG->analytics_key = false;  // "UA-423997-16";
$CFG->analytics_name = false; // "dr-chuck.com";

// Universal Analytics
$CFG->universal_analytics = false; // "UA-57880800-1";

// Effectively an "airplane mode" for the appliction.
// Setting this to true makes it so that when you are completely 
// disconnected, various tools will not access network resources 
// like Google's map library and hang.  Also the Google login will 
// be faked.  Don't run this in production.

$CFG->OFFLINE = false;

// Leave these here
require_once $CFG->dirroot."/setup.php";
require_once $CFG->dirroot."/lib/lms_lib.php";

// No trailing tag to avoid inadvertent white space
