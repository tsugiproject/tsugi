<?php

namespace Tsugi\UI;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage;
use Tsugi\Util\U;

/**
 * Simple Application class to replace Lumen Application
 * 
 * Provides a lightweight container and routing without Lumen dependencies
 */
class SimpleApplication {
    private $container = [];
    public $router;
    public $tsugi_path;
    public $tsugi_parent;

    /**
     * Create a new SimpleApplication instance
     * 
     * @param object $launch The Tsugi launch object
     */
    public function __construct($launch) {
        global $CFG;
        
        if (!isset($CFG->loader)) {
            echo("<pre>\n".'Please fix your config.php to set $CFG->loader as follows:'."\n");
            echo('$loader = require_once($dirroot."/vendor/autoload.php");'."\n");
            echo("...\n".'$CFG = ...'."\n...\n");
            echo('$CFG->loader = $loader;'."\n");
            echo("Please see config-dist.php for sample code.\n</pre>\n");
            die('Need to set $CFG->loader');
        }
        
        $launch->cfg = $CFG;
        $this->container['tsugi'] = $launch;
        $launch->output->buffer = true;  // Buffer output

        // Start session
        $session = new Session(new PhpBridgeSessionStorage());
        $session->start();
        $this->container['session'] = $session;
        
        $this->tsugi_path = U::get_rest_path();
        $this->tsugi_parent = U::get_rest_parent();
        
        // Initialize router
        $this->router = new SimpleRouter();
    }

    /**
     * Array access for container
     */
    public function offsetGet($key) {
        return isset($this->container[$key]) ? $this->container[$key] : null;
    }

    public function offsetSet($key, $value) {
        $this->container[$key] = $value;
    }

    public function offsetExists($key) {
        return isset($this->container[$key]);
    }

    public function offsetUnset($key) {
        unset($this->container[$key]);
    }

    /**
     * Array access via [] syntax
     */
    public function __get($key) {
        return $this->offsetGet($key);
    }

    public function __set($key, $value) {
        $this->offsetSet($key, $value);
    }

    /**
     * Redirect to a route name (for compatibility)
     */
    public function tsugiReroute($route) {
        global $CFG;
        return redirect(U::addSession($CFG->wwwroot . $route));
    }

    /**
     * Redirect to home
     */
    public function tsugiRedirectHome() {
        global $CFG;
        $home = isset($CFG->apphome) ? $CFG->apphome : $CFG->wwwroot;
        return redirect($home);
    }

    /**
     * Deprecated alias
     */
    public function tsugiRedirect($route) {
        return $this->tsugiReroute($route);
    }

    /**
     * Flash success message
     */
    public function tsugiFlashSuccess($message) {
        $_SESSION['success'] = $message;
        if (isset($this->container['session'])) {
            $this->container['session']->getFlashBag()->add('success', $message);
        }
    }

    /**
     * Flash error message
     */
    public function tsugiFlashError($message) {
        $_SESSION['error'] = $message;
        if (isset($this->container['session'])) {
            $this->container['session']->getFlashBag()->add('error', $message);
        }
    }

    /**
     * Run the application - dispatch routes
     */
    public function run() {
        $request = Request::createFromGlobals();
        $response = $this->router->dispatch($request);
        $response->send();
    }
}

