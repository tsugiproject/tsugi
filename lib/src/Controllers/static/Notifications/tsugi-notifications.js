import { LitElement, html, css } from 'https://cdn.jsdelivr.net/gh/lit/dist@3/core/lit-core.min.js';

/**
 * Tsugi Notifications Web Component
 * 
 * Usage:
 *   <tsugi-notifications 
 *       api-url="/tsugi/api/notifications.php" 
 *       notifications-view-url="/notifications"
 *       announcements-view-url="/announcements">
 *   </tsugi-notifications>
 * 
 * Attributes:
 *   - api-url: URL to fetch notifications and announcements JSON (uses new unified API)
 *   - notifications-view-url: URL to view all notifications (defaults to /notifications)
 *   - announcements-view-url: URL to view all announcements (defaults to /announcements)
 */
class TsugiNotifications extends LitElement {
    static properties = {
        apiUrl: { type: String, attribute: 'api-url' },
        notificationsViewUrl: { type: String, attribute: 'notifications-view-url' },
        announcementsViewUrl: { type: String, attribute: 'announcements-view-url' },
        notifications: { type: Array, state: true },
        announcements: { type: Array, state: true },
        unreadNotificationCount: { type: Number, state: true },
        unreadAnnouncementCount: { type: Number, state: true },
        pushNotificationCount: { type: Number, state: true },
        popupOpen: { type: Boolean, state: true }
    };

    static styles = css`
        :host {
            display: inline-block;
            position: relative;
            padding: 0 0.05em;
        }
        
        .bell-icon-container {
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
        
        .notification-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .notification-item {
            display: flex;
            align-items: center;
            padding: 8px 15px;
            border-bottom: 1px solid #eee;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-item.read {
            opacity: 0.7;
        }
        
        .notification-item.unread {
            border-left: 3px solid #d9534f;
            background-color: #fff5f5;
        }
        
        .notification-title {
            flex: 1;
            text-decoration: none;
            color: #337ab7;
            font-size: 14px;
            text-align: left;
        }
        
        .notification-title:hover {
            text-decoration: underline;
        }
        
        .notification-type-badge {
            display: inline-block;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 3px;
            margin-right: 6px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .notification-type-badge.notification {
            background-color: #5bc0de;
            color: white;
        }
        
        .notification-type-badge.announcement {
            background-color: #5cb85c;
            color: white;
        }
        
        .empty-message {
            padding: 10px;
            color: #999;
        }
    `;

    constructor() {
        super();
        this.notifications = [];
        this.announcements = [];
        this.unreadNotificationCount = 0;
        this.unreadAnnouncementCount = 0;
        this.pushNotificationCount = 0;
        this.popupOpen = false;
        this.apiUrl = '';
        this.notificationsViewUrl = '/notifications';
        this.announcementsViewUrl = '/announcements';
    }

    connectedCallback() {
        super.connectedCallback();
        this.loadData();
        // Close popup when clicking outside
        document.addEventListener('click', this.handleOutsideClick.bind(this));
        
        // Refresh notifications periodically (every 30 seconds)
        this.notificationRefreshInterval = setInterval(() => {
            this.loadData();
        }, 30000);
        
        // Clear push notifications if we're on the notifications page
        if (this.notificationsViewUrl) {
            const currentPath = window.location.pathname;
            const viewPath = new URL(this.notificationsViewUrl, window.location.origin).pathname;
            if (currentPath === viewPath || currentPath.includes('/notifications')) {
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
                    // Also refresh notifications when push is received
                    this.loadData();
                }
            });
            
            // Listen for announcements updates (when announcements are dismissed)
            this.announcementsChannel = new BroadcastChannel('announcements-updates');
            this.announcementsChannel.addEventListener('message', (event) => {
                if (event.data && (event.data.type === 'announcement-dismissed' || event.data.type === 'announcements-all-dismissed')) {
                    // Refresh data to update badge count
                    this.loadData();
                }
            });
            
