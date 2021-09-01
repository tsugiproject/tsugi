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

    /**
     * Produce a Python-style get() to avoid use of ternary operator
     */
    public static function get($arr, $key, $default=null) {
        if ( !is_array($arr) ) return $default;
        if ( !isset($key) ) return $default;
        if ( !isset($arr[$key]) ) return $default;
        return $arr[$key];
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
        if ( ! is_string($string) ) return $string;
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

    /**
     * Get the last bit of the path
     *
     * input: /py4e/lessons/intro?x=2
     * output: intro
     */
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

    /**
     * Get the protocol, host, and port from an absolute URL
     *
     * input: http://localhost:8888/tsugi
     * output: http://localhost:8888
     */
    public static function get_base_url($url) {
        $pieces = parse_url($url);
        $retval = $pieces['scheme'].'://'.$pieces['host'];
        $port = self::get($pieces,'port');
        if ( $port && $port != 80 && $port != 443 ) $retval .= ':' . $port;
        return $retval;
    }

    /**
     * Get the path to the current request, w/o trailing slash
     *
     * input: /py4e/lessons/intro?x=2
     * output: /py4e/lessons/intro
     *
     * input: /py4e/lessons/intro/?x=2
     * output: /py4e/lessons/intro
     */
    public static function get_rest_path($uri=false) {
        if ( ! $uri ) $uri = $_SERVER['REQUEST_URI'];     // /tsugi/lti/some/cool/stuff
        $pos = strpos($uri,'?');
        if ( $pos > 0 ) $uri = substr($uri,0,$pos);
        if ( self::endsWith($uri, '/') ) {
            $uri = substr($uri, 0, strlen($uri)-1);
        }
        return $uri;
    }

    /**
     * Get the path to one above the current request, w/o trailing slash
     *
     * input: /py4e/lessons/intro?x=2
     * output: /py4e/lessons
     *
     * input: /py4e/lessons/intro/?x=2
     * output: /py4e/lessons/intro
     */
    public static function get_rest_parent($uri=false) {
        if ( ! $uri ) $uri = $_SERVER['REQUEST_URI'];     // /tsugi/lti/some/cool/stuff
        $pos = strpos($uri,'?');
        if ( $pos > 0 ) $uri = substr($uri,0,$pos);
        if ( self::endsWith($uri, '/') ) {
            $uri = substr($uri, 0, strlen($uri)-1);
            return $uri;
        }

        $pieces = explode('/', $uri);
        if ( count($pieces) > 1 ) {
            array_pop($pieces);
            $uri = implode('/', $pieces);
        }
        return $uri;
    }

    /**
     * Get the controller for the current request
     *
     * executing script:  /py4e/koseu.php
     * input: /py4e/lessons/intro?x=2
     * output: (/py4e, lessons, intro)
     *
     * input: /py4e/lessons/intro/?x=2
     * output: /py4e/lessons/intro
     */
    public static function parse_rest_path($uri=false, $SERVER_SCRIPT_NAME=false /* unit test only */) {
        if ( ! $SERVER_SCRIPT_NAME ) $SERVER_SCRIPT_NAME = $_SERVER["SCRIPT_NAME"];  // /py4e/koseu.php
        if ( ! $uri ) $uri = $_SERVER['REQUEST_URI'];     // /py4e/lessons/intro/happy
        // Remove Query string...
        $pos = strpos($uri, '?');
        if ( $pos !== false ) $uri = substr($uri,0,$pos);
        $cwd = dirname($SERVER_SCRIPT_NAME);
        if ( strpos($uri,$cwd) !== 0 ) return false;

        $controller = '';
        $extra = '';
        $remainder = substr($uri, strlen($cwd));
        if ( strpos($remainder,'/') === 0 ) $remainder = substr($remainder,1);
        if ( strlen($remainder) > 1 ) {
            $pieces = explode('/',$remainder,2);
            if ( count($pieces) > 0 ) $controller = $pieces[0];
            if ( count($pieces) > 1 ) $extra = $pieces[1];
        }

        if ( $cwd == '/' ) $cwd = '';
        $retval = array($cwd, $controller, $extra);
        return $retval;
    }

    /**
     * Return a rest-path
     *
     * This knows the current working folder we are in.  The model
     * is that a folder is an "application" with many possible controllers
     * and within each controller there are actions followed by parameters.
     *
     * for example, assume these files exist in a folder:
     *
     *     ../tsugi/mod/zap/index.php
     *     ../tsugi/mod/zap/rows.php
     *     ../tsugi/mod/zap/store.php
     *
     * If this URL were called with .htaccess and tsugi.php set up, rows.php
     * would be run and this would be the output:
     *
     *     http://localhost:8888/tsugi/mod/zap/rows/add/1/2
     *     base_url: http://localhost:8888
     *     parent:  /tsugi/mod/zap
     *     current:  /tsugi/mod/zap/rows
     *     controller: rows
     *     action: add
     *     parameters: (1, 2)
     *     full: /tsugi/mod/zap/rows/add/1/2
     *
     * As a special case, when the index.php runs (not through tsugi.php)
     * The results are as follows:
     *
     *     http://localhost:8888/tsugi/mod/zap/
     *     base_url: http://localhost:8888
     *     parent:  /tsugi/mod/zap
     *     current:  /tsugi/mod/zap
     *     controller: empty string
     *     action: empty string
     *     parameters: empty array
     *     full: /tsugi/mod/zap
     */
    public static function rest_path($uri=false, $SERVER_SCRIPT_NAME=false /* unit test only */) {
        global $CFG;
        $retval = self::parse_rest_path($uri, $SERVER_SCRIPT_NAME);
        if ( ! is_array($retval) ) return false;
        $retobj = new \stdClass();
        if ( $retval[0] == '/' ) {
            $retobj->parent = '';
        } else {
            $retobj->parent = $retval[0];
        }
        $retobj->base_url = self::get_base_url($CFG->wwwroot);
        $retobj->controller = $retval[1];
        $retobj->extra = $retval[2];
        $pieces = explode('/', $retval[2]);
        $retobj->action = false;
        $retobj->parameters = array();
        if ( count($pieces) > 0 && strlen($pieces[0]) ) {
            $retobj->action = array_shift($pieces);
            $retobj->parameters = $pieces;
        }
        $retobj->current = $retobj->parent;
        $retobj->full = $retobj->parent;
        if ( strlen($retobj->controller) > 0 ) {
             $retobj->current .= '/' . $retobj->controller;
             $retobj->full .= '/' . $retobj->controller;
        }
        if ( strlen($retobj->extra) > 0 ) {
             $retobj->full .= '/' . $retobj->extra;
        }

        return $retobj;
    }

    public static function addSession($url, $force=false) {
        if ( !$force && ini_get('session.use_cookies') != '0' ) return $url;
        if ( stripos($url, '&'.session_name().'=') > 0 ||
             stripos($url, '?'.session_name().'=') > 0 ) return $url;
        $session_id = session_id();

        // If doing this before the session is running, check for the
        // id as GET or POST parameter
        if ( strlen($session_id) < 1 ) {
            $session_id = self::get($_POST, session_name());
        }
        if ( strlen($session_id) < 1 ) {
            $session_id = self::get($_GET, session_name());
        }

        // Don't add more than once...
        $parameter = session_name().'=';
        if ( strpos($url, $parameter) !== false ) return $url;

        $url = self::add_url_parm($url, session_name(), $session_id);
        return $url;
    }

    public static function reconstruct_query($baseurl, $newparms=false) {
        foreach ( $_GET as $k => $v ) {
            if ( $k == session_name() ) continue;
            if ( is_array($newparms) && array_key_exists($k, $newparms) ) continue;
            $baseurl = self::add_url_parm($baseurl, $k, $v);
        }
        if ( is_array($newparms) ) foreach ( $newparms as $k => $v ) {
            $baseurl = self::add_url_parm($baseurl, $k, $v);
        }

        return $baseurl;
    }

    public static function add_url_parm($url, $key, $val) {
        $url .= strpos($url,'?') === false ? '?' : '&';
        $url .= urlencode($key) . '=' . urlencode($val);
        return $url;
    }

    public static function absolute_url_ref(&$url) {
        $url = self::absolute_url($url);
    }

    public static function absolute_url($url) {
        global $CFG;
        if ( strpos($url,'http://') === 0 ) return $url;
        if ( strpos($url,'https://') === 0 ) return $url;
        $retval = $CFG->apphome;
        if ( strpos($url,'/') !== 0 ) $retval .= '/';
        $retval .= $url;
        return $retval;
    }

    /**
     * Remove any relative elements from a path
     *
     * Before   After
     * a/b/c    a/b/c
     * a/b/c/   a/b/c/
     * a/./c/   a/c/
     * a/../c/  c/
     */
    public static function remove_relative_path($path) {
        $pieces = explode('/', $path);
        $new_pieces = array();
        for($i=0; $i < count($pieces); $i++) {
            if ($pieces[$i] == '.' ) continue;
            if ($pieces[$i] == '..' ) {
                array_pop($new_pieces);
                continue;
            }
            $new_pieces[] = $pieces[$i];
        }
        $retval = implode("/",$new_pieces);
        return $retval;
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

    /**
     * Return the URL as seen by PHP (no query string or parameters)
     *
     * Borrowed from from_request on OAuthRequest.php
     */
    public static function curPHPUrl() {
        $scheme = (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on")
              ? 'http'
              : 'https';
        $port = "";
        if ( isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != "80" && $_SERVER['SERVER_PORT'] != "443" &&
            strpos(':', $_SERVER['HTTP_HOST']) < 0 ) {
            $port =  ':' . $_SERVER['SERVER_PORT'] ;
        }
        $http_url = $scheme .
                              '://' . $_SERVER['HTTP_HOST'] .
                              $port .
                              $_SERVER['REQUEST_URI'];
        return $http_url;
    }

    /** Tightly serialize an integer-only PHP array
     *
     *     $arar = Array ( 1 => 42 ,2 => 43, 3 => 44 );
     *     $str = U::array_Integer_Serialize($arar);
     *     echo($str); // 1=42,2=43,3=44
     *
     * https://stackoverflow.com/questions/30231476/i-want-to-array-key-and-array-value-comma-separated-string
     */
    public static function array_Integer_Serialize($arar) {
        $result = implode(',',array_map('\Tsugi\Util\U::array_Integer_Serialize_Map',array_keys($arar),$arar));
        return $result;
    }

    public static function array_Integer_Serialize_Map($a,$b){
        // We are not messing around :)
        if ( ! is_int($a) || ! is_int($b) ) {
            throw new \Exception('array_Integer_Serialize requires integers '.$a.':'.$b);
        }
        return $a.'='.$b;
    }

    /**
     * Deserialize an tightly serialized integer-only PHP array
     *
     *     $str = '1=42,2=43,3=44';
     *     $arar = U::array_Integer_Deserialize($str);
     *     print_r($arar); // Array ( '1' => 42 ,'2' => 43, '3' => 44 );
     *
     * https://stackoverflow.com/questions/4923951/php-split-string-in-key-value-pairs
     */
    public static function array_Integer_Deserialize($input) {
        $r = array();
        preg_match_all("/([^,= ]+)=([^,= ]+)/", $input, $r);
        $result = array();
        for($i=0; $i<count($r[1]);$i++) {
            $k = $r[1][$i];
            $v = $r[2][$i];
            if ( !is_numeric($k) || !is_numeric($v) ) {
                throw new \Exception('array_Integer_Deserialize requires integers '.$k.'='.$v.' ('.$i.')');
            }
            $k = $k + 0;
            $v = $v + 0;
            if ( ! is_int($k) || ! is_int($v) ) {
                throw new \Exception('array_Integer_Deserialize requires integers '.$k.'='.$v.' ('.$i.')');
            }
            $result[$k] = $v;
        }

        return $result;
    }

    /**
     * Pull off the first element of a key/value array
     *
     *     $arr = array('x'=>'ball','y'=>'hat','z'=>'apple');
     *     print_r($arr);
     *     print_r(array_kshift($arr)); // [x] => ball
     *     print_r($arr);
     *
     * http://php.net/manual/en/function.array-shift.php#84179
     */
    public static function array_kshift(&$arr) {
        list($k) = array_keys($arr);
        $r  = array($k=>$arr[$k]);
        unset($arr[$k]);
        return $r;
    }

    /**
     * Indicate if the user has not requested "Do not Track"
     *
     * This is just the  inverted value for the "Do not Track".
     * Using "allow" sematics makes writing code easier!
     *
     *  http://donottrack.us/
     */
    // From: http://donottrack.us/application
    public static function allow_track() {
        $DoNotTrackHeader = "DNT";
        $DoNotTrackValue = "1";

        $phpHeader = "HTTP_" . strtoupper(str_replace("-", "_", $DoNotTrackHeader));

        $retval = ! ((array_key_exists($phpHeader, $_SERVER)) and ($_SERVER[$phpHeader] == $DoNotTrackValue));
        return $retval;
    }

    // Clean out the array of 'secret' keys
    public static function safe_var_dump($x) {
            ob_start();
            if ( isset($x['secret']) ) $x['secret'] = MD5($x['secret']);
            if ( is_array($x) ) foreach ( $x as &$v ) {
                if ( is_array($v) && isset($v['secret']) ) $v['secret'] = MD5($v['secret']);
            }
            var_dump($x);
            $result = ob_get_clean();
            return $result;
    }

    public static function safe_array($inp) {
        if ( ! is_array($inp) ) return $inp;
        $new = $inp;
        foreach ( $inp as $k => $v ) {
            if (strpos($k, 'secret') !== false ) {
                $new[$k] = '*****';
            }
        }
        return $new;
    }

    /**
     * Give the current time in the "conversion format"
     *
     * 201711261315
     */
    public static function conversion_time($time="now")
    {
        $dt = new \DateTime($time);
        return $dt->format('Ymdhi');
    }

    public static function iso8601($time="now")
    {
        $dt = new \DateTime($time, new \DateTimeZone('utc'));
        return $dt->format(\DateTime::ATOM);
    }

    // https://stackoverflow.com/questions/4393626/how-do-i-know-if-any-php-caching-is-enabled
    /**
     * Return if APC cache is available
     */
    public static function apcAvailable() {
        return (extension_loaded('apc') && ini_get('apc.enabled'));
    }

    /**
     * Return if APCU cache is available
     */
    public static function apcuAvailable() {
        return (function_exists('apcu_enabled') && apcu_enabled());
    }

    public static function appCacheGet($key, $default=null) {
        if ( ! self::apcuAvailable() ) return $default;
        $retval = apcu_fetch($key,$success);
        if ( ! $success ) return $default;
        return $retval;
    }

    public static function appCacheSet($key, $value, $ttl=0) {
        if ( ! self::apcuAvailable() ) return;
        apcu_store($key, $value, $ttl);
    }

    public static function appCacheDelete($key) {
        if ( ! self::apcuAvailable() ) return;
        apcu_delete($key);
    }

    // https://stackoverflow.com/questions/2110732/how-to-get-name-of-calling-function-method-in-php
    /**
     * Get caller outside this file as string
     *
     *      error_log("Called by: ".U::getCaller());
     *      error_log("Called by: ".U::getCaller(2));
     *
     *      Called by: /Applications/MAMP/htdocs/py4e/tsugi/vendor/tsugi/lib/src/Core/LTIX.php:1766 /Applications/MAMP/htdocs/py4e/tools/pythonauto/sendgrade.php:18
     */
    // Need to replicate the code or U.php will be in the traceback
    public static function getCaller($count=1)
    {
        $dbts=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,6+$count);
        if ( ! is_array($dbts) || count($dbts) < 2 ) return null;
        if ( ! isset($dbts[0]['file']) ) return null;
        $myfile = $dbts[0]['file'];
        $retval = '';
        foreach($dbts as $dbt) {
            if ( ! isset($dbt['file']) ) continue;
            if ( ! isset($dbt['line']) ) continue;
            if ( $myfile != $dbt['file'] ) {
                if ( strlen($retval) > 0 ) $retval .= " ";
                $retval.= $dbt['file'].':'.$dbt['line'];
                $count--;
                if ( $count < 1 ) return $retval;
            }
        }
        return $retval;
    }

    // Convert to a user readable size
    public static function displaySize($size) {
        if ( $size > 1024*1024*1024*2 ) {
            return (int) ($size/(1024*1024*1024))."GB";
        }
        if ( $size > 1024*1024*2 ) {
            return (int) ($size/(1024*1024))."MB";
        }
        if ( $size > 1024*2 ) {
            return (int) ($size/(1024))."KB";
        }
        return $size."B";
    }

    /**
     * Create a unique GUID
     * return string
     *
     * https://www.texelate.co.uk/blog/create-a-guid-with-php
    */
    public static function createGUID() {
        // Create a token
        $token = $_SERVER['HTTP_HOST'];
        $token .= $_SERVER['REQUEST_URI'];
        $token .= uniqid(rand(), true);
        // GUID is 128-bit hex
        $hash = strtoupper(md5($token));
        // Create formatted GUID
        $guid = '';
        // GUID format is XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX for readability
        $guid .= substr($hash,  0,  8) .
            '-' .
            substr($hash,  8,  4) .
            '-' .
            substr($hash, 12,  4) .
            '-' .
            substr($hash, 16,  4) .
            '-' .
            substr($hash, 20, 12);
        return $guid;
    }

    public static function isGUIDValid($guid) {
        return (preg_match("/^(\{)?[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}(?(1)\})$/i", $guid)
    ? true : false);
    }

    public static function getServerBase($url) {
        $pieces = parse_url($url);
        $port = U::get($pieces, 'port');
        $retval = U::get($pieces, 'scheme') . '://' . U::get($pieces, 'host');
        if ( $port ) $retval .= ':' . $port;
        return $retval;
    }

    /**
     * Validate a CSS color value
     */

    public static function isValidCSSColor($color) {
        $patterns = array(
            "/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/",
        );
        foreach($patterns as $pattern) {
            if (preg_match($pattern,$color) ) return true;
        }
        return false;
    }

}
