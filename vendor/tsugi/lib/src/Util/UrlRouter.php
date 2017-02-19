<?php
namespace Tsugi\Util;

/**
 * Based on RegexRouter from https://github.com/moagrius/RegexRouter
 *
 *     require_once 'RegexRouter.php';
 *     $router = new RegexRouter();
 *     $router->route('/^\/blog\/(\w+)\/(\d+)\/?$/', function($category, $id){
 *         print "category={$category}, id={$id}";
 *      });
 *     $router->execute($_SERVER['REQUEST_URI']);
 *
 * First Improvement: Function somewhere other than /
 */

class UrlRouter {
    
    private $routes = array();
    
    public function route($pattern, $callback) {
        if ( strpos($pattern, '/') !== 0 ) die('Route must tstart with /');
        if ( strpos($pattern, '/~') === 0 ) {
            $cwd = str_replace('/','\/',self::cwd());
            $pattern = '/' . $cwd . substr($pattern,2);
        }
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
        foreach ($this->routes as $pattern => $callback) {
            if (preg_match($pattern, $uri, $params) === 1) {
                array_shift($params);
                return call_user_func_array($callback, array_values($params));
            }
        }
    }
    
}
