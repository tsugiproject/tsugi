<?php
namespace Tsugi\Util;

/**
 * Super Simple File Based Router
 *
 * This router routes paths like .../install to .../install.php if the
 * file exists.
 *
 *     $router = new Tsugi\Util\FileRouter();
 *     $file = $router->fileCheck();
 *     if ( $file ) {
 *         require_once($file);
 *         return;
 *     }
 *
 * This pattern uses GET parameters rather than REST parameters but
 * a single route is all that is needed to route requests to a file.
 * You should use one or the other but not both to keep things simple.
 */

class FileRouter {
    
    /**
     * Gives the current folder of the executing script
     *
     * ["SCRIPT_NAME"]=> string(21) "/wa4e/tsugi/route.php"
     * 
     * returns "/wa4e/tsugi" (no trailing slash)
     */
    public static function cwd() {
        return dirname($_SERVER["SCRIPT_NAME"]);
    }
    
    public function trimQuery($uri) {
        $pos = strpos($uri, '?');
        if ( $pos === false ) return $uri;
        return substr($uri,0,$pos);
    }

    // /wa4e/install returns install.php if the file exists
    public function fileCheck($uri=null) {
        global $TSUGI_REST_PATH_VALUES;
        global $TSUGI_REST_PATH;
        $TSUGI_REST_PATH = false;
        $TSUGI_REST_PATH_VALUES = false;
        if ( $uri === null ) $uri = $_SERVER['REQUEST_URI'];
        $uri = self::trimQuery($uri);
        // /wa4e/tsugi
        $cwd = self::cwd();
	if ( ! endsWith($cwd, '/') ) $cwd = $cwd .'/';
        if ( strpos($uri,$cwd) === 0 ) {
            $remainder = substr($uri, strlen($cwd));
            if ( strlen($remainder) < 1 ) return false;
            $pieces = explode('/',$remainder,2);
            $file = $pieces[0] . '.php';
            if ( file_exists($file) ) {
                $TSUGI_REST_PATH = $cwd . '/' . $pieces[0];
                if ( count($pieces) > 1 ) $TSUGI_REST_PATH_VALUES = $pieces[1];
                return $file;
            }
        }
        return false;
    }
    
}
