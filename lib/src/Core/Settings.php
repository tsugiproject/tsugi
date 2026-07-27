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
     * Get a key value or fall back to a custom value in the launch
     *
     * @params $key The key to set in settings.
     */
    public static function linkGetCustom($key)
    {
        global $LINK, $LAUNCH;
        if ( ! $LINK ) return null;
        $value = $LINK->settingsGet($key, null);
        if ( $value !== null ) return $value; // Already set

        if ( ! $LAUNCH ) return null;
        $custom = $LAUNCH->ltiCustomGet($key, null);
        if ( $custom === null ) return null; // Nothing in custom, no current value

        // Set the local settings value from custom as the default
        $LINK->settingsSet($key, $custom);
        return $custom;
    }

    /**
     * Default link configuration from launch sources into Settings.
     *
     * Opt-in helper for tools that want a placement setting defaulted on first
     * launch from LTI custom or ?key=. Once defaulted, later launches use
     * Settings and no longer depend on custom or GET.
     *
     * Precedence:
     *   1. Link Settings (already configured) — when $LINK is present
     *   2. LTI custom for $key (persisted when present) — when $LINK/$LAUNCH
     *   3. $_GET[$key] (persisted when $LINK is present)
     *
     * When $LINK is not defined (non-LTI / standalone use), Settings and
     * custom are skipped and a present GET value is returned without
     * persistence so tools can still run outside an LTI launch.
     *
     * When $allowed is provided, empty, "0", false, or any value not in
     * $allowed is treated as unset and does not block later sources. Invalid
     * values are never persisted. When $allowed is omitted, no validity
     * checking is done — any present value is accepted and persisted.
     *
     * @param string $key Setting / custom / GET parameter name
     * @param array|null $allowed Optional list of valid values (values, not labels)
     * @return string|null
     */
    public static function linkDefaultConfigurationFromLaunch($key, $allowed=null)
    {
        global $LINK, $LAUNCH;

        // Standalone / non-LTI launches may leave these unset or null.
        $link = (isset($LINK) && is_object($LINK)) ? $LINK : null;
        $launch = (isset($LAUNCH) && is_object($LAUNCH)) ? $LAUNCH : null;

        if ( ! is_string($key) || $key === '' ) {
            return null;
        }
        if ( $allowed !== null && ! is_array($allowed) ) {
            return null;
        }

        $checkAllowed = is_array($allowed);
        $valid = array();
        if ( $checkAllowed ) {
            foreach ( $allowed as $v ) {
                if ( is_string($v) || is_int($v) ) {
                    $valid[(string) $v] = true;
                }
            }
        }

        $normalize = function ($value) use ($checkAllowed, $valid) {
            if ( $value === null ) {
                return null;
            }
            if ( $checkAllowed ) {
                if ( $value === false || $value === ''
                    || $value === 0 || $value === '0' ) {
                    return null;
                }
                if ( ! is_string($value) && ! is_int($value) ) {
                    return null;
                }
                $value = (string) $value;
                return isset($valid[$value]) ? $value : null;
            }
            if ( is_string($value) || is_int($value) || is_float($value) ) {
                return (string) $value;
            }
            if ( is_bool($value) ) {
                return $value ? '1' : '0';
            }
            return null;
        };

        if ( $link && method_exists($link, 'settingsGet') ) {
            $value = $normalize($link->settingsGet($key, null));
            if ( $value !== null ) {
                return $value;
            }
        }

        if ( $link && $launch && method_exists($launch, 'ltiCustomGet')
            && method_exists($link, 'settingsSet') ) {
            $custom = $normalize($launch->ltiCustomGet($key, null));
            if ( $custom !== null ) {
                $link->settingsSet($key, $custom);
                return $custom;
            }
        }

        if ( isset($_GET[$key]) ) {
            $fromGet = $normalize($_GET[$key]);
            if ( $fromGet !== null ) {
                if ( $link && method_exists($link, 'settingsSet') ) {
                    $link->settingsSet($key, $fromGet);
                }
                return $fromGet;
            }
        }

        return null;
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
