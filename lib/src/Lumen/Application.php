<?php

namespace Tsugi\Lumen;

use Tsugi\Util\U;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

/**
 * Minimal Application implementation to replace Lumen
 *
 *     <?php
 *     require_once "../config.php";
 *     $launch = \Tsugi\Core\LTIX::requireData();
 *     $app = new \Tsugi\Lumen\Application($launch);
 *     $app->router->get('/', 'AppBundle\\Attend@get');
 *     $app->router->post('/', 'AppBundle\\Attend@post');
 *     $app->run();
 *
 */
class Application implements \ArrayAccess
{
    /**
     * The router instance.
     *
     * @var Router
     */
    public $router;

    /**
     * Container for storing services.
     *
     * @var array
     */
    protected $container = [];

    /**
     * The FastRoute dispatcher.
     *
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * The current route being dispatched.
     *
     * @var array
     */
    protected $currentRoute;

    /**
     * Requires a Tsugi Launch object for initializing.
     *
     *     $launch = \Tsugi\Core\LTIX::requireData();
     *     $app = new \Tsugi\Lumen\Application($launch);
     *
     * The launch object is added to the $app variable and can be accessed
     * as follows:
     *
     *     $app['tsugi']->user->displayname;
     */
    function __construct($launch, $basePath = __DIR__) {
        global $CFG;
        if ( ! isset($CFG->loader) ) {
            echo("<pre>\n".'Please fix your config.php to set $CFG->loader as follows:'."\n");
            echo('$loader = require_once($dirroot."/vendor/autoload.php");'."\n");
            echo("...\n".'$CFG = ...'."\n...\n");
            echo('$CFG->loader = $loader;'."\n");
            echo("Please see config-dist.php for sample code.\n</pre>\n");
            die('Need to set $CFG->loader');
        }
        
        // Load helper functions (redirect, response)
        if (!function_exists('response')) {
            require_once(__DIR__.'/helpers.php');
        }
        
        $this['tsugi'] = $launch;
        $launch->output->buffer = true;  // Buffer output

        $this->router = new Router($this);
    }

    /**
     * Run the application and send the response.
     *
     * @param  SymfonyRequest|null  $request
     * @return void
     */
    public function run($request = null)
    {
        $response = $this->dispatch($request);
        $response->send();
    }

    /**
     * Dispatch request and return response.
     *
     * @param  SymfonyRequest|null  $request
     * @return SymfonyResponse
     */
    public function dispatch($request = null)
    {
        if (!$request) {
            $request = SymfonyRequest::createFromGlobals();
        }

        $this['request'] = $request;

        try {
            [$method, $pathInfo] = $this->parseIncomingRequest($request);

            // Check for exact match first
            if (isset($this->router->getRoutes()[$method.$pathInfo])) {
                $response = $this->handleFoundRoute([true, $this->router->getRoutes()[$method.$pathInfo]['action'], []]);
            } else {
                // Use FastRoute for parameterized routes
                $response = $this->handleDispatcherResponse(
                    $this->createDispatcher()->dispatch($method, $pathInfo)
                );
            }
        } catch (\Throwable $e) {
            $response = $this->sendExceptionToHandler($e);
        }

        return $this->prepareResponse($response);
    }

    /**
     * Parse the incoming request and return the method and path info.
     *
     * @param  SymfonyRequest  $request
     * @return array
     */
    protected function parseIncomingRequest(SymfonyRequest $request)
    {
        return [$request->getMethod(), '/'.trim($request->getPathInfo(), '/')];
    }

    /**
     * Create a FastRoute dispatcher instance for the application.
     *
     * @return Dispatcher
     */
    protected function createDispatcher()
    {
        return $this->dispatcher ?: simpleDispatcher(function (RouteCollector $r) {
            foreach ($this->router->getRoutes() as $route) {
                $r->addRoute($route['method'], $route['uri'], $route['action']);
            }
        });
    }

    /**
     * Set the FastRoute dispatcher instance.
     *
     * @param  Dispatcher  $dispatcher
     * @return void
     */
    public function setDispatcher(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Handle the response from the FastRoute dispatcher.
     *
     * @param  array  $routeInfo
     * @return mixed
     */
    protected function handleDispatcherResponse($routeInfo)
    {
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw new NotFoundHttpException('Route not found');
            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new NotFoundHttpException('Method not allowed');
            case Dispatcher::FOUND:
                return $this->handleFoundRoute($routeInfo);
        }
    }

    /**
     * Handle a route found by the dispatcher.
     *
     * @param  array  $routeInfo
     * @return mixed
     */
    protected function handleFoundRoute($routeInfo)
    {
        $this->currentRoute = $routeInfo;

        $action = $routeInfo[1];

        return $this->callActionOnArrayBasedRoute($routeInfo);
    }

