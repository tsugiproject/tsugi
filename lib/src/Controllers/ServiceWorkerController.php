<?php

namespace Tsugi\Controllers;

use Tsugi\Lumen\Controller;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ServiceWorker controller for serving service worker JavaScript at /sw.js
 * 
 * Provides a minimal service worker implementation with:
 * - Install/activate handlers
 * - Push notification handler
 * - Notification click handler
 * 
 * To register the service worker from a Tsugi page:
 *   if ('serviceWorker' in navigator) {
 *     navigator.serviceWorker.register('/sw.js')
 *       .then(reg => console.log('SW registered:', reg))
 *       .catch(err => console.error('SW registration failed:', err));
 *   }
 * 
 * Testing:
 * - curl -i https://ca4e.localhost/sw.js
 * - Chrome DevTools: Application -> Service Workers
 * - Check browser console for registration messages
 */
class ServiceWorkerController extends Controller {

    const ROUTE = '/sw.js';

    /**
     * Register routes for the service worker
     * 
     * @param Application $app
     * @param string $prefix Route prefix (defaults to self::ROUTE)
     */
    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, function(Request $request) use ($app) {
            return ServiceWorkerController::getServiceWorker($app);
        });
        // Also handle with trailing slash for consistency
        $app->router->get($prefix.'/', function(Request $request) use ($app) {
            return ServiceWorkerController::getServiceWorker($app);
        });
    }

    /**
     * Generate and return the service worker JavaScript
     * 
     * @param Application $app
     * @return Response
     */
    public static function getServiceWorker(Application $app)
    {
        global $CFG;

        // Generate the service worker JavaScript code
        $swJs = self::generateServiceWorkerJs();

        // Create response with JavaScript content
        $response = new Response($swJs, 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
        ]);

        // Set cache headers
        // In dev: no-cache to ensure fresh updates during development
        // In production: short max-age (e.g., 1 hour) to allow updates
        // Service workers are versioned, so caching is generally safe
        if (isset($CFG->debug) && $CFG->debug) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        } else {
            // Production: allow short caching (1 hour)
            $response->headers->set('Cache-Control', 'public, max-age=3600');
        }

        return $response;
    }

    /**
     * Generate the service worker JavaScript code
     * 
     * @return string JavaScript code for the service worker
     */
    private static function generateServiceWorkerJs(): string
    {
        // Service worker JavaScript code
        // Using heredoc for readability
        return <<<'SWJS'
// Service Worker for Tsugi/Koseu
// Version: 1.0.0

// Install event - skip waiting to activate immediately
self.addEventListener('install', (event) => {
    // Skip waiting to activate immediately
    self.skipWaiting();
});

// Activate event - claim all clients immediately
self.addEventListener('activate', (event) => {
    // Claim all clients immediately
    event.waitUntil(self.clients.claim());
});

// Push event - handle push notifications
self.addEventListener('push', (event) => {
    console.log('[SW] Push event received');
    
    // Send message to all clients so announcements component can update badge
    // Use BroadcastChannel only - it's reliable and avoids duplicate badge increments
    // (We removed postMessage fallback to prevent the component from receiving the same message twice)
    event.waitUntil(
        new Promise((resolve) => {
            try {
                const channel = new BroadcastChannel('push-notifications');
                channel.postMessage({
                    type: 'push-received',
                    message: 'Push event received in service worker',
                    timestamp: Date.now()
                });
                channel.close();
                resolve();
            } catch (err) {
                console.error('[SW] BroadcastChannel error:', err);
                resolve();
            }
        })
    );
    
    let notificationData = {
        title: 'Notification',
        body: 'You have a new notification',
        icon: '/favicon.ico',
        badge: '/favicon.ico',
        tag: 'default',
        data: {
            url: '/'
        }
    };

    // Try to parse JSON data from push event
    if (event.data) {
        try {
            const data = event.data.json();
            if (data.title) notificationData.title = data.title;
            if (data.body) notificationData.body = data.body;
            if (data.icon) notificationData.icon = data.icon;
            if (data.badge) notificationData.badge = data.badge;
            if (data.tag) notificationData.tag = data.tag;
            if (data.url) notificationData.data.url = data.url;
            if (data.data && data.data.url) notificationData.data.url = data.data.url;
        } catch (e) {
            // If not JSON, try text
            try {
                const text = event.data.text();
                if (text) {
                    notificationData.body = text;
                }
            } catch (e2) {
                console.error('[SW] Error parsing push data:', e2);
            }
        }
    }

    // Set app badge (for dock/taskbar indicator)
    // Note: Firefox on desktop/macOS doesn't support Badging API yet
    if ('setAppBadge' in navigator) {
        navigator.setAppBadge(1).catch(() => {
            // Badging API not supported, ignore
        });
    }

    // Show notification
    event.waitUntil(
        self.registration.showNotification(notificationData.title, {
            body: notificationData.body,
            icon: notificationData.icon,
            badge: notificationData.badge,
            tag: notificationData.tag,
            data: notificationData.data,
            requireInteraction: false,
            silent: false
        }).catch((error) => {
            console.error('[SW] Error showing notification:', error);
        })
    );
});

// Notification click event - open URL from notification data
self.addEventListener('notificationclick', (event) => {
    // Clear app badge when notification is clicked
    if ('clearAppBadge' in navigator) {
        navigator.clearAppBadge().catch(() => {
            // Badging API not supported, ignore
        });
    }
    
    // Close the notification
    event.notification.close();

    // Get URL from notification data, fallback to '/'
    const urlToOpen = event.notification.data?.url || '/';

    // Open or focus the window
    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        }).then((clientList) => {
            // Check if there's already a window open with this URL
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus();
                }
            }
            // If no window found, open a new one
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});

