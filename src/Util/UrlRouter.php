<?php
namespace Tsugi\Util;

/**
 * Simple URL Routing for "Old school" PHP apps
 *
 * This is a simple router if you want to write an old-style PHP
 * application but with decent looking URLs.  It provides a simple
 * alternative to building a full up Silex application.
 *
 * Based on RegexRouter from https://github.com/moagrius/RegexRouter
 *
 * Improvements:
 * - Add some "hot spots" {s} {d}
 * - Make it so "/" is relative to cwd
 * - Make it so the we add escapes and slashes to make the regex
 * 
 * Routing Rest URLs by pattern (Silex-Style)
 *
 *     $router = new Tsugi\Util\UrlRouter();
 *     $router->route('/lessons/{s}$', function($id){
 *         ...
 *     }
 *     $router->execute();
 */

class UrlRouter {
    
    private $routes = array();
    
    public function route($pattern, $callback) {
        # /lessons/{s}$
        if ( strpos($pattern, '/') !== 0 ) die('Route must start with /');

        # \/lessons\/{s}$
        $pattern = str_replace('/','\/', $pattern);

        # \/lessons\/([^\/]*)$
        $subst = array('{d}' => '([0-9]+)', '{s}' => '([^\/]*)');
        foreach($subst as $old => $new ) {
            $pattern = str_replace($old, $new, $pattern);
        }

        # \/wa4e\/tsugi
        $cwd = str_replace('/','\/',self::cwd());

        # \/wa4e\/tsugi\/lessons\/([^\/]*)$
        $pattern = '/' . $cwd . $pattern . '/';

        $this->routes[$pattern] = $callback;
    }

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
    
    public function execute($uri) {
        $uri = self::trimQuery($uri);
        foreach ($this->routes as $pattern => $callback) {
            if (preg_match($pattern, $uri, $params) === 1) {
                array_shift($params);
                return call_user_func_array($callback, array_values($params));
            }
        }
    }

    public function trimQuery($uri=null) {
        if ( $uri === null ) $uri = $_SERVER['REQUEST_URI'];
        $pos = strpos($uri, '?');
        if ( $pos === false ) return $uri;
        return substr($uri,0,$pos);
    }

}
