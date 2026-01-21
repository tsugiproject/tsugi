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

}
