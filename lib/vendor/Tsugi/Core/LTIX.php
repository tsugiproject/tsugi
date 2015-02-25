<?php

namespace Tsugi\Core;

use \Tsugi\OAuth\TrivialOAuthDataStore;
use \Tsugi\OAuth\OAuthServer;
use \Tsugi\OAuth\OAuthSignatureMethod_HMAC_SHA1;
use \Tsugi\OAuth\OAuthRequest;

use \Tsugi\Util\LTI;
use \Tsugi\Core\Settings;

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

    /**
     * Silently check if this is a launch and if so, handle it
     */
    public static function launchCheck() {
        if ( ! LTI::isRequest() ) return false;
        $session_id = self::setupSession();
        if ( $session_id === false ) return false;

        // Redirect back to ourselves...
        $url = curPageURL();
        $query = false;
        if ( isset($_SERVER['QUERY_STRING']) && strlen($_SERVER['QUERY_STRING']) > 0) {
            $query = true;
            $url .= '?' . $_SERVER['QUERY_STRING'];
        }

        $location = addSession($url);
        session_write_close();  // To avoid any race conditions...

        if ( headers_sent() ) {
            echo('<p><a href="'.$url.'">Click to continue</a></p>');
        } else {
            header('Location: '.$location);
        }
        exit();
    }

    /**
     * Pull a keyed variable from the LTI data in the current session with default
     */
    public static function sessionGet($varname, $default=false) {
        if ( ! isset($_SESSION) ) return $default;
        if ( ! isset($_SESSION['lti']) ) return $default;
        $lti = $_SESSION['lti'];
        if ( ! isset($lti[$varname]) ) return $default;
        return $lti[$varname];
    }

    /**
     * Return the original $_POST array
     */
    public static function postArray() {
        if ( ! isset($_SESSION) ) return array();
        if ( ! isset($_SESSION['lti_post']) ) return array();
        return($_SESSION['lti_post']);
    }

    /**
     * Pull a keyed variable from the original LTI post data in the current session with default
     */
    public static function postGet($varname, $default=false) {
        if ( ! isset($_SESSION) ) return $default;
        if ( ! isset($_SESSION['lti_post']) ) return $default;
        $lti_post = $_SESSION['lti_post'];
        if ( ! isset($lti_post[$varname]) ) return $default;
        return $lti_post[$varname];
    }

    /**
     * Pull out a custom variable from the LTIX session
     */
    public static function customGet($varname, $default=false) {
        return self::postGet('custom_'.$varname, $default);
    }

    /**
     * Extract all of the post data, set up data in tables, and set up session.
     */
    public static function setupSession() {
        global $CFG, $PDOX;
        if ( ! LTI::isRequest() ) return false;

        // Pull LTI data out of the incoming $_POST and map into the same
        // keys that we use in our database (i.e. like $row)
        $post = self::extractPost();
        if ( $post === false ) {
            $pdata = safe_var_dump($_POST);
            error_log('Missing post data: '.$pdata);
            require('lti/nopost.php');
            return;
        }

        if ( $post['key'] == '12345' && ! $CFG->DEVELOPER) {
            die_with_error_log('You can only use key 12345 in developer mode');
        }

        // We make up a Session ID Key because we don't want a new one
        // each time the same user launches the same link.
        $session_id = self::getCompositeKey($post, $CFG->sessionsalt);
        session_id($session_id);
        session_start();
        header('Content-Type: text/html; charset=utf-8');

        // Since we might reuse session IDs, clean everything out
        session_unset();
        $_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp

        // Read all of the data from the database with a very long
        // LEFT JOIN and get all the data we have back in the $row variable
        // $row = loadAllData($CFG->dbprefix, false, $post);
        $row = self::loadAllData($CFG->dbprefix, $CFG->dbprefix.'profile', $post);

        $delta = 0;
        if ( isset($_POST['oauth_timestamp']) ) {
            $server_time = $_POST['oauth_timestamp']+0;
            $delta = abs(time() -  $server_time);
            if ( $delta > 480 ) { // More than four minutes is getting close
                error_log('Warning: Time skew, delta='.$delta.' sever_time='.$server_time.' our_time='.time());
            }
        }

        // Check the nonce to make sure there is no reuse
        if ( $row['nonce'] !== null) {
            die_with_error_log('OAuth nonce error key='.$post['key'].' nonce='.$row['nonce']);
        }

        // Use returned data to check the OAuth signature on the
        // incoming data - returns true or an array
        $valid = LTI::verifyKeyAndSecret($post['key'],$row['secret']);

        // If there is a new_secret it means an LTI2 re-registration is in progress and we
        // need to check both the current and new secret until the re-registration is committed
        if ( $valid !== true && strlen($row['new_secret']) > 0 && $row['new_secret'] != $row['secret']) {
            $valid = LTI::verifyKeyAndSecret($post['key'],$row['new_secret']);
            if ( $valid ) {
                $row['secret'] = $row['new_secret'];
            }
            $row['new_secret'] = null;
        }

        if ( $valid !== true ) {
            print "<pre>\n";
            print_r($valid);
            print "</pre>\n";
            die_with_error_log('OAuth validation fail key='.$post['key'].' delta='.$delta.' error='.$valid[0]);
        }

        $actions = self::adjustData($CFG->dbprefix, $row, $post);

        // Record the nonce but first probabilistically check
        if ( $CFG->noncecheck > 0 ) {
            if ( (time() % $CFG->noncecheck) == 0 ) {
                $PDOX->queryDie("DELETE FROM {$CFG->dbprefix}lti_nonce WHERE
                    created_at < DATE_ADD(CURRENT_TIMESTAMP(), INTERVAL -{$CFG->noncetime} SECOND)");
                // error_log("Nonce table cleanup done.");
            }
            $PDOX->queryDie("INSERT INTO {$CFG->dbprefix}lti_nonce
                (key_id, nonce) VALUES ( :key_id, :nonce)",
                array( ':nonce' => $post['nonce'], ':key_id' => $row['key_id'])
            );
        }

        // If there is an appropriate role override variable, we use that role
        if ( isset($row['role_override']) && isset($row['role']) &&
            $row['role_override'] > $row['role'] ) {
            $row['role'] = $row['role_override'];
        }

        // Put the information into the row variable
        // TODO: do AES on the secret
        $_SESSION['lti'] = $row;
        $_SESSION['lti_post'] = $_POST;
        if ( isset($_SERVER['HTTP_USER_AGENT']) ) $_SESSION['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
        if ( isset($_SERVER['REMOTE_ADDR']) ) $_SESSION['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['CSRF_TOKEN'] = uniqid();

        // Save this to make sure the user does not wander unless we launched from the root
        $scp = getScriptPath();
        if ( strlen($scp) > 0 ) {
            $_SESSION['script_path'] = getScriptPath();
        }

        // Check if we can auto-login the system user
        if ( Settings::linkGet('dologin', false) && isset($PDOX) && $PDOX !== false ) loginSecureCookie();

        // Set up basic custom values (legacy)
        if ( isset($_POST['custom_due'] ) ) {
            $when = strtotime($_POST['custom_due']);
            if ( $when === false ) {
                echo('<p>Error, bad setting for custom_due='.htmlentities($_POST['custom_due']).'</p>');
                error_log('Bad custom_due='.$_POST['custom_due']);
                flush();
            } else {
                $_SESSION['due'] = $_POST['custom_due'];
            }
        }

        if ( isset($_POST['custom_timezone'] ) ) {
            $_SESSION['timezone'] = $_POST['custom_timezone'];
        }

        if ( isset($_POST['custom_penalty_time'] ) ) {
            if ( $_POST['custom_penalty_time'] + 0 == 0 ) {
                echo('<p>Error, bad setting for custom_penalty_time='.htmlentities($_POST['custom_penalty_time']).'</p>');
                error_log('Bad custom_penalty_time='.$_POST['custom_penalty_time']);
                flush();
            } else {
                $_SESSION['penalty_time'] = $_POST['custom_penalty_time'];
            }
        }

        if ( isset($_POST['custom_penalty_cost'] ) ) {
            if ( $_POST['custom_penalty_cost'] + 0 == 0 ) {
                echo('<p>Error, bad setting for custom_penalty_cost='.htmlentities($_POST['custom_penalty_cost']).'</p>');
                error_log('Bad custom_penalty_cost='.$_POST['custom_penalty_cost']);
                flush();
            } else {
                $_SESSION['penalty_cost'] = $_POST['custom_penalty_cost'];
            }
        }

        $breadcrumb = 'Launch,';
        $breadcrumb .= isset($row['key_id']) ? $row['key_id'] : '';
        $breadcrumb .= ',';
        $breadcrumb .= isset($row['user_id']) ? $row['user_id'] : '';
        $breadcrumb .= ',';
        $breadcrumb .= isset($_POST['user_id']) ? str_replace(',',';', $_POST['user_id']) : '';
        $breadcrumb .= ',';
        $breadcrumb .= $session_id;
        $breadcrumb .= ',';
        $breadcrumb .= curPageURL();
        $breadcrumb .= ',';
        $breadcrumb .= isset($_SESSION['email']) ? $_SESSION['email'] : '';
        error_log($breadcrumb);

        return $session_id;
    }

    /**
     * Pull the LTI POST data into our own data structure
     *
     * We follow our naming conventions that match the column names in
     * our lti_ tables.
     */
    public static function extractPost() {
        // Unescape each time we use this stuff - someday we won't need this...
        $FIXED = array();
        foreach($_POST as $key => $value ) {
            if (get_magic_quotes_gpc()) $value = stripslashes($value);
            $FIXED[$key] = $value;
        }
        $retval = array();
        $retval['key'] = isset($FIXED['oauth_consumer_key']) ? $FIXED['oauth_consumer_key'] : null;
        $retval['nonce'] = isset($FIXED['oauth_nonce']) ? $FIXED['oauth_nonce'] : null;
        $retval['context_id'] = isset($FIXED['context_id']) ? $FIXED['context_id'] : null;
        $retval['link_id'] = isset($FIXED['resource_link_id']) ? $FIXED['resource_link_id'] : null;
        $retval['user_id'] = isset($FIXED['user_id']) ? $FIXED['user_id'] : null;

        if ( $retval['key'] && $retval['nonce'] && $retval['context_id'] &&
            $retval['link_id']  && $retval['user_id'] ) {
            // OK To Continue
        } else {
            return false;
        }

        // LTI 1.x settings and Outcomes
        $retval['service'] = isset($FIXED['lis_outcome_service_url']) ? $FIXED['lis_outcome_service_url'] : null;
        $retval['sourcedid'] = isset($FIXED['lis_result_sourcedid']) ? $FIXED['lis_result_sourcedid'] : null;

        // LTI 2.x settings and Outcomes
        $retval['result_url'] = isset($FIXED['custom_result_url']) ? $FIXED['custom_result_url'] : null;
        $retval['link_settings_url'] = isset($FIXED['custom_link_settings_url']) ? $FIXED['custom_link_settings_url'] : null;
        $retval['context_settings_url'] = isset($FIXED['custom_context_settings_url']) ? $FIXED['custom_context_settings_url'] : null;

        $retval['context_title'] = isset($FIXED['context_title']) ? $FIXED['context_title'] : null;
        $retval['link_title'] = isset($FIXED['resource_link_title']) ? $FIXED['resource_link_title'] : null;

        // Getting email from LTI 1.x and LTI 2.x
        $retval['user_email'] = isset($FIXED['lis_person_contact_email_primary']) ? $FIXED['lis_person_contact_email_primary'] : null;
        $retval['user_email'] = isset($FIXED['custom_person_email_primary']) ? $FIXED['custom_person_email_primary'] : $retval['user_email'];

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
        $retval['role'] = 0;
        $roles = '';
        if ( isset($FIXED['custom_membership_role']) ) { // From LTI 2.x
            $roles = $FIXED['custom_membership_role'];
        } else if ( isset($FIXED['roles']) ) { // From LTI 1.x
            $roles = $FIXED['roles'];
        }

        if ( strlen($roles) > 0 ) {
            $roles = strtolower($roles);
            if ( ! ( strpos($roles,'instructor') === false ) ) $retval['role'] = 1;
            if ( ! ( strpos($roles,'administrator') === false ) ) $retval['role'] = 1;
        }
        return $retval;
    }

    // Make sure to include the file in case multiple instances are running
    // on the same Operating System instance and they have not changed the
    // session secret.  Also make these change every 30 minutes
    public static function getCompositeKey($post, $session_secret) {
        $comp = $session_secret .'::'. $post['key'] .'::'. $post['context_id'] .'::'.
            $post['link_id']  .'::'. $post['user_id'] .'::'. intval(time() / 1800) .
            $_SERVER['HTTP_USER_AGENT'] . '::' . __FILE__;
        return md5($comp);
    }

    /**
     * Load the data from our lti_ tables using one long LEFT JOIN
     *
     * This data may or may not exist - hence the use of the long
     * LEFT JOIN.
     */
    public static function loadAllData($p, $profile_table, $post) {
        global $PDOX;
        $errormode = $PDOX->getAttribute(\PDO::ATTR_ERRMODE);
        $PDOX->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $sql = "SELECT k.key_id, k.key_key, k.secret, k.new_secret, c.settings_url AS key_settings_url,
            n.nonce,
            c.context_id, c.title AS context_title, context_sha256, c.settings_url AS context_settings_url,
            l.link_id, l.title AS link_title, l.settings AS link_settings, l.settings_url AS link_settings_url,
            u.user_id, u.displayname AS user_displayname, u.email AS user_email, user_key,
            u.subscribe AS subscribe, u.user_sha256 AS user_sha256,
            m.membership_id, m.role, m.role_override,
            r.result_id, r.grade, r.result_url, r.sourcedid";

        if ( $profile_table ) {
            $sql .= ",
            p.profile_id, p.displayname AS profile_displayname, p.email AS profile_email,
            p.subscribe AS profile_subscribe";
        }

        if ( $post['service'] ) {
            $sql .= ",
            s.service_id, s.service_key AS service";
        }

        $sql .="\nFROM {$p}lti_key AS k
            LEFT JOIN {$p}lti_nonce AS n ON k.key_id = n.key_id AND n.nonce = :nonce
            LEFT JOIN {$p}lti_context AS c ON k.key_id = c.key_id AND c.context_sha256 = :context
            LEFT JOIN {$p}lti_link AS l ON c.context_id = l.context_id AND l.link_sha256 = :link
            LEFT JOIN {$p}lti_user AS u ON k.key_id = u.key_id AND u.user_sha256 = :user
            LEFT JOIN {$p}lti_membership AS m ON u.user_id = m.user_id AND c.context_id = m.context_id
            LEFT JOIN {$p}lti_result AS r ON u.user_id = r.user_id AND l.link_id = r.link_id";

        if ( $profile_table ) {
            $sql .= "
            LEFT JOIN {$profile_table} AS p ON u.profile_id = p.profile_id";
        }

        if ( $post['service'] ) {
            $sql .= "
            LEFT JOIN {$p}lti_service AS s ON k.key_id = s.key_id AND s.service_sha256 = :service";
        }

        $sql .= "\nWHERE k.key_sha256 = :key LIMIT 1\n";

        // echo($sql);
        $parms = array(
            ':key' => lti_sha256($post['key']),
            ':nonce' => substr($post['nonce'],0,128),
            ':context' => lti_sha256($post['context_id']),
            ':link' => lti_sha256($post['link_id']),
            ':user' => lti_sha256($post['user_id']));

        if ( $post['service'] ) {
            $parms[':service'] = lti_sha256($post['service']);
        }

        $row = $PDOX->rowDie($sql, $parms);

        // Restore ERRMODE
        $PDOX->setAttribute(\PDO::ATTR_ERRMODE, $errormode);
        return $row;
    }

    /**
     * Make sure that the data in our lti_ tables matches the POST data
     *
     * This routine compares the POST dat to the data pulled from the
     * lti_ tables and goes through carefully INSERTing or UPDATING
     * all the nexessary data in the lti_ tables to make sure that
     * the lti_ table correctly match all the data from the incoming post.
     *
     * While this looks like a lot of INSERT and UPDATE statements,
     * the INSERT statements only run when we see a new user/course/link
     * for the first time and after that, we only update is something
     * changes.   S0 in a high percentage of launches we are not seeing
     * any new or updated data and so this code just falls through and
     * does absolutely no SQL.
     */
    public static function adjustData($p, &$row, $post) {
        global $PDOX;
        $errormode = $PDOX->getAttribute(\PDO::ATTR_ERRMODE);
        $PDOX->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $actions = array();
        if ( $row['context_id'] === null) {
            $sql = "INSERT INTO {$p}lti_context
                ( context_key, context_sha256, settings_url, title, key_id, created_at, updated_at ) VALUES
                ( :context_key, :context_sha256, :settings_url, :title, :key_id, NOW(), NOW() )";
            $PDOX->queryDie($sql, array(
                ':context_key' => $post['context_id'],
                ':context_sha256' => lti_sha256($post['context_id']),
                ':settings_url' => $post['context_settings_url'],
                ':title' => $post['context_title'],
                ':key_id' => $row['key_id']));
            $row['context_id'] = $PDOX->lastInsertId();
            $row['context_title'] = $post['context_title'];
            $row['context_settings_url'] = $post['context_settings_url'];
            $actions[] = "=== Inserted context id=".$row['context_id']." ".$row['context_title'];
        }

        if ( $row['link_id'] === null && isset($post['link_id']) ) {
            $sql = "INSERT INTO {$p}lti_link
                ( link_key, link_sha256, settings_url, title, context_id, created_at, updated_at ) VALUES
                    ( :link_key, :link_sha256, :settings_url, :title, :context_id, NOW(), NOW() )";
            $PDOX->queryDie($sql, array(
                ':link_key' => $post['link_id'],
                ':link_sha256' => lti_sha256($post['link_id']),
                ':settings_url' => $post['link_settings_url'],
                ':title' => $post['link_title'],
                ':context_id' => $row['context_id']));
            $row['link_id'] = $PDOX->lastInsertId();
            $row['link_title'] = $post['link_title'];
            $row['link_settings_url'] = $post['link_settings_url'];
            $actions[] = "=== Inserted link id=".$row['link_id']." ".$row['link_title'];
        }

        $user_displayname = isset($post['user_displayname']) ? $post['user_displayname'] : null;
        $user_email = isset($post['user_email']) ? $post['user_email'] : null;
        if ( $row['user_id'] === null && isset($post['user_id']) ) {
            $sql = "INSERT INTO {$p}lti_user
                ( user_key, user_sha256, displayname, email, key_id, created_at, updated_at ) VALUES
                ( :user_key, :user_sha256, :displayname, :email, :key_id, NOW(), NOW() )";
            $PDOX->queryDie($sql, array(
                ':user_key' => $post['user_id'],
                ':user_sha256' => lti_sha256($post['user_id']),
                ':displayname' => $user_displayname,
                ':email' => $user_email,
                ':key_id' => $row['key_id']));
            $row['user_id'] = $PDOX->lastInsertId();
            $row['user_email'] = $user_email;
            $row['user_displayname'] = $user_displayname;
            $row['user_key'] = $post['user_id'];
            $actions[] = "=== Inserted user id=".$row['user_id']." ".$row['user_email'];
        }

        if ( $row['membership_id'] === null && $row['context_id'] !== null && $row['user_id'] !== null ) {
            $sql = "INSERT INTO {$p}lti_membership
                ( context_id, user_id, role, created_at, updated_at ) VALUES
                ( :context_id, :user_id, :role, NOW(), NOW() )";
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
                    ( :service_key, :service_sha256, :key_id, NOW(), NOW() )";
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
                $sql = "UPDATE {$p}lti_result SET service_id = :service_id WHERE result_id = :result_id";
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
                ( :link_id, :user_id, NOW(), NOW() )";
            $PDOX->queryDie($sql, array(
                ':link_id' => $row['link_id'],
                ':user_id' => $row['user_id']));
            $row['result_id'] = $PDOX->lastInsertId();
            $actions[] = "=== Inserted result id=".$row['result_id'];
       }

        // Set these values to null if they were not in the post
        if ( ! isset($post['sourcedid']) ) $post['sourcedid'] = null;
        if ( ! isset($post['service']) ) $post['service'] = null;
        if ( ! isset($row['service']) ) $row['service'] = null;
        if ( ! isset($post['result_url']) ) $post['result_url'] = null;

        // Here we handle updates to sourcedid or result_url including if we
        // just inserted the result row
        if ( $row['result_id'] != null &&
            ($post['sourcedid'] != $row['sourcedid'] || $post['result_url'] != $row['result_url'] ||
            $post['service'] != $row['service'] )
        ) {
            $sql = "UPDATE {$p}lti_result
                SET sourcedid = :sourcedid, result_url = :result_url, service_id = :service_id
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

        // Here we handle updates to context_title, link_title, user_displayname, user_email, or role
        if ( isset($post['context_title']) && $post['context_title'] != $row['context_title'] ) {
            $sql = "UPDATE {$p}lti_context SET title = :title WHERE context_id = :context_id";
            $PDOX->queryDie($sql, array(
                ':title' => $post['context_title'],
                ':context_id' => $row['context_id']));
            $row['context_title'] = $post['context_title'];
            $actions[] = "=== Updated context=".$row['context_id']." title=".$post['context_title'];
        }

        if ( isset($post['link_title']) && $post['link_title'] != $row['link_title'] ) {
            $sql = "UPDATE {$p}lti_link SET title = :title WHERE link_id = :link_id";
            $PDOX->queryDie($sql, array(
                ':title' => $post['link_title'],
                ':link_id' => $row['link_id']));
            $row['link_title'] = $post['link_title'];
            $actions[] = "=== Updated link=".$row['link_id']." title=".$post['link_title'];
        }

        if ( isset($post['user_displayname']) && $post['user_displayname'] != $row['user_displayname'] && strlen($post['user_displayname']) > 0 ) {
            $sql = "UPDATE {$p}lti_user SET displayname = :displayname WHERE user_id = :user_id";
            $PDOX->queryDie($sql, array(
                ':displayname' => $post['user_displayname'],
                ':user_id' => $row['user_id']));
            $row['user_displayname'] = $post['user_displayname'];
            $actions[] = "=== Updated user=".$row['user_id']." displayname=".$post['user_displayname'];
        }

        if ( isset($post['user_email']) && $post['user_email'] != $row['user_email'] && strlen($post['user_email']) > 0 ) {
            $sql = "UPDATE {$p}lti_user SET email = :email WHERE user_id = :user_id";
            $PDOX->queryDie($sql, array(
                ':email' => $post['user_email'],
                ':user_id' => $row['user_id']));
            $row['user_email'] = $post['user_email'];
            $actions[] = "=== Updated user=".$row['user_id']." email=".$post['user_email'];
        }

        if ( isset($post['role']) && $post['role'] != $row['role'] ) {
            $sql = "UPDATE {$p}lti_membership SET role = :role WHERE membership_id = :membership_id";
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
     */
    public static function requireData($needed=self::ALL) {
        global $CFG, $USER, $CONTEXT, $LINK;

        if ( $needed == self::NONE ) $needed = array();
        if ( $needed == self::ALL ) {
            $needed = array(self::CONTEXT, self::LINK, self::USER);
        }
        if ( is_string($needed) ) $needed = array($needed);

        // Check if we are processing an LTI launch.  If so, handle it
        self::launchCheck();

        // Check to see if the session already exists.
        $sess = session_name();
        if ( ini_get('session.use_cookies') != '0' ) {
            if ( ! isset($_COOKIE[$sess]) ) {
                send403();
                die_with_error_log("Missing session cookie - please re-launch");
            }
        } else { // non-cookie session
            if ( isset($_POST[$sess]) || isset($_GET[$sess]) ) {
                // We tried to set a session..
            } else {
                if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
                    send403();
                    die_with_error_log('Missing '.$sess.' from POST data');
                } else {
                    send403();
                    die_with_error_log('This tool should be launched from a learning system using LTI');
                }
            }
        }

        // Start a session if it has not been started..
        if ( session_id() == "" ) {
            session_start();  // Should reassociate
        }

        // This happens from time to time when someone closes and reopens a laptop
        // Or their computer goes to sleep and wakes back up hours later.
        // So it is just a warning - nothing much we can do except tell them.
        if ( !isset($_SESSION['lti']) ) {
            // $debug = safe_var_dump($_SESSION);
            // error_log($debug);
            send403(); error_log('Session expired - please re-launch '.session_id());
            die('Session expired - please re-launch'); // with error_log
        }

        // Check the referrer...
        $trusted = checkReferer() || checkCSRF();

        // Check to see if we switched browsers or IP addresses
        // TODO: Change these to warnings once we get more data
        if ( (!$trusted) && isset($_SESSION['HTTP_USER_AGENT']) ) {
            if ( (!isset($_SERVER['HTTP_USER_AGENT'])) ||
                $_SESSION['HTTP_USER_AGENT'] != $_SERVER['HTTP_USER_AGENT'] ) {
                send403();
                die_with_error_log("Session has expired", " ".session_id()." HTTP_USER_AGENT ".
                    $_SESSION['HTTP_USER_AGENT'].' ::: '.
                    isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Empty user agent',
                'DIE:');
            }
        }

        // We only check the first three octets as some systems wander throught the addresses on
        // class C - Perhaps it is even NAT - who knows - but we forgive those on the same Class C
        if ( (!$trusted) &&  isset($_SESSION['REMOTE_ADDR']) && isset($_SERVER['REMOTE_ADDR']) ) {
            $sess_pieces = explode('.',$_SESSION['REMOTE_ADDR']);
            $serv_pieces = explode('.',$_SERVER['REMOTE_ADDR']);
            if ( count($sess_pieces) == 4 && count($serv_pieces) == 4 ) {
                if ( $sess_pieces[0] != $serv_pieces[0] || $sess_pieces[1] != $serv_pieces[1] ||
                    $sess_pieces[2] != $serv_pieces[2] ) {
                    send403();
                    die_with_error_log('Session address has expired', " ".session_id()." REMOTE_ADDR ".
                        $_SESSION['REMOTE_ADDR'].' '.$_SERVER['REMOTE_ADDR'], 'DIE:');
                }
            }
        }

        // Check to see if the user has navigated to a new place in the hierarchy
        if ( isset($_SESSION['script_path']) && getScriptPath() != 'core/blob' && 
            strpos(getScriptPath(), $_SESSION['script_path']) !== 0 ) {
            send403();
            die_with_error_log('Improper navigation detected', " ".session_id()." script_path ".
                $_SESSION['script_path'].' /  '.getScriptPath(), 'DIE:');
        }

        $LTI = $_SESSION['lti'];
        if ( is_array($needed) ) {
            foreach ( $needed as $feature ) {
                if ( isset($LTI[$feature]) ) continue;
                die_with_error_log("This tool requires an LTI launch parameter:".$feature);
            }
        }

        // Check to see if the session needs to be extended due to this request
        checkHeartBeat();

        // Restart the number of continuous heartbeats
        $_SESSION['HEARTBEAT_COUNT'] = 0;

        // Populate the $USER $CONTEXT and $LINK objects
        if ( isset($LTI['user_id']) && ! is_object($USER) ) {
            $USER = new \Tsugi\Core\User();
            $USER->id = $LTI['user_id'];
            if (isset($LTI['user_email']) ) $USER->email = $LTI['user_email'];
            if (isset($LTI['user_displayname']) ) {
                $USER->displayname = $LTI['user_displayname'];
                $pieces = explode(' ',$USER->displayname);
                if ( count($pieces) > 0 ) $USER->firstname = $pieces[0];
                if ( count($pieces) > 1 ) $USER->lastname = $pieces[count($pieces)-1];
            }
            $USER->instructor = isset($LTI['role']) && $LTI['role'] != 0 ;
        }

        if ( isset($LTI['context_id']) && ! is_object($CONTEXT) ) {
            $CONTEXT = new \Tsugi\Core\Context();
            $CONTEXT->id = $LTI['context_id'];
            if (isset($LTI['context_title']) ) $CONTEXT->title = $LTI['context_title'];
        }

        if ( isset($LTI['link_id']) && ! is_object($LINK) ) {
            $LINK = new \Tsugi\Core\Link();
            $LINK->id = $LTI['link_id'];
            if (isset($LTI['grade']) ) $LINK->grade = $LTI['grade'];
            if (isset($LTI['link_title']) ) $LINK->title = $LTI['link_title'];
            if (isset($LTI['result_id']) ) $LINK->result_id = $LTI['result_id'];
        }

        // Return the LTI structure
        return $LTI;
    }

    /**
      * Load the grade for a particular row and update our local copy
      *
      * Call the right LTI service to retrieve the server's grade and
      * update our local cached copy of the server_grade and the date
      * retrieved. This routine pulls the key and secret from the LTIX
      * session to avoid crossing cross tennant boundaries.
      *
      * TODO: Add LTI 2.x support for the JSON style services to this
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
        global $CFG, $PDOX;

        $key_key = self::sessionGet('key_key');
        $secret = self::sessionGet('secret');
        if ( $row !== false ) {
            $sourcedid = isset($row['sourcedid']) ? $row['sourcedid'] : false;
            $service = isset($row['service']) ? $row['service'] : false;
            // Fall back to session if it is missing
            if ( $service === false ) $service = self::sessionGet('service');
            $result_id = isset($row['result_id']) ? $row['result_id'] : false;
        } else {
            $sourcedid = self::sessionGet('sourcedid');
            $service = self::sessionGet('service');
            $result_id = self::sessionGet('result_id');
        }

        if ( $key_key == false || $secret === false ||
            $sourcedid === false || $service === false ) {
            error_log("LTIX::gradeGet is missing required data");
            return false;
        }

        $grade = LTI::getPOXGrade($sourcedid, $service, $key_key, $secret, $debug_log);

        if ( is_string($grade) ) return $grade;

        // UPDATE our local copy of the server's view of the grade
        if ( $result_id !== false ) {
            $stmt = $PDOX->queryDie(
                "UPDATE {$CFG->dbprefix}lti_result SET server_grade = :server_grade,
                    retrieved_at = NOW() WHERE result_id = :RID",
                array( ':server_grade' => $grade, ":RID" => $result_id)
            );
        }
        return $grade;
    }

    /**
      * Send a grade and update our local copy
      *
      * Call the right LTI service to send a new grade up to the server.
      * update our local cached copy of the server_grade and the date
      * retrieved. This routine pulls the key and secret from the LTIX
      * session to avoid crossing cross tennant boundaries.
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
      * @return mixed If this work this returns true.  If not, you get
      * a string with an error.
      *
      */
    public static function gradeSend($grade, $row=false, &$debug_log=false) {
        global $CFG, $PDOX, $LINK, $USER;
        global $LastPOXGradeResponse;
        $LastPOXGradeResponse = false;

        // Secret and key from session to avoid crossing tenant boundaries
        $key_key = self::sessionGet('key_key');
        $secret = self::sessionGet('secret');
        if ( $row !== false ) {
            $result_url = isset($row['result_url']) ? $row['result_url'] : false;
            $sourcedid = isset($row['sourcedid']) ? $row['sourcedid'] : false;
            $service = isset($row['service']) ? $row['service'] : false;
            // Fall back to session if it is missing
            if ( $service === false ) $service = self::sessionGet('service');
            $result_id = isset($row['result_id']) ? $row['result_id'] : false;
        } else {
            $result_url = self::sessionGet('result_url');
            $sourcedid = self::sessionGet('sourcedid');
            $service = self::sessionGet('service');
            $result_id = self::sessionGet('result_id');
        }

        if ( $key_key == false || $secret === false ||
            $sourcedid === false || $service === false ||
            !isset($USER) || !isset($LINK) ) {
            error_log("LTIX::gradeGet is missing required data");
            return false;
        }

        $comment = "YO";
        if ( strlen($result_url) > 0 ) {
            $status = LTI::sendJSONGrade($grade, $comment, $result_url, $key_key, $secret, $debug_log);
        } else {
            $status = LTI::sendPOXGrade($grade, $sourcedid, $service, $key_key, $secret, $debug_log);
        }

        if ( $status === true ) {
            $msg = 'Grade sent '.$grade.' to '.$sourcedid.' by '.$USER->id;
            if ( is_array($debug_log) )  $debug_log[] = array($msg);
            error_log($msg);
        } else {
            $msg = 'Grade failure '.$grade.' to '.$sourcedid.' by '.$USER->id;
            if ( is_array($debug_log) )  $debug_log[] = array($msg);
            error_log($msg);
            return $status;
        }

        // Update result in the database and in the LTI session area and $LINK
        $_SESSION['lti']['grade'] = $grade;
        if ( isset($LINK) ) $LINK->grade = $grade;

        // Update the local copy of the grade in the lti_result table
        if ( $PDOX !== false ) {
            $stmt = $PDOX->queryReturnError(
                "UPDATE {$CFG->dbprefix}lti_result SET grade = :grade,
                    updated_at = NOW() WHERE result_id = :RID",
                array(
                    ':grade' => $grade,
                    ':RID' => $result_id)
            );
            if ( $stmt->success ) {
                $msg = "Grade updated result_id=".$result_id." grade=$grade";
            } else {
                $msg = "Grade NOT updated result_id=".$result_id." grade=$grade";
            }
            error_log($msg);
            if ( is_array($debug_log) )  $debug_log[] = array($msg);
        }

        return $status;
    }

    /**
      * Send settings to the LMS using the simple JSON approach
      */
    public static function settingsSend($settings, $settings_url, &$debug_log=false) {

        $key_key = self::sessionGet('key_key');
        $secret = self::sessionGet('secret');

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

        $caliperURL = LTIX::postGet('custom_sub_canvas_caliper_url');
        if ( strlen($caliperURL) == 0 ) {
            if ( is_array($debug_log) ) $debug_log[] = array('custom_sub_canvas_caliper_url not found in launch data');
            return false;
        }

        $key_key = self::sessionGet('key_key');
        $secret = self::sessionGet('secret');

        $retval = LTI::sendJSONBody("POST", $caliperBody, $content_type, 
            $caliperURL, $key_key, $secret, $debug_log);
        return $retval;
    }

    /**
     * Send a JSON Body to a URL after looking up the key and secret
     */
    public static function jsonSend($method, $postBody, $content_type, 
        $service_url, &$debug_log=false) {

        $key_key = self::sessionGet('key_key');
        $secret = self::sessionGet('secret');

        $retval = LTI::sendJSONBody($method, $postBody, $content_type, 
            $service_url, $key_key, $secret, $debug_log);
        return $retval;
    }

}
