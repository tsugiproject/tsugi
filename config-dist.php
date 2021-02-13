<?php
// Configuration file - copy from config-dist.php to config.php
// and then edit.  Since config.php has passwords and other secrets
// never check config.php into a source repository

use \Tsugi\Util\U;

// uncomment for 500 error debugging during early install steps
/*
function __the_end(){
    if(($err=error_get_last()))
        die('<pre>'.print_r($err,true).'</pre>');
}
register_shutdown_function('__the_end');
*/

// If this file is symbolically linked you'll need to manually define the absolute path,
// otherwise this will resolve incorrectly.

$dirroot = realpath(dirname(__FILE__));

// New for 2021 - We may need to do some tweaking before the autoloader wakes up
// So we need to add this to front of config.php
require_once $dirroot."/vendor/tsugi/lib/include/pre_config.php";

$loader = require_once($dirroot."/vendor/autoload.php");

// If we just are using Tsugi but not part of another site
$apphome = false;
// $apphome = "https://www.tsugicloud.org";
// $apphome = "http://localhost:8888/tsugi-org";

// Set the path to the Tsugi folder without a trailing slash
if ( $apphome ) {
    $wwwroot = $apphome . '/tsugi';
} else if ( U::get($_SERVER,'SERVER_PORT') == 8888 ) {
    $wwwroot = 'http://localhost:8888/tsugi'; // Mac XAMP
} else {
    $wwwroot = "http://localhost/tsugi";
}
// Once you are on a real server delete the above if statement
// and set the wwwroot directly.  This must be the actual URL used
// on the Internet for LTI signatures to compute correctly
// $wwwroot = "https://www.tsugicloud.org/tsugi";
// $wwwroot = "https://fb610139.ngrok.io/tsugi";  // To test with ngrok

// We store the configuration in a global object
// Additional documentation on these fields is
// available in that class or in the PHPDoc for that class
unset($CFG);
global $CFG;
$CFG = new \Tsugi\Config\ConfigInfo($dirroot, $wwwroot);
unset($wwwroot);
unset($dirroot);
$CFG->loader = $loader;
if ( $apphome ) $CFG->apphome = $apphome; // Leave unset if not embedded
unset($apphome);

// If we have a web socket server, put its URL here
// Do not add a path here - just the host and port
// Make sure the port is open on your server
// $CFG->websocket_secret = 'changeme';
// $CFG->websocket_url = 'ws://localhost:2021'; // Local dev test
// $CFG->websocket_url = 'wss://socket.tsugicloud.org:443'; // Production

// If you are running a reverse proxy (proxy_wstunnel) set this to the port
// you will forward to in your apache config
// $CFG->websocket_proxyport = 8080;

// Database connection information to configure the PDO connection
// You need to point this at a database with am account and password
// that can create tables.   To make the initial tables go into Admin
// to run the upgrade.php script which auto-creates the tables.
$CFG->pdo       = 'mysql:host=127.0.0.1;dbname=tsugi';
// $CFG->pdo       = 'mysql:host=127.0.0.1;port=8889;dbname=tsugi'; // MAMP
$CFG->dbuser    = 'ltiuser';
$CFG->dbpass    = 'ltipassword';

// Sometimes the PDO constructor call needs additional parameters
// $CFG->pdo_options = array(\PDO::MYSQL_ATTR_SSL_CA => './BaltimoreCyberTrustRoot.crt.pem'))
$CFG->pdo_options  = false;

// These URLs are used in your app store, they are optional but
// strongly recommended - you can borrow from the samples below
// for some wording - but be honest in your pages.  These will
// be necessary when/if you integrate with Google Classroom
// so you might as well make them now :)
// $CFG->privacy_url = 'https://www.tsugicloud.org/services/policies/privacy';
// $CFG->sla_url = 'https://www.tsugicloud.org/services/policies/service-level-agreement';

// Tools to hide in the store for non-admin users.  Each tool sets their status
// in their register.php with a line like:
//     "tool_phase" => "sample",
// If this is false, then all tools are shown.
$CFG->storehide = false; // A regex like - '/dev/sample|test|beta/';