// Message event - handle messages from clients
self.addEventListener('message', (event) => {
    // Handle SKIP_WAITING message
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    // Handle test connection message
    if (event.data && event.data.type === 'test-connection') {
        // Send test message back to verify connection works
        event.waitUntil(
            Promise.all([
                // Method 1: BroadcastChannel
                new Promise((resolve) => {
                    try {
                        const channel = new BroadcastChannel('push-notifications');
                        channel.postMessage({
                            type: 'test-response',
                            component: event.data.component,
                            timestamp: Date.now()
                        });
                        console.log('[SW] Sent test-response via BroadcastChannel');
                        channel.close();
                        resolve();
                    } catch (err) {
                        console.log('[SW] BroadcastChannel not available for test:', err);
                        resolve();
                    }
                }),
                // Method 2: client.postMessage
                self.clients.matchAll({includeUncontrolled: true, type: 'window'}).then(clients => {
                    console.log('[SW] Found', clients.length, 'clients to send test response to');
                    clients.forEach(client => {
                        try {
                            console.log('[SW] Sending test-response to:', client.url);
                            client.postMessage({
                                type: 'test-response',
                                component: event.data.component,
                                timestamp: Date.now()
                            });
                        } catch (err) {
                            console.error('[SW] Error sending test-response:', err);
                        }
                    });
                }).catch(err => {
                    console.error('[SW] Error getting clients for test-response:', err);
                })
            ])
        );
        return;
    }
    
    // Handle keepalive messages (helps keep service worker active)
    if (event.data && event.data.type === 'keepalive') {
        // Respond to keep the connection alive
        if (event.ports && event.ports[0]) {
            event.ports[0].postMessage({
                type: 'keepalive-response',
                timestamp: event.data.timestamp
            });
        }
        return;
    }
    
    // Echo back the message
    if (event.ports && event.ports[0]) {
        event.ports[0].postMessage({
            type: 'response',
            data: event.data
        });
    }
    
    // Also respond via clients API
    event.waitUntil(
        self.clients.matchAll().then(clients => {
            clients.forEach(client => {
                client.postMessage({
                    type: 'response',
                    data: event.data
                });
            });
        })
    );
});

SWJS;
    }
}
