<?php

namespace Tsugi\UI;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Tsugi\Util\U;

/**
 * Simple router to replace Lumen router functionality
 * 
 * Provides basic GET/POST routing without the full Lumen framework
 */
class SimpleRouter {
    private $routes = [];
    private $namespace = null;

    /**
     * Register a GET route
     */
    public function get($path, $handler) {
        $this->routes[] = [
            'method' => 'GET',
            'path' => $path,
            'handler' => $handler
        ];
    }

    /**
     * Register a POST route
     */
    public function post($path, $handler) {
        $this->routes[] = [
            'method' => 'POST',
            'path' => $path,
            'handler' => $handler
        ];
    }

    /**
     * Group routes with a namespace
     */
    public function group(array $options, callable $callback) {
        $oldNamespace = $this->namespace;
        if (isset($options['namespace'])) {
            $this->namespace = $options['namespace'];
        }
        $callback();
        $this->namespace = $oldNamespace;
    }

    /**
     * Dispatch the current request to matching route
     */
    public function dispatch(Request $request) {
        $method = $request->getMethod();
        $path = $request->getPathInfo();
        
        // Normalize path - remove trailing slash for matching
        $path = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        foreach ($this->routes as $route) {
            // Check method
            if ($route['method'] !== $method) {
                continue;
            }

            // Check path - support route parameters like {anchor}
            $routePath = rtrim($route['path'], '/');
            if ($routePath === '') {
                $routePath = '/';
            }

            // Check if route matches (exact match or with parameters)
            $params = [];
            if ($this->matchRoute($routePath, $path, $params)) {
                $handler = $route['handler'];
                
                // Handle string handlers like 'Controller@method'
                if (is_string($handler) && strpos($handler, '@') !== false) {
                    list($controller, $method) = explode('@', $handler, 2);
                    
                    // Add namespace if set
                    if ($this->namespace) {
                        $controller = $this->namespace . '\\' . $controller;
                    }
                    
                    if (class_exists($controller) && method_exists($controller, $method)) {
                        $controllerInstance = new $controller();
                        // Pass parameters to method
                        if (!empty($params)) {
                            return call_user_func_array([$controllerInstance, $method], array_merge([$request], array_values($params)));
                        }
                        return $controllerInstance->$method($request);
                    }
                }
                
                // Handle callable handlers
                if (is_callable($handler)) {
                    // Pass parameters to callable
                    if (!empty($params)) {
                        return call_user_func_array($handler, array_merge([$request], array_values($params)));
                    }
                    return call_user_func($handler, $request);
                }
            }
        }

        // No route found - return 404
        return new Response('Not Found', 404);
    }

    /**
     * Match a route pattern against a path and extract parameters
     * 
     * @param string $pattern Route pattern (e.g., '/lessons/{anchor}')
     * @param string $path Actual path (e.g., '/lessons/123')
     * @param array &$params Output array to store extracted parameters
     * @return bool True if pattern matches path
     */
    private function matchRoute($pattern, $path, &$params) {
        // Exact match
        if ($pattern === $path) {
            return true;
        }

        // Convert pattern to regex
        $patternParts = explode('/', $pattern);
        $pathParts = explode('/', $path);
        
        if (count($patternParts) !== count($pathParts)) {
            return false;
        }

        $params = [];
        for ($i = 0; $i < count($patternParts); $i++) {
            $patternPart = $patternParts[$i];
            $pathPart = $pathParts[$i];
            
            // Check for parameter placeholder {name}
            if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $patternPart, $matches)) {
                $paramName = $matches[1];
                $params[$paramName] = $pathPart;
            } elseif ($patternPart !== $pathPart) {
                return false;
            }
        }

        return true;
    }
}

