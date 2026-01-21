<?php

namespace Tsugi\Crypt;

class SecureCookie {

    /**
     * Utility code to deal with Secure Cookies.
     */

    public static function encrypt($plain) {
        global $CFG;
        $cipher = \Tsugi\Crypt\AesOpenSSL::encrypt($plain, $CFG->cookiesecret) ;
        return $cipher;
    }

    public static function decrypt($cipher) {
        global $CFG;
        // TODO: Use AesOpenSSL after time passes from March 1, 2022
        // $plain = \Tsugi\Crypt\AesOpenSSL::decrypt($cipher, $CFG->cookiesecret) ;
        $plain = \Tsugi\Crypt\AesCtr::decrypt($cipher, $CFG->cookiesecret, 256) ;
        return $plain;
    }

    public static function create($id,$guid,$context_id,$debug=false) {
        global $CFG;
        $pt = $CFG->cookiepad.'::'.$id.'::'.$guid.'::'.$context_id;
        if ( $debug ) echo("PT1: $pt\n");
        $ct = self::encrypt($pt);
        return $ct;
    }

    public static function extract($encr,$debug=false) {
        global $CFG;
        $pt = self::decrypt($encr);
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