            // Listen for notification updates (when notifications are marked as read)
            this.notificationsChannel = new BroadcastChannel('notifications-updates');
            this.notificationsChannel.addEventListener('message', (event) => {
                if (event.data && (event.data.type === 'notification-read' || event.data.type === 'notifications-all-read')) {
                    // Refresh data to update badge count
                    this.loadData();
                }
            });
        }
        
        // Also listen for custom DOM events
        document.addEventListener('announcement-dismissed', () => {
            // Refresh data to update badge count when an announcement is dismissed
            this.loadData();
        });
        
        document.addEventListener('announcements-all-dismissed', () => {
            // Refresh data to update badge count when all announcements are dismissed
            this.loadData();
        });
        
        document.addEventListener('notification-read', () => {
            // Refresh data to update badge count when a notification is marked as read
            this.loadData();
        });
        
        document.addEventListener('notifications-all-read', () => {
            // Refresh data to update badge count when all notifications are marked as read
            this.loadData();
        });
        
        if ('serviceWorker' in navigator) {
            // Notify service worker that notifications component is ready
            navigator.serviceWorker.ready.then(registration => {
                if (registration.active) {
                    registration.active.postMessage({type: 'notifications-ready'});
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
        console.log('[Notifications] Test: Manually triggering push notification');
        this.handlePushNotification();
    }
    
    clearPushNotifications() {
        // Clear push notification count (e.g., when user visits /notifications)
        const hadNotifications = this.pushNotificationCount > 0;
        this.pushNotificationCount = 0;
        if (hadNotifications) {
            console.log('[Notifications] Push notifications cleared');
        }
    }

    disconnectedCallback() {
        super.disconnectedCallback();
        document.removeEventListener('click', this.handleOutsideClick.bind(this));
        // Clear refresh interval
        if (this.notificationRefreshInterval) {
            clearInterval(this.notificationRefreshInterval);
        }
        // Close BroadcastChannel when component is removed
        if (this.pushChannel) {
            this.pushChannel.close();
        }
        if (this.announcementsChannel) {
            this.announcementsChannel.close();
        }
        if (this.notificationsChannel) {
            this.notificationsChannel.close();
        }
    }

    async loadData() {
        if (!this.apiUrl) return;
        
        try {
            const response = await fetch(this.apiUrl);
            if (!response.ok) {
                if (response.status === 403) {
                    console.log('[Notifications] Not authenticated - skipping load');
                    return;
                }
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (data.status === 'success') {
                this.notifications = data.notifications || [];
                this.announcements = data.announcements || [];
                this.unreadNotificationCount = data.unread_notification_count || 0;
                this.unreadAnnouncementCount = data.unread_announcement_count || 0;
            } else {
                console.error('[Notifications] API returned error:', data);
            }
        } catch (error) {
            console.error('[Notifications] Error loading data:', error);
        }
    }

    togglePopup() {
        this.popupOpen = !this.popupOpen;
        if (this.popupOpen) {
            // Refresh data when opening popup
            this.loadData();
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

    render() {
        // Combine notifications and announcements, sort by created_at (most recent first)
        const allItems = [
            ...this.notifications.map(n => ({...n, created_at: n.created_at, type: 'notification'})),
            ...this.announcements.map(a => ({...a, created_at: a.created_at, type: 'announcement'}))
        ].sort((a, b) => {
            const dateA = new Date(a.created_at);
            const dateB = new Date(b.created_at);
            return dateB - dateA; // Most recent first
        }).slice(0, 20); // Show up to 20 most recent items
        
        // Total badge count includes unread notifications, unread announcements, and push notifications
        const totalBadgeCount = this.unreadNotificationCount + this.unreadAnnouncementCount + (this.pushNotificationCount || 0);
        
        const hasAnyContent = allItems.length > 0 || (this.pushNotificationCount > 0);
        
        return html`
            <span class="bell-icon-container" @click=${this.togglePopup}>
                <svg class="bell-icon" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" preserveAspectRatio="xMidYMid meet">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" fill="none"/>
                </svg>
                ${totalBadgeCount > 0 ? html`<span class="badge">${totalBadgeCount}</span>` : ''}
            </span>
            ${this.popupOpen ? html`
                <div class="popup">
                    <div class="popup-header">
                        <strong>Notifications</strong>
                        <span class="popup-close" @click=${() => this.popupOpen = false}>×</span>
                    </div>
                    <div class="popup-content">
                        ${!hasAnyContent ? html`
                            <div class="empty-message">No notifications</div>
                        ` : html`
                            <div class="notification-list">
                                ${this.pushNotificationCount > 0 && this.notificationsViewUrl ? html`
                                    <div class="notification-item unread">
                                        <a href="${this.notificationsViewUrl}" class="notification-title" @click=${() => this.clearPushNotifications()}>
                                            🔔 ${this.pushNotificationCount} new push notification${this.pushNotificationCount > 1 ? 's' : ''}
                                        </a>
                                    </div>
                                ` : ''}
                                ${allItems.map(item => {
                                    // Always use the view URL (notifications or announcements page) instead of item.url
                                    // This allows users to see the full notification/announcement details and then navigate to the URL from there
                                    const viewUrl = item.type === 'notification' 
                                        ? (this.notificationsViewUrl || '#')
                                        : (this.announcementsViewUrl || '#');
                                    return html`
                                        <div class="notification-item ${item.is_read || item.dismissed ? 'read' : 'unread'}">
                                            <a href="${viewUrl}" class="notification-title">
                                                <span class="notification-type-badge ${item.type}">${item.type === 'notification' ? 'N' : 'A'}</span>
                                                ${!item.is_read && !item.dismissed ? html`<strong>` : ''}${item.title}${!item.is_read && !item.dismissed ? html`</strong>` : ''}
                                            </a>
                                        </div>
                                    `;
                                })}
                            </div>
                        `}
                    </div>
                </div>
            ` : ''}
        `;
    }
}

customElements.define('tsugi-notifications', TsugiNotifications);

// Global test function - can be called from console
// Usage: testNotificationsPush()
window.testNotificationsPush = function() {
    const component = document.querySelector('tsugi-notifications');
    if (component) {
        console.log('[Test] Found notifications component, triggering test push');
        component.testPushNotification();
        return 'Test push notification triggered! Check the badge.';
    } else {
        console.error('[Test] Notifications component not found on page');
        return 'Error: Notifications component not found. Make sure you\'re on a page with the component.';
    }
};

// Test BroadcastChannel directly
window.testNotificationsBroadcastChannel = function() {
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
