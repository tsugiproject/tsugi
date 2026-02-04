/**
 * Push Notifications Management
 * 
 * Handles subscription and unsubscription for push notifications
 */

// Get VAPID public key and subscribe to push notifications
async function subscribeToNotifications() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        alert('Push notifications are not supported in this browser.');
        return;
    }

    try {
        // Request notification permission
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            alert('Notification permission denied.');
            return;
        }

        // Get service worker registration
        const registration = await navigator.serviceWorker.ready;
        
        // Get VAPID public key
        const subscribeBtn = document.getElementById('subscribe-btn');
        const vapidKeyUrl = subscribeBtn.getAttribute('data-vapid-key-url');
        const response = await fetch(vapidKeyUrl);
        const data = await response.json();
        const vapidPublicKey = data.publicKey;

        // Convert VAPID key to Uint8Array
        const applicationServerKey = urlBase64ToUint8Array(vapidPublicKey);

        // Subscribe to push notifications
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: applicationServerKey
        });

        // Send subscription to server
        const subscribeUrl = subscribeBtn.getAttribute('data-url');
        const subResponse = await fetch(subscribeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(subscription.toJSON())
        });

        const result = await subResponse.json();
        
        if (result.success) {
            // Reload page to show updated status
            window.location.reload();
        } else {
            alert('Failed to subscribe: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error subscribing to notifications:', error);
        alert('Error subscribing to notifications: ' + error.message);
    }
}

// Unsubscribe from push notifications
async function unsubscribeFromNotifications() {
    try {
        const unsubscribeBtn = document.getElementById('unsubscribe-btn');
        const unsubscribeUrl = unsubscribeBtn.getAttribute('data-url');
        
        const response = await fetch(unsubscribeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        });

        const result = await response.json();
        
        if (result.success) {
            // Unsubscribe from service worker
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();
            if (subscription) {
                await subscription.unsubscribe();
            }
            
            // Reload page to show updated status
            window.location.reload();
        } else {
            alert('Failed to unsubscribe: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error unsubscribing from notifications:', error);
        alert('Error unsubscribing from notifications: ' + error.message);
    }
}

// Send test notification (instructor only)
async function sendTestNotification() {
    try {
        console.log('[Notifications] Sending test notification...');
        const testBtn = document.getElementById('test-notification-btn');
        const testUrl = testBtn.getAttribute('data-url');
        
        const response = await fetch(testUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        });

        // Check if response is OK and content-type is JSON
        const contentType = response.headers.get('content-type');
        console.log('[Notifications] Response status:', response.status);
        console.log('[Notifications] Response statusText:', response.statusText);
        console.log('[Notifications] Content-Type:', contentType);
        console.log('[Notifications] Response URL:', response.url);
        
        // Get response as text first to see what we're actually getting
        const responseText = await response.text();
        console.log('[Notifications] Response text (first 1000 chars):', responseText.substring(0, 1000));
        
        if (!contentType || !contentType.includes('application/json')) {
            // Not JSON - show what we got
            console.error('[Notifications] Non-JSON response. Full response:', responseText);
            alert('Server returned non-JSON response (status: ' + response.status + '). Check browser console (F12) for full details.');
            return;
        }
        
        // Try to parse as JSON
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('[Notifications] Failed to parse JSON:', e);
            console.error('[Notifications] Response text:', responseText);
            alert('Server returned invalid JSON. Check browser console for details.');
            return;
        }

        console.log('[Notifications] Server response:', result);
        
        if (result.success) {
            console.log('[Notifications] Test notification sent successfully. Waiting for push event...');
            
            // Also try showing a local notification for testing
            if ('Notification' in window && Notification.permission === 'granted') {
                console.log('[Notifications] Attempting to show local test notification...');
                new Notification('Local Test Notification', {
                    body: 'This is a local test to verify notifications work',
                    icon: '/favicon.ico',
                    tag: 'test-local'
                }).addEventListener('click', () => {
                    console.log('[Notifications] Local test notification was clicked');
                });
            }
            
            // Check service worker registration and listen for messages
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.ready.then(registration => {
                    console.log('[Notifications] Service worker is ready');
                    console.log('[Notifications] Service worker registration:', registration);
                    
                    // Listen for messages from service worker
                    navigator.serviceWorker.addEventListener('message', event => {
                        console.log('[Notifications] Message from service worker:', event.data);
                    });
                    
                    // Listen for push events (this won't work from main thread, but helps debug)
                    navigator.serviceWorker.addEventListener('push', event => {
                        console.log('[Notifications] Push event received in main thread (unusual):', event);
                    });
                    
                    // Check if push manager is available
                    if (registration.pushManager) {
                        registration.pushManager.getSubscription().then(subscription => {
                            if (subscription) {
                                console.log('[Notifications] Push subscription active:', {
                                    endpoint: subscription.endpoint.substring(0, 50) + '...',
                                    expirationTime: subscription.expirationTime
                                });
                                
                                // Log full endpoint for debugging
                                console.log('[Notifications] Full endpoint:', subscription.endpoint);
                                
                                // Check if subscription matches what's in database
                                console.log('[Notifications] Subscription keys:', {
                                    p256dh: subscription.getKey('p256dh') ? 'Present' : 'Missing',
                                    auth: subscription.getKey('auth') ? 'Present' : 'Missing'
                                });
                            } else {
                                console.warn('[Notifications] No push subscription found - need to resubscribe');
                            }
                        }).catch(err => {
                            console.error('[Notifications] Error getting subscription:', err);
                        });
                    }
                    
                    // Check service worker state
                    if (registration.active) {
                        console.log('[Notifications] Service worker state:', registration.active.state);
                    }
                });
                
                // Also listen for controller changes
                navigator.serviceWorker.addEventListener('controllerchange', () => {
                    console.log('[Notifications] Service worker controller changed');
                });
            }
            
            // Wait a bit and check if push event was received
            setTimeout(() => {
                console.log('[Notifications] Checking for push event after 3 seconds...');
                console.log('[Notifications] If you see [SW] Push event received logs, the push worked!');
                console.log('[Notifications] Check the Service Worker console in about:debugging for [SW] logs');
            }, 3000);
            
            alert('Test notification sent! Check your notifications. If you don\'t see it, check the browser console (F12) for details.');
        } else {
            alert('Failed to send test notification: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error sending test notification:', error);
        alert('Error sending test notification: ' + error.message);
    }
}

