<?php

namespace Tsugi\Controllers;

use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tsugi\Core\LTIX;
use Tsugi\Util\U;

/**
 * StaticFiles controller for serving controller-specific static files (JS, CSS, etc.)
 * 
 * Serves static files from tsugi/lib/src/Controllers/static/{ControllerName}/
 * with proper caching headers and MIME types.
 */
class StaticFiles {

    const ROUTE = '/static';
    
    // Allowed file extensions and their MIME types
    private static $mimeTypes = [
        'js' => 'application/javascript',
        'css' => 'text/css',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
    ];

    public static function routes(Application $app, $prefix=self::ROUTE) {
        // Route pattern: /static/{controller}/{filename}
        // Register both with and without trailing slash for flexibility
        // Note: FastRoute pattern syntax - {controller} and {filename} are placeholders
        // The route will match paths like /static/{controller}/{filename} or 
        // /{parent}/static/{controller}/{filename} depending on how the app is mounted
        $app->router->get($prefix.'/{controller}/{filename}', 'StaticFiles@serve');
        $app->router->get($prefix.'/{controller}/{filename}/', 'StaticFiles@serve');
    }

    /**
     * Serve a controller static file
     * 
     * @param Request $request
     * @param string $controller Controller name (e.g., 'Announcements', 'Pages')
     * @param string $filename Static filename (e.g., 'tsugi-announce.js')
     * @return Response
     */
    public function serve(Request $request, $controller, $filename)
    {
        // Security: Validate controller and filename to prevent directory traversal
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $controller)) {
            return new Response('Invalid controller name', 400);
        }
        
        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $filename)) {
            return new Response('Invalid filename', 400);
        }

        // Determine static directory based on controller name
        // __DIR__ is /Users/csev/htdocs/ca4e/tsugi/lib/src/Controllers
        $controllerDir = __DIR__ . '/static/' . $controller;
        $filePath = $controllerDir . '/' . $filename;
        
        error_log("StaticFiles: controllerDir=$controllerDir");
        error_log("StaticFiles: filePath=$filePath");
        error_log("StaticFiles: __DIR__=" . __DIR__);

        // Security: Ensure file is within the static directory (prevent directory traversal)
        $realControllerDir = realpath($controllerDir);
        $realFilePath = realpath($filePath);
        
        if ($realControllerDir === false || $realFilePath === false) {
            error_log("StaticFiles: Directory not found. controllerDir=$controllerDir, filePath=$filePath");
            error_log("StaticFiles: __DIR__=" . __DIR__);
            error_log("StaticFiles: controller=$controller, filename=$filename");
            return new Response('File not found: ' . basename($filePath) . " (dir: $controllerDir)", 404);
        }
        
        if (strpos($realFilePath, $realControllerDir) !== 0) {
            return new Response('Invalid file path', 403);
        }

        // Check if file exists
        if (!file_exists($filePath) || !is_file($filePath)) {
            error_log("StaticFiles: File does not exist. filePath=$filePath, realFilePath=$realFilePath");
            return new Response('File not found: ' . basename($filePath), 404);
        }

        // Get file extension for MIME type
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mimeType = isset(self::$mimeTypes[$extension]) 
            ? self::$mimeTypes[$extension] 
            : 'application/octet-stream';

        // Get file modification time for ETag and caching
        $lastModified = filemtime($filePath);
        $etag = md5($filePath . $lastModified);

        // Check if client has cached version
        $ifNoneMatch = $request->headers->get('If-None-Match');
        $ifModifiedSince = $request->headers->get('If-Modified-Since');
        
        if ($ifNoneMatch === $etag || 
            ($ifModifiedSince && strtotime($ifModifiedSince) >= $lastModified)) {
            return new Response('', 304); // Not Modified
        }

        // Create response with file content
        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('ETag', $etag);
        $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
        
        // Set caching headers (1 year for static files)
        $response->setMaxAge(31536000); // 1 year
        $response->setSharedMaxAge(31536000);
        $response->setPublic();
        
        // Add cache-control header
        $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');

        return $response;
    }

    /**
     * Generate URL for a controller static file
     * 
     * Static helper method that can be used without instantiating the controller.
     * When called from a Tool controller, basePath should be toolParent() . '/static'
     * 
     * @param string $controller Controller name (e.g., 'Announcements')
     * @param string $filename Static filename (e.g., 'tsugi-announce.js')
     * @param string|null $basePath Optional base path (defaults to determining from request using Tool::determineParentPath())
     * @return string Full URL to the static file
     */
    public static function url($controller, $filename, $basePath = null)
    {
        if ($basePath === null) {
            // Use Tool's static method to determine parent path dynamically
            $parentPath = Tool::determineParentPath();
            // Normalize: if parent is "/", use empty string to avoid double slashes
            if ($parentPath === '/') {
                $parentPath = '';
            }
            $basePath = $parentPath . self::ROUTE;
        }
        
        return $basePath . '/' . urlencode($controller) . '/' . urlencode($filename);
    }
}
