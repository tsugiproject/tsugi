<?php

namespace Tsugi\Config;

/**
 * A class that contains the configuration fields and defaults.
 *
 * The normal way you configure Tsugi is copy the file
 * config-dist.php to config.php and then edit the fields.
 * you generally leave the default values in this file unchanged
 * and overide the fields in your config.php.
 */

#[\AllowDynamicProperties]
class ConfigInfo {

    /**
     * Extensions
     */
    public $extensions;

    /**
     * The URL where tsugi is located
     *
     * Do not add a trailing slash to this string
     * If you get this value wrong, the first problem will
     * be that CSS files will not load
     *
     * If you are using MAMP, you should set this to something
     * like:
     *
     *     $wwwroot = 'http://localhost:8888/tsugi';
     */
    public $wwwroot = 'http://localhost/tsugi';

    /**
     * The Application URL when Tsugi is *part* of an application
     *
     * Do not add a trailing slash to this string
     *
     * An example configuration might be:
     *
     *     $wwwroot = 'http://localhost:8888/wa4e/tsugi';
     *     $apphome = 'http://localhost:8888/wa4e';
     *
     * This is not required, its default for this is $wwwroot.
     */
    public $apphome = null;

    /**
     * This is how the system will refer to itself.
     */
    public $servicename = 'TSUGI (dev)';

    /**
     * This is how the system will describe itself.
     *
     * This should be a sentance and end with a period.  It
     * can include HTML tags - so be careful.
     */
    public $servicedesc = false;

    /**
     * Information on the owner of this system
     *
     * The $ownername and $owneremail can be generic values like
     * "Support Team at Example Com" and "support@example.com"
     */
    public $ownername = false;
    public $owneremail = false;

    /**
     * Whether or not we accept key requests on this system
     *
     * Tsugi has a workflow to allow users to come directly to
     * the Tsugi web site and request an LTI 1.x or 2.x key.
     * If this value is 'false', this feature is not available.
     * To enable this feature, you must configure both the
     * $ownername and $owneremail parameters so the system
     * knows who to send the mail for key requests to.
     */
    public $providekeys = false;
    public $autoapprovekeys = false; // A regex like - '/.+@gmail\\.com/'

    /**
     * Database connection information to configure the PDO connection
     *
     * You need to point this at a database with am account and password
     * that can create tables.   To make the initial tables go into Admin
     * to run the upgrade.php script which auto-creates the tables.
     *
     * As an example, to run this on MAMP with its database server on
     * port 8889 you might use:
     *
     *     $pdo = 'mysql:host=127.0.0.1;port=8889;dbname=tsugi';
     *
     * You will need to create a database, user, and password
     * like this:
     *
     *     CREATE DATABASE tsugi DEFAULT CHARACTER SET utf8;
     *     GRANT ALL ON tsugi.* TO 'ltiuser'@'localhost' IDENTIFIED BY 'ltipassword';
     *     GRANT ALL ON tsugi.* TO 'ltiuser'@'127.0.0.1' IDENTIFIED BY 'ltipassword';
     *
     * Of course clever people would choose wiser passwords.
     */
    public $pdo       = 'mysql:host=127.0.0.1;dbname=tsugi';

    /**
     * Database user for the PDO connection
     */
    public $dbuser    = 'ltiuser';

    /**
     * Database password for the PDO connection
     */
    public $dbpass    = 'ltipassword';

    /**
     * Additional parameter for the PDO constructor with an array of key-value options
     *
     * $CFG->pdo_options = array(\PDO::MYSQL_ATTR_SSL_CA => './BaltimoreCyberTrustRoot.crt.pem'))
     *
     * See also: https://www.php.net/manual/en/pdo.construct.php
     */
    public $pdo_options  = false;

    /**
     * A prefix to prepend to all table names.
     *
     * The dbprefix allows you to give all the tables a prefix
     * in case your hosting only gives you one database.  This
     * can be short like "t_" and can even be an empty string if you
     * can make a separate database for each instance of TSUGI.
     * This allows you to host multiple instances of TSUGI in a
     * single database if your hosting choices are limited.  For
     * example, you might only have one MySql database and host
     * Moodle tables with an "mdl_" prefix and Tsugi tables with
     * a "t_" prefix.
     */
    public $dbprefix  = 't_';

    /**
     * The slow_query setting indicated when we want PDOX to log a query for being too slow.
     */
    public $slow_query;