// You can use the CDN copy of the static content - it is the
// default unless you override it.
// $CFG->staticroot = 'https://static.tsugi.org';

// If you check out a copy of the static content locally and do not
// want to use the CDN copy (perhaps you are on a plane or are otherwise
// not connected) use this configuration option instead of the above:
// $CFG->staticroot = 'http://localhost/tsugi-static';  // For normal
// $CFG->staticroot = 'http://localhost:8888/tsugi-static';   // For MAMP

// The slow_query setting indicated when we want PDOX to log a query for
// being too slow.  Set to -1 to log all queries.
$CFG->slow_query = 10.0;   //  Set to zero for no logging at all

// The dbprefix allows you to give all the tables a prefix
// in case your hosting only gives you one database.  This
// can be short like "t_" and can even be an empty string if you
// can make a separate database for each instance of TSUGI.
// This allows you to host multiple instances of TSUGI in a
// single database if your hosting choices are limited.
$CFG->dbprefix  = '';

// This is the PW that you need to access the Administration
// features of this application. It can be the plaintext password
// or a sha256 hash of the admin password.  Please don't use either
// the 'tsugi' or the sha256 of 'tsugi' example values below.
$CFG->adminpw = false;
// $CFG->adminpw = 'tsugi';
// $CFG->adminpw = 'sha256:9c0ccb0d53dd71b896cde69c78cf977acbcb36546c96bedec1619406145b5e9e';

// Theme Tsugi to your institutions colors. If not set, default colors will be used.
/*$CFG->theme = array(
    "primary" => "#0D47A1", //default color for nav background, splash background, buttons, text of tool menu
    "secondary" => "#EEEEEE", // Nav text and nav item border color, background of tool menu
    "text" => "#111111", // Standard copy color
    "text-light" => "#5E5E5E", // A lighter version of the standard text color for elements like "small"
    "font-url" => "https://fonts.googleapis.com/css?family=Muli:400,900", // Optional custom font url for using Google fonts
    "font-family" => "'Muli', sans-serif", // Font family
    "font-size" => "14px", // This is the base font size used for body copy. Headers,etc. are scaled off this value
);*/

// If we are running Embedded Tsugi we need to set the
// "course title" for the course that represents
// the "local" students that log in through Google.
// $CFG->context_title = "Web Applications for Everybody";

// If we are going to use the lessons tool and/or badges, we need to
// create and point to a lessons.json file
// $CFG->lessons = $CFG->dirroot.'/../lessons.json';

// If we are going to use the Topics section, we need to create and
// point to the topics.json file
// $CFG->topics = $CFG->dirroot.'/../topics.json';

// This allows you to include various tool folders.  These are scanned
// for register.php, database.php and index.php files to do automatic
// table creation as well as making lists of tools in various UI places
// such as ContentItem

// For normal tsugi, by default we use the built-in admin tools, and
// install new tools (see /admin/install/) into mod.
$CFG->tool_folders = array("admin", "mod");
$CFG->install_folder = $CFG->dirroot.'/mod';

// For Embedded Tsugi, you probably want to ignore the mod folder
// in /tsugi and instead install new tools into "mod" in the parent folder
if ( isset($CFG->apphome) ) {
    $CFG->tool_folders = array("admin", "../tools", "../mod");
    $CFG->install_folder = $CFG->dirroot.'/../mod';
}

// You can also include tool/module folders that are outside of this folder
// using the following pattern:
// $CFG->tool_folders = array("admin", "mod",
//      "../tsugi-php-standalone", "../tsugi-php-module",
//      "../tsugi-php-samples", "../tsugi-php-exercises");

// Set to true to redirect to the upgrading.php script
// Also copy upgrading-dist.php to upgrading.php and add your message
$CFG->upgrading = false;

// This is how the system will refer to itself.
$CFG->servicename = 'TSUGI';
$CFG->servicedesc = false;

