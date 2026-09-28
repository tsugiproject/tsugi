<?php

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Create a new redirect response to the given path.
 *
 * @param  string  $to
 * @param  int  $status
 * @param  array  $headers
 * @return \Symfony\Component\HttpFoundation\RedirectResponse
 */
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
