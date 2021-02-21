<?php

namespace Tsugi\Core;

use \Tsugi\Util\U;

/**
 * This is a class holding convienence methods to access settings from core objects.
 * 
 * Settings exist at the key, link, and context.  Link settings are the most common.
 */

trait SettingsTrait {

    // A place to store the debugging setting
    public $settingsDebugArray;

    /**
     * Retrieve an array of all of the settings
     *
     * If there are no settings, return an empty array. 
     */
    public function settingsGetAll()
    {
        global $CFG;
        $name = $this->ENTITY_NAME;
        $retval = $this->ltiParameter($name."_settings", false);
        $inSession = $retval !== false;

        // Null means in the session - false means not in the session
        if ( $retval === null || ( is_string($retval) && strlen($retval) < 1 ) ) {
            $retval = array();
        } else if ( strlen($retval) > 0 ) {
            try {
                $retval = json_decode($retval, true);
            } catch(Exception $e) {
                $retval = array();
            }
        }
        // echo("Session $name: "); var_dump($retval);
        if ( is_array($retval) ) return $retval;

        $row = $this->launch->pdox->rowDie("SELECT settings FROM {$CFG->dbprefix}{$this->TABLE_NAME}
            WHERE {$name}_id = :ID",
            array(":ID" => $this->id));

        if ( $row === false ) return array();
        $json = $row['settings'];
        if ( $json === null ) return array();
        try {
            $retval = json_decode($json, true);
        } catch(Exception $e) {
            $retval = array();
        }

        // Put in session for quicker retrieval
        $this->ltiParameterUpdate($name."_settings", json_encode($retval));
        // echo("<hr>From DB\n");var_dump($retval);
        return $retval;
    }

    /**
     * Retrieve a particular key from the settings.
     *
     * Returns the value found in settings or false if the key was not found.
     *
     * @param $key - The key to get from the settings.
     * @param $default - What to return if the key is not present
     */
    public function settingsGet($key, $default=false)
    {
        $allSettings = $this->settingsGetAll();
        if ( array_key_exists ($key, $allSettings ) ) {
            return $allSettings[$key];
        } else {
            return $default;
        }
    }

    /**
     * Update a single key in settings
     */
    public function settingsSet($key, $value)
    {
        $allSettings = $this->settingsGetAll();
        $allSettings[$key] = $value;
        $this->settingsSetAll($allSettings);
    }

    /**
     * Set or update a number of keys to new values in link settings.
     *
     * @params $keyvals An array of key value pairs that are to be placed in the
     * settings.
     */
    public function settingsUpdate($keyvals)
    {
        $allSettings = $this->settingsGetAll();
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
        return $this->settingsSetAll($newSettings);
    }

    /**
     * Replace all the settings (Dangerous)
     */
    protected function settingsSetAll($new_settings)
    {
        global $CFG;

        $this->settingsDebugArray = array();
        if ( ! is_array($new_settings) ) {
            $this->settingsDebugArray[] = 'settingsSetAll requires an array';
            return false;
        }

        $name = $this->ENTITY_NAME;

        // Update in session for quicker retrieval
        $this->ltiParameterUpdate($name."_settings", json_encode($new_settings));

        $stmt = $this->launch->pdox->queryDie("UPDATE {$CFG->dbprefix}{$this->TABLE_NAME}
            SET settings = :NEW, updated_at=NOW() WHERE {$name}_id = :ID",
            array(":NEW" => json_encode($new_settings), ":ID" => $this->id));

        if ( ! $stmt->success ) {
            $this->settingsDebugArray[] = "Failed to update settings {$name}_id=".$this->id;
            return false;
        }
        $this->settingsDebugArray[] = count($new_settings)." settings updated {$name}_id=".$this->id;

        $settings_url = $this->ltiParameter($name."_settings_url", null);  // NULL is actually empty
        if ( $settings_url === null ) return true;

        $this->settingsDebugArray[] = array("Sending settings to ".$settings_url);
        $retval = LTIX::settingsSend($new_settings, $settings_url, $this->settingsDebugArray);
        return $retval;
    }

    /**
      * Retrieve the debug array for the last operation.
      */
    public function settingsDebug()
    {
        return $this->settingsDebugArray;
    }

}