// If the launch experiences an error and there is not return_url in the launch, the user
// is sent to this URL with a detail= parameter
// $CFG->launcherror = "https://www.tsugi.org/launcherror";
// $CFG->launcherror = $CFG->apphome . "/launcherror";

// Define the default language for the site
$CFG->lang='en';

// Information on the owner of this system and whether we
// allow folks to request keys for the service
$CFG->ownername = false;  // 'Charles Severance'
$CFG->owneremail = false; // 'csev@example.com'
$CFG->providekeys = false;  // true
$CFG->autoapprovekeys = false; // A regex like - '/.+@gmail\\.com/'

// Go to https://console.developers.google.com/apis/credentials
// create a new OAuth 2.0 credential for a web application,
// get the key and secret, and put them here:
$CFG->google_client_id = false; // '96041-nljpjj8jlv4.apps.googleusercontent.com';
$CFG->google_client_secret = false; // '6Q7w_x4ESrl29a';

// This is a legacy backwards compatibility.   In the round-trip to Google it used to
// come back login.php after login was successful - If this is true, we come back
// to login (without the php) - se thtis to false to restore the old pattern
$CFG->google_login_new = true;

$CFG->google_translate = false;

// Defaults to $CFG->apphome if defined and $CFG->wwwroot if that is not defined or false
$CFG->logout_return_url = false;
// Defaults to $CFG->apphome if defined and $CFG->wwwroot if that is not defined or false
$CFG->login_return_url = false;

// You can specify a default menu for Tsugi to use across the site if there is no
// defined menu given
// $buildmenu = $CFG->dirroot."/../buildmenu.php";
// if ( file_exists($buildmenu) ) {
    // require_once $buildmenu;
    // $CFG->defaultmenu = buildMenu();
// }

// If these are not set, the auto expiration scripts in admin/expire
// do nothing.  You can still manually expire data in the admin UI without
// these values. There are software enforced mimimums for these values so you don't
// inadvertently wipe out your whole database using either the auto expire
// scripts or the admin expiry UI.
// $CFG->expire_pii_days = 150;  // Three months  (minimum 20)
// $CFG->expire_user_days = 400;  // One year (minimum 40)
// $CFG->expire_context_days = 600; // 1.5 Years (minimum 60)
// $CFG->expire_tenant_days = 800; // Two years (minimum 80)

// Alpha: Google Classroom support
// First, Go to https://console.developers.google.com/apis/credentials
// And add access to "Google Classroom API" to your google_client_id (above)

// Set the secret to a long random string - this is used for internal
// url Tsugi signing - not for Google interactions.  Don't change it
// once you set it.
// $CFG->google_classroom_secret = 'oLKJHi....jkhgJGHJGH';

// This should be an absolute URL that will be used to generate previews
// in Google Classroom
// $CFG->logo_url = 'https://www.wa4e.com/logo.png';

// Indicate whether the PHP on this server wants to verify SSL or not
// It seems that PHP > 7 and curl > 7.53 or above want this to be true
// The legacy value for this was false
$CFG->verifypeer = false;

// Whether or not to unify accounts between global site-wide login
// and LTI launches
$CFG->unify = true;

// Go to https://console.developers.google.com/apis/credentials
// Create and configure an API key and enter it here
$CFG->google_map_api_key = false; // 'Ve8eH490843cIA9IGl8';

// Badge generation settings - once you set these values to something
// other than false and start issuing badges - don't change these or
// existing badge images that have been downloaded from the system
// will be invalidated.
$CFG->badge_encrypt_password = false; // "somethinglongwithhex387438758974987";
$CFG->badge_assert_salt = false; // "mediumlengthhexstring";

// This folder contains the badge images - This example
// is for Embedded Tsugi and the badge images are in the
// parent folder.
// $CFG->badge_path = $CFG->dirroot . '/../bimages';
// $CFG->badge_url = $CFG->apphome . '/bimages';