    /**
     * Support memcache for session caching
     *
     * Memcache is php-only and so is likely to require less overall dependencies.
     *
     * http://php.net/manual/en/memcache.sessions.php
     *
     * Installed on Ubuntu using
     *
     * apt-get install -y php${TSUGI_PHP_VERSION}-memcache
     *
     * You should only select one of memcache and memcached
     *
     * $CFG->memcache = 'tcp://memcache-tsugi.4984vw.cfg.use2.cache.amazonaws.com:11211';
     *
     * In addition to setting this variable, your config.php must include the code
     * to configure the PHP session save handler as shown in config-dist.php
     *
     */
    public $memcache;

    /**
     * Support memcached for session caching
     *
     * Memcached is a combination of PHP and C and so may require extra dependencies.
     *
     * http://php.net/manual/en/memcached.sessions.php
     *
     * Installed on Ubuntu using
     *
     * apt-get install -y php${TSUGI_PHP_VERSION}-memcached
     *
     * You should only select one of memcache and memcached
     *
     * $CFG->memcached = 'memcache-tsugi.4984vw.cfg.use2.cache.amazonaws.com:11211';
     *
     * Note no "tcp://" for the memcached version of the url
     *
     * In addition to setting this variable, your config.php must include the code
     * to configure the PHP session save handler as shown in config-dist.php
     */
    public $memcached;

    /**
     * Adding in support for using Redis for session caching.
     *
     * $CFG->redis = 'tcp://localhost:6379?auth=addYourRedisPasswordHere';
     */
    public $redis;

    /**
     * This is the PW that you need to access the Administration features of this application.
     *
     * You should change this from the default.
     */
    public $adminpw = 'warning:please-change-adminpw-89b543!';

    /**
     * Prefix for tool registration codes.
     *
     * For LTI 2.0 registrations: This is a prefix applied to the tool registration
     * codes for LTI 2.0. You may want to keep this default if you want LMS's to use
     * the local equivalent for the tools hosted herein.
     */
    public $resource_type_prefix = 'tsugi_';

    /**
     * Set to true to redirect all requests to the upgrading.php script
     *
     * This allows you to turn off access to the application with a single
     * variable.  Also copy upgrading-dist.php to upgrading.php and
     * add your message.
     */
    public $upgrading = false;

    /**
     * Default time zone - see http://www.php.net/....
     */
    public $timezone = 'America/New_York';

    /**
     * Indicate whether the PHP on this server wants to verify SSL or not
     */
    public $verifypeer;

    /**
     * Enable developer features of the application.
     *
     * When this is true it enables a Developer test harness that can launch
     * tools using LTI.  It allows quick testing without setting up an LMS
     * course, etc.
     *
     * If you turn this on in a production environment, you should change
     * the developer PW (in lti_key table) for the 12345 key to something
     * other than 'secret'.
     */
    public $DEVELOPER = true;

    /**
     * Set the URL prefix for the static content folder (i.e. on a CDN)
     *
     * This allows you to serve the materials in the static folder using
     * a content distribution network - it is normal and typical for this
     * to be the same as wwwroot
     *
     * This field is generally left as default.
     */

    public $staticroot;

    /**
     * Top level file system folder for the application
     *
     * This should not be changed.  It allows included PHP files to reference
     * library files with an absolute path.
     *
     * This is usually set automatically in config.php to be the folder
     * containing config.php.
     *
     * Unless you are using a CDN, this should be the same as wwwroot.
     */
    public $dirroot;

    /**
     * Configure  where TSUGI will store uploaded files.
     *
     * It is ideal for this not to be in the same folder as the rest of
     * the application, but you may have no other choice to leave this false
     * (default). Make sure that this folder is readable and writable by
     * the web server.
     *
     * If you set $dataroot to a writeable folder, Tsugi will store blobs on disk
     * instead of the database using the following folder / file pattern
     *     tsugi_blobs/1f/84/00001754/1f84ab151...56a
     *     tsugi_blobs/67/fb/00001754/67fba23c8...123b
     * The folders are based on the sha256 of file contents
     *
     * In a normal setup - make sure the folder is writable by the web server,
     * backed up and not in the document root hierarchy.
     *
     * It is important to note that changing dataroot does not migrate the data.
     * Tsugi stores the blob path in the blob_file table.  Data uploaded to a blob
     * will stay there and data uploaded to a path will stay there regardless of
     * this setting.  There will are separate migration processes to move blob data
     * from the database to dataroot.  See tsugi/admin/blob for more detail.
     *
     * You can set dataroot to a temporary folder for dev but never for production
     */
    public $dataroot = false; // '/backedup/tsugi_blobs';

