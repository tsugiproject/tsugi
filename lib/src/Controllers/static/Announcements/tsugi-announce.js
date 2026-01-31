import { LitElement, html, css } from 'https://cdn.jsdelivr.net/gh/lit/dist@3/core/lit-core.min.js';

/**
 * Tsugi Announcements Web Component
 * 
 * Usage:
 *   <tsugi-announce json-url="/announce/json.php" dismiss-url="/announce/dismiss.php" view-url="/announce/index.php"></tsugi-announce>
 * 
 * Attributes:
 *   - json-url: URL to fetch announcements JSON
 *   - dismiss-url: URL for dismiss endpoint
 *   - view-url: URL to view all announcements
 */
class TsugiAnnounce extends LitElement {
    static properties = {
        jsonUrl: { type: String, attribute: 'json-url' },
        dismissUrl: { type: String, attribute: 'dismiss-url' },
        viewUrl: { type: String, attribute: 'view-url' },
        notificationsUrl: { type: String, attribute: 'notifications-url' },
        announcements: { type: Array, state: true },
        undismissedCount: { type: Number, state: true },
        pushNotificationCount: { type: Number, state: true },
        popupOpen: { type: Boolean, state: true }
    };

    static styles = css`
        :host {
            display: inline-block;
            position: relative;
            padding: 0 0.05em;
        }
        
        .bullhorn-icon {
            font-size: 1em;
            cursor: pointer;
            user-select: none;
            position: relative;
            display: inline-block;
            width: 2em;
            height: 2em;
            line-height: 1;
            vertical-align: middle;
            padding: 0 0.15em;
        }
        
        .bell-icon {
            width: 100%;
            height: 100%;
            fill: none !important;
            stroke: currentColor;
            stroke-width: 1.5;
        }
        
        .bell-icon path {
            fill: none !important;
        }
        
        .badge {
            position: absolute;
            top: -0.2em;
            right: -0.4em;
            background-color: #d9534f;
            color: white;
            border-radius: 0.35em;
            padding: 0.12em 0.35em;
            font-size: 0.6em;
            font-weight: bold;
            line-height: 1.2;
            min-width: 0.9em;
            text-align: center;
            box-shadow: 0 0.05em 0.15em rgba(0,0,0,0.3);
        }
        
        .popup {
            position: absolute;
            top: 100%;
            margin-top: 8px;
            background: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            min-width: 300px;
            max-width: 400px;
            z-index: 1000;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .popup.align-right {
            right: 0;
        }
        
        .popup.align-left {
            left: 0;
        }
        
        .popup-header {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            background-color: #f5f5f5;
            border-radius: 4px 4px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-align: left;
        }
        
        .popup-close {
            font-size: 20px;
            line-height: 1;
            color: #999;
            cursor: pointer;
        }
        
        .popup-close:hover {
            color: #333;
        }
        
        .popup-content {
            padding: 0;
            text-align: left;
        }
        
        .announcement-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .announcement-item {
            display: flex;
            align-items: center;
            padding: 8px 15px;
            border-bottom: 1px solid #eee;
        }
        
        .announcement-item:last-child {
            border-bottom: none;
        }
        
        .announcement-item.dismissed {
            opacity: 0.7;
        }
        
        .announcement-title {
            flex: 1;
            text-decoration: none;
            color: #337ab7;
            font-size: 14px;
            text-align: left;
        }
        
        .announcement-title:hover {
            text-decoration: underline;
        }
        
        .dismiss-icon {
            display: inline-block;
            font-size: 14px;
            font-weight: bold;
            line-height: 1;
            text-align: center;
            user-select: none;
            color: #999;
            border: 2px solid #ccc;
            border-radius: 3px;
            padding: 4px 6px;
            min-width: 20px;
            min-height: 20px;
            box-sizing: border-box;
            cursor: pointer;
            margin-left: 8px;
        }
        
        .dismiss-icon:hover {
            opacity: 0.7;
        }
        
        .dismiss-icon.dismissed {
            color: #5cb85c;
            background-color: #f0f8f0;
            border-color: #5cb85c;
        }
        
        .empty-message {
            padding: 10px;
            color: #999;
        }
    `;

    constructor() {
        super();
        this.announcements = [];
        this.undismissedCount = 0;
        this.pushNotificationCount = 0;
        this.popupOpen = false;
        this.jsonUrl = '';
        this.dismissUrl = '';
        this.viewUrl = '';
        this.notificationsUrl = '';
    }

