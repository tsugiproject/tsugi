<?php

namespace Tsugi\Util;

/**
 * Some really small, simple, and self-contained utility public static functions
 */
class U {

    public static function die_with_error_log($msg, $extra=false, $prefix="DIE:") {
        error_log($prefix.' '.$msg.' '.$extra);
        print_stack_trace();
        die($msg); // with error_log
    }

    public static function echo_log($msg) {
        echo(htmlent_utf8($msg));
        error_log(str_replace("\n"," ",$msg));
    }

    public static function session_safe_id() {
        $retval = session_id();
        if ( strlen($retval) > 10 ) return '**********'.substr($retval,5);
    }

    public static function print_stack_trace() {
        ob_start();
        debug_print_backtrace();
        $data = ob_get_clean();
        error_log($data);
    }

    public static function htmlpre_utf8($string) {
        return str_replace("<","&lt;",$string);
    }

    public static function htmlspec_utf8($string) {
        return htmlspecialchars($string,ENT_QUOTES,$encoding = 'UTF-8');
    }

    public static function htmlent_utf8($string) {
        return htmlentities($string,ENT_QUOTES,$encoding = 'UTF-8');
    }

    // Makes sure a string is safe as an href
    public static function safe_href($string) {
        return str_replace(array('"', '<'),
            array('&quot;',''), $string);
    }

    // Convienence method to wrap sha256
    public static function lti_sha256($val) {
        return hash('sha256', $val);
    }

    // Convienence method to get the local path if we are doing
    public static function route_get_local_path($dir) {
        $uri = $_SERVER['REQUEST_URI'];     // /tsugi/lti/some/cool/stuff
        $root = $_SERVER['DOCUMENT_ROOT'];  // /Applications/MAMP/htdocs
        $cwd = $dir;                        // /Applications/MAMP/htdocs/tsugi/lti
        if ( strlen($cwd) < strlen($root) + 1 ) return false;
        $lwd = substr($cwd,strlen($root));  // /tsugi/lti
        if ( strlen($uri) < strlen($lwd) + 2 ) return false;
        $local = substr($uri,strlen($lwd)+1); // some/cool/stuff
        return $local;
    }

    public static function get_request_document() {
        $uri = $_SERVER['REQUEST_URI'];     // /tsugi/lti/some/cool/stuff
        $pieces = explode('/',$uri);
        if ( count($pieces) > 1 ) {
            $local_path = $pieces[count($pieces)-1];
            $pos = strpos($local_path,'?');
            if ( $pos > 0 ) $local_path = substr($local_path,0,$pos);
            return $local_path;
        }
        return false;
    }

    public static function addSession($url) {
        if ( ini_get('session.use_cookies') != '0' ) return $url;
        if ( stripos($url, '&'.session_name().'=') > 0 ||
             stripos($url, '?'.session_name().'=') > 0 ) return $url;
        $parameter = session_name().'='.session_id();
        if ( strpos($url, $parameter) !== false ) return $url;
        $url = $url . (strpos($url,'?') > 0 ? "&" : "?");
        $url = $url . $parameter;
        return $url;
    }

    public static function reconstruct_query($baseurl, $newparms=false) {
        foreach ( $_GET as $k => $v ) {
            if ( $k == session_name() ) continue;
            if ( is_array($newparms) && array_key_exists($k, $newparms) ) continue;
            $baseurl = add_url_parm($baseurl, $k, $v);
        }
        if ( is_array($newparms) ) foreach ( $newparms as $k => $v ) {
            $baseurl = add_url_parm($baseurl, $k, $v);
        }

        return $baseurl;
    }

    public static function add_url_parm($url, $key, $val) {
        $url .= strpos($url,'?') === false ? '?' : '&';
        $url .= urlencode($key) . '=' . urlencode($val);
        return $url;
    }

    // Request headers for earlier version of PHP and nginx
    // http://www.php.net/manual/en/public static function.getallheaders.php
    public static function apache_request_headers() {
        foreach($_SERVER as $key=>$value) {
            if (substr($key,0,5)=="HTTP_") {
                $key=str_replace(" ","-",ucwords(strtolower(str_replace("_"," ",substr($key,5)))));
                $out[$key]=$value;
            } else {
                $out[$key]=$value;
            }
        }
        return $out;
    }

    // http://stackoverflow.com/questions/3258634/php-how-to-send-http-response-code
    // Backwards compatibility http_response_code
    // For 4.3.0 <= PHP <= 5.4.0
    public static function http_response_code($newcode = NULL)
    {
        static $code = 200;
        if($newcode !== NULL)
        {
            header('X-PHP-Response-Code: '.$newcode, true, $newcode);
            if(!headers_sent())
                $code = $newcode;
        }       
        return $code;
    }

    // Convience method, pattern borrowed from WordPress
    public static function __($message, $textdomain=false) {
        if ( ! function_exists('gettext')) return $message;
        if ( $textdomain === false ) {
            return gettext($message);
        } else {
            return dgettext($textdomain, $message);
        }
    }

    public static function _e($message, $textdomain=false) {
        echo(__($message, $textdomain));
    }

    public static function _m($message, $textdomain=false) {
        return __($message, "master");
    }

    public static function _me($message, $textdomain=false) {
        echo(_m($message, $textdomain));
    }

    public static function isCli() {
         if(php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) {
              return true;
         } else {
              return false;
         }
    }

    public static function lmsDie($message=false) {
        global $CFG, $DEBUG_STRING;
        if($message !== false) {
            echo($message);
            error_log($message);
        }
        if ( $CFG->DEVELOPER === TRUE ) {
            if ( strlen($DEBUG_STRING) > 0 ) {
                echo("\n<pre>\n");
                echo(htmlentities($DEBUG_STRING));
                echo("\n</pre>\n");
            }
        }
        die();  // lmsDie
    }

    public static function line_out($output) {
        echo(htmlent_utf8($output)."<br/>\n");
        flush();
    }

    public static function error_out($output) {
        echo('<span style="color:red"><strong>'.htmlent_utf8($output)."</strong></span><br/>\n");
        flush();
    }

    public static function success_out($output) {
        echo('<span style="color:green"><strong>'.htmlent_utf8($output)."</strong></span><br/>\n");
        flush();
    }

    // http://stackoverflow.com/questions/834303/startswith-and-endswith-public static functions-in-php
    public static function startsWith($haystack, $needle) {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
    }

    public static function endsWith($haystack, $needle) {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
    }

    public static function goodFolder($folder) {
        return 1 == preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', $folder);
    }

    public static function conservativeUrl($url) {
        if ( filter_var($url, FILTER_VALIDATE_URL) === false ) return false;
        if ( strpos($url, '*') !== false ) return false;
        if ( strpos($url, '\\') !== false ) return false;
        if ( strpos($url, "'") !== false ) return false;
        if ( strpos($url, ';') !== false ) return false;
        if ( strpos($url, '"') !== false ) return false;
        return true;
    }


}