    /**
     * This turns on auto-migration of blobs from the database to dataroot, as
     * blobs are accessed
     */
    public $migrateblobs = false;

    /**
     * Configure lti keys that go into blob_blob regardless of $dataroot
     *
     * The use case for this can be for testing.  Many servers set up the
     * 12345/secret ask key and secret to allow testing.   The Admin tool
     * has the ability to clear out the 12345 data regularly.   We want to
     * wipe out any blob data that is stored whlist testing with 12345.
     *
     * Blobs in the blob_blob table get removed during the 12345 cleanup.
     *
     * This can also be used for testing to route a particular key into
     * blob_blob.
     */
    public $testblobs = false; // array('12345');

    /**
     * Configure the long-term login cookie encryption values.
     *
     * These values configure the cookie used to record the overall
     * login in a long-lived encrypted cookie.   Look at the library
     * code SecureCookie::create() for more detail on how these operate.
     */
    public $cookiesecret = 'warning:please-change-cookie-secret-a289b543';
    public $cookiename = 'TSUGIAUTO';
    public $cookiepad = '390b246ea9';

    /**
     * Configure mail sending.
     *
     * If you leave maildomain false, mail will not be sent.  If you set
     * maildomain to a real domain, best practice is for it to be a
     * real address with a wildcard box that you check periodially.
     */
    public $maildomain = false;  // Don't send mail
    // public $maildomain = 'mail.example.com';
    public $mailsecret = 'warning:please-change-mailsecret-92ds29';
    public $maileol = "\n";  // Depends on your mailer - may need to be \r\n

    /**
     * Configure the security for constructing LTI Launch session IDs
     *
     * Since we want to reuse sessions across multiple LTI launches
     * we construct our session ids from launch data.  See LTIX::getCompositeKey()
     * for detail on how this operates.  But we do not want folks
     * to be able to guess a session id solely on the launch data so
     * we add a bit of secret info to each pre-hash string before we hash
     * it.
     *
     * Just make this a long random string - the longer and randomer -
     * the better.
     */
    public $sessionsalt = "warning:please-change-sessionsalt-89b543";

    /**
     * Store sessions in database instead of default PHP session storage
     *
     * When enabled, Tsugi will use database-backed session storage
     * instead of the default PHP session handler.
     *
     * $CFG->sessions_in_db = false;
     */
    public $sessions_in_db = false;

    /*
     * The default language for this system
     */
    public $lang = 'en';

    /**
     * Fallback locale when Accept-Language header is not available
     *
     * If set, this locale will be used when the browser doesn't provide
     * an Accept-Language header or when locale detection fails.
     *
     * $CFG->fallbacklocale = 'de_DE';
     */
    public $fallbacklocale = false;

    /**
     * Enable translation checking/recording
     *
     * When enabled, Tsugi will record all translatable strings to the
     * database for translation management.
     *
     * $CFG->checktranslation = true;
     */
    public $checktranslation = false;

    /**
     * Enable Google Translate on this site
     *
     * You must go to Google Translate and set up your web site
     *
     * http://translate.google.com/manager/website/
     */
    public $google_translate = false;

    /**
     * Effectively an "airplane mode" for the appliction.
     *
     * Setting this to true makes it so that when you are completely
     * disconnected, various tools * will not access network resources
     * like Google's map library and hang.  Also the Google login will
     * be faked.  Don't run this in production.
     */
    public $OFFLINE = false;

    /**
     * Indicate which directories to scan for tools.
     *
     *  This allows you to include various tool folders.  These are scanned
     *  for register.php, database.php and index.php files to do automatic
     *  table creation as well as making lists of tools in various UI places
     *  such as ContentItem and Deep Linking.
     */
    public $tool_folders = array("admin", "mod");

    /**
     * Indicate which folder to install new modules into.
     *
     * By default we use the built-in admin tools, and
     * install new tools (see /admin/install/) into mod.  If this is left to
     * false, it will suppress automatic tool installation in Tsugi admin.
     *
     * $CFG->install_folder = $CFG->dirroot.'/mod';
     */
    public $install_folder = false;

