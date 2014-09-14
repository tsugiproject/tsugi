<?php

// Debugging utilities - partially implemented
namespace Tsugi\Core;

global $DEBUG_STRING;
$DEBUG_STRING='';

/**
 * This is a place holder.  Other than dumpPost() it needs a bit of work.
 *
 * The goal here is to greate a debug mode where the code
 * streams out a debug log to a string during a request and
 * if we are in developer mode and debugging, the log is
 * shown in the UI in a div that can be toggled like in
 * Wicket or OLAT.
 *
 * But this needs a bit of work to appear in the UI and
 * so for now, please don't use it until it is cleaned up.
 * Also it should stop using global values and stop being
 * static. But other than that, this is sweet code :).
 */
class Debug {
     public static function clear() {
         global $DEBUG_STRING;
         unset($_SESSION['__zzz_debug']);
     }

     public static function log($text,$mixed=false) {
         global $DEBUG_STRING;
         $sess = (strlen(session_id()) > 0 );
         if ( $sess && isset($_SESSION['__zzz_debug']) ) {
             if ( strlen($DEBUG_STRING) > 0 && strlen($_SESSION['__zzz_debug']) > 0) {
                 $_SESSION['__zzz_debug'] = $_SESSION['__zzz_debug'] ."\n" . $DEBUG_STRING;
             } else if ( strlen($DEBUG_STRING) > 0 ) {
                 $_SESSION['__zzz_debug'] = $DEBUG_STRING;
             }
             $DEBUG_STRING = $_SESSION['__zzz_debug'];
         }
         if ( strlen($text) > 0 ) {
             if ( strlen($DEBUG_STRING) > 0 ) {
                 if ( substr($DEBUG_STRING,-1) != "\n") $DEBUG_STRING .= "\n";
             }
             $DEBUG_STRING .= $text;
         }
         if ( $mixed !== false ) {
             if ( strlen($DEBUG_STRING) > 0 ) {
                 if ( substr($DEBUG_STRING,-1) != "\n") $DEBUG_STRING .= "\n";
             }
             if ( $mixed !== $_SESSION ) {
                 $DEBUG_STRING .= print_r($mixed, TRUE);
             } else {
                 $tmp = $mixed;
                 unset($tmp['__zzz_debug']);
                 $DEBUG_STRING .= print_r($tmp, TRUE);
             }
         }
         if ( $sess ) { // Move debug to session.
             $_SESSION['__zzz_debug'] = $DEBUG_STRING;
             $DEBUG_STRING = '';
             // echo("<br/>=== LOG $text ====<br/>".$_SESSION['__zzz_debug']."<br/>\n");flush();
         }
     }

     // Calling this clears debug buffer...
     public static function dump() {
         global $DEBUG_STRING;
         $retval = '';
         $sess = (strlen(session_id()) > 0 );
         if ( $sess ) {
             // echo("<br/>=== DUMP ====<br/>".$_SESSION['__zzz_debug']."<br/>\n");flush();
             if (strlen($_SESSION['__zzz_debug']) > 0) {
                 $retval = $_SESSION['__zzz_debug'];
                 unset($_SESSION['__zzz_debug']);
             }
         }
         if ( strlen($retval) > 0 && strlen($DEBUG_STRING) > 0) {
             $retval .= "\n";
         }
         if (strlen($DEBUG_STRING) > 0) {
             $retval .= $DEBUG_STRING;
             $DEBUG_STRING = '';
         }
         return $retval;
     }

    /**
     * Dump out the contents of the $_POST properly escaped in pre tags.
     */
    public static function dumpPost() {
        print "<pre>\n";
        print "Raw POST Parameters:\n\n";
        ksort($_POST);
        foreach($_POST as $key => $value ) {
            if (get_magic_quotes_gpc()) $value = stripslashes($value);
            print "$key=$value (".mb_detect_encoding($value).")\n";
        }
        print "</pre>";
    }

}
