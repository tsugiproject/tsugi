<?php

namespace Tsugi;

class Cache {

    public static function check($cacheloc, $cachekey)
    {
        $cacheloc = "cache_" . $cacheloc;
        if ( isset($_SESSION[$cacheloc]) ) {
            $cache_row = $_SESSION[$cacheloc];
            if ( $cache_row[0] == $cachekey ) {
                // error_log("Cache hit $cacheloc");
                return $cache_row[1];
            }
            unset($_SESSION[$cacheloc]);
        }
        return false;
    }

    // Don't cache the non-existence of something
    public static function set($cacheloc, $cachekey, $cacheval)
    {
        $cacheloc = "cache_" . $cacheloc;
        if ( $cacheval === null || $cacheval === false ) {
            unset($_SESSION[$cacheloc]);
            return;
        }
        $_SESSION[$cacheloc] = array($cachekey, $cacheval);
    }

    public static function clear($cacheloc)
    {
        $cacheloc = "cache_" . $cacheloc;
        if ( isset($_SESSION[$cacheloc]) ) {
            // error_log("Cache clear $cacheloc");
        }
        unset($_SESSION[$cacheloc]);
    }

}
