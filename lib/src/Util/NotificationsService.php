<?php

namespace Tsugi\Util;

use Tsugi\Core\LTIX;
use Tsugi\Core\Cache;

/**
 * NotificationsService - Utility class for managing user notifications
 * 
 * Provides methods to create, read, and manage notifications for users.
 */
class NotificationsService {

    /**
     * Create a notification for a user
     * 
     * @param int $user_id The user ID to notify
     * @param string $title Notification title
     * @param string|null $text Notification text/content (optional)
     * @param string|null $url Optional URL to link to
     * @param array|object|null $json Optional JSON data to store
     * @param string|null $dedupe_key Optional deduplication key - if provided, notifications with the same key for the same user will be de-duplicated within the time window
     * @return array|false Returns notification data on success, false on failure
     */
    public static function create($user_id, $title, $text = null, $url = null, $json = null, $dedupe_key = null) {
        global $PDOX, $CFG;
        
        if (empty($user_id) || empty($title)) {
            throw new \InvalidArgumentException("NotificationsService::create - Missing required parameters: user_id and title are required");
        }
        
        // Normalize text - convert empty string to null
        if ($text !== null && trim($text) === '') {
            $text = null;
        }
        
        LTIX::getConnection();
        
        $p = $CFG->dbprefix;
        
        // Check for existing unread notification with same dedupe_key within time window
        if (!empty($dedupe_key)) {
            $dedupe_window = isset($CFG->notification_dedupe_window) ? intval($CFG->notification_dedupe_window) : 900;
            
            if ($dedupe_window > 0) {
                // Look for existing unread notification with same dedupe_key within time window
                $find_sql = "SELECT notification_id 
                            FROM {$p}notification 
                            WHERE user_id = :user_id 
                              AND dedupe_key = :dedupe_key 
                              AND dedupe_key IS NOT NULL
                              AND read_at IS NULL
                              AND created_at >= DATE_SUB(NOW(), INTERVAL :window_seconds SECOND)
                            ORDER BY created_at DESC 
                            LIMIT 1";
                
                $existing = $PDOX->rowDie($find_sql, array(
                    ':user_id' => $user_id,
                    ':dedupe_key' => $dedupe_key,
                    ':window_seconds' => $dedupe_window
                ));
                
                if ($existing && !empty($existing['notification_id'])) {
                    // Update existing notification instead of creating new one
                    $notification_id = intval($existing['notification_id']);
                    
                    // Convert json array/object to string if provided
                    $json_value = null;
                    if ($json !== null) {
                        $json_value = is_string($json) ? $json : json_encode($json);
                    }
                    
                    $update_sql = "UPDATE {$p}notification 
                                  SET title = :title,
                                      text = :text,
                                      url = :url,
                                      json = :json,
                                      updated_at = NOW()
                                  WHERE notification_id = :notification_id";
                    
                    $update_values = array(
                        ':notification_id' => $notification_id,
                        ':title' => $title,
                        ':text' => $text,
                        ':url' => $url,
                        ':json' => $json_value
                    );
                    
                    $q = $PDOX->queryReturnError($update_sql, $update_values);
                    if (!$q->success) {
                        error_log("NotificationsService::create - Database error updating notification: " . $q->errorImplode);
                        return false;
                    }
                    
                    return self::getById($notification_id);
                }
            }
        }
        
        // No existing notification found (or dedupe disabled), create new one
        $sql = "INSERT INTO {$p}notification 
                (user_id, title, text, url, json, dedupe_key, created_at, updated_at)
                VALUES (:user_id, :title, :text, :url, :json, :dedupe_key, NOW(), NOW())";
        
        // Convert json array/object to string if provided
        $json_value = null;
        if ($json !== null) {
            $json_value = is_string($json) ? $json : json_encode($json);
        }
        
        $values = array(
            ':user_id' => $user_id,
            ':title' => $title,
            ':text' => $text,
            ':url' => $url,
            ':json' => $json_value,
            ':dedupe_key' => $dedupe_key
        );
        
        $q = $PDOX->queryReturnError($sql, $values);
        if (!$q->success) {
            error_log("NotificationsService::create - Database error: " . $q->errorImplode);
            return false;
        }
        
        $notification_id = $PDOX->lastInsertId();
        return self::getById($notification_id);
    }
    
