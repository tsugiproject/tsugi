<?php

namespace Tsugi\Crypt;

class SecureCookie {

    /**
     * Utility code to deal with Secure Cookies.
     */
    public static function create($id,$guid,$context_id,$debug=false) {
        global $CFG;
        $pt = $CFG->cookiepad.'::'.$id.'::'.$guid.'::'.$context_id;
        if ( $debug ) echo("PT1: $pt\n");
        $ct = \Tsugi\Crypt\AesCtr::encrypt($pt, $CFG->cookiesecret, 256) ;
        return $ct;
    }
    
    public static function extract($encr,$debug=false) {
        global $CFG;
        $pt = \Tsugi\Crypt\AesCtr::decrypt($encr, $CFG->cookiesecret, 256) ;
        if ( $debug ) echo("PT2: $pt\n");
        $pieces = explode('::',$pt);
        if ( count($pieces) != 4 ) return false;
        if ( $pieces[0] != $CFG->cookiepad ) return false;
        return Array($pieces[1], $pieces[2], $pieces[3]);
    }

    // We also session_unset - because something is not right
    // See: http://php.net/manual/en/function.setcookie.php
    public static function delete() {
        global $CFG;
        setcookie($CFG->cookiename,'',time() - 100, '/'); // Expire 100 seconds ago
        session_unset();
    }

    // We have a user - set their secure cookie
    public static function set($user_id, $userEmail, $context_id) {
        global $CFG;
        $ct = self::create($user_id,$userEmail, $context_id);
        setcookie($CFG->cookiename,$ct,time() + (86400 * 45), '/'); // 86400 = 1 day
    }
}