// Sets the default locale for users without a locale
// in the launch or in the browser
// If you want to change this and test using the test harness, make sure
// to clear out the '12345' data in Admin in order to make sure the lti_user
// table does not override the new value for this.
// $CFG->fallbacklocale = 'de_DE';

// When this is true it enables a Developer test harness that can launch
// tools using LTI.  It allows quick testing without setting up an LMS
// course, etc.  If this is on, developer-oriented menus and adminstrator
// menus will feature prominently in the UI.  In production, this should be
// set to false so these non-end-user features are less prominent in the
// navigation.
$CFG->DEVELOPER = true;

// Is this is true, Tsugi will do a translation log into the table
// tsugi_string while the application is being executed.  This allows
// you to see all of messages that go through the translation methods
// __() and _m() - It can help make translations more complete.
// $CFG->checktranslation = true;

// If you set $CFG->dataroot to a writeable folder, Tsugi will store blobs on disk
// instead of the database using the following folder / file pattern
//     tsugi_blobs/1f/84/00001754/1f84ab151...56a
//     tsugi_blobs/67/fb/00001754/67fba23c8...123b
// The folders are based on the sha256 of file contents

// A normal setup - make sure the folder is writable by the web server,
// backed up and not in the document root hierarchy.
//    mkdir /backedup/tsugi_blobs
// You can turn this on and off (false or unset means store in the database)
// $CFG->dataroot = '/backedup/tsugi_blobs';

// An array of keys that go into blob_blob regardless of the setting of
// dataroot for easy removal, you can override this.  Default if not
// set is that the 12345 key goes into blob_blob regardless. Change this
// to false to store 12345 blobs in the dataroot.
// $CFG->testblobs = array('12345');
// $CFG->testblobs = false;

// It is important to note that changing dataroot does not migrate the data.
// Tsugi stores the blob path in the blob_file table.  Data uploaded to a blob
// will stay there and data uploaded to a path will stay there regardless of
// this setting.  There will are separate migration processes to move data
// between stores - see tsugi/admin/blob for more detail.

// This turns on auto-migration as blobs are accessed
// $CFG->migrateblobs = true;

// You can set dataroot to a temporary folder for dev but never for production
/*
if ( $CFG->DEVELOPER && ! isset($CFG->dataroot) ) {
    $tmp = sys_get_temp_dir();
    if (strlen($tmp) > 1 && substr($tmp, -1) == '/') $tmp = substr($tmp,0,-1);
    $CFG->dataroot = $tmp . '/tsugi_blobs';
}
*/

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

// Universal Analytics
$CFG->universal_analytics = false; // "UA-57880800-1";

// Effectively an "airplane mode" for the application.
// Setting this to true makes it so that when you are completely
// disconnected, various tools will not access network resources
// like Google's map library and hang.  Also the Google login will
// be faked.  Don't run this in production.

$CFG->OFFLINE = false;

// IMS says that resource_link_id, lti_message_type, and lti_version are required fields,
// and IMS certification fails if we allow a valid launch when either
// of these are not sent (even though in many instances, an application
// can happily do what it needs to do without them).
// Set these to true to make launches fail when either/both are not sent.
$CFG->require_conformance_parameters = true;

// Since IMS certification is capricious at times and has bugs or bad assumptions,
// set this when running certification
$CFG->certification = false;

// A consumer may pass both the LTI 1 lis_outcome_service_url
// and the LTI 2 custom_result_url; in this case we have to decide which
// to use for the gradeSend service.  The LTI 1 method is more established...
$CFG->prefer_lti1_for_grade_send = true;

// On a Mac localhost, if you have git installed, you should be able to
// use the auto-install with no further configuration.

// On Windows, to run the automatic install of modules:
// (1) Make sure Git is installed (https://git-scm.com/download/win
// Maybe also install a GIT GUI https://git-scm.com/downloads/guis
// (2) Open "cmd" and type "git --version"
// this should give you the current version of git. If this fails
// then git is not setup in your path
// (Control Panel > System and Security > System > Advanced System Settings > Environment Variables)
// (3) Then here in "config.php":
// $CFG->git_command = 'git'