    /**
     * Configure git for auto-installation
     *
     * On Windows, to run the automatic install of modules:
     *
     * (1) Make sure Git is installed (https://git-scm.com/download/win
     * Maybe also install a GIT GUI https://git-scm.com/downloads/guis
     *
     * (2) Open "cmd" and type "git --version"
     * this should give you the current version of git. If this fails
     * then git is not setup in your path
     * (Control Panel > System and Security > System > Advanced System Settings > Environment Variables)
     *
     * (3) Then in "config.php":
     * $CFG->git_command = 'git'
     *
     * In order to run git from the a PHP script, we may need a setuid version
     * of git - example commands if you are not root:
     *
     *    cd /home/csev
     *    cp /usr/bin/git .
     *    chmod a+s git
     *
     * If you are root, your web area and git must belong to the user that owns
     * the web process.  You can check this using:
     *
     * apache2ctl -S
     *  ..
     *  User: name="www-data" id=33
     *  Group: name="www-data" id=33
     *
     * cd /var/www/html
     * chown -R 33:33 site-folder
     * chown 33:33 /home/csev/git
     *
     * This of course is something to consider carefully.
     * $CFG->git_command = '/home/csev/git';
     */
    public $git_command = false;

    /**
     * If defined, this is displayed as the privacy URL when Tsugi
     * is used as an "App Store".  If you want to use Google login,
     * you need these URLs available on the OAuth application.
     *
     * You can see sample wording at:
     *
     * https://www.py4e.com/service.php
     */
    public $privacy_url = false;

    /**
     * If defined, this is displayed as the SLA URL when Tsugi
     * is used as an "App Store".  If you want to use Google login,
     * you need these URLs available on the OAuth application.
     *
     * You can see sample wording at:
     *
     * https://www.py4e.com/service.php
     */
    public $sla_url = false;

    /**
     *
     * Tools to hide in the store for non-admin users.  Each tool sets their status
     * in their register.php with a line like:
     *     "tool_phase" => "sample",
     * If this is null, then all tools are shown.
     */
    public $storehide = null; // A regex like - '/dev|sample|test|beta/';

    /**
     * Set the session timeout - in seconds
     */
    public $sessionlifetime = 3000;

    /**
     * Set the nonce clearing factor
     *
     * If this is zero, we do not store nonces in the database.  If this
     * is non-zero we take a modulo of the current time in seconds and
     * if the remainder is zero, we remove old entries from the nonce
     * table based on the $noncetime setting.  Setting this to 1 causes
     * the process to run every time a launch happens - which is nice for
     * testing the nonce clearing process.
     */
    public $noncecheck = 100;

    /**
     * Set the expiration time for nonces in seconds
     *
     * This is enforced probabilistically depending on the value for
     * $noncecheck - We can be assured that when the cleanup run executes
     * we will purge all nonces that are older than the expiration time.
     * But unitl the cleanup runs we might have older nonces in the tables
     * for a while.  It is not harmful to check against more nonces - we just
     * don't want the table to grow forever.
     */
    public $noncetime = 1800;

    /**
     * An array of css styles to apply to css variables used to skin Tsugi.
     *
     * Example:
     *
     *     $CFG->theme = array(
     *        "primary" => "#336791", //default color for nav background, splash background, buttons, text of tool menu
     *        "secondary" => "#EEEEEE", // Nav text and nav item border color, background of tool menu
     *        "text" => "#111111", // Standard copy color
     *        "text-light" => "#5E5E5E", // A lighter version of the standard text color for elements like "small"
     *        "font-url" => "https://fonts.googleapis.com/css2?family=Open+Sans", // Optional custom font url for using Google fonts
     *        "font-family" => "'Open Sans', Corbel, Avenir, 'Lucida Grande', 'Lucida Sans', sans-serif", // Font family
     *        "font-size" => "14px", // This is the base font size used for body copy. Headers,etc. are scaled off this value
     *    );
     */
    public $theme;

    /**
     * The color to be used as the theme base
     * This could optionally be overridden by a launch parameter
     */
    public $theme_base;

    /**
     * A boolean indicator as to whether dark mode should be used
     * This could optionally be overridden by a launch parameter
     */
    public $theme_dark_mode;

    /**
     * The path to an LTI-launch error handling page
     *
     * When the LTI runtime (LTIX.php) is lost and confused because
     * something is broken with the launch, it pops up an error modal
     * with a "Continue" button.  When you press the button, it goes
     * to "https://www.tsugi.org/launcherror" and passed a "detail"
     * parameter.   This allows www.tsugi.org to have much longer error
     * explanations.  But if you want to provide your own error handling
     * endpoint you can send these errors to your own code.
     *
     * $CFG->launcherror = $CFG->apphome . "/launcherror";
     */
    public $launcherror;

