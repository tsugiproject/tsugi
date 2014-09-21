<?php

namespace Tsugi\Core;

use \Tsugi\OAuth\TrivialOAuthDataStore;
use \Tsugi\OAuth\OAuthServer;
use \Tsugi\OAuth\OAuthSignatureMethod_HMAC_SHA1;
use \Tsugi\OAuth\OAuthRequest;

use \Tsugi\Util\LTI;
use \Tsugi\Core\Settings;

/**
 * This an extended LTI class that defines how Tsugi tools interact with LTI
 *
 * This class deals with all of the session and database/data model
 * details that Tsugi tools make use of during runtime.  Since this extends
 * LTI, some of the methods from LTI are low-level while the LTIX-added methods
 * are higher level.
 *
 */
class LTIX Extends LTI {

    /**
     * Silently check if this is a launch and if so, handle it
     */
    public static function launchCheck() {
        if ( ! self::isRequest() ) return false;
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
     * Pull a keyed variable from the LTI data in the current session.
     */
    public static function getLTIData($varname, $default=false) {
        if ( ! isset($_SESSION['lti']) ) return $default;
        $lti = $_SESSION['lti'];
        if ( ! isset($lti[$varname]) ) return $default;
        return $lti[$varname];
    }

    /**
     * Pull out a custom variable from the LTIX session
     */
    public static function getCustom($varname, $default=false) {
        if ( isset($_SESSION['lti_post']) &&
                isset($_SESSION['lti_post']['custom_'.$varname]) ) {
            return $_SESSION['lti_post']['custom_'.$varname];
        }
        return $default;
    }

    /**
     * Extract all of the post data, set up data in tables, and set up session.
     */
    public static function setupSession() {
        global $CFG, $PDOX;
        if ( ! self::isRequest() ) return false;

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
        $valid = self::verifyKeyAndSecret($post['key'],$row['secret']);

        // If there is a new_secret it means an LTI2 re-registration is in progress and we
        // need to check both the current and new secret until the re-registration is committed
        if ( $valid !== true && strlen($row['new_secret']) > 0 && $row['new_secret'] != $row['secret']) {
            $valid = self::verifyKeyAndSecret($post['key'],$row['new_secret']);
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
        // Unescape each time we use this stuff - somedy we won't need this...
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

        $retval['service'] = isset($FIXED['lis_outcome_service_url']) ? $FIXED['lis_outcome_service_url'] : null;
        $retval['sourcedid'] = isset($FIXED['lis_result_sourcedid']) ? $FIXED['lis_result_sourcedid'] : null;

        $retval['context_title'] = isset($FIXED['context_title']) ? $FIXED['context_title'] : null;
        $retval['link_title'] = isset($FIXED['resource_link_title']) ? $FIXED['resource_link_title'] : null;
        $retval['user_email'] = isset($FIXED['lis_person_contact_email_primary']) ? $FIXED['lis_person_contact_email_primary'] : null;
        if ( isset($FIXED['lis_person_name_full']) ) {
            $retval['user_displayname'] = $FIXED['lis_person_name_full'];
        } else if ( isset($FIXED['lis_person_name_given']) && isset($FIXED['lis_person_name_family']) ) {
            $retval['user_displayname'] = $FIXED['lis_person_name_given'].' '.$FIXED['lis_person_name_family'];
        } else if ( isset($FIXED['lis_person_name_given']) ) {
            $retval['user_displayname'] = $FIXED['lis_person_name_given'];
        } else if ( isset($FIXED['lis_person_name_family']) ) {
            $retval['user_displayname'] = $FIXED['lis_person_name_given'];
        }
        $retval['role'] = 0;
        if ( isset($FIXED['roles']) ) {
            $roles = strtolower($FIXED['roles']);
            if ( ! ( strpos($roles,'instructor') === false ) ) $retval['role'] = 1;
            if ( ! ( strpos($roles,'administrator') === false ) ) $retval['role'] = 1;
        }
        return $retval;
    }

    // Make sure to include the file in case multiple instances are running
    // on the same server and they have not changed the session secret
    // Also make these change every 30 minutes
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
        $sql = "SELECT k.key_id, k.key_key, k.secret, k.new_secret,
            n.nonce,
            c.context_id, c.title AS context_title,
            l.link_id, l.title AS link_title, l.settings AS link_settings,
            u.user_id, u.displayname AS user_displayname, u.email AS user_email,
            u.subscribe AS subscribe, u.user_sha256 AS user_sha256,
            m.membership_id, m.role, m.role_override";

        if ( $profile_table ) {
            $sql .= ",
            p.profile_id, p.displayname AS profile_displayname, p.email AS profile_email,
            p.subscribe AS profile_subscribe";
        }

        if ( $post['service'] ) {
            $sql .= ",
            s.service_id, s.service_key AS service";
        }

        if ( $post['sourcedid'] ) {
            $sql .= ",
            r.result_id, r.sourcedid, r.grade";
        }

        $sql .="\nFROM {$p}lti_key AS k
            LEFT JOIN {$p}lti_nonce AS n ON k.key_id = n.key_id AND n.nonce = :nonce
            LEFT JOIN {$p}lti_context AS c ON k.key_id = c.key_id AND c.context_sha256 = :context
            LEFT JOIN {$p}lti_link AS l ON c.context_id = l.context_id AND l.link_sha256 = :link
            LEFT JOIN {$p}lti_user AS u ON k.key_id = u.key_id AND u.user_sha256 = :user
            LEFT JOIN {$p}lti_membership AS m ON u.user_id = m.user_id AND c.context_id = m.context_id";

        if ( $profile_table ) {
            $sql .= "
            LEFT JOIN {$profile_table} AS p ON u.profile_id = p.profile_id";
        }

        if ( $post['service'] ) {
            $sql .= "
            LEFT JOIN {$p}lti_service AS s ON k.key_id = s.key_id AND s.service_sha256 = :service";
        }
        if ( $post['sourcedid'] ) {
            $sql .= "
            LEFT JOIN {$p}lti_result AS r ON u.user_id = r.user_id AND l.link_id = r.link_id";
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
                ( context_key, context_sha256, title, key_id, created_at, updated_at ) VALUES
                ( :context_key, :context_sha256, :title, :key_id, NOW(), NOW() )";
            $PDOX->queryDie($sql, array(
                ':context_key' => $post['context_id'],
                ':context_sha256' => lti_sha256($post['context_id']),
                ':title' => $post['context_title'],
                ':key_id' => $row['key_id']));
            $row['context_id'] = $PDOX->lastInsertId();
            $row['context_title'] = $post['context_title'];
            $actions[] = "=== Inserted context id=".$row['context_id']." ".$row['context_title'];
        }

        if ( $row['link_id'] === null && isset($post['link_id']) ) {
            $sql = "INSERT INTO {$p}lti_link
                ( link_key, link_sha256, title, context_id, created_at, updated_at ) VALUES
                    ( :link_key, :link_sha256, :title, :context_id, NOW(), NOW() )";
            $PDOX->queryDie($sql, array(
                ':link_key' => $post['link_id'],
                ':link_sha256' => lti_sha256($post['link_id']),
                ':title' => $post['link_title'],
                ':context_id' => $row['context_id']));
            $row['link_id'] = $PDOX->lastInsertId();
            $row['link_title'] = $post['link_title'];
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
            $row['user_sha256'] = lti_sha256($post['user_id']);
            $row['user_displayname'] = $user_displayname;
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

        // We need to handle the case where the service URL changes but we already have a sourcedid
        $oldserviceid = $row['service_id'];
        if ( $row['service_id'] === null && $post['service'] && $post['sourcedid'] ) {
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
        if ( $oldserviceid === null && $row['result_id'] !== null && $row['service_id'] !== null && $post['service'] && $post['sourcedid'] ) {
            $sql = "UPDATE {$p}lti_result SET service_id = :service_id WHERE result_id = :result_id";
            $PDOX->queryDie($sql, array(
                ':service_id' => $row['service_id'],
                ':result_id' => $row['result_id']));
            $actions[] = "=== Updated result id=".$row['result_id']." service=".$row['service_id']." ".$post['sourcedid'];
        }

        // If we don'have a result but do have a service - link them together
        if ( $row['result_id'] === null && $row['service_id'] !== null && $post['service'] && $post['sourcedid'] ) {
            $sql = "INSERT INTO {$p}lti_result
                ( sourcedid, sourcedid_sha256, service_id, link_id, user_id, created_at, updated_at ) VALUES
                ( :sourcedid, :sourcedid_sha256, :service_id, :link_id, :user_id, NOW(), NOW() )";
            $PDOX->queryDie($sql, array(
                ':sourcedid' => $post['sourcedid'],
                ':sourcedid_sha256' => lti_sha256($post['sourcedid']),
                ':service_id' => $row['service_id'],
                ':link_id' => $row['link_id'],
                ':user_id' => $row['user_id']));
            $row['result_id'] = $PDOX->lastInsertId();
            $row['sourcedid'] = $post['sourcedid'];
            $actions[] = "=== Inserted result id=".$row['result_id']." service=".$row['service_id']." ".$post['sourcedid'];
        }

        // If we don'have a result and do not have a service - just store the result (prep for LTI 2.0)
        if ( $row['result_id'] === null && $row['service_id'] === null && ! $post['service'] && $post['sourcedid'] ) {
            $sql = "INSERT INTO {$p}lti_result
                ( sourcedid, sourcedid_sha256, link_id, user_id, created_at, updated_at ) VALUES
                ( :sourcedid, :sourcedid_sha256, :link_id, :user_id, NOW(), NOW() )";
            $PDOX->queryDie($sql, array(
                ':sourcedid' => $post['sourcedid'],
                ':sourcedid_sha256' => lti_sha256($post['sourcedid']),
                ':link_id' => $row['link_id'],
                ':user_id' => $row['user_id']));
            $row['result_id'] = $PDOX->lastInsertId();
            $actions[] = "=== Inserted LTI 2.0 result id=".$row['result_id']." service=".$row['service_id']." ".$post['sourcedid'];
        }

        // Here we handle updates to sourcedid
        if ( $row['result_id'] != null && $post['sourcedid'] != null && $post['sourcedid'] != $row['sourcedid'] ) {
            $sql = "UPDATE {$p}lti_result
                SET sourcedid = :sourcedid, sourcedid_sha256 = :sourcedid_sha256
                WHERE result_id = :result_id";
            $PDOX->queryDie($sql, array(
                ':sourcedid' => $post['sourcedid'],
                ':sourcedid_sha256' => lti_sha256($post['sourcedid']),
                ':result_id' => $row['result_id']));
            $row['sourcedid'] = $post['sourcedid'];
            $actions[] = "=== Updated sourcedid=".$row['sourcedid'];
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
     */
    public static function requireData($needed) {
        global $CFG, $USER, $CONTEXT, $LINK;

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
                    die_with_error_log('Missing '.$sess.'= on URL (Missing call to addSession?)');
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

        $LTI = $_SESSION['lti'];
        if ( is_string($needed) && ! isset($LTI[$needed]) ) {
            die_with_error_log("This tool requires an LTI launch parameter:".$needed);
        }
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
        if ( ! is_object($USER) ) {
            $USER = new \Tsugi\Core\User();
            if (isset($LTI['user_id']) ) $USER->id = $LTI['user_id'];
            if (isset($LTI['user_sha256']) ) $USER->sha256 = $LTI['user_sha256'];
            if (isset($LTI['user_email']) ) $USER->email = $LTI['user_email'];
            if (isset($LTI['user_displayname']) ) {
                $USER->displayname = $LTI['user_displayname'];
                $pieces = explode(' ',$USER->displayname);
                if ( count($pieces) > 0 ) $USER->firstname = $pieces[0];
                if ( count($pieces) > 1 ) $USER->lastname = $pieces[count($pieces)-1];
            }
            $USER->instructor = isset($LTI['role']) && $LTI['role'] != 0 ;
        }

        if ( ! is_object($CONTEXT) ) {
            $CONTEXT = new \Tsugi\Core\Context();
            if (isset($LTI['context_id']) ) $CONTEXT->id = $LTI['context_id'];
            if (isset($LTI['context_sha256']) ) $CONTEXT->sha256 = $LTI['context_sha256'];
            if (isset($LTI['context_title']) ) $CONTEXT->title = $LTI['context_title'];
        }

        if ( ! is_object($LINK) ) {
            $LINK = new \Tsugi\Core\Link();
            if (isset($LTI['link_id']) ) $LINK->id = $LTI['link_id'];
            if (isset($LTI['link_sha256']) ) $LINK->sha256 = $LTI['link_sha256'];
            if (isset($LTI['link_title']) ) $LINK->title = $LTI['link_title'];
        }

        // Return the LTI structure
        return $LTI;
    }


    /**
      * Load the grade for a particular row and update our local copy
      *
      * Call the right LTI service to retrieve the server's grade and
      * update our local cached copy of the server_grade and the date
      * retrieved.
      *
      * TODO: Add LTI 2.x support for the JSON style services to this
      *
      * @param An array with the data that has the result_id, sourcedid,
      * and service (url)
      *
      * @return mixed If this work this returns a float.  If not you get
      * a string with an error.
      *
      */
    function gradeGet($row) {
        global $CFG, $PDOX;

        $key_key = self::getLTIData('key_key');
        $secret = self::getLTIData('secret');
        $sourcedid = isset($row['sourcedid']) ? $row['sourcedid'] : false;
        $service = isset($row['service']) ? $row['service'] : false;
        if ( $key_key == false || $secret === false ||
            $sourcedid === false || $service === false ) {
            error_log("LTIX::gradeGet is missing reuired data");
            return false;
        }

        $grade = LTI::getPOXGrade($sourcedid, $service, $key_key, $secret);

        if ( is_string($grade) ) return $grade;

        $result_id = isset($row['result_id']) ? $row['result_id'] : false;

        // UPDATE our local copy of the server's view of the grade
        $stmt = $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}lti_result SET server_grade = :server_grade,
                retrieved_at = NOW() WHERE result_id = :RID",
            array( ':server_grade' => $grade, ":RID" => $result_id)
        );
        return $grade;
    }


}
