<?php

namespace Tsugi\Core;

use \Tsugi\Core\LTIX;

/**
 * This is a class to provide access to the setting service.
 *
 * There are three scopes of settings: link, context, and key
 * The link level settings are by far the most widely used.
 *
 *
 * In effect, this should be deprecated and folks should use the
 * methods in each entity.
 */
class Settings {

    /**
      * Retrieve the debug array for the last operation.
      */
    public static function getDebugArray()
    {
        global $settingsDebugArray;
        if ( !isset($settingsDebugArray) ) $settingsDebugArray = array();
        return $settingsDebugArray;
    }

    /**
     * Set all of the the link-level settings.
     *
     * @param $keyvals An array of key/value pairs that is serialized
     * in JSON and stored.  If this is an empty array, this effectively
     * empties out all the settings.
     */
    public static function linkSetAll($keyvals)
    {
        global $CFG, $PDOX, $LINK;
        global $settingsDebugArray;

        $settingsDebugArray = array();
        $json = json_encode($keyvals);
        $q = $PDOX->queryDie("UPDATE {$CFG->dbprefix}lti_link 
                SET settings = :SET WHERE link_id = :LID",
            array(":SET" => $json, ":LID" => $LINK->id)
        );
        $settingsDebugArray[] = array(count($keyvals)." settings updated link_id=".$LINK->id);
        if ( isset($_SESSION['lti']) ) {
            $_SESSION['lti']['link_settings'] = $json;
            unset($_SESSION['lti']['link_settings_merge']);
        }
        $settings_url = LTIX::ltiParameter('link_settings_url',null);
        if ( $settings_url === null ) return;

        $settingsDebugArray[] = array("Sending settings to ".$settings_url);
        $retval = LTIX::settingsSend($keyvals, $settings_url, $settingsDebugArray);
    }

    /**
     * Retrieve an array of all of the link level settings
     *
     * If there are no settings, return an empty array.  
     *
     * This routine also looks for legacy custom fields and treats
     * them as defaults for settings if the corresponding key is not
     * already present in settings.  This will slowly convert LTI 
     * 1.x custom parameters under the control of the LMS to LTI 2.x 
     * style settings under control of our local tools.
     */
    public static function linkGetAll()
    {
        global $CFG, $PDOX, $LINK;

        if ( ! isset($_SESSION['lti']) ) return array();

        if ( isset($_SESSION['lti']['link_settings_merge']) ) {
            return $_SESSION['lti']['link_settings_merge'];
        }

        $legacy_fields = array('dologin', 'close', 'due', 'timezone', 'penalty_time', 'penalty_cost');
        $defaults = array();
        foreach($legacy_fields as $k ) {
            $value = LTIX::ltiCustomGet($k);
            $defaults[$k] = $value;
        }
        if ( isset($_SESSION['lti']['link_settings']) ) {
            $json = $_SESSION['lti']['link_settings'];
            if ( strlen($json) < 0 ) return $defaults;
            $retval = json_decode($json, true); // No objects
            $retval = array_merge($defaults, $retval);
            $_SESSION['lti']['link_settings_array'] = $retval;
            return $retval;
        }

        // Not in session - retrieve from the database

        // We cannot assume the $LINK is fully set up yet...
        if ( !isset($_SESSION['lti']['link_id']) ) return $defaults;

        $row = $PDOX->rowDie("SELECT settings FROM {$CFG->dbprefix}lti_link WHERE link_id = :LID",
            array(":LID" => $_SESSION['lti']['link_id']));
        if ( $row === false ) return $defaults;
        $json = $row['settings'];
        if ( $json === null ) return $defaults;
        $retval = json_decode($json, true); // No objects
        $retval = array_merge($defaults, $retval);

        // Store in session for later
        $_SESSION['lti']['link_settings'] = $json;
        $_SESSION['lti']['link_settings_array'] = $retval;
        return $retval;
    }

    /**
     * Retrieve a particular key from the link settings.
     *
     * Returns the value found in settings or false if the key was not found.
     *
     * @param $key - The key to get from the settings.
     * @param $default - What to return if the key is not present
     */
    public static function linkGet($key, $default=false)
    {
        $allSettings = self::linkGetAll();
        if ( array_key_exists ($key, $allSettings ) ) {
            return $allSettings[$key];
        } else {
            return $default;
        }
    }

    /**
     * Set or update a key to a new value in link settings.
     *
     * @params $key The key to set in settings.
     * @params $value The value to set for that key
     */
    public static function linkSet($key, $value)
    {
        $newset = array($key => $value);
        self::linkUpdate($newset);
    }

    /**
     * Set or update a number of keys to new values in link settings.
     *
     * @params $keyvals An array of key value pairs that are to be placed in the
     * settings.
     */
    public static function linkUpdate($keyvals)
    {
        global $PDOX;
        $allSettings = self::linkGetAll();
        $different = false;
        foreach ( $keyvals as $k => $v ) {
            if ( array_key_exists ($k, $allSettings ) ) {
                if ( $v != $allSettings[$k] ) {
                    $different = true;
                    break;
                }
            } else {
                $different = true;
                break;
            }
        }
        if ( ! $different ) return;
        $newSettings = array_merge($allSettings, $keyvals);
        self::linkSetAll($newSettings);
    }

}
