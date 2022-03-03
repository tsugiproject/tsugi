<?php

namespace Tsugi\Crypt;

class SimpleEncryption {

    public static CIPHER = "AES/CBC/PKCS5Padding";
   
    public static encrypt($key, $plaintext) {
    }

    public static decrypt($key, $encrypted) {
    }

    public static function create($id,$guid,$debug=false) {
        global $CFG;
        $pt = $CFG->cookiepad.'::'.$id.'::'.$guid;
        if ( $debug ) echo("PT1: $pt\n");
        $ct = \Tsugi\Crypt\AesOpenSSL::encrypt($pt, $CFG->cookiesecret) ;
        return $ct;
    }
    
    public static function extract($encr,$debug=false) {
        global $CFG;
        $pt = \Tsugi\Crypt\AesOpenSSL::decrypt($encr, $CFG->cookiesecret) ;
        if ( $debug ) echo("PT2: $pt\n");
        $pieces = explode('::',$pt);
        if ( count($pieces) != 3 ) return false;
        if ( $pieces[0] != $CFG->cookiepad ) return false;
        return Array($pieces[1], $pieces[2]);
    }

    // We also session_unset - because something is not right
    // See: http://php.net/manual/en/function.setcookie.php
    public static function delete() {
        global $CFG;
        setcookie($CFG->cookiename,'',time() - 100); // Expire 100 seconds ago
        session_unset();
    }

    // We have a user - set their secure cookie
    public static function set($user_id, $userSHA) {
        global $CFG;
        $ct = self::create($user_id,$userSHA);
        setcookie($CFG->cookiename,$ct,time() + (86400 * 45)); // 86400 = 1 day
    }
}