    /**
     * A default menu for pages
     *
     * Normally, when you navigate to a site like www.py4e.com it defines a default
     * top navigation menu and stores it in the session.  The $OUTPUT code adds this
     * menu when it generates pages.  But sometimes the user navigates to a subfolder
     * link in a Tsugi/Koseu site and sees the default menu - which is not too pretty.
     * This value allows you to define a menu (must be a MenuSet object) to be
     * used when Tsugi's $OUTPUT code has no other menu.
     *
     * $set = new \Tsugi\UI\MenuSet();
     * $set->addLeft('Lessons', $CFG->apphome.'/lessons');
     *  ...
     * $CFG->defaultmenu = $set;
     */
    public $defaultmenu;

    /*
     * If we are running Embedded Tsugi we need to set the
     * "course title" for the course that represents
     * the "local" students that log in through Google.
     *
     * $CFG->context_title = "Web Applications for Everybody";
     */
    public $context_title = false;

    /**
     * Path (on disk) to the gift quiz content.
     *
     * You can maintain a set of gift quizes
     * as text files in github if you like.   These can be part of your main
     * Koseu repository or a separate checked-out private repository.
     * When you are configuring a quiz, quiz content can be loaded from these files.
     * There is a '.lock' file in the folder if you want to hide the quiz content
     * from those using the test feature in the store.
     *
     * $CFG->giftquizzes = $CFG->dirroot.'/../py4e-private/quiz';
     */
    public $giftquizzes;

    /**
     * The url of the installed instance of the tdiscus tool.
     *
     * When you set this, and you add discussions to lessons.json
     * LTI links to your dicussions are added to exported common
     * cartridges.  It also enables the "/discussions" Koseu tool
     * as well.
     *
     * $CFG->tdiscus = $CFG->apphome . '/mod/tdiscus/';
     */
    public $tdiscus;

    /**
     * The url of the installed YouTube tool
     *
     * If you want lessons to launch YouTube URLs using the tracking
     * tool, put its path here.  If you have not installed this tool,
     * leave this value blank.
     *
     * $CFG->youtube_url = $CFG->apphome . '/mod/youtube/';
     *
     */
    public $youtube_url = false;

    /*
     * If we are going to use the lessons tool and/or badges, we need to
     * create and point to a lessons.json file
     *
     * $CFG->lessons = $CFG->dirroot.'/../lessons.json';
     */
    public $lessons = false;

    /*
     * If we are going to use the Topics section, we need to create and
     * point to the topics.json file
     *
     * $CFG->topics = $CFG->dirroot.'/../topics.json';
     */
    public $topics = false;

    /**
     * Storage location for Lumen Application.
     *
     * Needed for log files, by default dirroot."/storage/". This needs
     * to be a location on your server that has write access.
     *
     */
    public $lumen_storage;

    /*
     * Whether or not to track launch activity
     */
    public $launchactivity = true;

    /*
     * Set this to true if you are running certification since it is wonky at times
     */
    public $certification = false;
    public $require_conformance_parameters = false;
    public $prefer_lti1_for_grade_send = false;

    /*
     * Set these to enable Tsugi's option to uify accounts by email address
     */
    public $unify = false;

    /*
     * Controls the event push logic
     */
    public $eventcheck = false;
    public $eventtime = 7*24*60*60;
    public $eventpushtime = 2;
    public $eventpushcount = 0;

    /*
     * Controls google log in and maps setup.
     *
     * Go to https://console.developers.google.com/apis/credentials
     * create a new OAuth 2.0 credential for a web application,
     * get the key and secret, and put them in these attributes
     */
    public $google_client_id = false;
    public $google_client_secret = false;
    public $google_map_api_key = false;

    /*
     * Tells Google to come back to "/login" after Google Login.
     * If set to false our login comes back to "login.php".
     *
     * The login return is part of your OAuth 2.0 configuration
     * in Google.  And some old integrations used login.php.
     * New integrations should use "/login" and leave this true.
     * This is here to for old integrations.
     */
    public $google_login_new = true;

