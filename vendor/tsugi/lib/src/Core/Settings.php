<?php

namespace Tsugi\Core;

use \Tsugi\Core\LTIX;

/**
 * This is a class to provide access to the setting service.
 *
 * There are three scopes of settings: link, context, and key
 * The link level settings are by far the most widely used.
 *
 * In effect, this should be deprecated and folks should use the
 * methods in each entity.  
 *
 * A better pattern:
 *
 * $LAUNCH = LTIX::requireData();
 * $LAUNCH->link->settingsSet('key', 'value');
 *
 * But this is widely used in tool code so it will be hard to remove.
 * At least now it wraps the Link settings.
 *
 * @deprecated 
 */
class Settings {

    /**
      * Retrieve the debug array for the last operation.
      */
    public static function getDebugArray()
    {
        global $LINK;
        $retval = array();
        if ( ! $LINK ) return $retval;
        return $LINK->settingsDebug();
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
        global $LINK;
        if ( ! $LINK ) return false;
        return $LINK->settingsSetAll($keyvals);
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
        global $LINK;

        if ( ! $LINK ) return false;
        return $LINK->settingsGetAll();
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
        global $LINK;
        if ( ! $LINK ) return $default;
        return $LINK->settingsGet($key, $default);
    }

    /**
     * Set or update a key to a new value in link settings.
     *
     * @params $key The key to set in settings.
     * @params $value The value to set for that key
     */
    public static function linkSet($key, $value)
    {
        global $LINK;
        if ( ! $LINK ) return $default;
        return $LINK->settingsSet($key, $value);
    }

    /**
     * Set or update a number of keys to new values in link settings.
     *
     * @params $keyvals An array of key value pairs that are to be placed in the
     * settings.
     */
    public static function linkUpdate($keyvals)
    {
        global $LINK;
        if ( ! $LINK ) return;
        return $LINK->settingsUpdate($keyvals);
    }

}
