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
        })
        .catch(function(err) {
            console.error('[SW] Service Worker registration failed:', err);
        });
}
</script>