    /*
     * This allows you to force login.php to always go to the same
     * page after login success.  Tsugi looks at the session for a
     * "go back after login" URL, and will go to apphome or wwwroot.
     *
     * But if you want for the return to always go to some particular
     * URL, set this field.
     *
     * $CFG->login_return_url = $CFG->apphome . "/welcome";
     */
    public $login_return_url = false;

    /**
     * Defaults to $CFG->apphome if defined and $CFG->wwwroot if that is not defined or false
     */
    public $logout_return_url;

    /**
     * If we have a web socket server, put its URL here
     * Do not add a path here - just the host and port
     * Make sure the port is open on your server
     *
     * $CFG->websocket_secret = 'changeme';
     * $CFG->websocket_url = 'ws://localhost:2021'; // Local dev test
     * $CFG->websocket_url = 'wss://socket.tsugicloud.org:443'; // Production
     *
     * If you are running a reverse proxy (proxy_wstunnel) set this to the port
     * you will forward to in your apache config
     *
     * $CFG->websocket_proxyport = 8080;
    */
    public $websocket_secret = false;
    public $websocket_url = false;
    public $websocket_proxyport = false;

    /**
     * If the web server is NOT behind a reverse proxy, you may optionally wish
     * to ignore forwarded IP headers such as x-forwarded-for and variations by
     * setting this to false. This will help to preserve authenticity of IPs by
     * only trusting IP addresses directly seen by the server.
     *
     * Never set this to false if you ARE behind a reverse proxy, otherwise all
     * requests will appear to originate from the same IP address (the proxy).
     *
     * If behind a reverse proxy, set to `true`:
     *     $CFG->trust_forwarded_ip = true; // (default)
     *
     * If not using a reverse proxy, set to `false`:
     *     $CFG->trust_forwarded_ip = false;
     */
    public $trust_forwarded_ip = true;

    /*
     * This is the internal version of the datbase.   This is an internal
     * value and set in setup.php and read in migrate.php - you should not
     * touch this value.
     */
    public $dbversion = false;

    public $vendorinclude = false;    // No longer used in the code base
    public $vendorroot = false;       // No longer used in the code base
    public $vendorstatic = false;     // No longer used in the code base

    /**
     * The autoloader to be used when loading classes.
     *
     * This is part of configuration startup and should be left as-is
     * from config.dist
     */
    public $loader = false;

    /*
     * Allows you to run your Tsugi on a branch other than master
     *
     * This is an array of key value pairs when Tsugi is auto-upgrading Tsugi
     * or a tool.   If it is checking out a particular remote, once
     * that is checked out - it will switch to the specified branch instead
     * of master.
     *
     *   $CFG->branch_override = array(
     *    "https://github.com/tsugiproject/tsugi.git" => "php81"
      *  }
     */
    public $branch_override = false;

    // Legacy values no longer used
    public $bootswatch = false;
    public $bootswatch_color = false;
    public $fontawesome = false;
    public $analytics_key = false;
    public $analytics_name = false;
    public $universal_analytics = false;

    /**
     * Badge generation settings - once you start issuing badges - don't change these
     */
    public $badge_encrypt_password = null; // "somethinglongwithhex387438758974987";
    public $badge_assert_salt = null; // "mediumlengthhexstring";
    public $badge_path = null; // $CFG->dirroot . '/../bimages';
    public $badge_url = null; // $CFG->apphome . '/bimages';
    
    /**
     * Email address for Open Badges issuer (OB2 required field)
     * 
     * This email address is used in badge issuer assertions.
     * If not set, defaults to "badge_issuer_email_not_set@example.com"
     * 
     * $CFG->badge_issuer_email = 'py4e@learnxp.com';
     */
    public $badge_issuer_email = null;
    
    /**
     * Organization name for badge issuer assertions
     * 
     * If not set, defaults to the result of getBadgeOrganization() method,
     * which falls back to "$CFG->servicedesc ($CFG->servicename)" format,
     * or just $CFG->servicename if servicedesc is not set.
     * 
     * $CFG->badge_organization = 'Learning Experiences';
     */
    public $badge_organization = null;
    
    /**
     * Organization URL for badge issuer assertions
     * 
     * The URL that represents the badge issuing organization.
     * If not set, defaults to $CFG->apphome.
     * 
     * $CFG->badge_organization_url = 'https://www.learnxp.com';
     */
    public $badge_organization_url = null;
    
    /**
     * Organization logo URL for badge issuer assertions
     * 
     * Optional logo image URL to include in OB3 issuer profiles.
     * If set, will be included as an "image" property in the issuer JSON.
     * 
     * $CFG->badge_organization_logo = 'https://www.learnxp.com/logo-square.png';
     */
    public $badge_organization_logo = null;
    
