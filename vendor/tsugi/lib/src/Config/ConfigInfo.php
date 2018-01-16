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

class ConfigInfo {

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
    public $dbuser    = 'ltiuser';
    public $dbpass    = 'ltipassword';

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
     * This is the PW that you need to access the Administration features of this application.
     *
     * You should change this from the default.
     */
    public $adminpw = 'warning:please-change-adminpw-89b543!';

    /** 
     * The product instance guid - generally the domain of the server.
     *
     * For testing you can leave this default, but for production this
     * needs to generally be the real domain name of the Tsugi server 
     * to make sure tools are uniquely identified.
     *
     * From LTI 2.0 spec: A globally unique identifier for the service provider. 
     * As a best practice, this value should match an Internet domain name 
     * assigned by ICANN, but any globally unique identifier is acceptable.
     */

    public $product_instance_guid = 'lti2.example.com';

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
     */
    public $dataroot = false;

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
     * Configure analytics for this Tsugi instance.
     *
     * Set to false if you do not want analytics - this uses the ga.js
     * analytics and sets three custom parameters
     * (oauth_consumer_key, context_id, and context_title)
     * is they are set.
     */
    public $analytics_key = false;  // "UA-423997-16";
    public $analytics_name = false; // "dr-chuck.com";

    /** 
     * Configure universal analytics for this Tsugi instance.
     *
     * Set to false if you do not want universal analytics
     */
    public $universal_analytics = false;  // "UA-423997-16";

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
     * This allows you to make your own tool folders.  These are scanned
     * for database.php, register.php and index.php files to do automatic 
     * table creation as well as making lists of tools in various UI places
     * and during LTI 2.x tool registration.
     */
    public $tool_folders = array("core", "mod", "samples");

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
        $this->staticroot = 'https://www.dr-chuck.net/tsugi-static';
/* TODO: Delete this in the future - it is a bad default
        if ( $dataroot === false ) {
            $this->dataroot = $dirroot . '/_files/a';
        } else {
            $this->dataroot = $dataroot;
        }
*/
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
}

