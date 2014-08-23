<?php

namespace Tsugi\Core;

/**
 * This is a class to provide access to the setting service.
 *
 * There are three scopes of settings: link, context, and key
 * The link level settings are by far the most widely used.
 *
 */
class Settings {

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
        $json = json_encode($keyvals);
        $q = $PDOX->queryDie("UPDATE {$CFG->dbprefix}lti_link 
                SET settings = :SET WHERE link_id = :LID",
            array(":SET" => $json, ":LID" => $LINK->id)
        );
        if ( isset($_SESSION['lti']) ) {
            $_SESSION['lti']['link_settings'] = $json;
        }
    }

    /**
     * Retrieve an array of all of the link level settings
     *
     * If there are no settings, return an empty array.
     */
    public static function linkGetAll()
    {
        global $CFG, $PDOX, $LINK;
        if ( isset($_SESSION['lti']) && isset($_SESSION['lti']['link_settings']) ) {
            $json = $_SESSION['lti']['link_settings'];
            if ( strlen($json) < 0 ) return array();
            $retval = json_decode($json, true); // No objects
            return $retval;
        }
        $row = $PDOX->rowDie("SELECT settings FROM {$CFG->dbprefix}lti_link WHERE link_id = :LID",
            array(":LID" => $LINK->id));
        if ( $row === false ) return array();
        $json = $row['settings'];
        if ( $json === null ) return array();
        $retval = json_decode($json, true); // No objects

        // Store in session for later
        if ( isset($_SESSION['lti']) ) {
            $_SESSION['lti']['link_settings'] = $json;
        }
        return $retval;
    }

    /**
     * Retrieve a particular key from the link settings.
     *
     * Returns the value found in settings or false if the key was not found.
     *
     * @param $key - The key to get from the settings.
     */
    public static function linkGet($key)
    {
        $allSettings = self::linkGetAll();
        if ( array_key_exists ($key, $allSettings ) ) {
            return $allSettings[$key];
        } else {
            return false;
        }
    }

    /**
     * Set or update a number of keys to new values in link settings.
     *
     * @params $keyvals An array of key value pairs that are to be placed in the
     * settings.
     */
    public static function linkSet($keyvals)
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
