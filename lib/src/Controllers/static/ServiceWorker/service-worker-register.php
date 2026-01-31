<?php
/**
 * Service Worker Registration Template
 * 
 * This template registers the service worker for push notifications and offline support.
 * Include this template in footer.php or other common templates.
 */
?>
<script>
// Register service worker for push notifications and offline support
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js')
        .then(function(reg) {
            console.log('[SW] Service Worker registered:', reg);
            
            // Keep service worker active by periodically checking its state
            setInterval(function() {
                reg.update().catch(function(err) {
                    console.log('[SW] Update check:', err.message);
                });
            }, 60000); // Check every minute
            
            // Listen for service worker state changes
            reg.addEventListener('updatefound', function() {
                console.log('[SW] New service worker found, updating...');
            });
            
            // Check if service worker is active
            if (reg.active) {
                console.log('[SW] Service worker is active');
                
                // Keep service worker alive by sending periodic messages
                setInterval(function() {
                    if (reg.active) {
                        reg.active.postMessage({type: 'keepalive', timestamp: Date.now()});
                    }
                }, 30000); // Every 30 seconds
            } else if (reg.installing) {
                console.log('[SW] Service worker is installing...');
            } else if (reg.waiting) {
                console.log('[SW] Service worker is waiting, skipWaiting...');
                reg.waiting.postMessage({type: 'SKIP_WAITING'});
            }
            
            // Monitor service worker state changes
            reg.addEventListener('updatefound', function() {
                const newWorker = reg.installing;
                if (newWorker) {
                    newWorker.addEventListener('statechange', function() {
                        if (newWorker.state === 'activated') {
                            console.log('[SW] New service worker activated');
                        }
                    });
                }
            });
        })
        .catch(function(err) {
            console.error('[SW] Service Worker registration failed:', err);
        });
    
    // Listen for service worker controller changes
    navigator.serviceWorker.addEventListener('controllerchange', function() {
        console.log('[SW] Service worker controller changed');
        window.location.reload();
    });
}
</script>