// Convert VAPID key from base64 URL-safe string to Uint8Array
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

// Update debug information
async function updateDebugInfo() {
    const permissionDiv = document.getElementById('permission-status');
    const swDiv = document.getElementById('service-worker-status');
    const subDiv = document.getElementById('subscription-status');

    // Detect current browser
    const userAgent = navigator.userAgent;
    let currentBrowser = 'Unknown';
    if (userAgent.includes('Firefox')) {
        currentBrowser = 'Firefox';
    } else if (userAgent.includes('Chrome') && !userAgent.includes('Edg')) {
        currentBrowser = 'Chrome';
    } else if (userAgent.includes('Edg')) {
        currentBrowser = 'Edge';
    } else if (userAgent.includes('Safari')) {
        currentBrowser = 'Safari';
    }

    // Check notification permission
    if ('Notification' in window) {
        const permission = Notification.permission;
        permissionDiv.innerHTML = `<strong>Notification Permission:</strong> ${permission} ${permission === 'granted' ? '✓' : permission === 'denied' ? '✗' : '(needs request)'}`;
    } else {
        permissionDiv.innerHTML = '<strong>Notification Permission:</strong> Not supported in this browser';
    }

    // Check service worker
    if ('serviceWorker' in navigator) {
        try {
            const registration = await navigator.serviceWorker.getRegistration();
            if (registration) {
                swDiv.innerHTML = `<strong>Service Worker:</strong> Registered ✓<br>Scope: ${registration.scope}`;
            } else {
                swDiv.innerHTML = '<strong>Service Worker:</strong> Not registered';
            }
        } catch (error) {
            swDiv.innerHTML = `<strong>Service Worker:</strong> Error checking: ${error.message}`;
        }
    } else {
        swDiv.innerHTML = '<strong>Service Worker:</strong> Not supported in this browser';
    }

    // Check push subscription
    if ('serviceWorker' in navigator && 'PushManager' in window) {
        try {
            const registration = await navigator.serviceWorker.getRegistration();
            if (registration) {
                const subscription = await registration.pushManager.getSubscription();
                if (subscription) {
                    const endpoint = subscription.endpoint;
                    const p256dhKey = subscription.getKey('p256dh');
                    const authKey = subscription.getKey('auth');
                    
                    // Detect browser from endpoint
                    let browserName = 'Unknown';
                    if (endpoint.includes('fcm.googleapis.com')) {
                        browserName = 'Chrome';
                    } else if (endpoint.includes('updates.push.services.mozilla.com')) {
                        browserName = 'Firefox';
                    } else if (endpoint.includes('wns2-') || endpoint.includes('notify.windows.com')) {
                        browserName = 'Edge';
                    }
                    
                    const browserMatch = browserName === currentBrowser ? ' ✓ (Current Browser)' : '';
                    
                    subDiv.innerHTML = `
                        <strong>Push Subscription:</strong> Active ✓<br>
                        <strong>Current Browser:</strong> ${currentBrowser}<br>
                        <strong>Subscription Browser:</strong> ${browserName}${browserMatch}<br>
                        <strong>Endpoint:</strong> ${endpoint.substring(0, 80)}...<br>
                        <strong>p256dh Key:</strong> ${p256dhKey ? 'Present (' + p256dhKey.byteLength + ' bytes)' : 'Missing'}<br>
                        <strong>Auth Key:</strong> ${authKey ? 'Present (' + authKey.byteLength + ' bytes)' : 'Missing'}
                    `;
                } else {
                    subDiv.innerHTML = `<strong>Push Subscription:</strong> Not subscribed<br><strong>Current Browser:</strong> ${currentBrowser}`;
                }
            } else {
                subDiv.innerHTML = '<strong>Push Subscription:</strong> No service worker registered';
            }
        } catch (error) {
            subDiv.innerHTML = `<strong>Push Subscription:</strong> Error checking: ${error.message}`;
        }
    } else {
        subDiv.innerHTML = '<strong>Push Subscription:</strong> Push API not supported';
    }
}

// Set up event listeners when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const subscribeBtn = document.getElementById('subscribe-btn');
    if (subscribeBtn) {
        subscribeBtn.addEventListener('click', subscribeToNotifications);
    }

    const unsubscribeBtn = document.getElementById('unsubscribe-btn');
    if (unsubscribeBtn) {
        unsubscribeBtn.addEventListener('click', unsubscribeFromNotifications);
    }

    const testBtn = document.getElementById('test-notification-btn');
    if (testBtn) {
        testBtn.addEventListener('click', sendTestNotification);
    }

    const refreshDebugBtn = document.getElementById('refresh-debug-btn');
    if (refreshDebugBtn) {
        refreshDebugBtn.addEventListener('click', updateDebugInfo);
    }

    // Update debug info on page load
    updateDebugInfo();
});
