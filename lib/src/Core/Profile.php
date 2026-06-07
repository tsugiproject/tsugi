<?php

namespace Tsugi\Core;

use \Tsugi\Util\U;

/**
 * Site profile for a Google-login (or similarly linked) user.
 *
 * Every {@see Launch} carries a Profile object after {@see LTIX::buildLaunch()}.
 * When no site profile is linked (typical Canvas launch), {@see $id} is 0, {@see $premium}
 * is 0, and {@see isLinked()} is false — but {@see isPremium()} and provider helpers
 * are safe to call without null checks.
 *
 * Billing integrations store per-provider metadata in {@see $premium_json}.
 * Top-level keys are provider ids (e.g. "stripe"); each value is that provider's object:
 *
 *   {"stripe":{"customer_id":"cus_...","subscription_id":"sub_..."},"paypal":{...}}
 *
 * {@see $premium} is a tier level: 0 = no premium, 1 = base tier, higher = richer tiers.
 *
 * User-facing preferences (theme, map, etc.) remain in the profile {@see json} column
 * via JsonTrait; premium_json is for subscription / payment metadata only.
 */
class Profile extends Entity {

    protected $TABLE_NAME = "profile";
    protected $PRIMARY_KEY = "profile_id";
    protected $ENTITY_NAME = "profile";

    /**
     * Primary key in the profile table (0 when no profile is linked).
     */
    public $id = 0;

    /**
     * Premium tier level (0 = none, 1+ = active tier).
     */
    public $premium = 0;

    /**
     * When premium access became active (NULL if never premium).
     */
    public $premium_at = null;

    /**
     * Payment metadata keyed by provider id (e.g. "stripe" => {...}). Raw JSON string.
     */
    public $premium_json = null;

    /**
     * Current premium tier (always >= 0).
     *
     * @return int
     */
    public function getPremiumLevel() {
        return self::normalizePremiumLevel($this->premium);
    }

    /**
     * True when the profile has any premium tier (level >= 1).
     *
     * @return bool
     */
    public function isPremium() {
        return $this->getPremiumLevel() > 0;
    }

    /**
     * True when this launch is linked to a row in the profile table.
     *
     * @return bool
     */
    public function isLinked() {
        return is_numeric($this->id) && $this->id > 0;
    }

    /**
     * True when the profile meets at least the requested tier.
     *
     * @param int $level Minimum tier required (1 = base premium)
     * @return bool
     */
    public function isPremiumLevel($level) {
        $level = self::normalizePremiumLevel($level);
        if ( $level < 1 ) {
            return true;
        }
        return $this->getPremiumLevel() >= $level;
    }

    /**
     * @param mixed $value
     * @return int
     */
    public static function normalizePremiumLevel($value) {
        if ( ! is_numeric($value) ) {
            return 0;
        }
        $level = (int) $value;
        if ( $level < 0 ) {
            return 0;
        }
        return $level;
    }

    /**
     * Decode premium_json to an associative array (provider id => provider data).
     *
     * @return array<string,mixed>
     */
    public function getPremiumJsonArray() {
        if ( ! is_string($this->premium_json) || U::strlen($this->premium_json) < 1 ) {
            return array();
        }
        try {
            $decoded = json_decode($this->premium_json, true);
        } catch (\Exception $e) {
            return array();
        }
        return is_array($decoded) ? $decoded : array();
    }

    /**
     * Whether premium_json has a block for the given provider.
     *
     * @param string $provider Provider id (e.g. "stripe")
     * @return bool
     */
    public function hasPremiumProvider($provider) {
        if ( ! is_string($provider) || U::strlen($provider) < 1 ) {
            return false;
        }
        $block = U::get($this->getPremiumJsonArray(), $provider, null);
        return is_array($block);
    }

    /**
     * Provider-specific metadata object from premium_json.
     *
     * @param string $provider Provider id (e.g. "stripe")
     * @return array<string,mixed>
     */
    public function getPremiumProviderArray($provider) {
        if ( ! is_string($provider) || U::strlen($provider) < 1 ) {
            return array();
        }
        $block = U::get($this->getPremiumJsonArray(), $provider, null);
        return is_array($block) ? $block : array();
    }

    /**
     * Read a key from one provider's block in premium_json.
     *
     * @param string $provider Provider id (e.g. "stripe")
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getPremiumProviderKey($provider, $key, $default=false) {
        return U::get($this->getPremiumProviderArray($provider), $key, $default);
    }

    /**
     * Build a Profile from the LTI session row.
     *
     * Always returns a Profile. When profile_id is missing, returns an empty
     * placeholder (id 0, premium 0, no providers).
     *
     * @param array<string,mixed> $LTI
     * @param Launch $launch
     * @return self
     */
    public static function fromLaunchRow(array $LTI, Launch $launch) {
        $profile = new self();
        $profile->launch = $launch;

        $profile_id = U::get($LTI, 'profile_id');
        if ( ! is_numeric($profile_id) || $profile_id < 1 ) {
            return $profile;
        }

        $profile->id = (int) $profile_id;

        if ( array_key_exists('profile_premium', $LTI) ) {
            $profile->premium = self::normalizePremiumLevel(U::get($LTI, 'profile_premium', 0));
            $profile->premium_at = U::get($LTI, 'profile_premium_at');
            $premium_json = U::get($LTI, 'profile_premium_json');
            $profile->premium_json = is_string($premium_json) ? $premium_json : null;
        } else {
            $profile->loadPremiumFromDatabase();
        }

        return $profile;
    }

    /**
     * Load premium columns from the profile table (Google login and other non-LTI paths).
     */
    protected function loadPremiumFromDatabase() {
        global $CFG, $PDOX;

        if ( ! $this->isLinked() ) {
            return;
        }
        if ( ! isset($PDOX) || ! $PDOX ) {
            return;
        }
        if ( ! $PDOX->columnExists('premium', "{$CFG->dbprefix}profile") ) {
            return;
        }

        $row = $PDOX->rowDie(
            "SELECT premium, premium_at, premium_json FROM {$CFG->dbprefix}profile
            WHERE profile_id = :PID AND (deleted IS NULL OR deleted = 0)",
            array(':PID' => $this->id)
        );
        if ( $row === false ) {
            return;
        }

        $this->premium = self::normalizePremiumLevel($row['premium']);
        $this->premium_at = $row['premium_at'];
        $this->premium_json = $row['premium_json'];
    }
}