    /**
     * Create notifications for multiple users
     * 
     * @param array $user_ids Array of user IDs to notify
     * @param string $title Notification title
     * @param string $text Notification text/content
     * @param string|null $url Optional URL to link to
     * @return int Number of notifications created
     */
    public static function createForUsers($user_ids, $title, $text, $url = null) {
        if (empty($user_ids) || !is_array($user_ids)) {
            return 0;
        }
        
        $count = 0;
        foreach ($user_ids as $user_id) {
            if (self::create($user_id, $title, $text, $url)) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Get a notification by ID
     * 
     * @param int $notification_id
     * @return array|false Notification data or false if not found
     */
    public static function getById($notification_id) {
        global $PDOX, $CFG;
        
        LTIX::getConnection();
        
        $p = $CFG->dbprefix;
        $sql = "SELECT notification_id, user_id, title, text, url, json, dedupe_key, read_at, created_at, updated_at
                FROM {$p}notification
                WHERE notification_id = :notification_id";
        
        $row = $PDOX->rowDie($sql, array(':notification_id' => $notification_id));
        return $row ?: false;
    }
    
    /**
     * Get all notifications for a user, ordered by most recent first
     * 
     * @param int $user_id The user ID
     * @param bool $unread_only If true, only return unread notifications
     * @param int $limit Maximum number of notifications to return (0 = no limit)
     * @return array Array of notification data
     */
    public static function getForUser($user_id, $unread_only = false, $limit = 0) {
        global $PDOX, $CFG;
        
        if (empty($user_id)) {
            return array();
        }
        
        // Opportunistic cleanup - check if old notifications should be deleted
        if (isset($CFG->notification_expiration_days) && $CFG->notification_expiration_days > 0) {
            self::cleanupOldNotifications($CFG->notification_expiration_days);
        }
        
        LTIX::getConnection();
        
        $p = $CFG->dbprefix;
        $sql = "SELECT notification_id, user_id, title, text, url, json, dedupe_key, read_at, created_at, updated_at
                FROM {$p}notification
                WHERE user_id = :user_id";
        
        if ($unread_only) {
            $sql .= " AND read_at IS NULL";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        if ($limit > 0) {
            $sql .= " LIMIT " . intval($limit);
        }
        
        $rows = $PDOX->allRowsDie($sql, array(':user_id' => $user_id));
        return $rows ?: array();
    }
    
    /**
     * Get unread notification count for a user
     * 
     * @param int $user_id The user ID
     * @return int Number of unread notifications
     */
    public static function getUnreadCount($user_id) {
        global $PDOX, $CFG;
        
        if (empty($user_id)) {
            return 0;
        }
        
        LTIX::getConnection();
        
        $p = $CFG->dbprefix;
        $sql = "SELECT COUNT(*) AS count
                FROM {$p}notification
                WHERE user_id = :user_id AND read_at IS NULL";
        
        $row = $PDOX->rowDie($sql, array(':user_id' => $user_id));
        return $row ? intval($row['count']) : 0;
    }
    
    /**
     * Mark a notification as read
     * 
     * @param int $notification_id The notification ID
     * @param int $user_id The user ID (for security - ensures user owns the notification)
     * @return bool True on success, false on failure
     */
    public static function markAsRead($notification_id, $user_id) {
        global $PDOX, $CFG;
        
        if (empty($notification_id) || empty($user_id)) {
            return false;
        }
        
        LTIX::getConnection();
        
        $p = $CFG->dbprefix;
        $sql = "UPDATE {$p}notification
                SET read_at = NOW(), updated_at = NOW()
                WHERE notification_id = :notification_id AND user_id = :user_id";
        
        $q = $PDOX->queryReturnError($sql, array(
            ':notification_id' => $notification_id,
            ':user_id' => $user_id
        ));
        
        return $q->success;
    }
    
    /**
     * Mark all notifications as read for a user
     * 
     * @param int $user_id The user ID
     * @return bool True on success, false on failure
     */
    public static function markAllAsRead($user_id) {
        global $PDOX, $CFG;
        
        if (empty($user_id)) {
            return false;
        }
        
        LTIX::getConnection();
        
        $p = $CFG->dbprefix;
        $sql = "UPDATE {$p}notification
                SET read_at = NOW(), updated_at = NOW()
                WHERE user_id = :user_id AND read_at IS NULL";
        
        $q = $PDOX->queryReturnError($sql, array(':user_id' => $user_id));
        
        return $q->success;
    }
    
    /**
     * Delete a notification
     * 
     * @param int $notification_id The notification ID
     * @param int $user_id The user ID (for security - ensures user owns the notification)
     * @return bool True on success, false on failure
     */
    public static function delete($notification_id, $user_id) {
        global $PDOX, $CFG;
        
        if (empty($notification_id) || empty($user_id)) {
            return false;
        }
        
        LTIX::getConnection();
        
        $p = $CFG->dbprefix;
        $sql = "DELETE FROM {$p}notification
                WHERE notification_id = :notification_id AND user_id = :user_id";
        
        $q = $PDOX->queryReturnError($sql, array(
            ':notification_id' => $notification_id,
            ':user_id' => $user_id
        ));
        
        return $q->success;
    }
    
    /**
     * Generate a dedupe key for a notification
     * 
     * @param string $source Source identifier (e.g., 'peer-grade', 'aipaper-comment')
     * @param int $user_id User ID
     * @param int|null $link_id Optional link/assignment ID
     * @param string|null $additional Optional additional identifier
     * @return string Dedupe key
     */
    public static function generateDedupeKey($source, $user_id, $link_id = null, $additional = null) {
        $parts = array($source, $user_id);
        if ($link_id !== null) {
            $parts[] = $link_id;
        }
        if ($additional !== null) {
            $parts[] = $additional;
        }
        return implode('-', $parts);
    }
    
    /**
     * Clean up old notifications (older than configured expiration days)
     * 
     * Uses opportunistic cleanup - only runs if cleanup hasn't run recently
     * to avoid performance impact. This method is called automatically when
     * notifications are fetched, but can also be called manually.
     * 
     * @param int $expiration_days Number of days after which to expire notifications (default: 30)
     * @param int $min_interval_seconds Minimum seconds between cleanup runs (default: 3600 = 1 hour)
     * @return array|false Returns array with cleanup stats on success, false if skipped or failed
     */
    public static function cleanupOldNotifications($expiration_days = 30, $min_interval_seconds = 3600) {
        global $PDOX, $CFG;
        
        if ($expiration_days <= 0) {
            // Expiration disabled
            return false;
        }
        
        LTIX::getConnection();
        
        // Check if cleanup was run recently using cache (avoid running too frequently)
        $last_cleanup_key = 'notification_cleanup';
        $cache_key = 'last_run';
        $last_cleanup = Cache::check($last_cleanup_key, $cache_key);
        
        if ($last_cleanup !== false) {
            $now = time();
            if (($now - $last_cleanup) < $min_interval_seconds) {
                // Cleanup ran recently, skip this time
                return false;
            }
        }
        
        $p = $CFG->dbprefix;
        
        // Count notifications that will be deleted (for logging)
        $count_sql = "SELECT COUNT(*) AS count 
                      FROM {$p}notification 
                      WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        $count_row = $PDOX->rowDie($count_sql, array(':days' => $expiration_days));
        $count_to_delete = $count_row ? intval($count_row['count']) : 0;
        
        if ($count_to_delete == 0) {
            // No notifications to delete, update timestamp and return
            Cache::set($last_cleanup_key, $cache_key, time(), 7200); // Cache for 2 hours
            return array('deleted' => 0, 'skipped' => true);
        }
        
        // Delete old notifications
        $delete_sql = "DELETE FROM {$p}notification 
                       WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        $q = $PDOX->queryReturnError($delete_sql, array(':days' => $expiration_days));
        
        if (!$q->success) {
            error_log("NotificationsService::cleanupOldNotifications - Database error: " . $q->errorImplode);
            return false;
        }
        
        // Update last cleanup timestamp in cache (expires in 2 hours)
        Cache::set($last_cleanup_key, $cache_key, time(), 7200);
        
        $deleted_count = $q->rowCount();
        error_log("NotificationsService::cleanupOldNotifications - Deleted $deleted_count notification(s) older than $expiration_days days");
        
        return array(
            'deleted' => $deleted_count,
            'skipped' => false,
            'expiration_days' => $expiration_days
        );
    }
}
