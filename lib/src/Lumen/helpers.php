<?php

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Create a new redirect response to the given path.
 * 
 * This overrides Lumen's redirect() helper which expects a Lumen Application.
 * We use Symfony's RedirectResponse directly instead.
 *
 * @param  string  $to
 * @param  int  $status
 * @param  array  $headers
 * @return \Symfony\Component\HttpFoundation\RedirectResponse
 */
// Note: Lumen's redirect() may already be loaded via Composer autoload.
// We need to redefine it, but PHP doesn't allow redefining functions.
// So we check if it exists and if it's the Lumen version, we'll handle it differently.
// Actually, we can't redefine, so we need to ensure this loads first or remove Lumen entirely.

// For now, if redirect doesn't exist, define it. If it does exist (from Lumen),
// we'll need to handle the case where it's called with a string (which is our use case).
if (!function_exists('redirect')) {
    function redirect($to = null, $status = 302, $headers = [])
    {
        return new RedirectResponse($to, $status, $headers);
    }
}

/**
 * Return a new response from the application.
 *
 * @param  string|array|null  $content
 * @param  int  $status
 * @param  array  $headers
 * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\JsonResponse
 */
if (!function_exists('response')) {
    function response($content = '', $status = 200, array $headers = [])
    {
        $factory = new class {
            public function json($data, $status = 200, array $headers = [], $options = 0)
            {
                return new JsonResponse($data, $status, $headers);
            }
        };
        
        if (func_num_args() === 0) {
            return $factory;
        }

        return new Response($content, $status, $headers);
    }
}
