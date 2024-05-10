<?php

namespace Tsugi\Core;

use \Tsugi\OAuth\TrivialOAuthDataStore;
use \Tsugi\OAuth\OAuthServer;
use \Tsugi\OAuth\OAuthRequest;

use \Tsugi\Util\LTI;
use \Tsugi\Util\LTI13;
use \Tsugi\Util\U;
use \Tsugi\Util\Net;
use \Tsugi\Util\DeepLinkRequest;
use \Tsugi\Util\LTIConstants;
use \Tsugi\UI\Output;
use \Tsugi\Core\I18N;
use \Tsugi\Core\Settings;
use \Tsugi\Core\SQLDialect;
use \Tsugi\Core\Keyset;
use \Tsugi\OAuth\OAuthUtil;
use \Tsugi\Crypt\SecureCookie;
use \Tsugi\Crypt\AesOpenSSL;

use \Firebase\JWT\JWT;

/**
 * This an opinionated LTI class that defines how Tsugi tools interact with LTI
 *
 * This class deals with all of the session and database/data model
 * details that Tsugi tools make use of during runtime.  This makes use of the
 * lower level \Tsugi\Util\LTI class which is focused on
 * meeting the protocol requirements.
 * Most tools will not use LTI at all - just LTIX.
 */
class LTIX {

    // Indicates that this code requires certain
    // launch data to function
    const CONTEXT = "context_id";
    const USER = "user_id";
    const LINK = "link_id";
    const ALL = "all";
    const NONE = "none";

    // The maximum length of the VARCHAR field
    const MAX_ACTIVITY = 1023;

    const ROLE_LEARNER = 0;
    const ROLE_INSTRUCTOR = 1000;
    const ROLE_ADMINISTRATOR = 5000;

    const BROWSER_MARK_COOKIE = "TSUGI-BROWSER-MARK";

    /**
     * Get a singleton global connection or set it up if not already set up.
     */
    public static function getConnection() {
        global $PDOX, $CFG;

        if ( isset($PDOX) && is_object($PDOX) && get_class($PDOX) == 'Tsugi\Util\PDOX' ) {
            return $PDOX;
        }

        if ( defined('PDO_WILL_CATCH') ) {
            if ( isset($CFG->pdo_options) && is_array($CFG->pdo_options)) {
                $PDOX = new \Tsugi\Util\PDOX($CFG->pdo, $CFG->dbuser, $CFG->dbpass, $CFG->pdo_options);
            } else {
                $PDOX = new \Tsugi\Util\PDOX($CFG->pdo, $CFG->dbuser, $CFG->dbpass);
            }
            $PDOX->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } else {
            try {
                $PDOX = new \Tsugi\Util\PDOX($CFG->pdo, $CFG->dbuser, $CFG->dbpass);
                $PDOX->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            } catch(\PDOException $ex){
                error_log("DB connection: ".$ex->getMessage());
                die('Failure connecting to the database, see error log'); // with error_log
            }
        }
        if ( isset($CFG->slow_query) ) $PDOX->slow_query = $CFG->slow_query;
        $PDOX->sqlPatch = function($PDOX, $sql) { return \Tsugi\Core\SQLDialect::sqlPatch($PDOX, $sql); } ;

        // Add meta entries describing primary and logical keys for LTI tables
        $p = $CFG->dbprefix;
        $PDOX->addPDOXMeta("{$p}lms_plugins", array("pk" => "plugin_id", "lk" => array("plugin_path")));
        $PDOX->addPDOXMeta("{$p}lti_key", array("pk" => "key_id", "lk" => array("key_sha256")));
        $PDOX->addPDOXMeta("{$p}lti_context", array("pk" => "context_id", "lk" => array("context_sha256", "key_id")));
        $PDOX->addPDOXMeta("{$p}lti_link", array("pk" => "link_id", "lk" => array("link_sha256", "context_id")));
        $PDOX->addPDOXMeta("{$p}lti_user", array("pk" => "user_id", "lk" => array("user_sha256", "subject_sha256", "key_id")));
        $PDOX->addPDOXMeta("{$p}lti_membership", array("pk" => "membership_id", "lk" => array("user_id", "context_id")));
        $PDOX->addPDOXMeta("{$p}lti_service", array("pk" => "service_id", "lk" => array("service_sha256", "key_id")));
        $PDOX->addPDOXMeta("{$p}lti_result", array("pk" => "result_id", "lk" => array("user_id", "link_id")));
        $PDOX->addPDOXMeta("{$p}lti_nonce", array("pk" => "none", "lk" => array("key_id", "nonce")));
        $PDOX->addPDOXMeta("{$p}cal_event", array("pk" => "event_id"));
        $PDOX->addPDOXMeta("{$p}tsugi_string", array("pk" => "string_id", "lk" => array("domain", "string_sha256")));
        $PDOX->addPDOXMeta("{$p}profile", array("pk" => "profile_id", "lk" => array("profile_sha256")));
        $PDOX->addPDOXMeta("{$p}blob_blob", array("pk" => "blob_id", "lk" => array("blob_sha256")));
        // Does not have a logical key
        $PDOX->addPDOXMeta("{$p}blob_file", array("pk" => "file_id"));
        return $PDOX;
    }

    /**
     * Silently check if this is a launch and if so, handle it and redirect
     * back to ourselves
     */
    public static function launchCheck($needed=self::ALL, $session_object=null,$request_data=false) {
        global $TSUGI_LAUNCH, $CFG;
        $needed = self::patchNeeded($needed);

        // Check if we are an LTI 1.1 or LTI 1.3 launch
        $LTI11 = false;
        $LTI13 = false;
        $detail = LTI13::isRequestDetail($request_data);
        if ( $detail === true ) {
            $LTI13 = true;
        } else {
            $lti11_request_data = $request_data;
            if ( $lti11_request_data === false ) $lti11_request_data = self::oauth_parameters();
            $LTI11 = LTI::isRequestCheck($lti11_request_data);
            if ( is_string($LTI11) ) {
                self::abort_with_error_log($LTI11, $request_data);
            }
        }
        if ( $LTI11 === false && $LTI13 === false ) return $detail;


        $session_id = self::setupSession($needed,$session_object,$request_data);
        if ( $session_id === false ) return false;

        // Redirect back to ourselves...
        $url = self::curPageUrl();

        if ( $session_object !== null ) {
            $TSUGI_LAUNCH->redirect_url = self::curPageUrl();
            return true;
        }

        $location = U::addSession($url);
        session_write_close();  // To avoid any race conditions...

        if ( headers_sent() ) {
            echo('<p><a href="'.$url.'">Click to continue</a></p>');
        } else {
            header('Location: '.$location);
        }
        exit();
    }

    /**
     * Encrypt a secret to put into the session
     */
    public static function encrypt_secret($secret)
    {
        global $CFG;
        if ( ! is_string($secret) ) return null;
        if ( startsWith($secret,'AES::') ) return $secret;
        $encr = AesOpenSSL::encrypt($secret, $CFG->cookiesecret) ;
        return 'AES::'.$encr;
    }

    /**
     * Decrypt a secret from the session
     */
    public static function decrypt_secret($secret)
    {
        global $CFG;
        if ( $secret === null || $secret === false ) return $secret;
        if ( ! startsWith($secret,'AES::') ) return $secret;
        $secret = substr($secret, 5);
        // TODO: Switch to AesOpenSSL after time passes from March 1, 2022
        // $decr = AesOpenSSL::decrypt($secret, $CFG->cookiesecret) ;
        $decr = \Tsugi\Crypt\AesCtr::decrypt($secret, $CFG->cookiesecret, 256) ;
        return $decr;
    }

    /**
     * Wrap getting a key from the session
     */
    public static function wrapped_session_get($session_object,$key,$default=null)
    {
        global $TSUGI_SESSION_OBJECT;
        if ( $session_object === null && isset($TSUGI_SESSION_OBJECT) ) $session_object = $TSUGI_SESSION_OBJECT;
        if ( is_object($session_object) ) {
            return $session_object->get($key,$default);
        }
        if ( is_array($session_object) ) {
            if ( isset($session_object[$key]) ) return $session_object[$key];
            return $default;
        }
        if ( ! isset($_SESSION) ) return $default;
        if ( ! isset($_SESSION[$key]) ) return $default;
        return $_SESSION[$key];
    }

    /**
     * Get all session values
     */
    public static function wrapped_session_all($session_object)
    {
        global $TSUGI_SESSION_OBJECT;
        if ( $session_object === null && isset($TSUGI_SESSION_OBJECT) ) $session_object = $TSUGI_SESSION_OBJECT;
        if ( is_object($session_object) ) {
            return $session_object->all();
        }
        if ( is_array($session_object) ) {
            $retval = array();
            $retval = array_merge($retval,$session_object); // Make a copy
            return $retval;
        }
        if ( ! isset($_SESSION) ) return array();
        $retval = array();
        $retval = array_merge($retval, $_SESSION);
        return $retval;
    }

    /**
     * Wrap setting a key from the session
     */
    public static function wrapped_session_put(&$session_object,$key,$value)
    {
        global $TSUGI_SESSION_OBJECT;
        if ( $session_object === null && isset($TSUGI_SESSION_OBJECT) ) $session_object = $TSUGI_SESSION_OBJECT;
        if ( is_object($session_object) ) {
            $session_object->put($key,$value);
            return;
        }
        if ( is_array($session_object) ) {
            $session_object[$key] = $value;
            return;
        }
        if ( isset($_SESSION) ) $_SESSION[$key] = $value;
    }

    /**
     * Wrap forgetting a key from the session
     */
    public static function wrapped_session_forget(&$session_object,$key)
    {
        global $TSUGI_SESSION_OBJECT;
        if ( $session_object === null && isset($TSUGI_SESSION_OBJECT) ) $session_object = $TSUGI_SESSION_OBJECT;
        if ( is_object($session_object) ) {
            $session_object->forget($key);
            return;
        }
        if ( is_array($session_object) ) {
            if ( isset($session_object[$key]) ) unset($session_object[$key]);
            return;
        }
        if ( isset($_SESSION) && isset($_SESSION[$key]) ) unset($_SESSION[$key]);
    }

    /**
     * Wrap flushing the session
     */
    public static function wrapped_session_flush(&$session_object)
    {
        global $TSUGI_SESSION_OBJECT;
        if ( $session_object === null && isset($TSUGI_SESSION_OBJECT) ) $session_object = $TSUGI_SESSION_OBJECT;
        if ( is_object($session_object) ) {
            $session_object->flush();
            return;
        }
        if ( is_array($session_object) ) {
            foreach($session_object as $k => $v ) {
                unset($session_object[$k]);
            }
            for ($i = 0; $i < count($session_object); $i++) {
                unset($session_object[$i]);
            }
        }
        session_unset();
    }

    /**
     * Pull a keyed variable from the LTI data in the current session with default
     *
     * @deprecated Session access should be through the Launch Object
     */
    public static function ltiParameter($varname, $default=false) {
        global $TSUGI_LAUNCH;
        if ( isset($TSUGI_LAUNCH) ) {
            return $TSUGI_LAUNCH->ltiParameter($varname, $default);
        }
        if ( ! isset($_SESSION) ) return $default;
        if ( ! isset($_SESSION['lti']) ) return $default;
        $lti = $_SESSION['lti'];
        if ( ! isset($lti[$varname]) ) return $default;
        return $lti[$varname];
    }

    /**
     * Return the original $_POST array
     *
     * @deprecated Session access should be through the Launch Object
     */
    public static function ltiRawPostArray() {
        global $TSUGI_LAUNCH;
        if ( isset($TSUGI_LAUNCH) ) {
            return $TSUGI_LAUNCH->ltiRawPostArray();
        }
        if ( ! isset($_SESSION) ) return array();
        if ( ! isset($_SESSION['lti_post']) ) return array();
        return($_SESSION['lti_post']);
    }

    /**
     * Pull a keyed variable from the original LTI post data in the current session with default
     *
     * @deprecated Session access should be through the Launch Object
     */
    public static function ltiRawParameter($varname, $default=false) {
        global $TSUGI_LAUNCH;
        if ( isset($TSUGI_LAUNCH) ) {
            return $TSUGI_LAUNCH->ltiRawParameter($varname, $default);
        }
        if ( ! isset($_SESSION) ) return $default;
        if ( ! isset($_SESSION['lti_post']) ) return $default;
        $lti_post = $_SESSION['lti_post'];
        if ( ! isset($lti_post[$varname]) ) return $default;
        return $lti_post[$varname];
    }

    /**
     * Pull out a custom variable from the LTIX session. Do not
     * include the "custom_" prefix - this is automatic.
     *
     * @deprecated Session access should be through the Launch Object
     */
    public static function ltiCustomGet($varname, $default=false) {
        return self::ltiRawParameter('custom_'.$varname, $default);
    }

    /**
     * The LTI parameter data
     *
     * This code is taken from OAuthRequest
     */
    public static function oauth_parameters() {
        // Find request headers
        $request_headers = OAuthUtil::get_headers();

        // Parse the query-string to find GET parameters
        if ( isset($_SERVER['QUERY_STRING']) ) {
            $parameters = OAuthUtil::parse_parameters($_SERVER['QUERY_STRING']);
        } else {
            $parameters = array();
        }

        // Add POST Parameters if they exist
        $parameters = array_merge($parameters, $_POST);

        // We have a Authorization-header with OAuth data. Parse the header
        // and add those overriding any duplicates from GET or POST
        if (isset($request_headers['Authorization']) &&
            substr($request_headers['Authorization'], 0, 6) == "OAuth ") {
            $header_parameters = OAuthUtil::split_header(
                $request_headers['Authorization']
            );
            $parameters = array_merge($parameters, $header_parameters);
        }
        return $parameters;
    }

    // Put as much oomph into setting a cookie as we can
    public static function setCookieStrong($name, $value, $expires) {
        global $CFG;
        Net::setCookieStrong($name, $value, parse_url($CFG->wwwroot)['host'], $expires);
    }

    public static function getBrowserMark() {
        global $CFG, $TSUGI_BROWSER_MARK;
        $browser_mark = U::get($_COOKIE, self::BROWSER_MARK_COOKIE);
        if (is_string($browser_mark) && U::strlen($browser_mark) > 1 ) {
            // error_log('Got browser_mark '.$browser_mark."\n");
        } else if ( is_string($TSUGI_BROWSER_MARK) && U::strlen($TSUGI_BROWSER_MARK) > 1 ) {
            $browser_mark = $TSUGI_BROWSER_MARK;
        } else {
            $browser_mark = uniqid();
            $expires = time() + 1*30*24*3600;
            self::setCookieStrong(
                self::BROWSER_MARK_COOKIE,
                $browser_mark,
                $expires
            );
            // error_log('Set browser_mark '.$browser_mark."\n");
        }

        // Only check once
        $TSUGI_BROWSER_MARK = $browser_mark;
        return $browser_mark;
    }