    /**
     * Call the Closure or invokable on the array based route.
     *
     * @param  array  $routeInfo
     * @return mixed
     */
    protected function callActionOnArrayBasedRoute($routeInfo)
    {
        $action = $routeInfo[1];
        $parameters = $routeInfo[2] ?? [];

        if (isset($action['uses'])) {
            return $this->callControllerAction($routeInfo);
        }

        // Handle closures - the action itself might be a closure
        if ($action instanceof \Closure) {
            return $this->call($action, $parameters);
        }

        // Handle array of callables
        foreach ($action as $value) {
            if ($value instanceof \Closure) {
                return $this->call($value, $parameters);
            }

            if (is_object($value) && is_callable($value)) {
                return $this->call($value, $parameters);
            }
        }

        throw new \RuntimeException('Unable to resolve route handler.');
    }

    /**
     * Call a controller based route.
     *
     * @param  array  $routeInfo
     * @return mixed
     */
    protected function callControllerAction($routeInfo)
    {
        $uses = $routeInfo[1]['uses'];
        $parameters = $routeInfo[2] ?? [];

        if (is_string($uses) && strpos($uses, '@') === false) {
            $uses .= '@__invoke';
        }

        [$controller, $method] = explode('@', $uses);

        $instance = $this->make($controller);

        if (! method_exists($instance, $method)) {
            throw new NotFoundHttpException("Method {$method} not found on {$controller}");
        }

        return $this->call([$instance, $method], $parameters);
    }

    /**
     * Call a callable with parameters.
     *
     * @param  callable  $callable
     * @param  array  $parameters
     * @return mixed
     */
    protected function call(callable $callable, array $parameters = [])
    {
        // Use reflection to match parameters
        if ($callable instanceof \Closure) {
            $reflection = new \ReflectionFunction($callable);
        } elseif (is_array($callable)) {
            $reflection = new \ReflectionMethod($callable[0], $callable[1]);
        } else {
            // For other callables, try to get reflection
            try {
                $reflection = new \ReflectionFunction($callable);
            } catch (\ReflectionException $e) {
                // Fallback: just call with parameters
                return call_user_func_array($callable, array_values($parameters));
            }
        }

        $args = [];
        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType();
            
            // Check if parameter type is Request
            if ($type && $type instanceof \ReflectionNamedType && $type->getName() === SymfonyRequest::class) {
                $args[] = $this['request'];
            } elseif (isset($parameters[$name])) {
                $args[] = $parameters[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                $args[] = null;
            }
        }

        return call_user_func_array($callable, $args);
    }

    /**
     * Make an instance of a class.
     *
     * @param  string  $class
     * @return mixed
     */
    public function make($class)
    {
        if (isset($this->container[$class])) {
            return $this->container[$class];
        }

        return new $class();
    }

    /**
     * Prepare the response for sending.
     *
     * @param  mixed  $response
     * @return SymfonyResponse
     */
    protected function prepareResponse($response)
    {
        if ($response instanceof SymfonyResponse) {
            return $response;
        }

        if (is_string($response)) {
            return new SymfonyResponse($response, 200);
        }

        return new SymfonyResponse('', 200);
    }

    /**
     * Send exception to handler.
     *
     * @param  \Throwable  $e
     * @return SymfonyResponse
     */
    protected function sendExceptionToHandler(\Throwable $e)
    {
        $handler = $this->resolveExceptionHandler();
        return $handler->render($this['request'], $e);
    }

    /**
     * Resolve the exception handler.
     *
     * @return ExceptionHandler
     */
    protected function resolveExceptionHandler()
    {
        return $this->make(ExceptionHandler::class);
    }

    /**
     * tsugiReroute - Send the browser to a new location with session
     *
     * Usage:
     *     $app->router->get('/', 'AppBundle\\Attend@get');
     *     $app->router->post('/', 'AppBundle\\Attend@post');
     *
     * Then at the end of the POST code, do this:
     *
     *     return $app->tsugiReroute('main');
     *
     */
    function tsugiReroute($route)
    {
        $url = isset($this->router->namedRoutes[$route]) 
            ? $this->router->namedRoutes[$route] 
            : $route;
        return new RedirectResponse(addSession($url));
    }

    function tsugiRedirectHome()
    {
        global $CFG;
        $home = isset($CFG->apphome) ? $CFG->apphome : $CFG->wwwroot;
        return new RedirectResponse($home);
    }

    function tsugiRedirect($route) { return $this->tsugiReroute($route); } // Deprecated

    /**
     * tsugiFlashSuccess - Add a success message to the flash session.
     */
    function tsugiFlashSuccess($message)
    {
        $_SESSION['success'] = $message;
    }

    /**
     * tsugiFlashError - Add an error message to the flash session.
     */
    function tsugiFlashError($message)
    {
        $_SESSION['error'] = $message;
    }

    /**
     * ArrayAccess implementation
     */
    public function offsetExists($offset): bool
    {
        return isset($this->container[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->container[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        $this->container[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->container[$offset]);
    }
}
