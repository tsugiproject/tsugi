<?php
namespace Tsugi\Util;

/**
 * Super Simple File Based Router
 *
 * This router routes paths like .../lessons to .../lessons.php if the
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

    // /wa4e/tsugi/lessons returns lessons.php if the file exists
    public function fileCheck($uri=null) {
        if ( $uri === null ) $uri = $_SERVER['REQUEST_URI'];
        $uri = self::trimQuery($uri);
        // /wa4e/tsugi
        $cwd = self::cwd();
	if ( ! endsWith($cwd, '/') ) $cwd = $cwd .'/';
        if ( strpos($uri,$cwd) === 0 ) {
            $file = substr($uri, strlen($cwd)) . '.php';
            if ( file_exists($file) ) return $file;
        }
        return false;
    }
    
}