    /**
     * Extract all of the post data, set up data in tables, and set up session.
     */
    public static function setupSession($needed=self::ALL, $session_object=null, $request_data=false) {
        global $CFG, $TSUGI_LAUNCH, $TSUGI_SESSION_OBJECT;
        global $PDOX;
        $TSUGI_SESSION_OBJECT = $session_object;

        $needed = self::patchNeeded($needed);

        // Check if we are an LTI 1.1 or LTI 1.3 launch
        $LTI11 = false;
        $LTI13 = false;
        $lti13_request_data = $request_data;
        if ( $lti13_request_data === false ) $lti11_request_data = $_POST;
        $detail = LTI13::isRequestDetail($request_data);
        if ( $detail === true ) {
            $LTI13 = true;
            $request_data = $lti13_request_data;
        } else {
            $lti11_request_data = $request_data;
            if ( $lti11_request_data === false ) $lti11_request_data = self::oauth_parameters();
            $LTI11 = LTI::isRequestCheck($lti11_request_data);
            if ( is_string($LTI11) ) {
                self::abort_with_error_log($LTI11, $request_data);
            }
            $request_data = $lti11_request_data;
        }
        if ( $LTI11 === false && $LTI13 === false ) return $detail;

        // Pull LTI data out of the incoming $request_data and map into the same
        // keys that we use in our database (i.e. like $row)
        $post = false;
        if ( $LTI11 ) {
            $post = self::extractPost($needed, $request_data);
        } else if ( $LTI13 ) {
            $post = self::extractJWT($needed, $request_data);
            // echo("<pre>\n");var_dump($post);echo("\n</pre>\n");
        }

        if ( ! is_array($post) ) {
            $msg = '';
            if ( is_string($post) ) $msg = $post . ' ';
            self::abort_with_error_log($msg, $request_data);
        }

        // We make up a Session ID Key because we don't want a new one
        // each time the same user launches the same link.
        if ( $session_object === null ) {
            if ( !defined('COOKIE_SESSION') ) {
                $session_id = self::getCompositeKey($post, $CFG->sessionsalt);
                session_id($session_id);
                session_start();
            // if we're using a cookie session, the session may already have been started; if not, start it now
            // (if we call session_start() and the session has already been started, php will generate a notice, which we don't want)
            } else if (U::isEmpty($_SESSION)) {
                session_start();
            }
            $session_id = session_id();

            // Since we might reuse session IDs, clean everything out except permanent stuff
            if ( !defined('COOKIE_SESSION') ) {
                $save_sess = self::wrapped_session_all($session_object);
                self::wrapped_session_flush($session_object);
                foreach($save_sess as $key => $v ) {
                    if ( strpos($key, 'tsugi_permanent_') !== 0 ) continue;
                    self::wrapped_session_put($session_object, $key, $v);
                }
            }
        }

        self::wrapped_session_put($session_object,'LAST_ACTIVITY', time());

        // Copy the tsugi_nav into the session
        $sess_key = 'tsugi_top_nav_'.$CFG->wwwroot;
        if ( isset($request_data['ext_tsugi_top_nav']) ) {
            self::wrapped_session_put($session_object,$sess_key, $request_data['ext_tsugi_top_nav']);
        } else {
            self::wrapped_session_forget($session_object,$sess_key);
        }

        // Read all of the data from the database with a very long
        // LEFT JOIN and get all the data we have back in the $row variable
        // $row = loadAllData($CFG->dbprefix, false, $post);
        $row = self::loadAllData($CFG->dbprefix, $CFG->dbprefix.'profile', $post);

        // For LTI 1.3
        if ( $row && U::get($post,'deployment_id') &&  ! U::get($row, 'deploy_key') ) {
            self::abort_with_error_log('Found issuer, but did not find corresponding deployment: '.htmlentities(U::get($post,'deployment_id')));
        }

        if ( ! $row || ! U::get($row, 'key_id') ) {
            if ( U::get($post,'key') ) {  // LTI 1.1
                self::abort_with_error_log('Launch could not find key: '.htmlentities(U::get($post,'key')));
            } else {
                self::abort_with_error_log('Launch could not find issuer: '.htmlentities(U::get($post,'issuer_key')));
            }
        }

        // Copy the deployment_id into run=time data for later
        if ( $LTI13 && U::get($post,'deployment_id') ) {
            $row['deployment_id'] = $post['deployment_id'];
        }

        $delta = 0;
        if ( isset($request_data['oauth_timestamp']) ) {
            $server_time = $request_data['oauth_timestamp']+0;
            $delta = abs(time() -  $server_time);
            if ( $delta > 480 ) { // More than four minutes is getting close
                error_log('Warning: Time skew, delta='.$delta.' sever_time='.$server_time.' our_time='.time());
            }
        }

        // Check the nonce to make sure there is no reuse
        if ( $row['nonce'] !== null) {
            self::abort_with_error_log('OAuth nonce error key='.$post['key'].' nonce='.$row['nonce']);
        }

        // Use returned data to check the validity of the incoming request
        $raw_jwt = false;
        $jwt = false;
        if ( $LTI11 ) {
            $valid = LTI::verifyKeyAndSecret($post['key'],$row['secret'],self::curPageUrl(), $request_data);

            // If there is a new_secret it means an LTI2 re-registration is in progress and we
            // need to check both the current and new secret until the re-registration is committed
            if ( $valid !== true && U::isNotEmpty($row['new_secret']) && $row['new_secret'] != $row['secret']) {
                $valid = LTI::verifyKeyAndSecret($post['key'],$row['new_secret'],self::curPageUrl(), $request_data);
                if ( $valid ) {
                    $row['secret'] = $row['new_secret'];
                }
                $row['new_secret'] = null;
            }

            if ( isset($TSUGI_LAUNCH) ) $TSUGI_LAUNCH->base_string = LTI::getLastOAuthBodyBaseString();

            // TODO: Might want to add more flows here...
            if ( $valid !== true ) {
                if ( isset($TSUGI_LAUNCH) ) $TSUGI_LAUNCH->error_message = $valid;
                self::abort_with_error_log('OAuth validation fail key='.$post['key'].' delta='.$delta.' error='.$valid[0],$valid[1]);
            }

        } else { // LTI 1.3
            $key_id = $row['key_id'];
            $issuer_id = $row['issuer_id'];
            $issuer_key = $post['issuer_key'];
            $issuer_client = $post['issuer_client'];
            $deployment_id = $post['deployment_id'];

            if ( $key_id < 1 ) {
                 self::abort_with_error_log("Could not find tenant/key for $issuer_key / clientid=$issuer_client deployment_id=$deployment_id");
            }

            $raw_jwt = LTI13::raw_jwt($request_data);
            $jwt = LTI13::parse_jwt($raw_jwt);

            $request_kid = isset($jwt->header->kid) ? $jwt->header->kid : null;
            $our_kid = $row['lti13_kid'];
            $our_keyset = $row['lti13_keyset'];
            $our_keyset_url = $row['lti13_keyset_url'];
            $public_key = $row['lti13_platform_pubkey'];

            $token_url = $row['lti13_token_url'];

            $public_key = self::getPlatformPublicKey($issuer_id, $key_id, $request_kid, $our_kid, $public_key, $our_keyset_url, $our_keyset);
/*
            // Sanity check
            if ( U::isEmpty($our_keyset_url) ) {
                 self::abort_with_error_log("Could not find keyset and $issuer_key");
            }

            // Make sure we have or update to the latest keyset if we have a keyset_url
            if ( U::isNotEmpty($our_keyset_url) &&
                    (U::isEmpty($our_keyset) || $our_kid != $request_kid ) ) {
                $our_keyset = file_get_contents($our_keyset_url);
                $decoded = json_decode($our_keyset);
                if ( $decoded && isset($decoded->keys) && is_array($decoded->keys) ) {
                    $PDOX->queryDie("UPDATE {$CFG->dbprefix}lti_issuer
                        SET lti13_keyset=:KS, updated_at=NOW() WHERE issuer_sha256 = :SHA",
                    array(':SHA' => $issuer_sha256, ':KS' => $our_keyset) );
                    error_log("Updated keyset $issuer_sha256 from $our_keyset_url\n");
                } else {
                    self::abort_with_error_log("Failure loading keyset from ".$our_keyset_url,
                                substr($our_keyset,0,1000));
                }
            }

            // If we have a keyset and a kid mismatch, lets grab that new key
            if ( U::isNotEmpty($our_keyset) &&
                ($our_kid != $request_kid || U::isEmpty($public_key)) ) {

                $new_public_key = LTI13::extractKeyFromKeySet($our_keyset, $request_kid);

                if ( $new_public_key ) {
                    $PDOX->queryDie("UPDATE {$CFG->dbprefix}lti_issuer
                        SET lti13_platform_pubkey=:PK, lti13_kid=:KID, updated_at=NOW() WHERE issuer_sha256 = :SHA",
                        array(':SHA' => $issuer_sha256, ':PK' => $new_public_key,
                            ':KID' => $request_kid )
                    );
                    error_log("New public key $issuer_sha256\n$new_public_key");
                    $public_key = $new_public_key;
                } else {
                    $PDOX->queryDie("UPDATE {$CFG->dbprefix}lti_issuer
                        SET lti13_platform_pubkey=NULL, updated_at=NOW() WHERE issuer_sha256 = :SHA",
                    array(':SHA' => $issuer_sha256) );
                    if ( U::isNotEmpty($public_key) ) {
                        error_log("Cleared public key $issuer_sha256 invalid kid");
                        self::abort_with_error_log("Invalid Key Id (header.kid), public key cleared");
                    } else {
                        error_log("Could not find public key $issuer_sha256 invalid kid");
                        self::abort_with_error_log("Invalid Key Id (header.kid), could not find public key");
                    }
                }
            }
 */

            $e = LTI13::verifyPublicKey($raw_jwt, $public_key, array($jwt->header->alg));
            if ( $e !== true ) {
                self::abort_with_error_log('JWT validation fail key='.$issuer_key.' error='.$e->getMessage());
            }

            // Check validity of LTI 1.1 transition data if it exists
            $lti11_transition_user_id = U::get($post, 'lti11_transition_user_id');
            if ( U::isNotEmpty($lti11_transition_user_id) ) {
                $lti11_oauth_consumer_key = $row['key_key'];  // From the join
                $lti11_oauth_consumer_secret = self::decrypt_secret($row['secret']);
                $check = LTI13::checkLTI11Transition($jwt->body, $lti11_oauth_consumer_key, $lti11_oauth_consumer_secret);
                if ( is_string($check) ) self::abort_with_error_log('LTI 1.1 Transition error: '.$check);
                if ( ! $check ) self::abort_with_error_log('LTI 1.1 Transition signature mis-match key='.$lti11_oauth_consumer_key);
            }

            $row['lti13_token_url'] = $token_url;

            // Just copy across
            if ( U::get($post,'lti13_deeplink') ) $row['lti13_deeplink'] = $post['lti13_deeplink'];

            self::wrapped_session_put($session_object, 'tsugi_jwt', $jwt);
            // self::wrapped_session_put($session_object, 'tsugi_raw_jwt', $raw_jwt);
        }

        // Store the launch path
        // TODO: Make sure we like this
        $post['link_path'] = self::curPageUrl();
        $actions = self::adjustData($CFG->dbprefix, $row, $post, $needed);

        $PDOX = self::getConnection();
        // Record the nonce but first probabilistically check if we will clean out
        if ( $CFG->noncecheck > 0 ) {
            if ( (time() % $CFG->noncecheck) == 0 ) {
                $PDOX->queryDie("DELETE FROM {$CFG->dbprefix}lti_nonce WHERE
                    created_at < DATE_ADD(CURRENT_TIMESTAMP(), INTERVAL -{$CFG->noncetime} SECOND)");
                // error_log("Nonce table cleanup done.");
            }
            $PDOX->queryDie("INSERT IGNORE INTO {$CFG->dbprefix}lti_nonce
                (key_id, nonce) VALUES ( :key_id, :nonce)",
                array( ':nonce' => $post['nonce'], ':key_id' => $row['key_id'])
            );
        }

        // If there is an appropriate role override variable, we use that role
        if ( isset($row['role_override']) && isset($row['role']) &&
            $row['role_override'] > $row['role'] ) {
            $row['role'] = $row['role_override'];
        }
        if ( isset($row['for_user_role_override']) && isset($row['for_user_role']) &&
            $row['for_user_role_override'] > $row['for_user_role'] ) {
            $row['for_user_role'] = $row['for_user_role_override'];
        }

        // Update the login_at data and do analytics if requested
        // There are a lot of queryReturnError() calls because we don't want to
        // fail on "nice to have" analytics data.
        $start_time = self::wrapped_session_get($session_object, 'tsugi_permanent_start_time', false);

        if ( isset($row['user_id']) && $start_time === false ) {
            self::noteLoggedIn($row);

            // Only learner launches are logged
            if ( $CFG->launchactivity && isset($row['link_id']) && $row['link_id'] && $row['role'] == 0 ) {
                $link_activity = isset($row['link_activity']) ? $row['link_activity'] : null;
                $link_count = isset($row['link_count']) ? $row['link_count'] : 0;

                if ( $link_activity == null || $link_count == 0 ) {

                    $sql = "INSERT IGNORE INTO {$CFG->dbprefix}lti_link_activity
                                (link_id, event, link_count, updated_at) VALUES
                                (:link_id, 0, 0, NOW())";

                    $stmt = $PDOX->queryReturnError($sql, array(
                        ':link_id' => $row['link_id']
                    ));

                    if ( ! $stmt->success ) {
                        error_log("Unable to create activity record link=".$row['link_id']);
                    }
                }

                $ent = new \Tsugi\Event\Entry();
                if ( $link_activity ) $ent->deSerialize($link_activity);
                $ent->total = $link_count;
                $ent->click();
                $activity = $ent->serialize(self::MAX_ACTIVITY);
                $sql = "UPDATE {$CFG->dbprefix}lti_link_activity
                        SET activity=:activity, updated_at=NOW(), link_count=link_count+1
                        WHERE link_id = :link_id AND event = 0";
                $stmt = $PDOX->queryReturnError($sql, array(
                  ':link_id' => $row['link_id'],
                  ':activity' => $activity
                ));

                if ( ! $stmt->success ) {
                    error_log("Unable to update activity record link=".$row['link_id']);
                }

                // Now user activity
                $link_activity = isset($row['link_user_activity']) ? $row['link_user_activity'] : null;
                $link_count = isset($row['link_user_count']) ? $row['link_user_count'] : 0;

                if ( isset($row['user_id']) && $row['user_id'] ) {
                    if ( $link_activity == null || $link_count == 0 ) {

                        $sql = "INSERT IGNORE INTO {$CFG->dbprefix}lti_link_user_activity
                                (link_id, user_id, event, link_user_count, updated_at) VALUES
                                (:link_id, :user_id, 0, 0, NOW())";
                        $stmt = $PDOX->queryReturnError($sql, array(
                            ':link_id' => $row['link_id'],
                            ':user_id' => $row['user_id']
                        ));

                        if ( ! $stmt->success ) {
                            error_log("Unable to create user activity record link=".$row['user_id']);
                        }
                    }

                    $ent = new \Tsugi\Event\Entry();
                    if ( $link_activity ) $ent->deSerialize($link_activity);
                    $ent->total = $link_count;
                    $ent->click();
                    $activity = $ent->serialize(self::MAX_ACTIVITY);
                    $sql = "UPDATE {$CFG->dbprefix}lti_link_user_activity
                        SET activity=:activity, updated_at=NOW(), link_user_count=link_user_count+1
                        WHERE link_id = :link_id AND user_id = :user_id AND event = 0";
                    $stmt = $PDOX->queryReturnError($sql, array(
                        ':link_id' => $row['link_id'],
                        ':user_id' => $row['user_id'],
                        ':activity' => $activity
                    ));

                    if ( ! $stmt->success ) {
                        error_log("Unable to update user activity record link=".$row['user_id']);
                    }
                }
            }

            // Now the place the event into the circular buffer
            if ( $CFG->eventcheck !== false ) {
                $event_nonce = $post['nonce'].':'.$row['key_key'];

                // Store binary nonce in fixed-length CHAR field with no encoding
                // https://stackoverflow.com/questions/3554296/how-to-store-hashes-in-mysql-databases-without-using-text-fields
                $event_nonce = md5($event_nonce, True);   // Was UNHEX(MD5(:nonce)) in MySQL

                // https://stackoverflow.com/questions/16001238/writing-to-a-bytea-field-error-invalid-byte-sequence-for-encoding-utf8-0x9
                if ( $PDOX->isPgSQL() ) {
                    $event_nonce = substr( base64_encode($event_nonce), 0, 16);
                }
                $event_launch = null;
                $canvasUrl = U::get($request_data,'custom_sub_canvas_caliper_url');
                if ( $canvasUrl ) {
                    $event_launch = 'canvas::'.$canvasUrl;
                }

                $sql = "INSERT INTO {$CFG->dbprefix}cal_event
                        (event, key_id, context_id, link_id, user_id, nonce, launch, updated_at) VALUES
                        (0, :key_id, :context_id, :link_id, :user_id, :nonce, :launch, NOW())";
                $stmt = $PDOX->queryReturnError($sql, array(
                    ':key_id' => U::get($row, 'key_id'),
                    ':context_id' => U::get($row, 'context_id'),
                    ':link_id' => U::get($row, 'link_id'),
                    ':user_id' => U::get($row, 'user_id'),
                    ':nonce' => $event_nonce,
                    ':launch' => $event_launch
                ));

                if ( ! $stmt->success ) {
                    error_log("Unable to insert event record");
                }
                $row['event_nonce'] = $event_nonce;
                $row['event_launch'] = $event_launch;
            }
        }

        // Make sure we debounce really fast relaunches
        self::wrapped_session_put($session_object, 'tsugi_permanent_start_time', time());

        // Encrypt the secret before placing in the session
        $row['secret'] = self::encrypt_secret($row['secret']);

        // Put the information into the row variable and put row into session
        self::wrapped_session_put($session_object, 'lti', $row);
        self::wrapped_session_put($session_object, 'lti_post', $request_data);

        if ( isset($_SERVER['HTTP_USER_AGENT']) ) {
            self::wrapped_session_put($session_object, 'HTTP_USER_AGENT', $_SERVER['HTTP_USER_AGENT']);
        }
        $ipaddr = Net::getIP();
        if ( is_string($ipaddr) ) {
            self::wrapped_session_put($session_object, 'REMOTE_ADDR', $ipaddr);
            // Check our list of IP address history
            // TODO: decrypt
            $iphistory = U::get($_COOKIE, "TSUGI-HISTORY",'');
            // Add this IP Address to the Tsugi IP History if it is not there
            if ( strpos($iphistory, $ipaddr) === false ) {
                $iphistory .= '!' . $ipaddr;
                // TODO: encrypt
                setcookie('TSUGI-HISTORY',$iphistory, 0, '/'); // Expire 100 seconds ago
            }
        }

        $browser_mark = self::getBrowserMark();
        self::wrapped_session_put($session_object, 'BROWSER_MARK', $browser_mark);

        self::wrapped_session_put($session_object, 'CSRF_TOKEN', uniqid());

        // Save this to make sure the user does not wander unless we launched from the root
        $scp = $CFG->getScriptPath();
        if ( U::isNotEmpty($scp) ) {
            self::wrapped_session_put($session_object, 'script_path', $CFG->getScriptPath());
        }

        // Check if we can auto-login the system user
        if ( Settings::linkGet('dologin', false) && isset($PDOX) && $PDOX !== false ) self::loginSecureCookie();

        $breadcrumb = 'Launch,';
        $breadcrumb .= isset($row['key_id']) ? $row['key_id'] : '';
        $breadcrumb .= ',';
        $breadcrumb .= isset($row['user_id']) ? $row['user_id'] : '';
        $breadcrumb .= ',';
        $breadcrumb .= isset($request_data['user_id']) ? str_replace(',',';', $request_data['user_id']) : '';
        $breadcrumb .= ',';
        if ( $session_object === null ) {
            $breadcrumb .= $session_id;
        }
        $breadcrumb .= ',';
        $breadcrumb .= self::curPageUrl();
        $breadcrumb .= ',';
        $breadcrumb .= self::wrapped_session_get($session_object, 'email',' ');
        error_log($breadcrumb);

        if ( $session_object === null ) {
            return $session_id;
        }
    }

    /**
     * getPlatformPublicKey - Get the platform public key for the various sources
     *
     * This will check the current kid, the current keyset, and if nothing matches
     * re-load the keyset url, and check the new keyset url.  If new information is retrieved, it
     * is cached until the kid chages for this issuer.
     */
    public static function getPlatformPublicKey($issuer_id, $key_id, $request_kid, $our_kid, $public_key, $our_keyset_url, $our_keyset)
    {
        global $PDOX, $CFG;

        if ( U::isNotEmpty($public_key) && $request_kid == $our_kid ) return $public_key;

        error_log("getPlatformPublicKey issuer_id=$issuer_id key_id=$key_id request_kid=$request_kid stored_kid=$our_kid");

        // Make sure we have or update to the latest keyset if we have a keyset_url
        // and the kid is new to us
        if ( U::isNotEmpty($our_keyset_url) ) {
            $our_keyset = @file_get_contents($our_keyset_url);
            if ( $our_keyset === false ) {
                $error = error_get_last();
                self::abort_with_error_log("Failure loading keyset from ".$our_keyset_url." detail:".U::get($error, 'message'));
            }

            $decoded = json_decode($our_keyset);
            if ( $decoded && isset($decoded->keys) && is_array($decoded->keys) ) {
                if ( $issuer_id > 0 ) {
                    $PDOX->queryDie("UPDATE {$CFG->dbprefix}lti_issuer
                        SET lti13_keyset=:KS, updated_at=NOW() WHERE issuer_id = :ID",
                    array(':ID' => $issuer_id, ':KS' => $our_keyset) );
                    error_log("Updated issuer keyset $issuer_id from $our_keyset_url\n");
                } else {
                    $PDOX->queryDie("UPDATE {$CFG->dbprefix}lti_key
                        SET lms_cache_keyset=:KS, updated_at=NOW() WHERE key_id = :ID",
                    array(':ID' => $key_id, ':KS' => $our_keyset) );
                    error_log("Updated lms_cache_keyset $issuer_id from $our_keyset_url\n");
                }
            } else {
                self::abort_with_error_log("Failure loading keyset from ".$our_keyset_url,
                            substr($our_keyset,0,1000));
            }
        }

        // If we have a keyset, lets look for the new key
        if ( U::isNotEmpty($our_keyset) ) {
            $new_public_key = LTI13::extractKeyFromKeySet($our_keyset, $request_kid);

            if ( $new_public_key ) {
                if ( $issuer_id > 0 ) {
                    $PDOX->queryDie("UPDATE {$CFG->dbprefix}lti_issuer
                        SET lti13_platform_pubkey=:PK, lti13_kid=:KID, updated_at=NOW() WHERE issuer_id = :ID",
                        array(':ID' => $issuer_id, ':PK' => $new_public_key,
                            ':KID' => $request_kid )
                    );
                    error_log("New issuer public key $issuer_id\n$new_public_key");
                } else {
                    $PDOX->queryDie("UPDATE {$CFG->dbprefix}lti_key
                        SET lms_cache_pubkey=:PK, lms_cache_kid=:KID, updated_at=NOW() WHERE key_id = :ID",
                        array(':ID' => $key_id, ':PK' => $new_public_key,
                            ':KID' => $request_kid )
                    );
                    error_log("New lms_cache_pubkey $key_id\n$new_public_key");
                }
                return $new_public_key;
            }
        }

        // Despite our best efforts, we could not get a key - clear things out to enable reset
        if ( $issuer_id > 0 ) {
            $PDOX->queryDie("UPDATE {$CFG->dbprefix}lti_issuer
                SET lti13_platform_pubkey=NULL, updated_at=NOW() WHERE issuer_id = :ID",
            array(':ID' => $issuer_id) );
            if ( U::isNotEmpty($public_key) ) {
                error_log("Cleared public key $issuer_id invalid kid");
                self::abort_with_error_log("Invalid Key Id (header.kid), public key cleared");
            } else {
                error_log("Could not find public key $issuer_id invalid kid");
                self::abort_with_error_log("Invalid Key Id (header.kid), could not find public key");
            }
        } else {
            $PDOX->queryDie("UPDATE {$CFG->dbprefix}lti_key
                SET lms_cache_pubkey=NULL, updated_at=NOW() WHERE key_id = :ID",
            array(':ID' => $key_id) );
            if ( U::isNotEmpty($public_key) ) {
                error_log("Cleared lms_cache_pubkey key $key_id invalid kid");
                self::abort_with_error_log("Invalid Key Id (header.kid), public key cleared");
            } else {
                error_log("Could not find lms_cache_pubkey $key_id invalid kid");
                self::abort_with_error_log("Invalid Key Id (header.kid), could not find public key");
            }
        }
    }

    /**
     * Pull the LTI POST data into our own data structure
     *
     * We follow our naming conventions that match the column names in
     * our lti_ tables.
     */
    public static function extractPost($needed=self::ALL, $input=false) {
        // Unescape each time we use this stuff - someday we won't need this...
        $needed = self::patchNeeded($needed);
        if ( $input === false ) $input = $_POST;
        $FIXED = array();
        foreach($input as $key => $value ) {
            if ( strpos($key, "custom_") === 0 ) {
                $newkey = substr($key,7);
                // Need to deal with custom_context_id=$Context.id
                if ( strpos($value,"$") === 0 ) {
                    $short_value = strtolower(substr($value,1));
                    $short_value = str_replace('.','_',$short_value);
                    if ( $newkey == $short_value ) {
                        continue;
                    }
                }
                if ( !isset($FIXED[$newkey]) ) $FIXED[$newkey] = $value;
            }
            $FIXED[$key] = $value;
        }
        $retval = array();
        $retval['key'] = isset($FIXED['oauth_consumer_key']) ? $FIXED['oauth_consumer_key'] : null;
        $retval['nonce'] = isset($FIXED['oauth_nonce']) ? $FIXED['oauth_nonce'] : null;
        $link_id = isset($FIXED['resource_link_id']) ? $FIXED['resource_link_id'] : null;
        $link_id = isset($FIXED['custom_resource_link_id']) ? $FIXED['custom_resource_link_id'] : $link_id;
        $retval['link_id'] = $link_id;

        $user_id = isset($FIXED['person_sourcedid']) ? $FIXED['person_sourcedid'] : null;
        $user_id = isset($FIXED['user_id']) ? $FIXED['user_id'] : $user_id;
        $user_id = isset($FIXED['custom_user_id']) ? $FIXED['custom_user_id'] : $user_id;
        $retval['user_id'] = $user_id;

        $context_id = isset($FIXED['courseoffering_sourcedid']) ? $FIXED['courseoffering_sourcedid'] : null;
        $context_id = isset($FIXED['context_id']) ? $FIXED['context_id'] : $context_id;
        $context_id = isset($FIXED['custom_context_id']) ? $FIXED['custom_context_id'] : $context_id;
        $retval['context_id'] = $context_id;

        // Sanity checks
        if ( ! $retval['key'] ) return "Missing oauth_consumer_key";
        if ( ! $retval['nonce'] ) return "Missing nonce";
        if ( in_array(self::USER, $needed) && ! $retval['user_id'] ) return "Missing required user_id";
        if ( in_array(self::CONTEXT, $needed) && ! $retval['context_id'] ) return "Missing required context_id";
        if ( in_array(self::LINK, $needed) && ! $retval['link_id'] ) return "Missing required resource_link_id";

        // LTI 1.x settings and Outcomes
        $retval['service'] = isset($FIXED['lis_outcome_service_url']) ? $FIXED['lis_outcome_service_url'] : null;
        $retval['sourcedid'] = isset($FIXED['lis_result_sourcedid']) ? $FIXED['lis_result_sourcedid'] : null;

        // LTI 2.x settings and Outcomes
        $retval['result_url'] = isset($FIXED['custom_result_url']) ? $FIXED['custom_result_url'] : null;
        $retval['link_settings_url'] = isset($FIXED['custom_link_settings_url']) ? $FIXED['custom_link_settings_url'] : null;
        $retval['context_settings_url'] = isset($FIXED['custom_context_settings_url']) ? $FIXED['custom_context_settings_url'] : null;

        // LTI 1.x / 2.x Service endpoints
        $retval['ext_memberships_id'] = isset($FIXED['ext_memberships_id']) ? $FIXED['ext_memberships_id'] : null;
        $retval['ext_memberships_url'] = isset($FIXED['ext_memberships_url']) ? $FIXED['ext_memberships_url'] : null;
        $retval['lineitems_url'] = isset($FIXED['lineitems_url']) ? $FIXED['lineitems_url'] : null;
        $retval['memberships_url'] = isset($FIXED['memberships_url']) ? $FIXED['memberships_url'] : null;

        // Copy the settings into session
        $retval['link_settings'] = isset($FIXED['link_settings']) ? $FIXED['link_settings'] : null;
        $retval['context_settings'] = isset($FIXED['context_settings']) ? $FIXED['context_settings'] : null;
        $retval['key_settings'] = isset($FIXED['key_settings']) ? $FIXED['key_settings'] : null;

        // Context
        $retval['context_title'] = isset($FIXED['context_title']) ? $FIXED['context_title'] : null;
        $retval['link_title'] = isset($FIXED['resource_link_title']) ? $FIXED['resource_link_title'] : null;

        $retval['user_locale'] = isset($FIXED['launch_presentation_locale']) ? $FIXED['launch_presentation_locale'] : null;

        // Getting email from LTI 1.x and LTI 2.x
        $retval['user_email'] = isset($FIXED['lis_person_contact_email_primary']) ? $FIXED['lis_person_contact_email_primary'] : null;
        $retval['user_email'] = isset($FIXED['custom_person_email_primary']) ? $FIXED['custom_person_email_primary'] : $retval['user_email'];

        $retval['user_image'] = isset($FIXED['user_image']) ? $FIXED['user_image'] : null;

        // Displayname from LTI 2.x
        if ( isset($FIXED['person_name_full']) ) {
            $retval['user_displayname'] = $FIXED['custom_person_name_full'];
        } else if ( isset($FIXED['custom_person_name_given']) && isset($FIXED['custom_person_name_family']) ) {
            $retval['user_displayname'] = $FIXED['custom_person_name_given'].' '.$FIXED['custom_person_name_family'];
        } else if ( isset($FIXED['custom_person_name_given']) ) {
            $retval['user_displayname'] = $FIXED['custom_person_name_given'];
        } else if ( isset($FIXED['custom_person_name_family']) ) {
            $retval['user_displayname'] = $FIXED['custom_person_name_family'];

        // Displayname from LTI 1.x
        } else if ( isset($FIXED['lis_person_name_full']) ) {
            $retval['user_displayname'] = $FIXED['lis_person_name_full'];
        } else if ( isset($FIXED['lis_person_name_given']) && isset($FIXED['lis_person_name_family']) ) {
            $retval['user_displayname'] = $FIXED['lis_person_name_given'].' '.$FIXED['lis_person_name_family'];
        } else if ( isset($FIXED['lis_person_name_given']) ) {
            $retval['user_displayname'] = $FIXED['lis_person_name_given'];
        } else if ( isset($FIXED['lis_person_name_family']) ) {
            $retval['user_displayname'] = $FIXED['lis_person_name_family'];
        }

        // Trim out repeated spaces and/or weird whitespace from the user_displayname
        if ( isset($retval['user_displayname']) ) {
            $retval['user_displayname'] = trim(preg_replace('/\s+/', ' ',$retval['user_displayname']));
        }

        // Get the role
        $retval['role'] = self::ROLE_LEARNER;
        $roles = '';
        if ( isset($FIXED['custom_membership_role']) ) { // From LTI 2.x
            $roles = $FIXED['custom_membership_role'];
        } else if ( isset($FIXED['roles']) ) { // From LTI 1.x
            $roles = $FIXED['roles'];
        }

        if ( U::isNotEmpty($roles) ) {
            $roles = strtolower($roles);
            if ( ! ( strpos($roles,'instructor') === false ) ) $retval['role'] = self::ROLE_INSTRUCTOR;
            if ( ! ( strpos($roles,'administrator') === false ) ) $retval['role'] = self::ROLE_ADMINISTRATOR;
            // Local superuser would be 10000
        }

        // Copy in some extensions.  Backwards compatibility for canvas xapi urls in legacy cartridges
        $sub_canvas_caliper_url = U::get($FIXED,'sub_canvas_xapi_url');
        if ( $sub_canvas_caliper_url ) {
            $sub_canvas_caliper_url = str_replace('xapi','caliper',$sub_canvas_caliper_url);
        }
        $sub_canvas_caliper_url = U::get($FIXED,'sub_canvas_caliper_url', $sub_canvas_caliper_url);
        if ($sub_canvas_caliper_url ) $retval['sub_canvas_caliper_url'] = $sub_canvas_caliper_url;

        $sub_caliper_url = U::get($FIXED,'sub_caliper_url');
        if ($sub_caliper_url ) $retval['sub_caliper_url'] = $sub_caliper_url;

        // Get the theme data
        $retval['theme_base'] = isset($FIXED['theme_base']) ? $FIXED['theme_base'] : null;
        $retval['theme_dark_mode'] = isset($FIXED['theme_dark_mode']) ? $FIXED['theme_dark_mode'] : null;

        return $retval;
    }

    /**
     * Pull the LTI JWT data into our own data structure
     *
     * We follow our naming conventions that match the column names in
     * our lti_ tables.
     */
    public static function extractJWT($needed=self::ALL, $input=false) {
        // Unescape each time we use this stuff - someday we won't need this...
        $needed = self::patchNeeded($needed);
        if ( $input === false ) $input = $_POST;

        $raw_jwt = LTI13::raw_jwt($input);
        $jwt = LTI13::parse_jwt($raw_jwt);

        if ( is_string($jwt) ) return "Could not extract jwt: ".$jwt;
        if ( ! $jwt ) return "Could not extract jwt";

        $body = $jwt->body;

        $issuer_sha256 = LTI13::extract_issuer_key($jwt);
        $issuer_key = $jwt->body->iss;
        error_log("issuer_key=$issuer_key\n");
        error_log("issuer_sha256=$issuer_sha256\n<hr/>\n");

        $retval = array();
        $retval['issuer_key'] = $issuer_key;
        $retval['issuer_sha256'] = $issuer_sha256;
        $retval['issuer_client'] = $jwt->body->aud;
        if ( isset($body->nonce) ) $retval['nonce'] = $body->nonce;

        // Don't set user_id for LTI 1.3
        if ( isset($body->sub) ) {
            $retval['user_subject'] = $body->sub;
        }

        // Pull in the legacy information - signatures will be checked later
        if ( isset($body->{LTI13::LTI11_TRANSITION_CLAIM}) ) {
            $lti11_transition = $body->{LTI13::LTI11_TRANSITION_CLAIM};
            if ( isset($lti11_transition->user_id) && isset($lti11_transition->oauth_consumer_key) &&
                    isset($lti11_transition->oauth_consumer_key_sign) ) {
                $retval['lti11_transition_user_id'] = $lti11_transition->user_id;
                $retval['lti11_transition_oauth_consumer_key'] = $lti11_transition->oauth_consumer_key;
                $retval['lti11_transition_oauth_consumer_key_sign'] = $lti11_transition->oauth_consumer_key_sign;
                // If the LTI 1.1 transition claim contains the resource_link_id,
                // assign its value to the link_id in the return value array.
                if (isset($lti11_transition->resource_link_id)) {
                    $retval['link_id'] = $lti11_transition->resource_link_id;
                }
            }
        }

        // Handle the for_user claim
        if ( isset($body->{LTI13::FOR_USER_CLAIM}) ) {
            $for_user_claim = $body->{LTI13::FOR_USER_CLAIM};
            $retval['for_user_subject'] = isset($for_user_claim->user_id) ? $for_user_claim->user_id : null;
            $retval['for_user_email'] = isset($for_user_claim->email) ? $for_user_claim->email : null;
            $retval['for_user_image'] = isset($for_user_claim->picture) ? $for_user_claim->picture : null;
            $retval['for_user_locale'] = isset($for_user_claim->locale) ? $for_user_claim->locale : null;
            $retval['for_user_displayname'] = self::displayNameFromClaim($for_user_claim);
        }

        // The rest of the claims
        $resource_link_claim = LTI13::RESOURCE_LINK_CLAIM;
        $context_id_claim = LTI13::CONTEXT_ID_CLAIM;
        $deployment_id_claim = LTI13::DEPLOYMENT_ID_CLAIM;
        // If link_id in the return value array has not already been set
        if ( isset($body->{$resource_link_claim}->id) && !isset($retval['link_id']) ) $retval['link_id'] = $body->{$resource_link_claim}->id;
        if ( isset($body->{$context_id_claim}->id) ) $retval['context_id'] = $body->{$context_id_claim}->id;
        if ( isset($body->{$deployment_id_claim}) ) $retval['deployment_id'] = $body->{$deployment_id_claim};

        // Sanity checks
        $failures = array();

        // Do the Jon Postel check
        LTI13::jonPostel($body, $failures);

        if ( ! U::get($retval,'issuer_key') ) $failures[] = "Could not deterimine key issuer_from iss/aud";
        if ( ! U::get($retval,'nonce') ) $failures[] = "Missing nonce";
        if ( in_array(self::USER, $needed) &&
            ! ( U::get($retval,'user_id') || U::get($retval,'user_subject')) ) $failures[] = "Missing subject/user_id (sub)";
        if ( in_array(self::CONTEXT, $needed) && ! U::get($retval,'context_id') ) $failures[] = "Missing context_id";
        if ( in_array(self::LINK, $needed) && ! U::get($retval,'link_id') ) $failures[] = "Missing resource_link->id";

        $failmsg = '';
        if ( count($failures) > 0 ) {
            foreach($failures as $failure) {
                if ( U::isNotEmpty($failmsg) ) $failmsg .= ", \n";
                $failmsg .= $failure;
            }
            error_log("Could not find all required items in body (link_id, user_id, context_id)");
            error_log($failmsg);
            error_log(json_encode($body));
            return $failmsg;
        }

        // Context
        $retval['context_title'] = isset($body->{$context_id_claim}->title) ? $body->{$context_id_claim}->title : null;
        $retval['link_title'] = isset($body->{$resource_link_claim}->title) ? $body->{$resource_link_claim}->title : null;

        $retval['user_locale'] = isset($body->locale) ? $body->locale : null;
        $retval['user_email'] = isset($body->email) ? $body->email : null;
        $retval['user_image'] = isset($body->picture) ? $body->picture : null;
        $retval['user_displayname'] = self::displayNameFromClaim($body);

        // Get the line item
        $retval['lti13_lineitem'] = null;
        if ( isset($body->{LTI13::ENDPOINT_CLAIM}) &&
            isset($body->{LTI13::ENDPOINT_CLAIM}->lineitem) &&
            is_string($body->{LTI13::ENDPOINT_CLAIM}->lineitem) ) {
            $retval['lti13_lineitem'] = $body->{LTI13::ENDPOINT_CLAIM}->lineitem;
        }

        // Get the line item
        $retval['lti13_lineitems'] = null;
        if ( isset($body->{LTI13::ENDPOINT_CLAIM}) &&
            isset($body->{LTI13::ENDPOINT_CLAIM}->lineitems) &&
            is_string($body->{LTI13::ENDPOINT_CLAIM}->lineitems) ) {
            $retval['lti13_lineitems'] = $body->{LTI13::ENDPOINT_CLAIM}->lineitems;
        }


        // Get the names and roles claim
        $retval['lti13_membership_url'] = null;
        if ( isset($body->{LTI13::NAMESANDROLES_CLAIM}) &&
            isset($body->{LTI13::NAMESANDROLES_CLAIM}->context_memberships_url) &&
            is_string($body->{LTI13::NAMESANDROLES_CLAIM}->context_memberships_url) &&
            isset($body->{LTI13::NAMESANDROLES_CLAIM}->service_versions) &&
            is_array($body->{LTI13::NAMESANDROLES_CLAIM}->service_versions) &&
            in_array("2.0", $body->{LTI13::NAMESANDROLES_CLAIM}->service_versions)
        ) {
            $retval['lti13_membership_url'] = $body->{LTI13::NAMESANDROLES_CLAIM}->context_memberships_url;
        }

        // Get the error url...
        $retval['launch_presentation_return_url'] = null;
        if ( isset($body->{LTI13::PRESENTATION_CLAIM}) &&
            isset($body->{LTI13::PRESENTATION_CLAIM}->return_url)
        ) {
            $retval['launch_presentation_return_url'] = $body->{LTI13::PRESENTATION_CLAIM}->return_url;
        }

        // Get the role
        $retval['role'] = self::ROLE_LEARNER;
        if ( isset($body->{LTI13::ROLES_CLAIM}) &&
           is_array($body->{LTI13::ROLES_CLAIM}) ) {

            $roles = implode(':',$body->{LTI13::ROLES_CLAIM});

            if ( U::isNotEmpty($roles) ) {
                $roles = strtolower($roles);
                if ( ! ( strpos($roles,'instructor') === false ) ) $retval['role'] = self::ROLE_INSTRUCTOR;
                if ( ! ( strpos($roles,'administrator') === false ) ) $retval['role'] = self::ROLE_ADMINISTRATOR;
                // Local superuser would be 10000
            }
        }

        // Handle the DeepLink Claim
        $retval['lti13_deeplink'] = null;
        if ( isset($body->{LTI13::DEEPLINK_CLAIM}) ) {
            $retval['lti13_deeplink'] = $body->{LTI13::DEEPLINK_CLAIM};
        }

        // Get the theme data
        $retval['theme_base'] = isset($body->theme_base) ? $body->theme_base : null;
        $retval['theme_dark_mode'] = isset($body->theme_dark_mode) ? $body->theme_dark_mode : null;

        return $retval;
    }

    /**
     * encode and sign a JWT with a bunch of parameters
     */
    public static function encode_jwt($params) {
        $success = Keyset::getSigning($privkey, $kid);

        // error_log('kid='. $kid.' priv='.$privkey);
        $jws = LTI13::encode_jwt($params, $privkey, $kid);
        return $jws;
    }

    // Make sure to include the file in case multiple instances are running
    // on the same Operating System instance and they have not changed the
    // session secret.  Also make these change every 30 minutes
    public static function getCompositeKey($post, $session_secret) {
        $key = U::get($post, 'issuer_key', U::get($post, 'key'));
        $user_info = U::get($post,'user_id') . U::get($post,'user_subject');
        $comp = $session_secret .'::'. $key .'::'. $post['context_id'] .'::'.
            U::get($post,'link_id')  .'::'. $user_info .'::'. intval(time() / 1800) .
            $_SERVER['HTTP_USER_AGENT'] . '::' . __FILE__;
        return md5($comp);
    }

    /**
     * Get a display name from a LTI 1.3 user claim
     */
    public static function displayNameFromClaim($claim) {
        if ( ! is_object($claim) ) return '';

        if ( isset($claim->name) ) {
            $retval = $claim->name;
        } else if ( isset($claim->given_name) && isset($claim->family_name) ) {
            $retval = $claim->given_name . ' ' . $claim->family_name;
        } else if ( isset($claim->given_name) ) {
            $retval = $claim->given_name;
        } else if ( isset($claim->family_name) ) {
            $retval = $claim->family_name;
        } else {
            $retval = ''; // TODO: IS THIS RIGHT?
        }
        // Trim out repeated spaces and/or weird whitespace from the user_displayname
        $retval=  trim(preg_replace('/\s+/', ' ',$retval));
        return $retval;
    }

    /**
     * Load the data from our lti_ tables using one long LEFT JOIN
     *
     * This data may or may not exist - hence the use of the long
     * LEFT JOIN.
     */
    public static function loadAllData($p, $profile_table, $post) {
        global $CFG;
        $PDOX = self::getConnection();
        $errormode = $PDOX->getAttribute(\PDO::ATTR_ERRMODE);
        $PDOX->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $issuer_key = U::get($post, "issuer_key", false);
        $LTI13 = $issuer_key !== false;
        $for_user_subject = U::get($post, "for_user_subject", false);

        if ( $LTI13 ) {
            $sql = "SELECT i.issuer_id, i.issuer_key, i.issuer_client, i.lti13_kid, i.lti13_keyset_url, i.lti13_keyset,
                i.lti13_platform_pubkey, i.lti13_token_url, i.lti13_token_audience,
                k.deploy_key, u.subject_key,
            ";
        } else {
            $sql = "SELECT ";
        }

        $sql .= "k.key_id, k.key_key, k.secret, k.new_secret, k.settings_url AS key_settings_url,
            k.login_at AS key_login_at, k.settings AS key_settings,
            k.lms_issuer, k.lms_client, k.lms_oidc_auth, k.lms_keyset_url,
            k.lms_token_url, k.lms_token_audience, k.lms_cache_keyset, k.lms_cache_pubkey, k.lms_cache_kid,
            n.nonce,
            c.context_id, c.title AS context_title, context_sha256, c.context_key as context_key,
            c.settings_url AS context_settings_url,
            c.ext_memberships_id AS ext_memberships_id, c.ext_memberships_url AS ext_memberships_url,
            c.lineitems_url AS lineitems_url, c.memberships_url AS memberships_url,
            c.lti13_lineitems AS lti13_lineitems, c.lti13_membership_url AS lti13_membership_url,
            c.settings AS context_settings,
            l.link_id, l.path AS link_path, l.title AS link_title, l.settings AS link_settings, l.settings_url AS link_settings_url,
            l.lti13_lineitem AS lti13_lineitem, l.settings AS link_settings,
            u.user_id, u.displayname AS user_displayname, u.email AS user_email, u.user_key AS user_key, u.image AS user_image,
            u.locale AS user_locale,
            u.subscribe AS subscribe, u.user_sha256 AS user_sha256,
            m.membership_id, m.role, m.role_override,
            r.result_id, r.grade, r.result_url, r.sourcedid";

        if ( $for_user_subject ) {
            $sql .= ",
            f.user_id AS for_user_id, f.displayname AS for_user_displayname, f.email AS for_user_email,
            f.user_key as for_user_key, f.image AS for_user_image, f.locale AS for_user_locale,
            f.subscribe AS for_subscribe, f.user_sha256 AS for_user_sha256,
            fm.membership_id AS for_membership_id, fm.role AS for_user_role, fm.role_override AS for_user_role_override";
        }

        if ( $profile_table ) {
            $sql .= ",
            p.profile_id, p.displayname AS profile_displayname, p.email AS profile_email,
            p.subscribe AS profile_subscribe";
        }

        if ( isset($post['service']) ) {
            $sql .= ",
            s.service_id, s.service_key AS service";
        }

        if ( $CFG->launchactivity ) {
            $sql .= ",
                a.link_count, a.activity AS link_activity,
                au.link_user_count, au.activity AS link_user_activity";
        }

        if ( $LTI13 ) {
            // $sql .="\nFROM {$p}lti_issuer AS i
                // LEFT JOIN {$p}lti_key AS k ON i.issuer_id = k.issuer_id";
            $sql .="\nFROM {$p}lti_key AS k
                LEFT JOIN {$p}lti_issuer AS i ON i.issuer_id = k.issuer_id AND
                (i.deleted IS NULL OR i.deleted = 0) AND
                (i.issuer_sha256 = :issuer_sha256 AND i.issuer_client = :issuer_client) ";
        } else {
            $sql .="\nFROM {$p}lti_key AS k";
        }

        // TODO: Collapse a few weeks after 05-27-2019
        if ( $LTI13 ) {
            $user_and = "(u.user_sha256 = :user OR u.subject_sha256 = :subject)";
        } else {
            $user_and = "u.user_sha256 = :user";
        }

        // Add the JOINs
        $sql .= "\n    LEFT JOIN {$p}lti_nonce AS n ON k.key_id = n.key_id AND n.nonce = :nonce
            LEFT JOIN {$p}lti_context AS c ON k.key_id = c.key_id AND c.context_sha256 = :context
            LEFT JOIN {$p}lti_link AS l ON c.context_id = l.context_id AND l.link_sha256 = :link
            LEFT JOIN {$p}lti_user AS u ON k.key_id = u.key_id AND $user_and
            LEFT JOIN {$p}lti_membership AS m ON u.user_id = m.user_id AND c.context_id = m.context_id
            LEFT JOIN {$p}lti_result AS r ON u.user_id = r.user_id AND l.link_id = r.link_id";

        if ( $profile_table ) {
            $sql .= "
            LEFT JOIN {$profile_table} AS p ON u.profile_id = p.profile_id";
        }

        if ( $for_user_subject ) {
            $sql .= "
            LEFT JOIN {$p}lti_user AS f ON f.key_id = k.key_id AND f.subject_sha256 = :for_user_subject_sha256
            LEFT JOIN {$p}lti_membership AS fm ON f.user_id = m.user_id AND c.context_id = m.context_id";
            // TODO: Afterwards - don't accept the user if it is not in the same context...
        }

        if ( isset($post['service']) ) {
            $sql .= "
            LEFT JOIN {$p}lti_service AS s ON k.key_id = s.key_id AND s.service_sha256 = :service";
        }

        if ( $CFG->launchactivity ) {
            $sql .= "
            LEFT JOIN {$p}lti_link_activity AS a ON a.link_id = l.link_id AND a.event = 0
            LEFT JOIN {$p}lti_link_user_activity AS au ON au.link_id = l.link_id AND au.user_id = u.user_id AND au.event = 0";
        }

        // Add the WHERE clause
        if ( $LTI13 ) {
            // TODO: Index lms_issuer_sha256
            $sql .= "\nWHERE (k.deploy_key = :deployment_id OR k.deploy_key IS NULL)
                AND (
                    (i.issuer_sha256 = :issuer_sha256 AND i.issuer_client = :issuer_client)
                    OR ( (lms_issuer_sha256 IS NULL OR lms_issuer_sha256 = :issuer_sha256 ) AND lms_client = :issuer_client )
                )
            ";
        } else {
           $sql .= "\nWHERE k.key_sha256 = :key AND (k.deleted IS NULL OR k.deleted = 0)";
        }

        // TODO: Fix this per SO - but wait until the migrations have run in production
        // https://stackoverflow.com/questions/44474250/which-is-better-in-mysql-an-ifnull-or-or-logic/44474286
        $sql .= "\nAND (c.deleted IS NULL OR c.deleted = 0)
            AND (l.deleted IS NULL OR l.deleted = 0)
            AND (u.deleted IS NULL OR u.deleted = 0)
            AND (m.deleted IS NULL OR m.deleted = 0)
            AND (r.deleted IS NULL OR r.deleted = 0)";

        if ( $profile_table ) {
            $sql .= "
            AND (p.deleted IS NULL OR p.deleted = 0)";
        }

        if ( isset($post['service']) ) {
            $sql .= "
            AND (s.deleted IS NULL OR s.deleted = 0)";
        }

        // There should only be one :)

        $sql .= "
            LIMIT 1\n";

        // ContentItem does not need link_id
        if ( ! isset($post['link_id']) ) $post['link_id'] = null;

        // Compute user identity bits based on user_subject, user_id and LTI 1.1 transition id if present
        $post_user_subject = U::get($post, 'user_subject');
        $subject_sha256 = U::isNotEmpty($post_user_subject) ? lti_sha256($post_user_subject) : null;

        // Allow for for the legacy user id
        $user_check = U::get($post, "user_id");
        if ( U::isEmpty($user_check) ) {
            $user_check = U::get($post, 'lti11_transition_user_id', null);
        }
        $user_sha256 = U::isNotEmpty($user_check) ? lti_sha256($user_check) : null;

        $parms = array(
            ':nonce' => substr($post['nonce'],0,128),
            ':context' => lti_sha256($post['context_id']),
            ':link' => lti_sha256($post['link_id']),
            ':user' => $user_sha256,
        );

        // Pick the correct public key, etc
        if ( $LTI13 ) {
            $parms[':subject'] = $subject_sha256;
            $parms[':issuer_sha256'] = $post["issuer_sha256"];
            $parms[':issuer_client'] = $post["issuer_client"];
            $parms[':deployment_id'] = $post["deployment_id"];
            if ( $for_user_subject ) $parms[":for_user_subject_sha256"] = lti_sha256($for_user_subject);
        } else {
            $parms[':key'] = lti_sha256($post['key']);
        }

        if ( isset($post['service']) ) {
            $parms[':service'] = lti_sha256($post['service']);
        }

        // echo("<pre>\n"); $zapsql = $sql; foreach($parms as $k => $v ) { $zapsql = str_replace($k, "'".$v."'", $zapsql); } echo("\n$zapsql\n"); // Debug
        // die(); // Debug
        $row = $PDOX->rowDie($sql, $parms);
        // echo("<pre>\n");var_dump($row);die();  // Debug

        // Check if we have an issuer for lti_issuers
        if ( $LTI13 && is_array($row) && $row['issuer_id'] < 1 ) {
            error_log("Using LTI13 key values ".$row['lms_issuer']." / ".$row['lms_client']);
            $row['issuer_key'] = $row['lms_issuer'];
            $row['issuer_client'] = $row['lms_client'];
            $row['lti13_kid'] = $row['lms_cache_kid'];
            $row['lti13_keyset_url'] = $row['lms_keyset_url'];
            $row['lti13_keyset'] = $row['lms_cache_keyset'];
            $row['lti13_platform_pubkey'] = $row['lms_cache_pubkey'];
            $row['lti13_token_url'] = $row['lms_token_url'];
            $row['lti13_token_audience'] = $row['lms_token_audience'];
        }

        // Restore ERRMODE
        $PDOX->setAttribute(\PDO::ATTR_ERRMODE, $errormode);

        return $row;
    }

    /**
     * Make sure that the data in our lti_ tables matches the POST data
     *
     * This routine compares the POST data to the data pulled from the
     * lti_ tables and goes through carefully INSERTing or UPDATING
     * all the nexessary data in the lti_ tables to make sure that
     * the lti_ table correctly match all the data from the incoming post.
     *
     * While this looks like a lot of INSERT and UPDATE statements,
     * the INSERT statements only run when we see a new user/course/link
     * for the first time and after that, we only update if something
     * changes.   So in a high percentage of launches we are not seeing
     * any new or updated data and so this code just falls through and
     * does absolutely no SQL.
     */
    public static function adjustData($p, &$row, $post, $needed) {

        $PDOX = self::getConnection();

        $errormode = $PDOX->getAttribute(\PDO::ATTR_ERRMODE);
        $PDOX->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $actions = array();

        // if we didn't get context_id from post, we can't update lti_context!

        // Since we can't do all this in a transaction, we need to handle duplicate keys
        // https://dev.mysql.com/doc/refman/5.6/en/insert-on-duplicate.html
        if ( $row['context_id'] === null && isset($post['context_id']) ) {
            $sql = "INSERT INTO {$p}lti_context
                ( context_key, context_sha256, settings_url, title, key_id, created_at, updated_at ) VALUES
                ( :context_key, :context_sha256, :settings_url, :title, :key_id, NOW(), NOW() )
                ON DUPLICATE KEY UPDATE updated_at = NOW();";
            $context_settings_url = U::get($post, 'context_settings_url', null);
            $PDOX->queryDie($sql, array(
                ':context_key' => $post['context_id'],
                ':context_sha256' => lti_sha256($post['context_id']),
                ':settings_url' => $context_settings_url,
                ':title' => $post['context_title'],
                ':key_id' => $row['key_id']));
            $row['context_id'] = $PDOX->lastInsertId();
            $row['context_key'] = $post['context_id'];  // We rename this for all internal structures...
            $row['context_title'] = $post['context_title'];
            $row['context_settings_url'] = $context_settings_url;
            $actions[] = "=== Inserted context id=".$row['context_id']." ".$row['context_title'];
        }

        // if we didn't get context_id from post, we can't update lti_link either
        if ( $row['link_id'] === null && $row['context_id'] !== null && isset($post['link_id']) ) {
            $sql = "INSERT INTO {$p}lti_link
                ( link_key, link_sha256, settings_url, title, context_id, path, created_at, updated_at ) VALUES
                    ( :link_key, :link_sha256, :settings_url, :title, :context_id, :path, NOW(), NOW() )
                    ON DUPLICATE KEY UPDATE updated_at = NOW();";
            $link_settings_url = U::get($post, 'link_settings_url', null);
            $PDOX->queryDie($sql, array(
                ':link_key' => $post['link_id'],
                ':link_sha256' => lti_sha256($post['link_id']),
                ':settings_url' => $link_settings_url,
                ':title' => $post['link_title'],
                ':context_id' => $row['context_id'],
                ':path' => $post['link_path']
            ));
            $row['link_id'] = $PDOX->lastInsertId();
            $row['link_title'] = $post['link_title'];
            $row['link_settings_url'] = $link_settings_url;
            $row['link_path'] = $post['link_path'];
            $actions[] = "=== Inserted link id=".$row['link_id']." ".$row['link_title'];
        }

        $user_displayname = isset($post['user_displayname']) ? $post['user_displayname'] : null;
        $user_email = isset($post['user_email']) ? $post['user_email'] : null;
        $user_image = isset($post['user_image']) ? $post['user_image'] : null;
        $user_locale = isset($post['user_locale']) ? $post['user_locale'] : null;
        $post_user_subject = isset($post['user_subject']) ? $post['user_subject'] : null;

        // $row['user_id'] is the primary key of the row
        // $post['user_id'] is the user_id from the launch
        // $post['user_subject'] is the user_subject from the launch
        // $row['subject_key'] is the subject from the row
        if ( $row['user_id'] === null && isset($post['user_id']) && U::isNotEmpty($post['user_id']) ) {
            $sql = "INSERT INTO {$p}lti_user
                /*PDOX pk: user_id lk: user_sha256,key_id */
                ( user_key, user_sha256, displayname, email, image, locale, key_id, created_at, updated_at ) VALUES
                ( :user_key, :user_sha256, :displayname, :email, :image, :locale, :key_id, NOW(), NOW() )
                ON DUPLICATE KEY UPDATE updated_at = NOW();";
            $PDOX->queryDie($sql, array(
                ':user_key' => $post['user_id'],
                ':user_sha256' => lti_sha256($post['user_id']),
                ':displayname' => $user_displayname,
                ':email' => $user_email,
                ':image' => $user_image,
                ':locale' => $user_locale,
                ':key_id' => $row['key_id']));
            $row['user_id'] = $PDOX->lastInsertId();
            $row['user_email'] = $user_email;
            $row['user_displayname'] = $user_displayname;
            $row['user_image'] = $user_image;
            $row['user_locale'] = $user_locale;
            $row['user_key'] = $post['user_id'];

            $actions[] = "=== Inserted user id=".$row['user_id']." ".$row['user_email'];

            // TODO: Combine this into the previous query after user_subject migrations run - 28-May-2019
            if ( U::isNotEmpty($post_user_subject) ) {
                $sql = "UPDATE {$p}lti_user SET
                    subject_key = :subject_key,
                    subject_sha256 = :subject_sha256,
                    updated_at = NOW()
                    WHERE user_id = :UID";

                $stmt = $PDOX->queryReturnError($sql, array(
                    ':UID' => $row['user_id'],
                    ':subject_key' => $post_user_subject,
                    ':subject_sha256' => lti_sha256($post_user_subject),
                    )
                );

                if ( $stmt->success ) {
                    $row['subject_key'] = $post_user_subject;
                    $actions[] = "=== Added subject to new user id=".$row['user_id']." ".$row['user_email'];
                } else {
                    error_log("Unable to update user_subject - please upgrade database email=".$row['user_email']);
                }
            }
        }

        // An LTI 1.3 launch with a subject and no legacy user_id
        $lti11_transition_user_id = isset($post['lti11_transition_user_id']) ? $post['lti11_transition_user_id'] : null;
        $lti11_transition_user_id_sha256 = $lti11_transition_user_id === null ? null : lti_sha256($lti11_transition_user_id);
        if ( $row['user_id'] === null && U::isNotEmpty($post_user_subject) ) {
            $sql = "INSERT INTO {$p}lti_user
                /*PDOX pk: user_id lk: subject_sha256,user_sha256,key_id */
                ( user_key, user_sha256, subject_key, subject_sha256, displayname, email, image, locale, key_id, created_at, updated_at ) VALUES
                ( :user_key, :user_sha256, :subject_key, :subject_sha256, :displayname, :email, :image, :locale, :key_id, NOW(), NOW() )
                ON DUPLICATE KEY UPDATE
                user_id=LAST_INSERT_ID(user_id), updated_at = NOW();";
            $PDOX->queryDie($sql, array(
                ':user_key' => $lti11_transition_user_id,
                ':user_sha256' => $lti11_transition_user_id_sha256,
                ':subject_key' => $post_user_subject,
                ':subject_sha256' => lti_sha256($post_user_subject),
                ':displayname' => $user_displayname,
                ':email' => $user_email,
                ':image' => $user_image,
                ':locale' => $user_locale,
                ':key_id' => $row['key_id']));
            $row['user_id'] = $PDOX->lastInsertId();
            $row['user_email'] = $user_email;
            $row['user_displayname'] = $user_displayname;
            $row['user_image'] = $user_image;
            $row['user_locale'] = $user_locale;
            $row['subject_key'] = $post_user_subject;

            $actions[] = "=== Inserted LTI1.3 user id=".$row['user_id']." ".$row['subject_key'];
        }

        // If we have a user subject and all of a we get a new transition id, keep it
        // Note that we will forever check for errors because of duplicate key possibilities
        if ( $row['user_id'] > 0 && U::isNotEmpty($post_user_subject) && U::isNotEmpty($lti11_transition_user_id) &&
            $row['user_key'] !=  $lti11_transition_user_id) {
            $sql = "UPDATE {$p}lti_user
                SET user_key = :user_key, user_sha256 = :user_sha256, updated_at = NOW()
                WHERE user_id = :uid";
            $stmt = $PDOX->queryReturnError($sql, array(
                ':uid' => $row['user_id'],
                ':user_key' => $lti11_transition_user_id,
                ':user_sha256' => $lti11_transition_user_id_sha256,
                )
            );

            if ( $stmt->success ) {
                $row['user_key'] = $lti11_transition_user_id;
                $actions[] = "=== Updated user_key for id=".$row['user_id']." subject=".$post_user_subject.
                " transition_user_uid=".$lti11_transition_user_id;
            } else {
                error_log("Unable to update user_key - transition problem subject=$post_user_subject user_id=".$row['user_id']);
            }
        }

        // If we already have a user_key and we just got a new post_user_subject
        // Always check and log errors in case the transition went badly and then was reconfigured
        $row_subject_key = U::get($row, 'subject_key');
        if ( $row['user_id'] > 0 && U::isNotEmpty($post_user_subject) && $post_user_subject != $row_subject_key ) {
            $sql = "UPDATE {$p}lti_user
                SET subject_key = :subject_key, subject_sha256 = :subject_sha256, updated_at = NOW()
                WHERE user_id = :uid";
            $stmt = $PDOX->queryReturnError($sql, array(
                ':uid' => $row['user_id'],
                ':subject_key' => $post_user_subject,
                ':subject_sha256' => lti_sha256($post_user_subject),
                )
            );
            if ( $stmt->success ) {
                $row['subject_key'] = $post_user_subject;
                $actions[] = "=== Updated subject_key for id=".$row['user_id']." post_user_subject=".$post_user_subject;
            } else {
                error_log("Unable to update subject_key - transition problems post_user_subject=".$post_user_subject." user_id=".$row['user_id']);
            }
        }

        if ( $row['membership_id'] === null && $row['context_id'] !== null && $row['user_id'] !== null ) {
            $sql = "INSERT INTO {$p}lti_membership
                ( context_id, user_id, role, created_at, updated_at ) VALUES
                ( :context_id, :user_id, :role, NOW(), NOW() )
                ON DUPLICATE KEY UPDATE updated_at = NOW();";
            $PDOX->queryDie($sql, array(
                ':context_id' => $row['context_id'],
                ':user_id' => $row['user_id'],
                ':role' => $post['role']));
            $row['membership_id'] = $PDOX->lastInsertId();
            $row['role'] = $post['role'];
            $actions[] = "=== Inserted membership id=".$row['membership_id']." role=".$row['role'].
                " user=".$row['user_id']." context=".$row['context_id'];
        }

        if ( isset($post['service'])) {
            // We need to handle the case where the service URL changes but we already have a sourcedid
            // This is for LTI 1.x only as service is not used for LTI 2.x
            $oldserviceid = $row['service_id'];
            if ( $row['service_id'] === null && $post['service'] ) {
                $sql = "INSERT INTO {$p}lti_service
                    ( service_key, service_sha256, key_id, created_at, updated_at ) VALUES
                    ( :service_key, :service_sha256, :key_id, NOW(), NOW() )
                    ON DUPLICATE KEY UPDATE
                    service_id=LAST_INSERT_ID(service_id), updated_at = NOW();";
                $PDOX->queryDie($sql, array(
                    ':service_key' => $post['service'],
                    ':service_sha256' => lti_sha256($post['service']),
                    ':key_id' => $row['key_id']));
                $row['service_id'] = $PDOX->lastInsertId();
                $row['service'] = $post['service'];
                $actions[] = "=== Inserted service id=".$row['service_id']." ".$post['service'];
            }

            // If we just created a new service entry but we already had a result entry, update it
            // This is for LTI 1.x only as service is not used for LTI 2.x
            if ( $oldserviceid === null && $row['result_id'] !== null && $row['service_id'] !== null && $post['service'] ) {
                $sql = "UPDATE {$p}lti_result SET service_id = :service_id, updated_at = NOW() WHERE result_id = :result_id";
                $PDOX->queryDie($sql, array(
                    ':service_id' => $row['service_id'],
                    ':result_id' => $row['result_id']));
                $actions[] = "=== Updated result id=".$row['result_id']." service=".$row['service_id'];
            }
        }

        // We always insert a result row if we have a link - we will store
        // grades locally in this row - even if we cannot send grades
        if ( $row['result_id'] === null && $row['link_id'] !== null && $row['user_id'] !== null ) {
            $sql = "INSERT INTO {$p}lti_result
                ( link_id, user_id, created_at, updated_at ) VALUES
                ( :link_id, :user_id, NOW(), NOW() )
                ON DUPLICATE KEY UPDATE updated_at = NOW();";
            $PDOX->queryDie($sql, array(
                ':link_id' => $row['link_id'],
                ':user_id' => $row['user_id']));
            $row['result_id'] = $PDOX->lastInsertId();
            $actions[] = "=== Inserted result id=".$row['result_id'];
       }

        // Set these values to null if they were not in the post
        if ( ! isset($post['sourcedid']) ) $post['sourcedid'] = null;
        if ( ! isset($post['service']) ) $post['service'] = null;
        if ( ! isset($post['result_url']) ) $post['result_url'] = null;
        if ( ! isset($post['lti13_lineitem']) ) $post['lti13_lineitem'] = null;
        if ( ! isset($post['lti13_membership_url']) ) $post['lti13_membership_url'] = null;
        if ( ! isset($post['lti13_lineitems']) ) $post['lti13_lineitems'] = null;
        if ( ! isset($row['service']) ) {
            $row['service'] = null;
            $row['service_id'] = null;
        }

        // Here we handle updates to sourcedid or result_url including if we
        // just inserted the result row
        if ( $row['result_id'] != null &&
            ($post['sourcedid'] != $row['sourcedid'] || $post['result_url'] != $row['result_url'] ||
            $post['service'] != $row['service'] )
        ) {
            $sql = "UPDATE {$p}lti_result
                SET sourcedid = :sourcedid, result_url = :result_url, service_id = :service_id, updated_at = NOW()
                WHERE result_id = :result_id";
            $PDOX->queryDie($sql, array(
                ':result_url' => $post['result_url'],
                ':sourcedid' => $post['sourcedid'],
                ':service_id' => $row['service_id'],
                ':result_id' => $row['result_id']));
            $row['sourcedid'] = $post['sourcedid'];
            $row['service'] = $post['service'];
            $row['result_url'] = $post['result_url'];
            $actions[] = "=== Updated result id=".$row['result_id']." result_url=".$row['result_url'].
                " sourcedid=".$row['sourcedid']." service_id=".$row['service_id'];
        }

        // Here we handle lti13_lineitem
        if ( isset($row['link_id']) && isset($post['lti13_lineitem']) &&
            array_key_exists('lti13_lineitem',$row) && $post['lti13_lineitem'] != $row['lti13_lineitem'] ) {
            $sql = "UPDATE {$p}lti_link
                SET lti13_lineitem = :lti13_lineitem, updated_at = NOW()
                WHERE link_id = :link_id";
            $PDOX->queryDie($sql, array(
                ':lti13_lineitem' => $post['lti13_lineitem'],
                ':link_id' => $row['link_id']));
            $row['lti13_lineitem'] = $post['lti13_lineitem'];
            $actions[] = "=== Updated result id=".$row['result_id']." lti13_lineitem=".$row['lti13_lineitem'];
        }

        // Here we handle lti13_membership_url
        if ( isset($row['context_id']) && isset($post['lti13_membership_url']) &&
            array_key_exists('lti13_membership_url',$row) && $post['lti13_membership_url'] != $row['lti13_membership_url'] ) {
            $sql = "UPDATE {$p}lti_context
                SET lti13_membership_url = :lti13_membership_url, updated_at = NOW()
                WHERE context_id = :context_id";
            $PDOX->queryDie($sql, array(
                ':lti13_membership_url' => $post['lti13_membership_url'],
                ':context_id' => $row['context_id']));
            $row['lti13_membership_url'] = $post['lti13_membership_url'];
            $actions[] = "=== Updated result id=".$row['result_id']." lti13_membership_url=".$row['lti13_membership_url'];
        }

        // Here we handle lti13_lineitems
        if ( isset($row['context_id']) && isset($post['lti13_lineitems']) &&
            array_key_exists('lti13_lineitems',$row) && $post['lti13_lineitems'] != $row['lti13_lineitems'] ) {
            $sql = "UPDATE {$p}lti_context
                SET lti13_lineitems = :lti13_lineitems, updated_at = NOW()
                WHERE context_id = :context_id";
            $PDOX->queryDie($sql, array(
                ':lti13_lineitems' => $post['lti13_lineitems'],
                ':context_id' => $row['context_id']));
            $row['lti13_lineitems'] = $post['lti13_lineitems'];
            $actions[] = "=== Updated result id=".$row['result_id']." lti13_lineitems=".$row['lti13_lineitems'];
        }

        // Here we handle updates to context_title, link_title, user_displayname, user_email, or role
        if ( isset($row['context_id']) && isset($post['context_title']) && $post['context_title'] != $row['context_title'] ) {
            $sql = "UPDATE {$p}lti_context SET title = :title, updated_at = NOW() WHERE context_id = :context_id";
            $PDOX->queryDie($sql, array(
                ':title' => $post['context_title'],
                ':context_id' => $row['context_id']));
            $row['context_title'] = $post['context_title'];
            $actions[] = "=== Updated context=".$row['context_id']." title=".$post['context_title'];
        }

        // Grab the context scoped service URLs...
        $context_services = array('ext_memberships_id', 'ext_memberships_url', 'lineitems_url', 'memberships_url');
        if ( isset($row['context_id']) ) {
            foreach($context_services as $context_service ) {
                if ( isset($post[$context_service]) && $post[$context_service] != $row[$context_service] ) {
                    $sql = "UPDATE {$p}lti_context SET {$context_service} = :value, updated_at = NOW()
                        WHERE context_id = :context_id";
                    $PDOX->queryDie($sql, array(
                        ':value' => $post[$context_service],
                        ':context_id' => $row['context_id']));
                    $row[$context_service] = $post[$context_service];
                    $actions[] = "=== Updated context=".$row['context_id']." {$context_service}=".$post[$context_service];
                }
            }
        }

        if ( isset($row['link_id']) && isset($post['link_title']) && $post['link_title'] != $row['link_title'] ) {
            $sql = "UPDATE {$p}lti_link SET title = :title, updated_at = NOW() WHERE link_id = :link_id";
            $PDOX->queryDie($sql, array(
                ':title' => $post['link_title'],
                ':link_id' => $row['link_id']));
            $row['link_title'] = $post['link_title'];
            $actions[] = "=== Updated link=".$row['link_id']." title=".$post['link_title'];
        }

        if ( isset($row['link_id']) && isset($post['link_path']) && $post['link_path'] != $row['link_path'] ) {
            $sql = "UPDATE {$p}lti_link SET path = :path, updated_at = NOW() WHERE link_id = :link_id";
            $PDOX->queryDie($sql, array(
                ':path' => $post['link_path'],
                ':link_id' => $row['link_id']));
            $row['link_path'] = $post['link_path'];
            $actions[] = "=== Updated link=".$row['link_id']." path=".$post['link_path'];
        }

        // Grab the user scoped fields...
        $user_fields = array('displayname', 'email', 'image', 'locale');
        if ( isset($row['user_id']) ) {
            foreach($user_fields as $u_field ) {
                $user_field = 'user_'.$u_field;
                if ( isset($post[$user_field]) && $post[$user_field] != $row[$user_field] && U::isNotEmpty($post[$user_field]) ) {
                    $sql = "UPDATE {$p}lti_user SET {$u_field} = :value, updated_at = NOW() WHERE user_id = :user_id";
                    $PDOX->queryDie($sql, array(
                        ':value' => $post[$user_field],
                        ':user_id' => $row['user_id']));
                    $row[$user_field] = $post[$user_field];
                    $actions[] = "=== Updated user=".$row['user_id']." {$user_field}=".$post[$user_field];
                }
            }
        }

        if ( isset($row['membership_id']) && isset($post['role']) && $post['role'] != $row['role'] ) {
            $sql = "UPDATE {$p}lti_membership SET role = :role, updated_at = NOW() WHERE membership_id = :membership_id";
            $PDOX->queryDie($sql, array(
                ':role' => $post['role'],
                ':membership_id' => $row['membership_id']));
            $row['role'] = $post['role'];
            $actions[] = "=== Updated membership=".$row['membership_id']." role=".$post['role'];
        }

        // Restore ERRMODE
        $PDOX->setAttribute(\PDO::ATTR_ERRMODE, $errormode);
        return $actions;
    }

    /**
     * Patch the value for the list of needed features
     *
     * Note - causes no harm if called more than once.
     */
    private static function patchNeeded($needed) {
        if ( $needed == self::NONE ) $needed = array();
        if ( $needed == self::ALL ) {
            $needed = array(self::CONTEXT, self::LINK, self::USER);
        }
        if ( is_string($needed) ) $needed = array($needed);
        return $needed;
    }

    /**
     * Optionally handle launch and/or set up the LTI session and global variables
     *
     * This will set up as much of the $USER, $CONTEXT, $LINK,
     * and $RESULT data as it can including leaving them all null
     * if this is called on a request with no LTI launch and no LTI
     * data in the session.  This functions as and performs a
     * PHP session_start().
     *
     * @return Launch A Tsugi Launch object.
     */
    public static function session_start() {
        return self::requireDataPrivate(self::NONE);
    }

    /**
     * Handle launch and/or set up the LTI session and global variables
     *
     * Make sure we have the values we need in the LTI session
     * This routine will not start a session if none exists.  It will
     * die is there if no session_name() (PHPSESSID) cookie or
     * parameter.  No need to create any fresh sessions here.
     *
     * @param $needed (optional, mixed)  Indicates which of
     * the data structures are * needed. If this is omitted,
     * this assumes that CONTEXT, LINK, and USER data are required.
     * If LTIX::NONE is present, then none of the three are rquired.
     * If some combination of the three are needed, this accepts
     * an array of the LTIX::CONTEXT, LTIX: LINK, and LTIX::USER
     * can be passed in.
     *
     * @return Launch A Tsugi Launch object.
     */
    public static function requireData($needed=self::ALL) {
        return self::requireDataPrivate($needed);
    }

    /**
     * Handle the launch, but with the caller given the chance to override defaults
     *
     * @param $pdox array - aproperly initialized  PDO object.  Can be null.
     * @param $request_data array - This must merge the $_POST, $_GET,
     * and OAuth Header data (in that order - header data has highest priority).
     * See self::oauth_parameters() for the way this data is normally pulled
     * from the three sources and merged into a single array.  Can be null.
     */
    public static function requireDataOverride($needed,
        $pdox, $session_object, $current_url, $request_data)
    {
        return self::requireDataPrivate($needed,
            $pdox, $session_object, $current_url, $request_data);
    }

    /**
     * Restore an LTI session and check if it worked
     *
     * If we are using memcached with php_serialize serialization,
     * we take a wild guess that we might be having a race condition
     * with memcache.  So we wait a tic, and re-try the read.
     */
    public static function restoreLTISession($session_id) {
        global $CFG;

        // You would think that this would just work :)
        if ( session_id() == "" ) {
            session_id($session_id);
            session_start();
        }

        if ( U::get($_SESSION, 'lti') && U::get($_SESSION, 'lti_post') ) return;

        // https://stackoverflow.com/questions/35728486/read-php-session-without-actually-starting-it
        $serializer = ini_get('session.serialize_handler');
        if ( ! isset($CFG->memcached) || U::isEmpty($CFG->memcached) || $serializer != 'php_serialize') return;

        sleep(1);
        try {
            $servers = explode(',', $CFG->memcached);
            $c = count($servers);
            for ($i = 0; $i < $c; ++$i) {
                $servers[$i] = explode(':', $servers[$i]);
            }

            $memcached = new \Memcached();
            $memcached->addServers($servers);
            $sessionPrefix = ini_get('memcached.sess_prefix');

            $rawData = $memcached->get($sessionPrefix.$session_id);
            if ( ! $rawData ) {
                error_log("restoreLTISession - nothing to retrieve from memcached ".$session_id);
                return;
            }

            // Keep unserialize() from issuing a notice
            $data = $rawData ? @unserialize($rawData) : false;
            if ( ! is_array($data) ) {
                error_log("restoreLTISession - could not unserialize () ".$session_id);
                return;
            }

            if ( count($data) < 1 ) {
                error_log("restoreLTISession - empty memcached data ".$session_id);
                return;
            }

            // Copy into session
            $fields = "";
            foreach($data as $k => $v) {
                $_SESSION[$k] = $data[$k];
                if ( strlen($fields) < 50 && is_string($data[$k]) ) $fields .= ' '.$k.'='.$data[$k];
            }
            error_log("restoreLTISession copied ".count($data)." ".$session_id.$fields);
        } catch(\Exception $e) {
            error_log("restoreLTISession exception ".$e->getMessage());
        }
    }

    /**
     * Internal method to handle the data setup
     */
    public static function requireDataPrivate($needed=self::ALL,
        $pdox=null, $session_object=null, $current_url=null, $request_data=null)
    {
        global $CFG, $TSUGI_LAUNCH;
        global $OUTPUT, $USER, $CONTEXT, $LINK, $RESULT, $ROSTER;

        // Always mark the browser
        $browser_mark = self::getBrowserMark();

        if ( $request_data == null ) $request_data = self::oauth_parameters();

        $TSUGI_LAUNCH = new \Tsugi\Core\Launch();
        if ( isset($OUTPUT) && is_object($OUTPUT) && get_class($OUTPUT) == 'Tsugi\UI\Output' ) {
            $TSUGI_LAUNCH->output = $OUTPUT;
            $OUTPUT->launch = $TSUGI_LAUNCH;
        } else {
            $OUTPUT = new \Tsugi\UI\Output();
            $TSUGI_LAUNCH->output = $OUTPUT;
            $OUTPUT->launch = $TSUGI_LAUNCH;
        }

        $USER = null;
        $CONTEXT = null;
        $LINK = null;
        $RESULT = null;
        $ROSTER = null;

        // Make sure to initialize the global connection object
        if ( $pdox === null ) {
            $PDOX = self::getConnection();
        } else {
            $PDOX = $pdox;
        }
        $TSUGI_LAUNCH->pdox = $PDOX;

        $needed = self::patchNeeded($needed);

        // Check to see if this is LTI Launch Authorization Flow
        if ( self::launchAuthorizationFlow($request_data) ) return;

        // Check if we are processing an LTI launch.  If so, handle it
        $newlaunch = self::launchCheck($needed, $session_object, $request_data);
        $detail = $newlaunch;  // If we got a string back, save it
        $newlaunch = $newlaunch === true;   // A string is "false"

        // If launchCheck comes back with a true, it means someone above us
        // needs to do the redirect
        if ( $newlaunch ) {
            return $TSUGI_LAUNCH;
        }

        // Check to see if the session already exists.
        if ( $session_object === null ) {
            $sess = session_name();
            if ( ini_get('session.use_cookies') == '0' ) {
                if ( $newlaunch || isset($_POST[$sess]) || isset($_GET[$sess]) ) {
                    $session_id = $_POST[$sess] ?? $_GET[$sess] ?? null;
                    // Do our best to restore a session
                    if ( $session_id ) self::restoreLTISession($session_id);
                } else {
                    self::wrapped_session_flush($session_object);
                    self::send403();
                    $msg = 'This tool should be launched from a learning system using LTI';
                    if ( is_string($detail) ) $msg .= ". Detail: ".$detail;
                    self::abort_with_error_log($msg,
                        U::get($_SERVER, 'HTTP_REFERER', Net::getIP())
                    );
                }
            }

            // Start a session if it has not been started..
            if ( session_id() == "" ) {
                session_start();  // Should reassociate
            }
        }

        // This happens from time to time when someone closes and reopens a laptop
        // Or their computer goes to sleep and wakes back up hours later.
        // So it is just a warning - nothing much we can do except tell them.
        if ( count($needed) > 0 && self::wrapped_session_get($session_object, 'lti',null) === null ) {
            self::wrapped_session_flush($session_object);
            self::abort_with_error_log('Session expired - please re-launch '.session_id(),
                U::get($_SERVER, 'HTTP_REFERER', Net::getIP())
            );
        }

        // Check the referrer...
        $trusted = $session_object != null || self::checkReferer() || self::checkCSRF();

        // Check to see if we switched browsers or IP addresses
        // TODO: Change these to warnings once we get more data
        $session_agent = self::wrapped_session_get($session_object, 'HTTP_USER_AGENT', null);
        if ( (!$trusted) && $session_agent != null ) {
            if ( (!isset($_SERVER['HTTP_USER_AGENT'])) ||
                $_SERVER['HTTP_USER_AGENT'] != $session_agent ) {
                self::wrapped_session_flush($session_object);
                self::send403();
                self::abort_with_error_log("Session has expired", " ".session_id()." HTTP_USER_AGENT ".
                    (($session_agent !== null ) ? $session_agent : 'Empty Session user agent') .
                    ' ::: '.
                    (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Empty browser user agent'),
                false,'DIE:');
            }
        }

        // Check our list of IP address history
        // TODO: decrypt
        $iphistory = U::get($_COOKIE, "TSUGI-HISTORY");

        // We only check the first three octets as some systems wander through the addresses on
        // class C - Perhaps it is even NAT - who knows - but we forgive those on the same Class C
        $session_addr = self::wrapped_session_get($session_object, 'REMOTE_ADDR', null);
        $ipaddr = Net::getIP();
        $browser_mark = self::getBrowserMark();
        $session_browser_mark = self::wrapped_session_get($session_object, 'BROWSER_MARK', null);
        if ( (!$trusted) &&  $session_addr && $ipaddr &&
            Net::isRoutable($session_addr) && Net::isRoutable($ipaddr) ) {
            $sess_pieces = explode('.',$session_addr);
            $serv_pieces = explode('.',$ipaddr);
            if ( count($sess_pieces) == 4 && count($serv_pieces) == 4 ) {
                if ( $sess_pieces[0] != $serv_pieces[0] || $sess_pieces[1] != $serv_pieces[1] ||
                    $sess_pieces[2] != $serv_pieces[2] ) {
                    if ( strpos($iphistory, $session_addr) !== false ) {
                        error_log("IP Address changed, session_addr=".  $session_addr.' current='.$ipaddr." but trusting iphistory=".$iphistory);
                        self::wrapped_session_put($session_object, 'REMOTE_ADDR', $ipaddr);
                        // Add new IP Address to the Tsugi IP History if it is not there
                        if ( strpos($iphistory, $ipaddr) === false ) {
                            $iphistory .= '!' . $ipaddr;
                            // TODO: encrypt
                            setcookie('TSUGI-HISTORY',$iphistory, 0, '/');
                        }

                    } else if ( $browser_mark == $session_browser_mark ) {
                        error_log("IP address change, trusting browser mark ".$browser_mark);
                        self::wrapped_session_put($session_object, 'REMOTE_ADDR', $ipaddr);
                    } else {
                        // Need to clear out session data
                        self::wrapped_session_flush($session_object);
                        self::send403();
                        self::abort_with_error_log('Session address has expired', " ".session_id()." session_addr=".
                            $session_addr.' current='.$ipaddr.' iphistory='.$iphistory, 'DIE:');
                    }
                }
            }
        }

        // Check to see if the user has navigated to a new place in the hierarchy
        $session_script = self::wrapped_session_get($session_object, 'script_path', null);
        if ( $session_script !== null &&
            (! endsWith(Output::getUtilUrl(''), $CFG->getScriptPath()) ) &&
            (! startsWith('api', $CFG->getScriptPath()) ) &&
            strpos($CFG->getScriptPath(), $session_script ) !== 0 ) {
            self::wrapped_session_flush($session_object);
            self::send403();
            self::abort_with_error_log('Improper navigation detected', " ".session_id()." script_path ".
                $session_script.' /  '.$CFG->getScriptPath(), 'DIE:');
        }

        // Check to see if the session needs to be extended due to this request
        self::checkHeartBeat($session_object);

        // Restart the number of continuous heartbeats
        self::wrapped_session_put($session_object, 'HEARTBEAT_COUNT', 0);

        // We don't have any launch data and don't need it
        $LTI = self::wrapped_session_get($session_object, 'lti', null);
        if ( count($needed) == 0 && $LTI === null ) {
            return $TSUGI_LAUNCH;
        }

        if ( is_array($needed) ) {
            foreach ( $needed as $feature ) {
                if ( isset($LTI[$feature]) ) continue;
                self::abort_with_error_log("This tool requires an LTI launch parameter:".$feature);
            }
        }

        return self::buildLaunch($LTI, $session_object);
    }

    public static function buildLaunch($LTI, $session_object=null) {
        global $CFG, $TSUGI_LAUNCH, $TSUGI_KEY;
        global $OUTPUT, $USER, $CONTEXT, $LINK, $RESULT, $ROSTER;

        if ( ! isset($TSUGI_LAUNCH) ) {
            $TSUGI_LAUNCH = new \Tsugi\Core\Launch();
        }

        // Populate the $USER $CONTEXT and $LINK objects
        if ( isset($LTI['user_id']) && ! is_object($USER) ) {
            $USER = new \Tsugi\Core\User();
            $USER->launch = $TSUGI_LAUNCH;
            $USER->id = $LTI['user_id'];
            $USER->key = $LTI['user_key'];
            if (isset($LTI['user_email']) ) $USER->email = $LTI['user_email'];
            if (isset($LTI['user_displayname']) ) {
                $USER->displayname = $LTI['user_displayname'];
                $pieces = explode(' ',$USER->displayname);
                if ( count($pieces) > 0 ) $USER->firstname = $pieces[0];
                if ( count($pieces) > 1 ) $USER->lastname = $pieces[count($pieces)-1];
            }
            if (isset($LTI['user_image']) ) $USER->image = $LTI['user_image'];
            if (isset($LTI['user_locale']) ) $USER->locale = $LTI['user_locale'];
            if ( $USER->locale ) {
                I18N::setLocale($USER->locale);
            }
            $USER->instructor = isset($LTI['role']) && $LTI['role'] != 0 ;
            $USER->admin = isset($LTI['role']) && $LTI['role'] >= self::ROLE_ADMINISTRATOR;
            $TSUGI_LAUNCH->user = $USER;
        }

        $TSUGI_LAUNCH->for_user = null;
        if ( isset($LTI['for_user_id']) ) {
            $for_user = new \Tsugi\Core\User();
            $for_user->launch = $TSUGI_LAUNCH;
            $for_user->id = $LTI['for_user_id'];
            $for_user->key = $LTI['for_user_key'];
            if (isset($LTI['for_user_email']) ) $for_user->email = $LTI['for_user_email'];
            if (isset($LTI['for_user_displayname']) ) {
                $for_user->displayname = $LTI['for_user_displayname'];
                $pieces = explode(' ',$for_user->displayname);
                if ( count($pieces) > 0 ) $for_user->firstname = $pieces[0];
                if ( count($pieces) > 1 ) $for_user->lastname = $pieces[count($pieces)-1];
            }
            if (isset($LTI['for_user_image']) ) $for_user->image = $LTI['for_user_image'];
            if (isset($LTI['for_user_locale']) ) $for_user->locale = $LTI['for_user_locale'];
            if ( $for_user->locale ) {
                I18N::setLocale($for_user->locale);
            }
            $for_user->instructor = isset($LTI['for_user_role']) && $LTI['for_user_role'] != 0 ;
            $for_user->admin = isset($LTI['for_user_role']) && $LTI['for_user_role'] >= self::ROLE_ADMINISTRATOR;
            $TSUGI_LAUNCH->for_user = $for_user;
        }

        if ( isset($LTI['key_id']) && ! is_object($TSUGI_KEY) ) {
            $TSUGI_KEY = new \Tsugi\Core\Key();
            $TSUGI_KEY->launch = $TSUGI_LAUNCH;
            $TSUGI_KEY->id = $LTI['key_id'];
            if (isset($LTI['key_title']) ) $TSUGI_KEY->title = $LTI['key_title'];
            if (isset($LTI['key_key']) ) $TSUGI_KEY->key = $LTI['key_key'];
            if (isset($LTI['secret']) ) $TSUGI_KEY->secret = $LTI['secret'];
            $TSUGI_LAUNCH->key = $TSUGI_KEY;
        }

        if ( isset($LTI['context_id']) && ! is_object($CONTEXT) ) {
            $CONTEXT = new \Tsugi\Core\Context();
            $CONTEXT->launch = $TSUGI_LAUNCH;
            $CONTEXT->id = $LTI['context_id'];
            if (isset($LTI['context_title']) ) $CONTEXT->title = $LTI['context_title'];
            if (isset($LTI['key_key']) ) $CONTEXT->key = $LTI['key_key'];
            if (isset($LTI['secret']) ) $CONTEXT->secret = $LTI['secret'];
            if (isset($LTI['context_key']) ) $CONTEXT->context_id = $LTI['context_key'];
            $TSUGI_LAUNCH->context = $CONTEXT;
        }

        if ( isset($LTI['link_id']) && ! is_object($LINK) ) {
            $LINK = new \Tsugi\Core\Link();
            $LINK->launch = $TSUGI_LAUNCH;
            $LINK->id = $LTI['link_id'];
            if (isset($LTI['link_title']) ) $LINK->title = $LTI['link_title'];
            if (isset($LTI['link_count']) ) $LINK->activity = $LTI['link_count']+0;
            if (isset($LTI['link_user_count']) ) $LINK->user_activity = $LTI['link_user_count']+0;

            // Check to see if we are supposed to use SHA256 for this link
            $settings_method = $LINK->settingsGet('oauth_signature_method');
            $post_data = self::wrapped_session_get($session_object, 'lti_post');
            $launch_method = null;
            if ( is_array($post_data) ) $launch_method = U::get($post_data, 'oauth_signature_method');

            // error_log("sm=$settings_method lm=$launch_method");
            if ( $settings_method == $launch_method ) {
                // All good
            } else if ( ! $settings_method && $launch_method == 'HMAC-SHA1' ) {
                // All good
            } else {
                // error_log("Setting oauth_signature_method=$launch_method");
                $LINK->settingsSet('oauth_signature_method', $launch_method);
            }

            // The activity (Don't make global to avoid bad habits)
            $TSUGI_LAUNCH->link = $LINK;
        }

        if ( isset($LTI['result_id']) && ! is_object($RESULT) ) {
            $RESULT = new \Tsugi\Core\Result();
            $RESULT->launch = $TSUGI_LAUNCH;
            $RESULT->id = $LTI['result_id'];
            if (isset($LTI['grade']) ) $RESULT->grade = $LTI['grade'];
            $TSUGI_LAUNCH->result = $RESULT;
        }

        if ( $TSUGI_LAUNCH->ltiRawParameter(LTIConstants::EXT_CONTEXT_MEMBERSHIP_ID) && ! is_object($ROSTER)) {
            $ROSTER = new \Tsugi\Core\Roster();
            $ROSTER->id = $TSUGI_LAUNCH->ltiRawParameter(LTIConstants::EXT_CONTEXT_MEMBERSHIP_ID);
            $ROSTER->url = $TSUGI_LAUNCH->ltiRawParameter(LTIConstants::EXT_CONTEXT_MEMBERSHIP_URL);
        }

        if ( isset($LTI['lti13_deeplink']) && is_object($LTI['lti13_deeplink']) ) {
            $TSUGI_LAUNCH->deeplink = new DeepLinkRequest($LTI['lti13_deeplink']);
        }

        // Return the Launch structure
        return $TSUGI_LAUNCH;
    }

    /**
     * Handle the optional LTI pre 1.3 Launch Authorization flow
     */
    public static function launchAuthorizationFlow($request_data)
    {
        global $CFG, $PDOX;

        $key = U::get($request_data, 'oauth_consumer_key');
        if ( ! $key ) return false;
        if ( U::get($request_data, 'oauth_signature_method') && U::get($request_data, 'oauth_timestamp') &&
            U::get($request_data, 'oauth_nonce') && U::get($request_data, 'oauth_version') &&
            U::get($request_data, 'oauth_signature') ) {
            // pass
        } else {
            return false;
        }

        // Handle second half of the pre LTI 1.3 Launch Authorization Flow
        $tool_state = U::get($request_data, 'tool_state');
        $my_tool_state = self::getBrowserSignature();
        if ( $tool_state && $tool_state != $my_tool_state ) {
            error_log("Mismatch tool_state, Incoming state $tool_state computed $my_tool_state");
            self::abort_with_error_log('Mismatched tool_state');
        }

        // Handle first half of the pre LTI 1.3 Launch Authorization Flow
        $relaunch_url = U::get($request_data, 'relaunch_url');
        if ( ! $relaunch_url ) return false;

        $key_sha256 = U::lti_sha256($key);
        $row = $PDOX->rowDie("SELECT key_id, key_key, secret
            FROM {$CFG->dbprefix}lti_key WHERE key_sha256 = :SHA",
            array(':SHA' => $key_sha256) );
        if ( ! $row ) {
            self::abort_with_error_log('Could not load oauth_consumer_key');
        }

        $valid = LTI::verifyKeyAndSecret($key,$row['secret'],self::curPageUrl(), $request_data);
        if ( $valid !== true ) {
            self::abort_with_error_log('OAuth validation fail key='.$key.' error='.$valid[0],$valid[1]);
        }

        $platform_state = U::get($request_data, 'platform_state');
        if ( $platform_state) $relaunch_url = U::add_url_parm($relaunch_url, "platform_state", $platform_state);
        $tool_state = self::getBrowserSignature();
        $relaunch_url = U::add_url_parm($relaunch_url, "tool_state", $tool_state);
        echo("Relaunching ".$relaunch_url);

        if ( headers_sent() ) {
            echo('<p><a href="'.$relaunch_url.'">Click to continue</a></p>');
        } else {
            header('Location: '.$relaunch_url);
        }
        return true;
    }

    /**
     * Dump out the internal data structures adssociated with the
     * current launch.  Best if used within a pre tag.
     */
    public static function var_dump() {
        global $USER, $CONTEXT, $LINK, $RESULT;
        echo('$USER:'."\n");
        if ( ! isset($USER) ) {
            echo("Not set\n");
        } else {
            var_dump($USER);
        }
        echo('$CONTEXT:'."\n");
        if ( ! isset($CONTEXT) ) {
            echo("Not set\n");
        } else {
            var_dump($CONTEXT);
        }
        echo('$LINK:'."\n");
        if ( ! isset($LINK) ) {
            echo("Not set\n");
        } else {
            var_dump($LINK);
        }
        echo('$RESULT:'."\n");
        if ( ! isset($RESULT) ) {
            echo("Not set\n");
        } else {
            var_dump($RESULT);
        }
        echo("\n<hr/>\n");
    }

    /**
     * Load the grade for a particular row and update our local copy (Deprecated - moved to Result)
     *
     * Call the right LTI service to retrieve the server's grade and
     * update our local cached copy of the server_grade and the date
     * retrieved. This routine pulls the key and secret from the LTIX
     * session to avoid crossing cross tenant boundaries.
     *
     * @param $row An optional array with the data that has the result_id, sourcedid,
     * and service (url) if this is not present, the data is pulled from the LTI
     * session for the current user/link combination.
     * @param $debug_log An (optional) array (by reference) that returns the
     * steps that were taken.
     * Each entry is an array with the [0] element a message and an optional [1]
     * element as some detail (i.e. like a POST body)
     *
     * @return mixed If this work this returns a float.  If not you get
     * a string with an error.
     *
     */
    public static function gradeGet($row=false, &$debug_log=false) {
        global $RESULT;
        if ( isset($RESULT) ) return $RESULT->gradeGet($row,$debug_log);
        return 'LTIX::gradeGet $RESULT not set';
    }

    /**
     * Send a grade and update our local copy (Deprecated - moved to Result)
     *
     * Call the right LTI service to send a new grade up to the server.
     * update our local cached copy of the server_grade and the date
     * retrieved. This routine pulls the key and secret from the LTIX
     * session to avoid crossing cross tenant boundaries.
     *
     * @param $grade A new grade - floating point number between 0.0 and 1.0
     * @param $row An optional array with the data that has the result_id, sourcedid,
     * and service (url) if this is not present, the data is pulled from the LTI
     * session for the current user/link combination.
     * @param $debug_log An (optional) array (by reference) that returns the
     * steps that were taken.
     * Each entry is an array with the [0] element a message and an optional [1]
     * element as some detail (i.e. like a POST body)
     *
     * @return mixed If this works it returns true.  If not, you get
     * a string with an error.
     *
     */
    public static function gradeSend($grade, $row=false, &$debug_log=false) {
        global $RESULT;
        if ( isset($RESULT) ) return $RESULT->gradeSend($grade,$row,$debug_log);
        return 'LTIX::gradeSend $RESULT not set';
    }

    /**
     * Send a grade applying the due date logic and only increasing grades (Deprecated - moved to Result)
     *
     * Puts messages in the session for a redirect.
     *
     * @param $gradetosend - The grade in the range 0.0 .. 1.0
     * @param $oldgrade - The previous grade in the range 0.0 .. 1.0 (optional)
     * @param $dueDate - The due date for this assignment
     */
    public static function gradeSendDueDate($gradetosend, $oldgrade=false, $dueDate=false) {
        global $RESULT;
        if ( isset($RESULT) ) return $RESULT->gradeSendDueDate($gradetosend,$oldgrade,$dueDate);
        return 'LTIX::gradeSendDueDate $RESULT not set';
    }

    /**
     * signParameters - Look up the key and secret and call the underlying code in LTI
     */
    public static function signParameters($oldparms, $endpoint, $method,
        $submit_text = false, $org_id = false, $org_desc = false) {

        $oauth_consumer_key = self::ltiParameter('key_key');
        $oauth_consumer_secret = self::decrypt_secret(self::ltiParameter('secret'));

        return LTI::signParameters($oldparms, $endpoint, $method, $oauth_consumer_key, $oauth_consumer_secret,
            $submit_text, $org_id, $org_desc);
    }
    /**
      * Send settings to the LMS using the simple JSON approach
      */
    public static function settingsSend($settings, $settings_url, &$debug_log=false) {

        $key_key = self::ltiParameter('key_key');
        $secret = self::decrypt_secret(self::ltiParameter('secret'));

        $retval = LTI::sendJSONSettings($settings, $settings_url, $key_key, $secret, $debug_log);
        return $retval;
    }

    /**
     * Send a Caliper Body to the correct URL using the key and secret
     *
     * This is not yet a standard or production - it uses the Canvas
     * extension only.
     *
     */
    public static function caliperSend($caliperBody, $content_type='application/json', &$debug_log=false)
    {

        $caliperURL = LTIX::ltiRawParameter('custom_sub_canvas_xapi_url');
        if ( U::strlen($caliperURL) == 0 ) {
            if ( is_array($debug_log) ) $debug_log[] = array('custom_sub_canvas_xapi_url not found in launch data');
            return false;
        }

        $key_key = self::ltiParameter('key_key');
        $secret = self::decrypt_secret(self::ltiParameter('secret'));

        $retval = LTI::sendJSONBody("POST", $caliperBody, $content_type,
            $caliperURL, $key_key, $secret, $debug_log);
        return $retval;
    }

    /**
     * Send a JSON Body to a URL after looking up the key and secret
     *
     * @param $method The HTTP Method to use
     * @param $postBody
     * @param $content_type
     * @param $service_url
     * @param bool $debug_log
     * @return mixed
     */
    public static function jsonSend($method, $postBody, $content_type,
        $service_url, &$debug_log=false) {

        $key_key = self::ltiParameter('key_key');
        $secret = self::decrypt_secret(self::ltiParameter('secret'));

        $retval = LTI::sendJSONBody($method, $postBody, $content_type,
            $service_url, $key_key, $secret, $debug_log);
        return $retval;
    }

    /**
     * ltiLinkUrl - Returns true if we can return LTI Links for this launch
     *
     * @return string The content_item_return_url or false
     */
    public static function ltiLinkUrl($postdata=false) {
        return LTI::ltiLinkUrl(self::ltiRawPostArray());
    }

    /**
     * getKeySecretForLaunch - Retrieve a Key/Secret for a Launch
     *
     * @param $url - The url to lookup
     */
    public static function getKeySecretForLaunch($url) {
        global $CFG, $CONTEXT;

        $PDOX = self::getConnection();
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);
        $key_id = self::ltiParameter('key_id', null);
        if ( $key_id == null ) return false;

        $sql = "SELECT consumer_key, secret FROM {$CFG->dbprefix}lti_domain
            WHERE domain = :DOM AND key_id = :KID";
        $values = array(":DOM" => $host, ":KID" => $key_id);
        if ( isset($CONTEXT->id) ) {
            $sql .= " AND (context_id IS NULL OR context_id = :CID)
                ORDER BY context_id DESC";
            $values[':CID'] = $CONTEXT->id;
        }

        $row = $PDOX->rowDie($sql, $values);
        if ( $row === false ) {
            error_log("Unable to key/secret key_id=$key_id url=$url");
            return false;
        }
        $row['key'] = $row['consumer_key'];
        return $row;
    }

    /**
     * curPageUrl - Returns the URL to the currently executing script with query string
     *
     * This is useful when we want to do OAuth where we need the exact
     * incoming path but our host, protocol, and port might be messed up
     * by a proxy or CDN.
     *
     * URL                              Result
     * http://x.com/data                http://x.com/data
     * http://x.com/data/index.php      http://x.com/data/index.php
     * http://x.com/data/index.php?y=1  http://x.com/data/index.php?y=1
     */
    public static function curPageUrl() {
        return self::curPageUrlBase() .  $_SERVER['REQUEST_URI'];
    }

    /**
     * curPageUrlNoQuery - Returns the URL to the currently executing query without query string
     *
     * URL                              Result
     * http://x.com/data                http://x.com/data
     * http://x.com/data/keys           http://x.com/data/keys
     * http://x.com/data/keys?x=1       http://x.com/data/keys
     */
    public static function curPageUrlNoQuery() {
        return self::removeQueryString(self::curPageUrlBase() .  $_SERVER['REQUEST_URI']);
    }

    /**
     * removeQueryString - Drop a query string from a url
     */
    public static function removeQueryString($url) {
        $pos = strpos($url, '?');
        if ( $pos === false ) return $url;
        $url = substr($url,0,$pos);
        return $url;
    }

    /**
     * curPageUrlFolder - Returns the URL to the folder currently executing
     *
     * This is useful when rest-style files want to link back to "index.php"
     * Note - this will not go up to a parent.
     *
     * URL                              Result
     * http://x.com/data/               http://x.com/data/
     * http://x.com/data/keys           http://x.com/data/
     */
    public static function curPageUrlFolder() {
        $folder = self::curPageUrlBase() .  $_SERVER['REQUEST_URI'];
        $folder = self::removeQueryString($folder);
        if ( preg_match('/\/$/', $folder) ) return $folder;
        return dirname($folder);
    }

    /**
     * curPageUrlScript - Returns the URL to the currently executing script
     *
     * This is useful when we want to make a URL to another script at this location.
     * Often we use this with str_replace().
     *
     *     URL                              Result
     *     http://x.com/data                http://x.com/data/index.php
     *     http://x.com/data/index.php      http://x.com/data/index.php
     *     http://x.com/data/index.php?y=1  http://x.com/data/index.php
     *
     *      http://stackoverflow.com/questions/279966/php-self-vs-path-info-vs-script-name-vs-request-uri
     *
     *      http://example.com/bob
     *      REQUEST_URI = /bob
     *      PHP_SELF = /bob/index.php
     */
    public static function curPageUrlScript() {
        return self::curPageUrlBase() .  $_SERVER['PHP_SELF'];
    }

    /**
     * curPageUrlBase - Returns the protocol, host, and port for the current URL
     *
     * This is useful when we are running behind a proxy like ngrok
     * or CloudFlare.  These proxies will accept with the http or
     * https version of the URL but our web server will likely only
     * se the incoming request as http.  So we need to fall back
     * to $CFG->wwwroot and reconstruct the right URL from there.
     * Since the wwwroot might have some of the request URI, like
     *
     *     http://tsugi.ngrok.com/tsugi
     *
     * We need to parse the wwwroot and put things back together.
     *
     * URL                              Result
     * http://x.com/data                http://x.com
     * http://x.com/data/index.php      http://x.com
     * http://x.com/data/index.php?y=1  http://x.com
     *
     * @return string The current page protocol, host, and optionally port URL
     */

    public static function curPageUrlBase() {
        global $CFG;

        $pieces = parse_url($CFG->wwwroot);

        if ( isset($pieces['scheme']) ) {
            $scheme = $pieces['scheme'];
        } else {
            $scheme = (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on")
                ? 'http' : 'https';
        }

        if ( isset($pieces['port']) ) {
            $port = ':'.$pieces['port'];
        } else {
            $port = '';
            if ( $_SERVER['SERVER_PORT'] != "80" && $_SERVER['SERVER_PORT'] != "443" &&
                strpos(':', $_SERVER['HTTP_HOST']) < 0 ) {
                $port =  ':' . $_SERVER['SERVER_PORT'] ;
            }
        }
        $host = isset($pieces['host']) ? $pieces['host'] : $_SERVER['HTTP_HOST'];

        $http_url = $scheme .  '://' . $host .  $port;
        return $http_url;
    }

    // See if we need to extend our session (heartbeat)
    // http://stackoverflow.com/questions/520237/how-do-i-expire-a-php-session-after-30-minutes
    private static function checkHeartBeat($session_object=null) {
        global $CFG;

        if ( session_id() == "" ) return;  // This should not start the session

        if ( isset($CFG->sessionlifetime) ) {
            if (self::wrapped_session_get($session_object,'LAST_ACTIVITY') ) {
                $heartbeat = $CFG->sessionlifetime/4;
                $ellapsed = time() - self::wrapped_session_get($session_object,'LAST_ACTIVITY');
                if ( $ellapsed > $heartbeat ) {
                    self::wrapped_session_put($session_object,'LAST_ACTIVITY', time());
                    $filename = isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '';
                    error_log("Heartbeat ".session_id().' '.$ellapsed.' '.$filename);
                }
            } else {
                self::wrapped_session_put($session_object,'LAST_ACTIVITY', time());
            }
        }
    }

    private static function send403() {
        header("HTTP/1.1 403 Forbidden");
    }

    // Returns true for a good referrer and false if we could not verify it
    private static function checkReferer() {
        global $CFG;
        return isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'],$CFG->wwwroot) === 0 ;
    }

    // Returns true for a good CSRF and false if we could not verify it
    private static function checkCSRF($session_object=null) {
        global $CFG;
        $token = self::wrapped_session_get($session_object,'CSRF_TOKEN');
        if ( ! $token ) return false;
        if ( isset($_POST['CSRF_TOKEN']) && $token == $_POST['CSRF_TOKEN'] ) return true;
        $headers = array_change_key_case(apache_request_headers());
        if ( isset($headers['x-csrf-token']) && $token == $headers['x-csrf-token'] ) return true;
        if ( isset($headers['x-csrftoken']) && $token == $headers['x-csrftoken'] ) return true;
        return false;
    }

    // Check the secure cookie and set login information appropriately
    public static function loginSecureCookie($session_object=null) {
        global $CFG, $PDOX;
        $pieces = false;
        $id = false;

        // Only do this if we are not already logged in...
        if ( self::wrapped_session_get($session_object,'id') || !isset($_COOKIE[$CFG->cookiename]) ||
             !isset($CFG->cookiepad) || $CFG->cookiepad === false) {
            return;
        }

        $ct = $_COOKIE[$CFG->cookiename];
        // error_log("Cookie: $ct \n");
        $pieces = SecureCookie::extract($ct);
        if ( $pieces === false || count($pieces) != 3) {
            error_log('Decrypt fail:'.$ct);
            SecureCookie::delete();
            return;
        }

        // print_r($pieces); die();

        // Convert to an integer and check valid
        $user_id = is_numeric($pieces[0])? $pieces[0] + 0 : 0;
        $userEmail = $pieces[1];
        $context_id = is_numeric($pieces[2]) ? $pieces[2] + 0 : 0;
        if ( $user_id < 1 || $context_id < 1 ) {
            $user_id = false;
            error_log('Decrypt bad ID:'.$ct);
            error_log(\Tsugi\UI\Output::safe_var_dump($pieces));
            SecureCookie::delete();
            return;
        }

        // The profile table might not even exist yet.
        // See also login.php line 339 (ish)
        $sql = "SELECT P.profile_id AS profile_id,
                U.user_id AS user_id, U.user_key AS user_key,
                U.displayname AS displayname, U.email AS email, U.image AS user_image,
                P.displayname AS p_displayname, P.email AS p_email, P.image AS p_user_image,
                role, C.context_key, C.context_id AS context_id,
                C.title AS context_title, C.title AS resource_title,
                K.key_id, K.key_key, K.secret, K.new_secret,
                NULL AS nonce
                FROM {$CFG->dbprefix}profile AS P
                LEFT JOIN {$CFG->dbprefix}lti_user AS U
                ON P.profile_id = U.profile_id AND user_sha256 = profile_sha256 AND
                    P.key_id = U.key_id
                LEFT JOIN {$CFG->dbprefix}lti_key AS K
                    ON U.key_id = K.key_id
                LEFT JOIN {$CFG->dbprefix}lti_context AS C
                    ON U.key_id = C.key_id
                LEFT JOIN {$CFG->dbprefix}lti_membership AS M
                    ON U.user_id = M.user_id AND C.context_id = M.context_id
                WHERE P.email = :EMAIL AND U.email = :EMAIL
                    AND U.user_id = :UID AND C.context_id = :CID LIMIT 1";

        $stmt = $PDOX->queryReturnError($sql,
            array('EMAIL' => $userEmail, ":UID" => $user_id, ":CID" => $context_id)
        );

        // print_r($stmt); die();
        if ( $stmt->success === false ) return;

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        // print_r($row); die();
        if ( $row === false ) {
            error_log("Unable to load user_id=$user_id EMAIL=$userEmail");
            SecureCookie::delete();
            return;
        }

        // Coalesce from profile to user where there is missing data
        if ( U::isEmpty($row['user_image']) ) $row['user_image'] = $row['p_user_image'];
        if ( U::isEmpty($row['email']) ) $row['email'] = $row['p_email'];
        if ( U::isEmpty($row['displayname']) ) $row['displayname'] = $row['p_displayname'];
        unset($row['p_user_image']);
        unset($row['p_email']);
        unset($row['p_displayname']);

        self::wrapped_session_put($session_object,'id',$row['user_id']);
        self::wrapped_session_put($session_object,'email',$row['email']);
        self::wrapped_session_put($session_object,'displayname',$row['displayname']);
        self::wrapped_session_put($session_object,'profile_id',$row['profile_id']);
        self::wrapped_session_put($session_object,'user_key',$row['user_key']);
        self::wrapped_session_put($session_object,'avatar',$row['user_image']);
        if ( isset($row['key_key']) ) {
            self::wrapped_session_put($session_object,'oauth_consumer_key',$row['key_key']);
        }
        if ( $row['role'] !== null ) {
            self::wrapped_session_put($session_object,'context_key',$row['context_key']);
            self::wrapped_session_put($session_object,'context_id',$row['context_id']);
        }

        if ( isset($row['secret']) ) {
            $row['secret'] = self::encrypt_secret($row['secret']);
            self::wrapped_session_put($session_object,'secret', $row['secret']);
        }
        if ( isset($row['new_secret']) ) {
            $row['new_secret'] = self::encrypt_secret($row['new_secret']);
        }

        // Emulate a session launch
        self::wrapped_session_put($session_object,'lti',$row);

        self::noteLoggedIn($row);

        error_log('Autologin:'.$row['user_id'].','.$row['displayname'].','.
            $row['email'].','.$row['profile_id']);

    }

    /**
     * getCoreLaunchData - Get the launch data common across launch types
     */
    public static function getCoreLaunchData()
    {
        global $CFG, $CONTEXT, $USER, $CONTEXT, $LINK;
        $ltiProps = array();
        $ltiProps[LTIConstants::LTI_VERSION] = LTIConstants::LTI_VERSION_1;
        $ltiProps[LTIConstants::CONTEXT_ID] = $CONTEXT->id;
        $ltiProps[LTIConstants::ROLES] = $USER->instructor ? LTIConstants::ROLE_INSTRUCTOR : LTIConstants::ROLE_LEARNER;
        $ltiProps[LTIConstants::USER_ID] = $USER->id;
        $ltiProps[LTIConstants::LIS_PERSON_NAME_FULL] = $USER->displayname;
        $ltiProps[LTIConstants::LIS_PERSON_CONTACT_EMAIL_PRIMARY] = $USER->email;

        $ltiProps['tool_consumer_instance_guid'] = $CFG->wwwroot;
        $ltiProps['tool_consumer_instance_description'] = $CFG->servicename;

        return $ltiProps;
    }

    /**
     * getLaunchData - Get the launch data for a normal LTI 1.x launch
     */
    public static function getLaunchData()
    {
        global $CFG, $CONTEXT, $USER, $CONTEXT, $LINK;
        $ltiProps = self::getCoreLaunchData();
        $ltiProps[LTIConstants::LTI_MESSAGE_TYPE] = LTIConstants::LTI_MESSAGE_TYPE_BASICLTILAUNCHREQUEST;
        $ltiProps[LTIConstants::RESOURCE_LINK_ID] = $LINK->id;

        $ltiProps['tool_consumer_instance_guid'] = $CFG->wwwroot;
        $ltiProps['tool_consumer_instance_description'] = $CFG->servicename;

        return $ltiProps;
    }

    /**
     * getLaunchData - Get the launch data for am LTI ContentItem launch
     */
    public static function getContentItem($contentReturn, $dataProps)
    {
        global $CFG, $CONTEXT, $USER, $CONTEXT, $LINK;
        $ltiProps = self::getCoreLaunchData();
        $ltiProps[LTIConstants::LTI_MESSAGE_TYPE] = LTIConstants::CONTENT_ITEM_SELECTION_REQUEST;
        $ltiProps[LTIConstants::ACCEPT_MEDIA_TYPES] = LTIConstants::MEDIA_LTILINKITEM;
        $ltiProps[LTIConstants::ACCEPT_PRESENTATION_DOCUMENT_TARGETS] = "iframe,window"; // Nice to add overlay
        $ltiProps[LTIConstants::ACCEPT_UNSIGNED] = "true";
        $ltiProps[LTIConstants::ACCEPT_MULTIPLE] = "false";
        $ltiProps[LTIConstants::ACCEPT_COPY_ADVICE] = "false"; // ???
        $ltiProps[LTIConstants::AUTO_CREATE] = "true";
        $ltiProps[LTIConstants::CAN_CONFIRM] = "false";
        $ltiProps[LTIConstants::CONTENT_ITEM_RETURN_URL] = $contentReturn;
        $ltiProps[LTIConstants::LAUNCH_PRESENTATION_RETURN_URL] = $contentReturn;

        // This is needed to trigger WarpWire to send us back the link
        $ltiProps['tool_consumer_info_product_family_code'] = 'canvas';
        $ltiProps['custom_canvas_course_id'] = $CONTEXT->id;

        return $ltiProps;
    }

    /**
     * getLaunchData - Get the launch data for am LTI ContentItem launch
     */
    public static function getLaunchUrl($endpoint, $debug=false)
    {
        $launchurl = Output::getUtilUrl('/launch.php?debug=');
        $launchurl .= ($debug) ? '1':'0';
        $launchurl .= '&endpoint=';
        $launchurl .= urlencode($endpoint);
        return $launchurl;
    }

    /**
     * getLaunchContent - Get the launch data for am LTI ContentItem launch
     */
    public static function getLaunchContent($endpoint, $debug=false)
    {
            $info = LTIX::getKeySecretForLaunch($endpoint);
            if ( $info === false ) {
                return '<p style="color:red">Unable to load key/secret for '.htmlentities($endpoint)."</p>\n";
            }
            $key = $info['key'];
            $secret = self::decrypt_secret($info['secret']);

            $parms = LTIX::getLaunchData();

            $parms = LTI::signParameters($parms, $endpoint, "POST", $key, $secret, "Button");

            $content = LTI::postLaunchHTML($parms, $endpoint, false);
            return $content;
    }

    /**
     * We are aborting this request.  If this is a launch, redirect back
     */
    public static function abort_with_error_log($msg, $extra=false, $prefix="DIE:") {
        global $CFG;

        $return_url = isset($_POST['launch_presentation_return_url']) ? $_POST['launch_presentation_return_url'] : null;
        if ( is_array($extra) ) $extra = Output::safe_var_dump($extra);

        // make the msg a bit friendlier
        if ( ! headers_sent() ) {
            header('X-Tsugi-Test-Harness: https://www.tsugi.org/lti-test/');
            header('X-Tsugi-Base-String-Checker: https://www.tsugi.org/lti-test/basecheck.php');
            if ( $extra ) {
                header('X-Tsugi-Error-Detail: '.str_replace("\n"," -- ",$extra));
            }
        }

        $headers = headers_list();
        $json = false;
        foreach($headers as $header ) {
            $header = strtolower($header);
            if ( stripos($header, 'content-type:') === 0 && stripos($header, 'json') > 0 ) $json = true;
        }

        if ( $json ) {
            $oldcode = http_response_code();
            if ( ! is_numeric($oldcode) || $oldcode < 400 || $oldcode > 700 ) http_response_code(403);
            $response = new \stdClass();
            $response->status = "error";
            if ( $extra ) $response->tsugi_detail = $extra;
            $response->tsugi_tester = 'https://www.tsugi.org/lti-test/';
            $response->base_string = 'https://www.tsugi.org/lti-test/basecheck.php';
            echo(json_encode($response));
            error_log($prefix.' '.$msg.' '.$extra);
            exit();
        }

        if ($return_url === null) {
            error_log($prefix.' '.$msg.' '.$extra);
            print_stack_trace();
            $url = "https://www.tsugi.org/launcherror";
            if ( isset($CFG->launcherror) ) $url = $CFG->launcherror;
            $url = U::add_url_parm($url, "detail", $msg);
            Output::htmlError("The LTI Launch Failed", "Detail: $msg", $url);
            exit();
        }
        $return_url .= ( strpos($return_url,'?') > 0 ) ? '&' : '?';
        $return_url .= 'lti_errormsg=' . urlencode($msg);
        if ( $extra !== false ) {
            if ( strlen($extra) < 200 ) $return_url .= '&detail=' . urlencode($extra);
        }
        header("Location: ".$return_url);
        error_log($prefix.' '.$msg.' '.$extra);
        exit();
    }

    /**
     * Update the login_at fields as appropriate
     */
    public static function noteLoggedIn($row)
    {
        global $CFG, $PDOX;

        if ( ! isset($row['user_id']) ) return;
        if ( ! isset($PDOX) || ! $PDOX ) return;

        if ( Net::getIP() !== NULL ) {
            $sql = "UPDATE {$CFG->dbprefix}lti_user
                SET login_at=NOW(), login_count=login_count+1, ipaddr=:IP WHERE user_id = :user_id";
            $stmt = $PDOX->queryReturnError($sql, array(
                ':IP' => Net::getIP(),
                ':user_id' => $row['user_id']));
        } else {
            $sql = "UPDATE {$CFG->dbprefix}lti_user
                SET login_at=NOW(), login_count=login_count+1 WHERE user_id = :user_id";
            $stmt = $PDOX->queryReturnError($sql, array(
                ':user_id' => $row['user_id']));
        }

        if ( ! $stmt->success ) {
            error_log("Unable to update login_at user_id=".$row['user_id']);
        }

        if ( isset($row['context_id']) ) {
            $sql = "UPDATE {$CFG->dbprefix}lti_context
                SET login_at=NOW(), login_count=login_count+1 WHERE context_id = :context_id";
            $stmt = $PDOX->queryReturnError($sql, array(
                ':context_id' => $row['context_id']));

            if ( ! $stmt->success ) {
                error_log("Unable to update login_at context_id=".$row['context_id']);
            }
        }

        // We do an update of login_at for the key
        if ( array_key_exists('key_id', $row) ) {
            $sql = "UPDATE {$CFG->dbprefix}lti_key
                SET login_at=NOW(),login_count=login_count+1 WHERE key_id = :key_id";
            $stmt = $PDOX->queryReturnError($sql, array(
                ':key_id' => $row['key_id']));

            if ( ! $stmt->success ) {
                error_log("Unable to update login_at key_id=".$row['context_id']);
            }
        }
    }

    /**
     * populateRoster
     *
     * If the LTI Extension: Context Memberships Service is supported in the launch, get the memberships
     * information and insert the information into lti_user and lti_membership
     *
     * @param bool $groups, whether or not to get groups in the Memberships
     * @param bool $insert, whether or not to insert the members found into lti_user, lti_membership
     * @return bool true if successful, false if not possible
     */
    public static function populateRoster($groups=false, $insert=false) {
        global $ROSTER, $CFG, $TSUGI_LAUNCH;
        if(!is_object($ROSTER)) {
            return false;
        }

        if (filter_var($ROSTER->url, FILTER_VALIDATE_URL) === FALSE) {
            return false;
        }

        $encryptedSecret = self::ltiParameter('secret');
        $key = self::ltiParameter('key_key');

        $response = LTI::getContextMemberships($ROSTER->id, $ROSTER->url, $key, self::decrypt_secret($encryptedSecret), $groups);

        if($response != false) {
            $ROSTER->data = $response;

            if ($insert) {
                $PDOX = self::getConnection();
                $p = $CFG->dbprefix;

                $errormode = $PDOX->getAttribute(\PDO::ATTR_ERRMODE);
                $PDOX->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                // Set up user
                $insertUser = "INSERT INTO {$p}lti_user
                        ( user_key, user_sha256, displayname, email, locale, json, key_id, created_at, updated_at ) VALUES ";

                // Set up membership
                $insertMembership = "INSERT INTO {$p}lti_membership
                        ( context_id, user_id, role, created_at, updated_at ) VALUES ";

                $key = $PDOX->rowDie("SELECT key_id
                      FROM {$CFG->dbprefix}lti_key WHERE key_key = :key",
                      array(':key' => $TSUGI_LAUNCH->context->key));
                if (! $key ) return false;

                $insertUserArray = [];
                $insertUserMembershipArray = [];
                foreach($ROSTER->data as $user) {
                    array_push($insertUserArray,
                            "('". $user['user_id'] ."', '".
                                    lti_sha256($user['user_id']) ."', '".
                                    $user['user_name'] ."', '".
                                    $user['user_email'] ."', '".
                                    ($TSUGI_LAUNCH->context->launch->user->locale ? $TSUGI_LAUNCH->context->launch->user->locale : ''). "', ".
                                    "'{\"sourcedId\": \"". $user['lis_result_sourcedid'] ."\"}', ".
                                    $key['key_id'] .", NOW(), NOW() )");

                    array_push($insertUserMembershipArray,
                            "(". $TSUGI_LAUNCH->context->id .", ".
                                "(select user_id from {$p}lti_user where user_key = '". $user['user_id'] ."' limit 1), ".
                                    ($user['role'] == "Instructor" ? LTIX::ROLE_INSTRUCTOR : LTIX::ROLE_LEARNER) .", NOW(), NOW() )");
                }
                $PDOX->queryDie($insertUser . implode(',', $insertUserArray)
                            ." ON DUPLICATE KEY UPDATE "
                                ." displayname = VALUES(displayname)"
                                ." ,email = VALUES(email)"
                                ." ,updated_at = NOW()"
                                ." ,json = IF(JSON_VALID(json), JSON_SET(json, '$.sourcedId', JSON_EXTRACT(VALUES(json),'$.sourcedId')), VALUES(json))");
                             //   ." ,json = REPLACE(CONCAT(IFNULL(json,'{}'), VALUES(json)), '}{', ',')");
                $PDOX->queryDie($insertMembership . implode(',', $insertUserMembershipArray). " ON DUPLICATE KEY UPDATE role = VALUES(role), updated_at=NOW()");
            } // if insert
        } // if response valid

        return true;
    }

    public static function getBrowserSignature() {
        $concat = self::getBrowserSignatureRaw();
        $h = hash('sha256', $concat);
        return $h;
    }

    // As the anti-trackers gain traction cookies come and go - so don't depend on them
    public static function getBrowserSignatureRaw() {
        global $CFG;

        $look_at = array( 'x-forwarded-proto', 'x-forwarded-port', 'host',
        'accept-encoding', 'cf-ipcountry', 'user-agent', 'accept', 'accept-language');

        $headers = \Tsugi\Util\U::apache_request_headers();

        // Living behind Cloudflare, IP adresses seem to change
        // $concat = \Tsugi\Util\Net::getIP();
        $concat = "";
        if ( isset($CFG->cookiepad) ) $concat .= ':::' . $CFG->cookiepad;
        if ( isset($CFG->cookiesecret) ) $concat .= ':::' . $CFG->cookiesecret;
        $used = array();
        ksort($headers);
        foreach($headers as $k => $v ) {
            if ( ! in_array(strtolower($k), $look_at) ) continue;
            if ( is_string($v) ) {
                $used[$k] = $v;
                $concat .= ':::' . $k . '=' . $v;
                continue;
            }
        }

        return $concat;
    }

    public static function getTsugiStateCookieName() {
        return "tsugi-state-lti-advantage";
    }

    /** Compute the kid has value from a public key
     *
     * @param pubkey The public key
     */
    public static function getKidForKey($pubkey) {
        return hash('sha256', trim($pubkey));
    }

    /** Retrieve the context and link ids for a previous installation of the tool, such as
     *  if it were imported from another site during a site import. Returns false, if not.
     * @return array[
     *  'context_id' => number,
     *  'link_id' => number
     * ] | false
     */
    public static function getLatestHistoryIds() {
        global $CFG, $LAUNCH, $PDOX;
        $resource_history = $LAUNCH->ltiCustomGet("resourcelink_id_history", '$ResourceLink.id.history');
        if ($resource_history != '$ResourceLink.id.history') {
            // Split the link_key and use the most recent one
            $link_key_arr = explode(',', $resource_history);
            $prev_link_key = end($link_key_arr);
            // Get the previous context and link ids
            $linkquery = "SELECT context_id, link_id FROM {$CFG->dbprefix}lti_link WHERE link_key = :link_key;";
            $linkarr = array(':link_key' => $prev_link_key);
            return $PDOX->rowDie($linkquery, $linkarr);
        } else {
            return false;
        }
    }
}
