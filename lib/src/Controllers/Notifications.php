<?php

namespace Tsugi\Controllers;

use Tsugi\Util\U;
use Tsugi\Util\NotificationsService;
use Tsugi\Core\LTIX;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Notifications extends Tool {

    const ROUTE = '/notifications';
    const NAME = 'Notifications';

    public static function routes(Application $app, $prefix=self::ROUTE) {
        $app->router->get($prefix, 'Notifications@index');
        $app->router->get($prefix.'/', 'Notifications@index');
        $app->router->get($prefix.'/configure-push', 'Notifications@configurePush');
        $app->router->get($prefix.'/send', 'Notifications@send');
        $app->router->post($prefix.'/send', 'Notifications@sendPost');
        $app->router->get($prefix.'/send-to-student', 'Notifications@sendToStudent');
        $app->router->post($prefix.'/send-to-student', 'Notifications@sendToStudentPost');
        $app->router->get($prefix.'/json', 'Notifications@json');
        $app->router->post($prefix.'/mark-read', 'Notifications@markRead');
        $app->router->post($prefix.'/mark-all-read', 'Notifications@markAllRead');
        $app->router->post($prefix.'/subscribe', 'Notifications@subscribe');
        $app->router->post($prefix.'/unsubscribe', 'Notifications@unsubscribe');
        $app->router->post($prefix.'/test', 'Notifications@test');
        $app->router->get($prefix.'/vapid-public-key', 'Notifications@getVapidPublicKey');
    }

    /**
     * Main notifications page - shows user notifications in reverse chronological order
     */
    public function index(Request $request) {
        global $OUTPUT, $CFG;

        $this->requireAuth();
        
        LTIX::getConnection();
        
        $user_id = $_SESSION['id'];

        // Record analytics
        $this->lmsRecordLaunchAnalytics(self::ROUTE, self::NAME);

        // Get notifications for user (most recent first)
        $notifications = NotificationsService::getForUser($user_id, false, 0);
        $unread_count = NotificationsService::getUnreadCount($user_id);

        // Check if service worker is enabled and VAPID keys are configured
        $service_worker_enabled = isset($CFG->service_worker) && $CFG->service_worker;
        $vapid_configured = !empty($CFG->vapid_public_key) && !empty($CFG->vapid_private_key);
        $push_enabled = $service_worker_enabled && $vapid_configured;

        $tool_home = $this->toolHome(self::ROUTE);
        $configure_push_url = $tool_home . '/configure-push';
        $json_url = $tool_home . '/json';
        $mark_read_url = $tool_home . '/mark-read';
        $mark_all_read_url = $tool_home . '/mark-all-read';

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <div class="container">
            <div class="row">
                <div class="col-xs-12">
                    <h1>Notifications</h1>
                </div>
            </div>
            <div class="row" style="margin-bottom: 20px;">
                <div class="col-xs-12">
                    <div class="notification-actions">
                        <a href="<?= htmlspecialchars($tool_home . '/send') ?>" class="btn btn-primary btn-sm">
                            <span class="hidden-xs">Test Notification</span>
                            <span class="visible-xs">Test</span>
                        </a>
                        <?php if ($this->isInstructor()): ?>
                            <a href="<?= htmlspecialchars($tool_home . '/send-to-student') ?>" class="btn btn-success btn-sm">
                                <span class="hidden-xs">Send to Student</span>
                                <span class="visible-xs">To Student</span>
                            </a>
                        <?php endif; ?>
                        <?php if ($push_enabled): ?>
                            <a href="<?= htmlspecialchars($configure_push_url) ?>" class="btn btn-default btn-sm">
                                <span class="hidden-xs">Configure Push</span>
                                <span class="visible-xs">Push</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php if ($unread_count > 1): ?>
                <div class="alert alert-info">
                    <div class="clearfix">
                        <div class="pull-left" style="line-height: 34px;">
                            <strong><?= $unread_count ?> unread notifications</strong>
                        </div>
                        <div class="pull-right">
                            <button id="mark-all-read-btn" class="btn btn-sm btn-primary" data-url="<?= htmlspecialchars($mark_all_read_url) ?>">
                                Mark All as Read
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (empty($notifications)): ?>
                <div class="alert alert-info">
                    <p>No notifications at this time.</p>
                </div>
            <?php else: ?>
                <div id="notifications-list">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="panel panel-default notification-item <?= $notification['read_at'] ? 'read' : 'unread' ?>" 
                             data-notification-id="<?= htmlspecialchars($notification['notification_id']) ?>">
                            <div class="panel-heading">
                                <div class="row">
                                    <div class="col-xs-12 col-sm-8">
                                        <h3 class="panel-title" style="margin-top: 0;">
                                            <?php if (!$notification['read_at']): ?>
                                                <span class="badge" style="background: #d9534f; margin-right: 10px;">New</span>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($notification['title']) ?>
                                        </h3>
                                    </div>
                                    <div class="col-xs-12 col-sm-4">
                                        <div style="text-align: right; margin-top: 5px;">
                                            <?php if (!$notification['read_at']): ?>
                                                <button class="btn btn-sm btn-primary mark-read-btn" 
                                                        data-notification-id="<?= htmlspecialchars($notification['notification_id']) ?>"
                                                        data-url="<?= htmlspecialchars($mark_read_url) ?>">
                                                    Mark as Read
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="panel-body">
                                <?php if (!empty($notification['text'])): ?>
                                <div class="notification-text">
                                    <?= nl2br(htmlspecialchars($notification['text'])) ?>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($notification['url'])): ?>
                                    <p class="notification-url" style="margin-top: 10px;">
                                        <a href="<?= htmlspecialchars($notification['url']) ?>" target="_blank" class="btn btn-link">
                                            Notification Source <span class="glyphicon glyphicon-new-window"></span>
                                        </a>
                                    </p>
                                <?php endif; ?>
                                <div class="text-muted small">
                                    <em>
                                        <?= date('M j, Y g:i A', strtotime($notification['created_at'])) ?>
                                    </em>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <style>
        .notification-item.unread {
            border-left: 4px solid #d9534f;
        }
        .notification-item.read {
            opacity: 0.7;
        }
        
        /* Button actions - horizontal with wrapping */
        .notification-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .notification-actions .btn {
            margin: 0;
        }
        
        /* Responsive alert - stack on very small screens */
        @media (max-width: 480px) {
            .alert .pull-left,
            .alert .pull-right {
                float: none !important;
                display: block;
                text-align: center;
            }
            .alert .pull-right {
                margin-top: 10px;
            }
        }
        
        /* Responsive notification header */
        @media (max-width: 480px) {
            .notification-item .panel-heading .row {
                margin: 0;
            }
            .notification-item .panel-heading .col-xs-12 {
                padding: 0;
            }
            .notification-item .panel-heading .col-sm-4 {
                margin-top: 5px;
            }
            .notification-item .panel-heading .col-sm-4 div {
                text-align: left !important;
            }
        }
        
        /* Ensure notification date styling matches announcements */
        .notification-item .panel-body .text-muted {
            margin-top: 10px;
        }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mark individual notification as read
            document.querySelectorAll('.mark-read-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var notificationId = this.getAttribute('data-notification-id');
                    var url = this.getAttribute('data-url');
                    var item = this.closest('.notification-item');
                    
                    var formData = new FormData();
                    formData.append('notification_id', notificationId);
                    
                    fetch(url, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            item.classList.remove('unread');
                            item.classList.add('read');
                            var badge = item.querySelector('.badge');
                            if (badge) badge.remove();
                            var btn = item.querySelector('.mark-read-btn');
                            if (btn) btn.remove();
                            // Update unread count without reload
                            var unreadCountEl = document.querySelector('.alert-info strong');
                            if (unreadCountEl) {
                                var match = unreadCountEl.textContent.match(/(\d+)/);
                                if (match) {
                                    var newCount = parseInt(match[1]) - 1;
                                    if (newCount <= 0) {
                                        var alertEl = unreadCountEl.closest('.alert-info');
                                        if (alertEl) alertEl.style.display = 'none';
                                    } else {
                                        unreadCountEl.textContent = newCount + ' unread notification' + (newCount > 1 ? 's' : '');
                                    }
                                }
                            }
                        } else {
                            alert('Error marking notification as read: ' + (data.detail || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        alert('Error marking notification as read: ' + error.message);
                    });
                });
            });
            
            // Mark all as read
            var markAllReadBtn = document.getElementById('mark-all-read-btn');
            if (markAllReadBtn) {
                markAllReadBtn.addEventListener('click', function() {
                    var url = this.getAttribute('data-url');
                    
                    fetch(url, {
                        method: 'POST'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            window.location.reload();
                        } else {
                            alert('Error marking all as read: ' + (data.detail || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        alert('Error marking all as read: ' + error.message);
                    });
                });
            }
        });
        </script>
        <?php
        $OUTPUT->footer();
    }

    /**
     * Configure push notifications page - moved from main index page
     */
    public function configurePush(Request $request) {
        global $OUTPUT, $PDOX, $CFG;

        $this->requireAuth();
        
        LTIX::getConnection();
        
        $user_id = $_SESSION['id'];

        // Record analytics
        $this->lmsRecordLaunchAnalytics(self::ROUTE . '/configure-push', self::NAME . ' - Configure Push');

        // Check if service worker is enabled and VAPID keys are configured
        $service_worker_enabled = isset($CFG->service_worker) && $CFG->service_worker;
        $vapid_configured = !empty($CFG->vapid_public_key) && !empty($CFG->vapid_private_key);
        
        $tool_home = $this->toolHome(self::ROUTE);
        $back_url = $tool_home;
        
        if (!$service_worker_enabled) {
            $_SESSION['error'] = 'Service worker is not enabled. Push notifications are not available.';
            return new RedirectResponse($back_url);
        }
        
        if (!$vapid_configured) {
            $_SESSION['error'] = 'Push notifications are not configured. VAPID keys are missing.';
            return new RedirectResponse($back_url);
        }

        // Check if user has active subscriptions (can have multiple - one per browser)
        $subscriptions = $this->getUserSubscriptions($user_id);
        $hasSubscription = !empty($subscriptions);
        
        // Get current browser info for display
        $current_browser = null;
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $ua = $_SERVER['HTTP_USER_AGENT'];
            if (strpos($ua, 'Firefox') !== false) {
                $current_browser = 'Firefox';
            } elseif (strpos($ua, 'Chrome') !== false && strpos($ua, 'Edg') === false) {
                $current_browser = 'Chrome';
            } elseif (strpos($ua, 'Edg') !== false) {
                $current_browser = 'Edge';
            } elseif (strpos($ua, 'Safari') !== false) {
                $current_browser = 'Safari';
            }
        }

        $tool_home = $this->toolHome(self::ROUTE);
        $back_url = $tool_home;
        $subscribe_url = $tool_home . '/subscribe';
        $unsubscribe_url = $tool_home . '/unsubscribe';
        $test_url = $tool_home . '/test';
        $vapid_key_url = $tool_home . '/vapid-public-key';

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <div class="container">
            <h1>Configure Push Notifications
                <span class="pull-right">
                    <a href="<?= htmlspecialchars($back_url) ?>" class="btn btn-default">Back to Notifications</a>
                </span>
            </h1>
            
            <div class="panel panel-default">
                <div class="panel-body">
                    <p>Enable push notifications to receive updates and announcements even when you're not on the site.</p>
                    
                    <div id="notification-status" style="margin: 20px 0;">
                        <?php if ($hasSubscription): ?>
                            <div class="alert alert-success">
                                <strong>✓ Notifications Enabled</strong>
                                <p>You have <?= count($subscriptions) ?> active subscription(s):</p>
                                <ul>
                                    <?php foreach ($subscriptions as $sub): ?>
                                        <li>
                                            <strong><?= htmlspecialchars($sub['browser_name'] ?? 'Unknown') ?></strong>
                                            <?php if ($current_browser && ($sub['browser_name'] ?? '') === $current_browser): ?>
                                                <span class="badge" style="background: #5cb85c;">Current Browser</span>
                                            <?php endif; ?>
                                            <br>
                                            <small style="color: #666;"><?= htmlspecialchars(substr($sub['endpoint'], 0, 80)) ?>...</small>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <button id="unsubscribe-btn" class="btn btn-danger" data-url="<?= htmlspecialchars($unsubscribe_url) ?>">
                                    Disable push notifications
                                </button>
                                <?php if (!$current_browser || !in_array($current_browser, array_column($subscriptions, 'browser_name'))): ?>
                                    <button id="subscribe-btn" class="btn btn-primary" data-url="<?= htmlspecialchars($subscribe_url) ?>" data-vapid-key-url="<?= htmlspecialchars($vapid_key_url) ?>" style="margin-left: 10px;">
                                        Subscribe in <?= htmlspecialchars($current_browser ?? 'this browser') ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <strong>Notifications Disabled</strong>
                                <p>Click the button below to enable push notifications<?= $current_browser ? ' in ' . htmlspecialchars($current_browser) : '' ?>.</p>
                                <button id="subscribe-btn" class="btn btn-primary" data-url="<?= htmlspecialchars($subscribe_url) ?>" data-vapid-key-url="<?= htmlspecialchars($vapid_key_url) ?>">
                                    Enable Notifications
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($this->isInstructor()): ?>
                        <hr>
                        <h3>Instructor Tools</h3>
                        <button id="test-notification-btn" class="btn btn-default" data-url="<?= htmlspecialchars($test_url) ?>">
                            Send Test Push
                        </button>
                    <?php endif; ?>

                    <hr>
                    <h3>Debug Information</h3>
                    <div id="debug-info" style="background: #f5f5f5; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 12px;">
                        <div id="permission-status">Checking permission status...</div>
                        <div id="service-worker-status" style="margin-top: 10px;">Checking service worker...</div>
                        <div id="subscription-status" style="margin-top: 10px;">Checking subscription...</div>
                    </div>
                    <button id="refresh-debug-btn" class="btn btn-sm btn-default" style="margin-top: 10px;">
                        Refresh Debug Info
                    </button>
                    
                    <hr>
                    <h3>How to Check Notification Settings</h3>
                    <div style="background: #e7f3ff; padding: 15px; border-radius: 4px; border-left: 4px solid #2196F3;">
                        <h4>Chrome/Edge:</h4>
                        <ol>
                            <li>Click the lock icon (🔒) or info icon (i) in the address bar</li>
                            <li>Click "Site settings"</li>
                            <li>Find "Notifications" and ensure it's set to "Allow"</li>
                            <li>Or go to: <code>chrome://settings/content/notifications</code> and check the "Allowed" list</li>
                        </ol>
                        
                        <h4>Firefox:</h4>
                        <ol>
                            <li>Click the lock icon in the address bar</li>
                            <li>Click "More Information"</li>
                            <li>Go to "Permissions" tab</li>
                            <li>Find "Notifications" and ensure it's set to "Allow"</li>
                            <li>Or go to: <code>about:preferences#privacy</code> → "Permissions" → "Notifications" → "Settings"</li>
                        </ol>
                        
                        <h4>Safari:</h4>
                        <ol>
                            <li>Safari menu → Settings → Websites</li>
                            <li>Click "Notifications" in the left sidebar</li>
                            <li>Find <code>local.ca4e.com</code> and ensure it's set to "Allow"</li>
                        </ol>
                        
                        <h4>macOS System Settings:</h4>
                        <ol>
                            <li>System Settings → Notifications</li>
                            <li>Find your browser (Chrome/Firefox/Safari)</li>
                            <li>Ensure notifications are enabled</li>
                            <li>Check "Do Not Disturb" isn't blocking notifications</li>
                        </ol>
                        
                        <h4>Check Service Worker Console:</h4>
                        <ol>
                            <li>Open Developer Tools (F12 or Cmd+Option+I)</li>
                            <li>Go to "Application" tab (Chrome) or "Storage" tab (Firefox)</li>
                            <li>Click "Service Workers" in the left sidebar</li>
                            <li>Click "Console" next to your service worker</li>
                            <li>Send a test notification and watch for <code>[SW]</code> logs</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <script type="module" src="<?= htmlspecialchars(\Tsugi\Controllers\StaticFiles::url('Notifications', 'notifications.js')) ?>"></script>
        <?php
        $OUTPUT->footer();
    }

    /**
     * Subscribe to push notifications
     */
    public function subscribe(Request $request) {
        global $PDOX, $CFG;

        $this->requireAuth();
        
        LTIX::getConnection();
        
        // Check if service worker is enabled
        $service_worker_enabled = isset($CFG->service_worker) && $CFG->service_worker;
        if (!$service_worker_enabled) {
            return new JsonResponse(['error' => 'Service worker is not enabled'], 403);
        }
        
        $user_id = $_SESSION['id'];

        // Get subscription data from request
        $subscription_json = $request->getContent();
        $subscription_data = json_decode($subscription_json, true);

        if (!$subscription_data || !isset($subscription_data['endpoint']) || !isset($subscription_data['keys'])) {
            return new JsonResponse(['error' => 'Invalid subscription data'], 400);
        }

        // Get VAPID public key
        $vapid_public_key = $this->getVapidPublicKeyValue();

        // Detect browser from endpoint
        $browser_info = $this->detectBrowserFromEndpoint($subscription_data['endpoint']);
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Store subscription in database (allow multiple per user - one per browser)
        // Check if subscription for this browser already exists
        $p = $CFG->dbprefix;
        $check_sql = "SELECT subscription_id FROM {$p}push_subscriptions 
                       WHERE user_id = :user_id AND browser_name = :browser_name 
                       LIMIT 1";
        $existing = $PDOX->rowDie($check_sql, array(
            ':user_id' => $user_id,
            ':browser_name' => $browser_info['name']
        ));
        
        if ($existing) {
            // Update existing subscription for this browser
            $sql = "UPDATE {$p}push_subscriptions 
                    SET endpoint = :endpoint,
                        p256dh = :p256dh,
                        auth = :auth,
                        browser_endpoint_type = :browser_type,
                        user_agent = :user_agent,
                        updated_at = NOW()
                    WHERE subscription_id = :subscription_id";
            $PDOX->queryDie($sql, array(
                ':subscription_id' => $existing['subscription_id'],
                ':endpoint' => $subscription_data['endpoint'],
                ':p256dh' => $subscription_data['keys']['p256dh'] ?? '',
                ':auth' => $subscription_data['keys']['auth'] ?? '',
                ':browser_type' => $browser_info['type'],
                ':user_agent' => substr($user_agent, 0, 500)
            ));
        } else {
            // Insert new subscription
            $sql = "INSERT INTO {$p}push_subscriptions 
                    (user_id, endpoint, p256dh, auth, browser_name, browser_endpoint_type, user_agent, created_at, updated_at)
                    VALUES (:user_id, :endpoint, :p256dh, :auth, :browser_name, :browser_type, :user_agent, NOW(), NOW())";
            $PDOX->queryDie($sql, array(
                ':user_id' => $user_id,
                ':endpoint' => $subscription_data['endpoint'],
                ':p256dh' => $subscription_data['keys']['p256dh'] ?? '',
                ':auth' => $subscription_data['keys']['auth'] ?? '',
                ':browser_name' => $browser_info['name'],
                ':browser_type' => $browser_info['type'],
                ':user_agent' => substr($user_agent, 0, 500)
            ));
        }


        return new JsonResponse([
            'success' => true,
            'message' => 'Subscription saved successfully',
            'vapid_public_key' => $vapid_public_key
        ]);
    }

    /**
     * Unsubscribe from push notifications
     * Can unsubscribe all or just current browser
     */
    public function unsubscribe(Request $request) {
        global $PDOX, $CFG;

        $this->requireAuth();
        
        LTIX::getConnection();
        
        // Check if service worker is enabled
        $service_worker_enabled = isset($CFG->service_worker) && $CFG->service_worker;
        if (!$service_worker_enabled) {
            return new JsonResponse(['error' => 'Service worker is not enabled'], 403);
        }
        
        $user_id = $_SESSION['id'];
        
        // Check if we should unsubscribe just current browser or all
        $unsubscribe_all = $request->get('all', 'false') === 'true';
        
        $p = $CFG->dbprefix;
        
        if ($unsubscribe_all) {
            // Remove all subscriptions for this user
            $sql = "DELETE FROM {$p}push_subscriptions WHERE user_id = :user_id";
            $PDOX->queryDie($sql, array(':user_id' => $user_id));
            $message = 'Unsubscribed from all browsers successfully';
        } else {
            // Remove subscription for current browser only
            // Get current browser from user agent
            $current_browser = null;
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $ua = $_SERVER['HTTP_USER_AGENT'];
                if (strpos($ua, 'Firefox') !== false) {
                    $current_browser = 'Firefox';
                } elseif (strpos($ua, 'Chrome') !== false && strpos($ua, 'Edg') === false) {
                    $current_browser = 'Chrome';
                } elseif (strpos($ua, 'Edg') !== false) {
                    $current_browser = 'Edge';
                }
            }
            
            if ($current_browser) {
                $sql = "DELETE FROM {$p}push_subscriptions WHERE user_id = :user_id AND browser_name = :browser_name";
                $PDOX->queryDie($sql, array(':user_id' => $user_id, ':browser_name' => $current_browser));
                $message = "Unsubscribed from $current_browser successfully";
            } else {
                // Fallback: remove all if we can't detect browser
                $sql = "DELETE FROM {$p}push_subscriptions WHERE user_id = :user_id";
                $PDOX->queryDie($sql, array(':user_id' => $user_id));
                $message = 'Unsubscribed successfully';
            }
        }

        return new JsonResponse([
            'success' => true,
            'message' => $message
        ]);
    }

    /**
     * Send a test notification (instructor only)
     */
    public function test(Request $request) {
        global $PDOX, $CFG;

        // Ensure no output before JSON
        if (ob_get_level() > 0) {
            ob_clean();
        }

        try {
            $this->requireAuth();
            
            LTIX::getConnection();
            
            if (!$this->isInstructor()) {
                return new JsonResponse(['error' => 'Instructor access required'], 403);
            }

            // Check if service worker is enabled
            $service_worker_enabled = isset($CFG->service_worker) && $CFG->service_worker;
            if (!$service_worker_enabled) {
                return new JsonResponse(['error' => 'Service worker is not enabled'], 403);
            }

            $user_id = $_SESSION['id'];
            $subscriptions = $this->getUserSubscriptions($user_id);

            if (empty($subscriptions)) {
                return new JsonResponse(['error' => 'You must be subscribed to receive test notifications'], 400);
            }
            
            $success_count = 0;
            $failure_count = 0;
            $results = [];
            
            // Send test notification to all subscriptions (user might have multiple browsers)
            foreach ($subscriptions as $subscription) {
                
                $result = $this->sendPushNotification(
                    $subscription['endpoint'],
                    $subscription['p256dh'],
                    $subscription['auth'],
                    'Test Notification',
                    'This is a test notification from CA4E!',
                    '/notifications'
                );
                
                $results[] = [
                    'browser' => $subscription['browser_name'] ?? 'Unknown',
                    'success' => $result['success'],
                    'error' => $result['error'] ?? null
                ];
                
                if ($result['success']) {
                    $success_count++;
                } else {
                    $failure_count++;
                }
            }

            if ($success_count > 0) {
                $message = "Test notification sent to $success_count subscription(s)";
                if ($failure_count > 0) {
                    $message .= " ($failure_count failed)";
                }
                return new JsonResponse([
                    'success' => true,
                    'message' => $message,
                    'results' => $results
                ]);
            } else {
                $error_msg = 'Failed to send notification to all subscriptions';
                if (!empty($results) && isset($results[0]['error'])) {
                    $error_msg .= ': ' . $results[0]['error'];
                }
                return new JsonResponse([
                    'success' => false,
                    'error' => $error_msg,
                    'results' => $results
                ], 500);
            }
        } catch (\Exception $e) {
            error_log("Exception in test notification: " . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send notification form - allows user to send a notification to themselves
     */
    public function send(Request $request) {
        global $OUTPUT;

        $this->requireAuth();
        
        LTIX::getConnection();
        
        $user_id = $_SESSION['id'];

        // Record analytics
        $this->lmsRecordLaunchAnalytics(self::ROUTE . '/send', self::NAME . ' - Test Notification');

        $tool_home = $this->toolHome(self::ROUTE);
        $back_url = $tool_home;
        $send_url = $tool_home . '/send';

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <div class="container">
            <h1>Test Notification
                <span class="pull-right">
                    <a href="<?= htmlspecialchars($back_url) ?>" class="btn btn-default">Back to Notifications</a>
                </span>
            </h1>
            
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Test Notification to Yourself</h3>
                </div>
                <div class="panel-body">
                    <p class="text-muted">This will send a notification to your account. Useful for testing or reminders.</p>
                    
                    <form method="POST" action="<?= htmlspecialchars($send_url) ?>">
                        <div class="form-group">
                            <label for="title">Title *</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?= htmlspecialchars(U::get($_POST, 'title', '')) ?>" 
                                   required
                                   placeholder="Enter notification title">
                        </div>
                        
                        <div class="form-group">
                            <label for="text">Text (optional)</label>
                            <textarea class="form-control" id="text" name="text" rows="5"
                                      placeholder="Enter notification message (optional)"><?= htmlspecialchars(U::get($_POST, 'text', '')) ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="url">URL (optional)</label>
                            <input type="url" class="form-control" id="url" name="url" 
                                   value="<?= htmlspecialchars(U::get($_POST, 'url', '')) ?>" 
                                   placeholder="https://example.com">
                            <small class="help-block">Optional link to include with the notification</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Send Test Notification</button>
                        <a href="<?= htmlspecialchars($back_url) ?>" class="btn btn-default">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
        <?php
        $OUTPUT->footer();
    }

    /**
     * Process send notification form submission
     */
    public function sendPost(Request $request) {
        global $CFG;

        $this->requireAuth();
        
        LTIX::getConnection();
        
        $user_id = $_SESSION['id'];
        
        $tool_home = $this->toolHome(self::ROUTE);
        $send_url = $tool_home . '/send';
        $back_url = $tool_home;
        
        $title = trim(U::get($_POST, 'title'));
        $text = trim(U::get($_POST, 'text'));
        $url = trim(U::get($_POST, 'url'));
        
        if (empty($title)) {
            $_SESSION['error'] = 'Title is required';
            return new RedirectResponse($send_url);
        }
        
        // Normalize empty values to null
        if (empty($text)) {
            $text = null;
        }
        if (empty($url)) {
            $url = null;
        }
        
        // Create notification using NotificationsService
        try {
            $notification = NotificationsService::create($user_id, $title, $text, $url);
            
            if ($notification) {
                $_SESSION['success'] = 'Notification sent successfully!';
                return new RedirectResponse($back_url);
            } else {
                $_SESSION['error'] = 'Error sending notification. Please try again.';
                return new RedirectResponse($send_url);
            }
        } catch (\InvalidArgumentException $e) {
            $_SESSION['error'] = 'Invalid notification data: ' . $e->getMessage();
            return new RedirectResponse($send_url);
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error sending notification. Please try again.';
            return new RedirectResponse($send_url);
        }
    }

    /**
     * Send notification to student form - instructor only
     */
    public function sendToStudent(Request $request) {
        global $OUTPUT, $PDOX, $CFG;

        $this->requireInstructor('/notifications');
        
        LTIX::getConnection();
        
        $user_id = $_SESSION['id'];
        $context_id = $_SESSION['context_id'];

        // Record analytics
        $this->lmsRecordLaunchAnalytics(self::ROUTE . '/send-to-student', self::NAME . ' - Send to Student');

        // Get all students in the context (role < ROLE_INSTRUCTOR)
        $p = $CFG->dbprefix;
        $sql = "SELECT M.user_id, U.displayname, U.email,
                    COALESCE(M.role_override, M.role, 0) AS role
                FROM {$p}lti_membership AS M
                JOIN {$p}lti_user AS U ON M.user_id = U.user_id
                WHERE M.context_id = :CID
                  AND COALESCE(M.role_override, M.role, 0) < :ROLE_INSTRUCTOR
                ORDER BY U.displayname ASC";
        
        $students = $PDOX->allRowsDie($sql, array(
            ':CID' => $context_id,
            ':ROLE_INSTRUCTOR' => LTIX::ROLE_INSTRUCTOR
        ));

        $tool_home = $this->toolHome(self::ROUTE);
        $back_url = $tool_home;
        $send_url = $tool_home . '/send-to-student';

        $OUTPUT->header();
        $OUTPUT->bodyStart();
        $OUTPUT->topNav();
        $OUTPUT->flashMessages();
        ?>
        <div class="container">
            <h1>Send Notification to Student
                <span class="pull-right">
                    <a href="<?= htmlspecialchars($back_url) ?>" class="btn btn-default">Back to Notifications</a>
                </span>
            </h1>
            
            <?php if (empty($students)): ?>
                <div class="alert alert-warning">
                    <p>No students found in this course.</p>
                </div>
            <?php else: ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Send Notification to a Student</h3>
                    </div>
                    <div class="panel-body">
                        <p class="text-muted">Select a student and compose a notification to send to them.</p>
                        
                        <form method="POST" action="<?= htmlspecialchars($send_url) ?>">
                            <div class="form-group">
                                <label for="student_id">Student *</label>
                                <select class="form-control" id="student_id" name="student_id" required>
                                    <option value="">-- Select a student --</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?= htmlspecialchars($student['user_id']) ?>" 
                                                <?= U::get($_POST, 'student_id') == $student['user_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($student['displayname']) ?>
                                            <?php if (!empty($student['email'])): ?>
                                                (<?= htmlspecialchars($student['email']) ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="title">Title *</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?= htmlspecialchars(U::get($_POST, 'title', '')) ?>" 
                                       required
                                       placeholder="Enter notification title">
                            </div>
                            
                            <div class="form-group">
                                <label for="text">Text (optional)</label>
                                <textarea class="form-control" id="text" name="text" rows="5"
                                          placeholder="Enter notification message (optional)"><?= htmlspecialchars(U::get($_POST, 'text', '')) ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="url">URL (optional)</label>
                                <input type="url" class="form-control" id="url" name="url" 
                                       value="<?= htmlspecialchars(U::get($_POST, 'url', '')) ?>" 
                                       placeholder="https://example.com">
                                <small class="help-block">Optional link to include with the notification</small>
                            </div>
                            
                            <button type="submit" class="btn btn-success">Send Notification</button>
                            <a href="<?= htmlspecialchars($back_url) ?>" class="btn btn-default">Cancel</a>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        $OUTPUT->footer();
    }

    /**
     * Process send notification to student form submission - instructor only
     */
    public function sendToStudentPost(Request $request) {
        global $CFG, $PDOX;

        $this->requireInstructor('/notifications');
        
        LTIX::getConnection();
        
        $user_id = $_SESSION['id'];
        $context_id = $_SESSION['context_id'];
        
        $tool_home = $this->toolHome(self::ROUTE);
        $send_url = $tool_home . '/send-to-student';
        $back_url = $tool_home;
        
        $student_id = U::get($_POST, 'student_id');
        $title = trim(U::get($_POST, 'title'));
        $text = trim(U::get($_POST, 'text'));
        $url = trim(U::get($_POST, 'url'));
        
        if (empty($student_id) || !is_numeric($student_id)) {
            $_SESSION['error'] = 'Please select a student';
            return new RedirectResponse($send_url);
        }
        
        if (empty($title)) {
            $_SESSION['error'] = 'Title is required';
            return new RedirectResponse($send_url);
        }
        
        // Verify the student is actually in this context and is a student (not instructor)
        $p = $CFG->dbprefix;
        $student_check = $PDOX->rowDie(
            "SELECT M.user_id, U.displayname,
                    COALESCE(M.role_override, M.role, 0) AS role
             FROM {$p}lti_membership AS M
             JOIN {$p}lti_user AS U ON M.user_id = U.user_id
             WHERE M.context_id = :CID 
               AND M.user_id = :SID
               AND COALESCE(M.role_override, M.role, 0) < :ROLE_INSTRUCTOR",
            array(
                ':CID' => $context_id,
                ':SID' => $student_id,
                ':ROLE_INSTRUCTOR' => LTIX::ROLE_INSTRUCTOR
            )
        );
        
        if (!$student_check) {
            $_SESSION['error'] = 'Invalid student selected or student not found in this course';
            return new RedirectResponse($send_url);
        }
        
        // Normalize empty values to null
        if (empty($text)) {
            $text = null;
        }
        if (empty($url)) {
            $url = null;
        }
        
        // Create notification using NotificationsService
        try {
            $notification = NotificationsService::create($student_id, $title, $text, $url);
            
            if ($notification) {
                $_SESSION['success'] = 'Notification sent successfully to ' . htmlspecialchars($student_check['displayname']) . '!';
                return new RedirectResponse($back_url);
            } else {
                $_SESSION['error'] = 'Error sending notification. Please try again.';
                return new RedirectResponse($send_url);
            }
        } catch (\InvalidArgumentException $e) {
            $_SESSION['error'] = 'Invalid notification data: ' . $e->getMessage();
            return new RedirectResponse($send_url);
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error sending notification. Please try again.';
            return new RedirectResponse($send_url);
        }
    }

    /**
     * Get notifications as JSON (for bell icon component)
     */
    public function json(Request $request) {
        global $CFG;
        
        $this->requireAuth();
        
        LTIX::getConnection();
        
        $user_id = $_SESSION['id'];
        
        $notifications = NotificationsService::getForUser($user_id, false, 50); // Limit to 50 most recent
        $unread_count = NotificationsService::getUnreadCount($user_id);
        
        $result = array(
            'status' => 'success',
            'notifications' => array(),
            'unread_count' => $unread_count
        );
        
        foreach ($notifications as $notification) {
            // Parse json field if present
            $json_data = null;
            if (!empty($notification['json'])) {
                $decoded = json_decode($notification['json'], true);
                $json_data = (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
            }
            
            $result['notifications'][] = array(
                'notification_id' => intval($notification['notification_id']),
                'title' => $notification['title'],
                'text' => $notification['text'],
                'url' => $notification['url'],
                'json' => $json_data,
                'read_at' => $notification['read_at'],
                'created_at' => $notification['created_at'],
                'updated_at' => $notification['updated_at'],
                'is_read' => !empty($notification['read_at'])
            );
        }
        
        return new JsonResponse($result);
    }

    /**
     * Mark a notification as read
     */
    public function markRead(Request $request) {
        $this->requireAuth();
        
        LTIX::getConnection();
        
        $user_id = $_SESSION['id'];
        $notification_id = U::get($_POST, 'notification_id');
        
        if (!$notification_id || !is_numeric($notification_id)) {
            return new JsonResponse(['status' => 'error', 'detail' => 'Invalid notification_id'], 400);
        }
        
        $success = NotificationsService::markAsRead(intval($notification_id), $user_id);
        
        if ($success) {
            return new JsonResponse(['status' => 'success']);
        } else {
            return new JsonResponse(['status' => 'error', 'detail' => 'Failed to mark notification as read'], 500);
        }
    }

    /**
     * Mark all notifications as read for the current user
     */
    public function markAllRead(Request $request) {
        $this->requireAuth();
        
        LTIX::getConnection();
        
        $user_id = $_SESSION['id'];
        
        $success = NotificationsService::markAllAsRead($user_id);
        
        if ($success) {
            return new JsonResponse(['status' => 'success']);
        } else {
            return new JsonResponse(['status' => 'error', 'detail' => 'Failed to mark all notifications as read'], 500);
        }
    }

    /**
     * Get VAPID public key for client-side subscription
     */
    public function getVapidPublicKey(Request $request) {
        global $CFG;
        
        // Check if service worker is enabled
        $service_worker_enabled = isset($CFG->service_worker) && $CFG->service_worker;
        if (!$service_worker_enabled) {
            return new JsonResponse(['error' => 'Service worker is not enabled'], 403);
        }
        
        $public_key = $this->getVapidPublicKeyValue();
        return new JsonResponse(['publicKey' => $public_key]);
    }

    /**
     * Get VAPID public key value (helper method)
     */
    private function getVapidPublicKeyValue() {
        global $CFG;

        // Check if VAPID keys are configured
        if (empty($CFG->vapid_public_key) || empty($CFG->vapid_private_key)) {
            // Generate keys if not set (you should set these in config.php)
            // For now, return a placeholder - you'll need to generate real VAPID keys
            return base64_encode('VAPID_PUBLIC_KEY_NOT_SET');
        }

        return $CFG->vapid_public_key;
    }

    /**
     * Detect browser from endpoint
     */
    private function detectBrowserFromEndpoint($endpoint) {
        if (strpos($endpoint, 'fcm.googleapis.com') !== false) {
            return ['name' => 'Chrome', 'type' => 'fcm'];
        } elseif (strpos($endpoint, 'updates.push.services.mozilla.com') !== false) {
            return ['name' => 'Firefox', 'type' => 'mozilla'];
        } elseif (strpos($endpoint, 'wns2-') !== false || strpos($endpoint, 'notify.windows.com') !== false) {
            return ['name' => 'Edge', 'type' => 'windows'];
        } else {
            return ['name' => 'Unknown', 'type' => 'unknown'];
        }
    }
    
    /**
     * Get user's push subscriptions (can have multiple - one per browser)
     */
    private function getUserSubscriptions($user_id, $browser_name = null) {
        global $PDOX, $CFG;

        $p = $CFG->dbprefix;
        $sql = "SELECT subscription_id, user_id, endpoint, p256dh, auth, browser_name, browser_endpoint_type, user_agent, created_at, updated_at
                FROM {$p}push_subscriptions
                WHERE user_id = :user_id";
        
        $params = [':user_id' => $user_id];
        
        if ($browser_name) {
            $sql .= " AND browser_name = :browser_name";
            $params[':browser_name'] = $browser_name;
        }
        
        $sql .= " ORDER BY updated_at DESC";
        
        $rows = $PDOX->allRowsDie($sql, $params);
        return $rows;
    }
    
    /**
     * Get user's push subscription (single - for backward compatibility)
     * Returns the most recent subscription
     */
    private function getUserSubscription($user_id) {
        $subscriptions = $this->getUserSubscriptions($user_id);
        return !empty($subscriptions) ? $subscriptions[0] : null;
    }

    /**
     * Send a push notification
     * 
     * NOTE: This is a simplified implementation. For production use, install
     * the minishlink/web-push library via Composer for proper VAPID signature generation.
     * 
     * To install: composer require minishlink/web-push
     */
    private function sendPushNotification($endpoint, $p256dh, $auth, $title, $body, $url = '/') {
        global $CFG;

        if (empty($CFG->vapid_public_key) || empty($CFG->vapid_private_key)) {
            return ['success' => false, 'error' => 'VAPID keys not configured'];
        }

        // Check if web-push library is available
        if (class_exists('\Minishlink\WebPush\WebPush')) {
            return $this->sendPushNotificationWithLibrary($endpoint, $p256dh, $auth, $title, $body, $url);
        }

        // Fallback: Simplified implementation (may not work correctly)
        // Prepare notification payload
        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'icon' => '/favicon.ico',
            'badge' => '/favicon.ico',
            'tag' => 'notification',
            'data' => ['url' => $url]
        ]);

        // Encrypt payload (simplified - proper implementation requires encryption)
        // For now, send unencrypted (some browsers may reject this)
        $encrypted_payload = $payload;

        // VAPID header generation (simplified - requires proper JWT signing)
        $vapid_subject = $CFG->vapid_subject ?? ('mailto:' . ($CFG->owneremail ?? 'admin@example.com'));
        $vapid_claims = [
            'sub' => $vapid_subject,
            'exp' => time() + 86400
        ];

        // Note: This simplified approach will likely fail with HTTP 400
        // because it doesn't properly sign the JWT with ECDSA P-256
        $headers = [
            'Content-Type: application/octet-stream',
            'Content-Encoding: aesgcm',
            'TTL: 86400'
        ];

        // Try to generate a basic Authorization header
        // This is NOT a proper VAPID signature - it will fail
        $auth_header = 'vapid t=' . base64_encode(json_encode($vapid_claims)) . ', k=' . $CFG->vapid_public_key;
        $headers[] = 'Authorization: ' . $auth_header;

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encrypted_payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($http_code >= 200 && $http_code < 300) {
            return ['success' => true];
        } else {
            // Log detailed error for debugging
            error_log("Push notification failed: HTTP $http_code - " . substr($response, 0, 200));
            error_log("Endpoint: " . substr($endpoint, 0, 100));
            
            $error_message = "HTTP $http_code";
            if ($response) {
                $error_message .= ": " . substr($response, 0, 200);
            } elseif ($error) {
                $error_message .= ": " . $error;
            }
            
            // Add helpful message about using web-push library
            $error_message .= " (Note: Install 'minishlink/web-push' library for proper VAPID signing)";
            
            return ['success' => false, 'error' => $error_message];
        }
    }

    /**
     * Send push notification using web-push library (if available)
     */
    private function sendPushNotificationWithLibrary($endpoint, $p256dh, $auth, $title, $body, $url = '/') {
        global $CFG;

        try {
            // Extract hostname from endpoint for DNS resolution
            $parsedUrl = parse_url($endpoint);
            $hostname = $parsedUrl['host'] ?? '';
            
            // Try to resolve IP using dns_get_record (more reliable on macOS)
            $ip = null;
            $records = @dns_get_record($hostname, DNS_A);
            if (!empty($records) && isset($records[0]['ip'])) {
                $ip = $records[0]['ip'];
            } else {
                // Fallback to gethostbyname
                $ip = @gethostbyname($hostname);
                if ($ip === $hostname) {
                    $ip = null; // Resolution failed
                }
            }
            
            $vapid = [
                'VAPID' => [
                    'subject' => $CFG->vapid_subject ?? ('mailto:' . ($CFG->owneremail ?? 'admin@example.com')),
                    'publicKey' => $CFG->vapid_public_key,
                    'privateKey' => $CFG->vapid_private_key,
                ],
            ];
            
            $curlOptions = [
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, // Force IPv4 resolution
            ];
            
            // If we successfully resolved the IP, use CURLOPT_RESOLVE to force curl to use it
            if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
                $port = $parsedUrl['port'] ?? (($parsedUrl['scheme'] ?? '') === 'https' ? 443 : 80);
                $curlOptions[CURLOPT_RESOLVE] = ["{$hostname}:{$port}:{$ip}"];
            }
            
            $clientOptions = [
                'force_ip_resolve' => 'v4', // Guzzle's built-in option
                'curl' => $curlOptions,
            ];

            // Pass clientOptions as 4th parameter (after auth, defaultOptions, timeout)
            // defaultOptions must be an array, not null
            $webPush = new \Minishlink\WebPush\WebPush($vapid, [], null, $clientOptions);
            
            $subscription = \Minishlink\WebPush\Subscription::create([
                'endpoint' => $endpoint,
                'keys' => [
                    'p256dh' => $p256dh,
                    'auth' => $auth,
                ],
            ]);

            $payload = json_encode([
                'title' => $title,
                'body' => $body,
                'icon' => '/favicon.ico',
                'badge' => '/favicon.ico',
                'tag' => 'notification',
                'data' => ['url' => $url],
                'badge_count' => 1  // Add badge count for dock indicator
            ]);

            $result = $webPush->sendOneNotification($subscription, $payload);

            if ($result->isSuccess()) {
                error_log("Push notification sent successfully");
                return ['success' => true];
            } else {
                $reason = $result->getReason();
                error_log("Push notification failed: " . $reason);
                
                // Get status code from response if available
                $statusCode = null;
                $response = $result->getResponse();
                if ($response && method_exists($response, 'getStatusCode')) {
                    $statusCode = $response->getStatusCode();
                }
                
                error_log("Response details: " . json_encode([
                    'statusCode' => $statusCode,
                    'reason' => $reason,
                    'expired' => $result->isSubscriptionExpired(),
                ]));
                return ['success' => false, 'error' => $reason];
            }
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            error_log("Push notification exception: " . $errorMsg);
            
            // Provide helpful error messages for common issues
            if (strpos($errorMsg, 'Could not resolve host') !== false || strpos($errorMsg, 'cURL error 6') !== false) {
                return [
                    'success' => false, 
                    'error' => "DNS resolution failed. The server cannot resolve the push service hostname. This is not a rate limit - check DNS configuration and network connectivity on the server."
                ];
            }
            
            return ['success' => false, 'error' => $errorMsg];
        }
    }
}