// In order to run git from the a PHP script, we may need a setuid version
// of git - example commands if you are not root:
//
//    cd /home/csev
//    cp /usr/bin/git .
//    chmod a+s git
//
// If you are root, your web area and git must belong to the user that owns
// the web process.  You can check this using:
//
// apache2ctl -S
//  ..
//  User: name="www-data" id=33
//  Group: name="www-data" id=33
//
// cd /var/www/html
// chown -R 33:33 site-folder
// chown 33:33 /home/csev/git
//
// This of course is something to consider carefully.
// $CFG->git_command = '/home/csev/git';

// By default tools and tusgi are auto-upgraded to the master branch,
// but you can override this on a repo by repo basis in the following
// array - if you remove an entry from this array the next auto-update
// will switch back to master.
$CFG->branch_override = array(
    // "https://github.com/tsugiproject/tsugi.git" => "beta",
);

// Should we record launch activity - multi-bucket lossy historgram
$CFG->launchactivity = true;

// how many launches between event cleanups (probabilistic)
$CFG->eventcheck = 200;        // Set to false to suspend event recording
$CFG->eventtime = 7*24*60*60;  // Length in seconds of the event buffer

// Maximum events to push in a batch
$CFG->eventpushcount = 50;     // Set to zero to suspend event push
$CFG->eventpushtime = 2;       // Maximum length in seconds to push events

// Store sessions in memcache - this seems like the fastest, best, and simplest
// way when running on AWS.
// http://php.net/manual/en/memcached.sessions.php

// $CFG->memcache = 'tcp://memcache-tsugi.4984vw.cfg.use2.cache.amazonaws.com:11211';
if ( isset($CFG->memcache) && strlen($CFG->memcache) > 0 ) {
    ini_set('session.save_handler', 'memcache');
    ini_set('session.save_path', $CFG->memcache);
}

// Note no "tcp://" for the memcached version of the url
// $CFG->memcached = 'memcache-tsugi.4984vw.cfg.use2.cache.amazonaws.com:11211';
if ( isset($CFG->memcached) && strlen($CFG->memcached) > 0 ) {
    ini_set('session.save_handler', 'memcached');
    ini_set('session.save_path', $CFG->memcached);
    // https://github.com/php-memcached-dev/php-memcached/issues/269
    ini_set('memcached.sess_locking', '0');
    ini_set('memcached.serializer', 'php');
    ini_set('session.serialize_handler', 'php_serialize');
}

// Store sessions in a database -  Keep this false until the DB upgrade
// has run once or you won't be able to get into the admin. The
// connection used should should be a different database or at
// least a different connection since the Symfony PdoSessionHandler
// messes with how the connection handles transactions for its own purposes.
// $CFG->sessions_in_db = false;
if ( isset($CFG->sessions_in_db) && $CFG->sessions_in_db ) {
    $session_save_pdo = new PDO($CFG->pdo, $CFG->dbuser, $CFG->dbpass);
    $session_save_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    session_set_save_handler(
        new \Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler(
            $session_save_pdo,
            array('db_table' => $CFG->dbprefix . "sessions")
        )
    );
}

if ( isset($CFG->apphome) && $CFG->apphome ) {
    $tsugi_settings = $CFG->dirroot."/../tsugi_settings.php";
    if ( file_exists($tsugi_settings) ) {
        require_once $tsugi_settings;
    }
}

// The vendor include and root - generally leave these alone
// unless you have a very custom checkout
$CFG->vendorroot = $CFG->wwwroot."/vendor/tsugi/lib/util";
$CFG->vendorinclude = $CFG->dirroot."/vendor/tsugi/lib/include";
$CFG->vendorstatic = $CFG->wwwroot."/vendor/tsugi/lib/static";

$CFG->lumen_storage = $CFG->dirroot."/storage/";

// Leave these here
require_once $CFG->vendorinclude."/setup.php";
require_once $CFG->vendorinclude."/lms_lib.php";
// No trailing tag to avoid inadvertent white space
