<?php

namespace Tsugi\Core;

use \Tsugi\Util\U;
use \Tsugi\Core\Cache;
use \Tsugi\Core\LTIX;

/**
 * This is a class to provide access to the resource link level data.
 *
 * This data comes from the LTI launch from the LMS.
 * A resource_link may or may not be in a context.  If there
 * is a link without a context, it is a "system-wide" link
 * like "view profile" or "show all courses"
 */

class Link extends Entity {

    // Needed to implement the Entity methods
    protected $TABLE_NAME = "lti_link";
    protected $PRIMARY_KEY = "link_id";

    // Links have settings...
    protected $ENTITY_NAME = "link";
    use SettingsTrait;  // Pull in the trait

    /**
     * The integer primary key for this link in the 'lti_link' table.
     */
    public $id;

    /**
     * The link title
     */
    public $title;

    /**
     * The current grade for the user
     *
     * If there is a current grade (float between 0.0 and 1.0)
     * it is in this variable.  If there is not yet a grade for
     * this user/link combination, this will be null.
     */
    public $grade = null;

    /**
     * The result_id for the link (if set)
     *
     * This is the primary key for the lti_result row for this
     * user/link combination.  It may be null.  Is this is not
     * null, we can send a grade back to the LMS for this
     * user/link combination.
     */
    public $result_id = null;

    /**
     * The count of the overall activity on this link at the moment of launch
     */
    public $activity = 0;

    /**
     * The count of the user's activity on this link at the moment of launch
     */
    public $user_activity = 0;

    /**
     * Load link information for a different link than current
     *
     * Make sure not to cross Context silos.
     *
     * Returns a row or false.
     */
    public static function loadLinkInfo($link_id)
    {
        global $CFG, $PDOX, $CONTEXT;

        $cacheloc = 'lti_link';
        $row = Cache::check($cacheloc, $link_id);
        if ( $row != false ) return $row;
        $stmt = $PDOX->queryDie(
            "SELECT title FROM {$CFG->dbprefix}lti_link
                WHERE link_id = :LID AND context_id = :CID",
            array(":LID" => $link_id, ":CID" => $CONTEXT->id)
        );
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        Cache::set($cacheloc, $link_id, $row);
        return $row;
    }

    /**
     * Get the placement secret for this Link
     */
    public function getPlacementSecret()
    {
        global $CFG;
        $PDOX = $this->launch->pdox;

        $stmt = $PDOX->queryDie(
            "SELECT placementsecret FROM {$CFG->dbprefix}lti_link
                WHERE link_id = :LID",
            array(':LID' => $this->id)
        );
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $placementsecret = $row['placementsecret'];
        if ( $placementsecret) return $placementsecret;

        // Set the placement secret
        $placementsecret = bin2Hex(openssl_random_pseudo_bytes(32));

        $stmt = $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}lti_link SET placementsecret=:PC, updated_at=NOW()
                WHERE link_id = :LID",
            array(':LID' => $this->id,
                ':PC' => $placementsecret
            )
        );
        return $placementsecret;
    }

    /**
     * Load defaults from custom parameters once
     *
     * Example:
     *
     *     $LAUNCH->link->settingsDefaultsFromCustom(array('tries', 'delay'));
     */
    public function settingsDefaultsFromCustom($keys)
    {
        $oldsettings = $this->settingsGetAll();
        $changed = false;
        if ( ! is_array($keys) ) return;
        foreach($keys as $key) {
            if ( array_key_exists($key, $oldsettings) ) continue;
            $default = $this->launch->ltiCustomGet($key);
            if ( $default && is_string($default) && U::strlen($default) > 0 ) {
                $oldsettings[$key] = $default;
                $changed = true;
            }
        }
        if ( ! $changed ) return;
        $this->settingsSetAll($oldsettings);
    }

    /**
     * Retrieve a setting by bubbling up from link to context to key to system level
     * 
     * This overrides the settingsGet method in the SettingsTrait.
     *
     * This method looks for a setting at the link level first. If not found or empty,
     * it checks the context level. If still not found or empty, it checks the key level.
     * If still not found or empty, it checks the system-wide extension settings via $CFG->getExtension()
     * adding a prefix of "globalsetting_" to the key.
     * Returns the first non-null value found, or the default if none is found.
     *
     * @param string $key The setting key to retrieve
     * @param mixed $default The default value to return if setting is not found or null at any level
     * @return mixed The setting value found at the lowest level that is not null, or the default
     */
    public function settingsGet($key, $default = false)
    {
        global $CFG;

        // First check link level
        $linkSettings = $this->settingsGetAll();
        if ( array_key_exists($key, $linkSettings) ) {
            $value = $linkSettings[$key];
            // Return if value is not null and not empty string
            if ( $value !== null && $value !== '' ) {
                return LTIX::decrypt_secret($value);
            }
            // If value is null or empty string, continue to next level
        }

        // Then check context level
        if ( isset($this->launch->context) && is_object($this->launch->context) ) {
            $contextSettings = $this->launch->context->settingsGetAll();
            if ( array_key_exists($key, $contextSettings) ) {
                $value = $contextSettings[$key];
                // Return if value is not null and not empty string
                if ( $value !== null && $value !== '' ) {
                    return LTIX::decrypt_secret($value);
                }
                // If value is null or empty string, continue to next level
            }
        }

        // Then check key level
        if ( isset($this->launch->key) && is_object($this->launch->key) ) {
            $keySettings = $this->launch->key->settingsGetAll();
            if ( array_key_exists($key, $keySettings) ) {
                $value = $keySettings[$key];
                // Return if value is not null and not empty string
                if ( $value !== null && $value !== '' ) {
                    return LTIX::decrypt_secret($value);
                }
                // If value is null or empty string, continue to next level
            }
        }

        // Finally check system-wide extension settings
        if ( isset($CFG) && is_object($CFG) && method_exists($CFG, 'getExtension') ) {
            $value = $CFG->getExtension("globalsetting_".$key, null);
            // Return if value is not null and not empty string
            if ( $value !== null && $value !== '' ) {
                return LTIX::decrypt_secret($value);
            }
        }

        // Not found at any level or all values were null/empty, return default
        return $default;
    }

}
