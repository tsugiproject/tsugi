<?php

namespace Tsugi\Core;

/** A very simple Cache - currently implemented in session
 *
 * Someday we will build a better cache but for now this
 * simply saves re-retrieving or re-constructing a few bits
 * of user data later in the same request or in a later
 * request.  Everything is saved in $_SESSION.
 *
 * Since the cache is in $_SESSION, it is already per user,
 * per link_id.  And within that we have "cache locations".
 * Each location, can have one key and one value.  We have
 * this limitation because we don't want our simple session
 * based cache to grow forever.  Each location stores only
 * one entry.
 *
 * For example in the loadUserInfoByPass() routine, we are
 * looking at users other than our own (usually as instructor).
 * To make sure we only make the SQL query once, we make
 * the following call:
 *
 *     Cache::set('lti_user', $user_id, $row);
 *
 * This will overwrite any other user_id value in the 'lti_user'
 * slot.  In effect there is one 'lti_user' slot and we store a
 * a key (which might change) from call to call and a value (the
 * row of retrieved data).
 *
 * This strictly bounds overall cache data in the session to the
 * number of cache locations and avoids any need for a garbage
 * collection or an independent expire process.
 *
 * Context-scoped entries (setContext / getContext) use the same
 * one-slot-per-location rule: each $cacheloc holds at most one
 * bundle { context_id, ts, ttl, value }. A get for a different
 * context removes the slot and returns null. Expiry uses ts + ttl
 * (seconds); ttl 0 means no time-based expiry (context mismatch
 * still invalidates).
 */

class Cache {

    /**
     * Place an entry in the cache.
     *
     * We don't cache null or false if that was our value.
     */
    public static function set($cacheloc, $cachekey, $cacheval, $expiresec=false)
    {
        $cacheloc = "cache_" . $cacheloc;
        if ( $cacheval === null || $cacheval === false ) {
            unset($_SESSION[$cacheloc]);
            return;
        }
        if ( $expiresec !== false ) $expiresec = time() + $expiresec;
        $_SESSION[$cacheloc] = array($cachekey, $cacheval, $expiresec);
    }

    /**
     * Check and return a value from the cache.
     *
     * Returns false if there is no entry.
     */
    public static function check($cacheloc, $cachekey)
    {
        $cacheloc = "cache_" . $cacheloc;
        if ( isset($_SESSION[$cacheloc]) ) {
            $cache_row = $_SESSION[$cacheloc];
            if ( time() >= $cache_row[2] ) { 
                unset($_SESSION[$cacheloc]);
                return false;
            }
            if ( $cache_row[0] == $cachekey ) {
                // error_log("Cache hit $cacheloc");
                return $cache_row[1];
            }
            unset($_SESSION[$cacheloc]);
        }
        return false;
    }

    /**
     * Check when value in the cache expires
     *
     * Returns false if there is no entry.
     */
    public static function expires($cacheloc, $cachekey)
    {
        $cacheloc = "cache_" . $cacheloc;
        if ( isset($_SESSION[$cacheloc]) ) {
            $cache_row = $_SESSION[$cacheloc];
            if ( time() >= $cache_row[2] ) { 
                unset($_SESSION[$cacheloc]);
                return false;
            }
            if ( $cache_row[0] != $cachekey ) return false;
            if ( $cache_row[0] === false ) return false;
            return $cache_row[2] - time();
        }
        return false;
    }

    /**
     * Delete an entry from the cache.
     */
    public static function clear($cacheloc)
    {
        $cacheloc = "cache_" . $cacheloc;
        if ( isset($_SESSION[$cacheloc]) ) {
            // error_log("Cache clear $cacheloc");
        }
        unset($_SESSION[$cacheloc]);
    }

    /**
     * Store a value for one cache location, tied to a context id and optional TTL (seconds).
     * Same session slot as getContext: overwrites any previous entry for this $cacheloc.
     *
     * @param string $cacheloc Location name (becomes cache_ prefix in session).
     * @param int $context_id Context this value belongs to.
     * @param mixed $value Data to cache; null or false removes the entry.
     * @param int|false $expiresec TTL in seconds from stored ts, or false/0 for no time expiry.
     */
    public static function setContext($cacheloc, $context_id, $value, $expiresec = false)
    {
        $cacheloc = "cache_" . $cacheloc;
        if ( $value === null || $value === false ) {
            unset($_SESSION[$cacheloc]);
            return;
        }
        $ttl = ( $expiresec === false || $expiresec === null ) ? 0 : (int) $expiresec;
        $_SESSION[$cacheloc] = array(
            'context_id' => (int) $context_id,
            'ts' => time(),
            'ttl' => $ttl,
            'value' => $value,
        );
    }

    /**
     * Return the cached value for this location if context matches and entry is not expired.
     * If the slot exists but context_id does not match, the entry is removed and null is returned.
     * If the entry is time-expired (ttl > 0 and age >= ttl), it is removed and null is returned.
     *
     * @param string $cacheloc Location name.
     * @param int $context_id Requesting context; must match stored context_id.
     * @return mixed|null
     */
    public static function getContext($cacheloc, $context_id)
    {
        $cacheloc = "cache_" . $cacheloc;
        if ( ! isset($_SESSION[$cacheloc]) || ! is_array($_SESSION[$cacheloc]) ) {
            return null;
        }
        $row = $_SESSION[$cacheloc];
        $want = (int) $context_id;
        $have = isset($row['context_id']) ? (int) $row['context_id'] : -1;
        if ( $want !== $have ) {
            unset($_SESSION[$cacheloc]);
            return null;
        }
        $ts = isset($row['ts']) ? (int) $row['ts'] : 0;
        $ttl = isset($row['ttl']) ? (int) $row['ttl'] : 0;
        if ( $ttl > 0 && $ts > 0 && (time() - $ts) >= $ttl ) {
            unset($_SESSION[$cacheloc]);
            return null;
        }
        if ( ! array_key_exists('value', $row ) ) {
            unset($_SESSION[$cacheloc]);
            return null;
        }
        return $row['value'];
    }

    /**
     * Remove the context cache entry for this location (any stored context).
     */
    public static function clearContext($cacheloc)
    {
        $cacheloc = "cache_" . $cacheloc;
        unset($_SESSION[$cacheloc]);
    }

    /**
     * Remove the entry only if it is currently cached for the given context_id.
     */
    public static function invalidateContext($cacheloc, $context_id)
    {
        $cacheloc = "cache_" . $cacheloc;
        if ( ! isset($_SESSION[$cacheloc]) || ! is_array($_SESSION[$cacheloc]) ) {
            return;
        }
        $row = $_SESSION[$cacheloc];
        $cid = (int) $context_id;
        if ( $cid < 1 ) {
            return;
        }
        $have = isset($row['context_id']) ? (int) $row['context_id'] : -1;
        if ( $have === $cid ) {
            unset($_SESSION[$cacheloc]);
        }
    }

    /**
     * Remove every Tsugi session cache entry (session keys starting with "cache_").
     * Use after login or other events where cached user/context data must not leak across identities.
     */
    public static function clearAllSessionCaches()
    {
        if ( ! isset($_SESSION) || ! is_array($_SESSION) ) {
            return;
        }
        foreach ( array_keys($_SESSION) as $k ) {
            if ( is_string($k) && str_starts_with($k, 'cache_') ) {
                unset($_SESSION[$k]);
            }
        }
    }

}