    connectedCallback() {
        super.connectedCallback();
        this.loadAnnouncements();
        // Close popup when clicking outside
        document.addEventListener('click', this.handleOutsideClick.bind(this));
        
        // Clear push notifications if we're on the notifications page
        if (this.notificationsUrl) {
            const currentPath = window.location.pathname;
            const notificationsPath = new URL(this.notificationsUrl, window.location.origin).pathname;
            if (currentPath === notificationsPath || currentPath.includes('/notifications')) {
                this.clearPushNotifications();
            }
        }
        
        // Listen for push notification messages from service worker
        // Use BroadcastChannel for reliable communication
        if ('BroadcastChannel' in window) {
            this.pushChannel = new BroadcastChannel('push-notifications');
            this.pushChannel.addEventListener('message', (event) => {
                if (event.data && event.data.type === 'push-received') {
                    this.handlePushNotification();
                }
            });
        }
        
        if ('serviceWorker' in navigator) {
            // Set up message handler for non-push messages (like test-response)
            // Note: push-received messages come via BroadcastChannel, not postMessage
            const messageHandler = (event) => {
                // Only handle non-push messages here (push messages come via BroadcastChannel)
                // Note: push-received messages are handled by BroadcastChannel listener above
            };
            
            // Use onmessage (global handler) for service worker messages
            navigator.serviceWorker.onmessage = messageHandler;
            
            // Also set up listener when service worker is ready
            navigator.serviceWorker.ready.then(registration => {
                // Listen for messages through the controller (for test messages, not push)
                if (navigator.serviceWorker.controller) {
                    navigator.serviceWorker.controller.addEventListener('message', messageHandler);
                }
                
                // Notify service worker that announcements component is ready
                if (registration.active) {
                    registration.active.postMessage({type: 'announcements-ready'});
                }
            });
            
            // Also listen for controller changes
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                if (navigator.serviceWorker.controller) {
                    navigator.serviceWorker.controller.addEventListener('message', messageHandler);
                }
            });
        }
    }
    
    handlePushNotification() {
        // Increment push notification count when a push is received
        this.pushNotificationCount = (this.pushNotificationCount || 0) + 1;
        // Force update to show the new badge count
        this.requestUpdate();
    }
    
    // Public method for testing - can be called from console
    testPushNotification() {
        console.log('[Announcements] Test: Manually triggering push notification');
        this.handlePushNotification();
    }
    
    clearPushNotifications() {
        // Clear push notification count (e.g., when user visits /notifications)
        const hadNotifications = this.pushNotificationCount > 0;
        this.pushNotificationCount = 0;
        if (hadNotifications) {
            console.log('[Announcements] Push notifications cleared');
        }
    }

    disconnectedCallback() {
        super.disconnectedCallback();
        document.removeEventListener('click', this.handleOutsideClick.bind(this));
        // Close BroadcastChannel when component is removed
        if (this.pushChannel) {
            this.pushChannel.close();
        }
    }

    async loadAnnouncements() {
        if (!this.jsonUrl) return;
        
        try {
            const response = await fetch(this.jsonUrl);
            const data = await response.json();
            
            if (data.status === 'success') {
                this.announcements = data.announcements || [];
                this.undismissedCount = data.dismissed_count !== undefined 
                    ? this.announcements.filter(a => !a.dismissed).length 
                    : this.announcements.length;
            }
        } catch (error) {
            console.error('Error loading announcements:', error);
        }
    }

    togglePopup() {
        this.popupOpen = !this.popupOpen;
        if (this.popupOpen) {
            this.updateComplete.then(() => this.positionPopup());
        }
    }

    positionPopup() {
        const popup = this.shadowRoot.querySelector('.popup');
        if (!popup) return;
        
        const rect = this.getBoundingClientRect();
        const popupWidth = 320;
        const spaceOnRight = window.innerWidth - rect.right;
        const margin = 15;
        
        popup.classList.remove('align-right', 'align-left');
        
        if (spaceOnRight < (popupWidth + margin)) {
            popup.classList.add('align-left');
        } else {
            popup.classList.add('align-right');
        }
        
        // Verify position after rendering
        setTimeout(() => {
            const popupRect = popup.getBoundingClientRect();
            if (popupRect.left < margin) {
                popup.style.left = margin + 'px';
                popup.style.right = 'auto';
            }
            if (popupRect.right > (window.innerWidth - margin)) {
                popup.style.right = margin + 'px';
                popup.style.left = 'auto';
            }
        }, 0);
    }

    handleOutsideClick(e) {
        if (this.popupOpen && !this.contains(e.target)) {
            this.popupOpen = false;
        }
    }

    async handleDismiss(e, announcementId, isDismissed) {
        e.preventDefault();
        e.stopPropagation();
        
        if (!this.dismissUrl) return;
        
        const formData = new FormData();
        formData.append('announcement_id', announcementId);
        formData.append('dismiss', isDismissed ? 0 : 1);
        
        try {
            const response = await fetch(this.dismissUrl, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.status === 'success') {
                // Update local state
                const announcement = this.announcements.find(a => a.announcement_id === announcementId);
                if (announcement) {
                    announcement.dismissed = !isDismissed;
                    this.undismissedCount = this.announcements.filter(a => !a.dismissed).length;
                    this.requestUpdate();
                }
            }
        } catch (error) {
            console.error('Error dismissing announcement:', error);
            alert('Error updating announcement. Please try again.');
        }
    }

    render() {
        // Show undismissed, or all if no undismissed
        const displayAnnouncements = this.announcements.filter(a => !a.dismissed);
        const finalAnnouncements = displayAnnouncements.length > 0 
            ? displayAnnouncements 
            : this.announcements;
        
        // Total badge count includes both announcements and push notifications
        const totalBadgeCount = this.undismissedCount + (this.pushNotificationCount || 0);
        
        return html`
            <span class="bullhorn-icon" @click=${this.togglePopup}>
                <svg class="bell-icon" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" fill="none"/>
                </svg>
                ${totalBadgeCount > 0 ? html`<span class="badge">${totalBadgeCount}</span>` : ''}
            </span>
            ${this.popupOpen ? html`
                <div class="popup">
                    <div class="popup-header">
                        <strong>Announcements</strong>
                        <span class="popup-close" @click=${() => this.popupOpen = false}>×</span>
                    </div>
                    <div class="popup-content">
                        ${finalAnnouncements.length === 0 && (!this.pushNotificationCount || this.pushNotificationCount === 0) ? html`
                            <div class="empty-message">No announcements</div>
                        ` : html`
                            <div class="announcement-list">
                                ${this.pushNotificationCount > 0 && this.notificationsUrl ? html`
                                    <div class="announcement-item">
                                        <a href="${this.notificationsUrl}" class="announcement-title" @click=${() => this.clearPushNotifications()}>
                                            🔔 ${this.pushNotificationCount} new notification${this.pushNotificationCount > 1 ? 's' : ''}
                                        </a>
                                    </div>
                                ` : ''}
                                ${finalAnnouncements.map(announcement => html`
                                    <div class="announcement-item ${announcement.dismissed ? 'dismissed' : ''}">
                                        <a href="${this.viewUrl}" class="announcement-title">
                                            ${announcement.title}
                                        </a>
                                        <span 
                                            class="dismiss-icon ${announcement.dismissed ? 'dismissed' : ''}"
                                            @click=${(e) => this.handleDismiss(e, announcement.announcement_id, announcement.dismissed)}
                                            title="${announcement.dismissed ? 'Undismiss' : 'Dismiss'}">
                                            ✓
                                        </span>
                                    </div>
                                `)}
                            </div>
                        `}
                    </div>
                </div>
            ` : ''}
        `;
    }
}

