<?php

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Helper function: redirect to a URL
 * 
 * @param string|null $to URL to redirect to
 * @param int $status HTTP status code (default 302)
 * @param array $headers Additional headers
 * @return RedirectResponse
 */
if (!function_exists('redirect')) {
    function redirect($to = null, $status = 302, $headers = []) {
        if (is_null($to)) {
            // Return a redirector-like object for chaining (minimal implementation)
            return new class {
                public function to($url, $status = 302, $headers = []) {
                    return new RedirectResponse($url, $status, $headers);
                }
            };
        }
        return new RedirectResponse($to, $status, $headers);
    }
}

/**
 * Helper function: create a response
 * 
 * @param string|array|null $content Response content
 * @param int $status HTTP status code (default 200)
 * @param array $headers Additional headers
 * @return Response|JsonResponse
 */
if (!function_exists('response')) {
    function response($content = '', $status = 200, array $headers = []) {
        if (func_num_args() === 0) {
            // Return a response factory-like object
            return new class {
                public function json($data, $status = 200, $headers = []) {
                    return new JsonResponse($data, $status, $headers);
                }
            };
        }
        
        // If content is an array, return JSON response
        if (is_array($content)) {
            return new JsonResponse($content, $status, $headers);
        }
        
        return new Response($content, $status, $headers);
    }
}