    /**
     * LinkedIn organization/company page URL
     * 
     * If set, displays a LinkedIn link on badge pages and may be included
     * in badge issuer extensions.
     * 
     * $CFG->linkedin_url = 'https://www.linkedin.com/company/learn-xp';
     */
    public $linkedin_url = null;
    
    /**
     * LinkedIn organization ID for badge sharing
     * 
     * The numeric organization ID used when generating LinkedIn "Add to Profile" URLs.
     * This replaces organizationName in LinkedIn certification URLs.
     * 
     * $CFG->linkedin_organization_id = '4264503';
     */
    public $linkedin_organization_id = null;

    /**
     * The defaults for data expiration.  Data expiration is not done by default, but can
     * be triggered in the Tsugi Admin UI or via a php CLI program.
     */
    public $expire_pii_days = 150;  // Three months
    public $expire_user_days = 400;  // One year
    public $expire_context_days = 600; // 1.5 Years
    public $expire_tenant_days = 800; // Two years

    /**
     * Legacy: Google Classroom support - this was an experiment and is no longer supported
     * First, Go to https://console.developers.google.com/apis/credentials
     * And add access to "Google Classroom API" to your google_client_id (above)

     * (legacy) Set the secret to a long random string - this is used for internal
     * url Tsugi signing - not for Google interactions.  Don't change it
     * once you set it.
     */
    public $google_classroom_secret = null;

    /**
     * (legacy) This should be an absolute URL that will be used to populate previews
     * in Google Classroom
     */
    public $google_classroom_logo = null;

    /**
     * Create the configuration object.
     *
     * Generally this is done once to create the global variable $CFG
     * in the file config.php.
     *
     * Example call with constants:
     *
     *     $CFG = new \Tsugi\Config\ConfigInfo('/Applications/MAMP/htdocs/tsugi',
     *         'http://localhost:8888/tsugi');
     *
     * Example call in config.php that does not hard-code the actual path:
     *
     *     $CFG = new \Tsugi\Config\ConfigInfo(realpath(dirname(__FILE__)),
     *         'http://localhost:8888/tsugi');
     *
     * Once the variable is constructed, the public member variables are
     * overridden directly by setting them in the PHP code in config.php.
     *
     *     $CFG = new \Tsugi ...
     *     $CFG->pdo = 'mysql:host=127.0.0.1;port=8889;dbname=tsugi'; // MAMP
     *     $CFG->dbuser = 'zippy';
     *     ...
     *
     * @param $dirroot The full path of the Tsugi source code.  This
     * value is used throughout Tsugi to include files with absolute
     * paths. Make sure not to include a trailing slash.
     *
     * @param $wwwroot The URL where Tsugi is being hosted.  Make sure
     * not to include a trailing slash.
     *
     * @param $dataroot An optional parameter that is and absolute path
     * preferably not a sub-folder of $dirroot that is readable and
     * writeable by the PHP code.   Tsugi uses this folder to store
     * and serve uploaded file blobs.  If this parameter is left off
     * Tsugi will attempt to use a folder named '/_files/a/' within
     * $dirroot as its blob storage area.
     */
    public function __construct($dirroot, $wwwroot, $dataroot=false) {
        $this->dirroot = $dirroot;
        $this->wwwroot = $wwwroot;
        $this->extensions = array();
        $this->staticroot = 'https://static.tsugi.org';
        $this->lumen_storage = sprintf("%s/storage/", $dirroot);
    }

    function getExtension($key, $default=null) {
	    return $this->extensions[$key] ?? $default;
    }

    /**
     * Set an extension value
     */
    function setExtension($key, $value) {
	    $this->extensions[$key] = $value;
    }

    function getCurrentFile($file) {
        $root = $this->dirroot;
        $path = realpath($file);
        if ( strlen($path) < strlen($root) ) return false;
        // The root must be the prefix of path
        if ( strpos($path, $root) !== 0 ) return false;
        $retval = substr($path, strlen($root));
        return $retval;
    }

    // Get the foldername of the currently called script
    // ["SCRIPT_FILENAME"]=> string(52) "/Applications/MAMP/htdocs/tsugi/mod/attend/index.php"
    // This function will return "attend"
    function getScriptFolder() {
        $path = self::getScriptPathFull();
        if ( $path === false ) return false;
        // Don't use DIRECTORY_SEPARATOR, PHP makes these forward slashes on Windows
        $pieces = explode('/', $path);
        if ( count($pieces) < 1 ) return false;
        return $pieces[count($pieces)-1];
    }