customElements.define('tsugi-announce', TsugiAnnounce);

// Global test function - can be called from console
// Usage: testAnnouncementsPush()
window.testAnnouncementsPush = function() {
    const component = document.querySelector('tsugi-announce');
    if (component) {
        console.log('[Test] Found announcements component, triggering test push');
        component.testPushNotification();
        return 'Test push notification triggered! Check the badge.';
    } else {
        console.error('[Test] Announcements component not found on page');
        return 'Error: Announcements component not found. Make sure you\'re on a page with the component.';
    }
};

// Also expose a function to simulate service worker message
window.testAnnouncementsMessage = function() {
    const component = document.querySelector('tsugi-announce');
    if (component) {
        console.log('[Test] Simulating service worker message');
        // Simulate the message event that would come from service worker
        const mockEvent = {
            data: {
                type: 'push-received',
                message: 'Test message from console',
                timestamp: Date.now()
            }
        };
        // Trigger the message handler
        if (navigator.serviceWorker && navigator.serviceWorker.onmessage) {
            navigator.serviceWorker.onmessage(mockEvent);
        } else {
            // Directly call the handler if onmessage isn't set
            component.handlePushNotification();
        }
        return 'Test message sent! Check the badge.';
    } else {
        return 'Error: Announcements component not found.';
    }
};

// Test BroadcastChannel directly
window.testBroadcastChannel = function() {
    if ('BroadcastChannel' in window) {
        const channel = new BroadcastChannel('push-notifications');
        channel.postMessage({
            type: 'push-received',
            message: 'Test message via BroadcastChannel',
            timestamp: Date.now()
        });
        console.log('[Test] Sent message via BroadcastChannel');
        setTimeout(() => channel.close(), 1000);
        return 'BroadcastChannel test message sent! Check if component received it.';
    } else {
        return 'BroadcastChannel not supported';
    }
};