    // This should be deprecated since it only works under tsugi
    function getCurrentFileUrl($file) {
        return $this->wwwroot.$this->getCurrentFile($file);
    }

    function getLoginUrl() {
        return $this->wwwroot.'/login';
    }

    /**
     * Get the current working directory of a file
     */
    function getPwd($file) {
        $root = $this->dirroot;
        $path = realpath(dirname($file));
        $root .= '/'; // Add the trailing slash
        if ( strlen($path) < strlen($root) ) return false;
        // The root must be the prefix of path
        if ( strpos($path, $root) !== 0 ) return false;
        $retval = substr($path, strlen($root));
        return $retval;
    }

    function getUrlFull($file) {
        $path = self::getPwd($file);
        return $this->wwwroot . "/" . $path;
    }

    public function getScriptPath() {
        $path = self::getScriptPathFull();
        if ( strpos($path, $this->dirroot) === 0 )  {
            $x = substr($path, strlen($this->dirroot)+1 ) ;
            return $x;
        } else {
            return "";
        }
    }

    public static function getScriptPathFull() {
        if ( ! isset( $_SERVER['SCRIPT_FILENAME']) ) return false;
        $script = $_SERVER['SCRIPT_FILENAME'];
        $path = dirname($script);
        return $path;
    }

    /**
     * Get the name of the script relative to the server document root
     *
     * /py4e/mod/peer-grade/maint.php
     */
    public static function getScriptName() {
        if ( ! isset( $_SERVER['SCRIPT_NAME']) ) return false;
        $script = $_SERVER['SCRIPT_NAME'];
        return $script;
    }

    /**
     * Get the current URL we are executing - no query parameters
     *
     * http://localhost:8888/py4e/mod/peer-grade/maint.php
     */
    public function getCurrentUrl() {
        $script = self::getScriptName();
        if ( $script === false ) return false;
        $pieces = $this->apphome;
        if ( $this->apphome ) {
            $pieces = parse_url($this->apphome);
        }
        // We only take scheme, host, and port from wwwroot / apphome
        if ( ! isset($pieces['scheme']) ) return false;
        $retval = $pieces['scheme'].'://'.$pieces['host'];
        if ( isset($pieces['port']) ) $retval .= ':'.$pieces['port'];
        return $retval . $script;
    }

    /**
     * Get the current folder of the URL we are executing - no trailing slash
     *
     * input: http://localhost:8888/py4e/mod/peer-grade/maint.php
     * output: http://localhost:8888/py4e/mod/peer-grade
     *
     */
    public function getCurrentUrlFolder() {
        $url = self::getCurrentUrl();
        $pieces = explode('/', $url);
        array_pop($pieces);
        $retval = implode('/', $pieces);
        return $retval;
    }

    /**
     * Are we on localhost?
     */
    public function localhost() {
        if ( strpos($this->wwwroot,'://localhost') !== false ) return true;
        if ( strpos($this->wwwroot,'://127.0.0.1') !== false ) return true;
        return false;
    }

    /**
     * Return a prefix unique to this server for things like shared cache keys
     */
    public function serverPrefix() : string {
        $prefix = $this->wwwroot;
        if ( is_string($this->apphome) && strlen($this->apphome) > 0 ) $prefix = $this->apphome;
        $prefix = preg_replace('/https?:\/\//', '', $prefix);
        if (strlen($prefix) > 50 ) $prefix = md5($prefix);
        return $prefix;
    }

    /**
     * Get the badge organization name with fallback logic
     * 
     * Returns $badge_organization if set, otherwise falls back to
     * "$servicedesc ($servicename)" format, or just $servicename if servicedesc is not set.
     * 
     * @return string The badge organization name
     */
    public function getBadgeOrganization() : string {
        // Use badge_organization if set
        if (isset($this->badge_organization) && !empty($this->badge_organization)) {
            return $this->badge_organization;
        }
        
        // Build fallback: servicedesc (servicename) or just servicename if servicedesc not set
        if (isset($this->servicedesc) && !empty($this->servicedesc)) {
            return $this->servicedesc . ' (' . $this->servicename . ')';
        }
        
        // Final fallback to just servicename
        return $this->servicename;
    }
}

