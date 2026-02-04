<?php

namespace Tsugi\Tests\Util;

use PHPUnit\Framework\TestCase;
use Tsugi\Util\NotificationsService;

/**
 * Unit tests for NotificationsService
 * 
 * These tests cover validation and input normalization logic that doesn't require a database.
 * Database-dependent functionality is tested in integration tests.
 * 
 * Run from lib folder with: vendor/bin/phpunit tests/Util/NotificationsServiceTest.php
 * Or run all tests: composer test
 */
class NotificationsServiceTest extends TestCase
{


    /**
     * Test creating a notification fails with missing user_id
     */
    public function testCreateNotificationFailsWithoutUserId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required parameters');
        NotificationsService::create(null, 'Test Title');
    }

    public function testCreateNotificationFailsWithZeroUserId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required parameters');
        NotificationsService::create(0, 'Test Title');
    }

    public function testCreateNotificationFailsWithEmptyStringUserId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required parameters');
        NotificationsService::create('', 'Test Title');
    }

    /**
     * Test creating a notification fails with missing title
     */
    public function testCreateNotificationFailsWithoutTitle(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required parameters');
        NotificationsService::create(1, '');
    }

    public function testCreateNotificationFailsWithNullTitle(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required parameters');
        NotificationsService::create(1, null);
    }


    /**
     * Test getting notifications for empty user_id returns empty array
     */
    public function testGetNotificationsForEmptyUserId(): void
    {
        $result = NotificationsService::getForUser(null);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
        
        $result = NotificationsService::getForUser(0);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
        
        $result = NotificationsService::getForUser('');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test getting unread count for empty user_id returns 0
     */
    public function testGetUnreadCountForEmptyUserId(): void
    {
        $result = NotificationsService::getUnreadCount(null);
        $this->assertEquals(0, $result);
        
        $result = NotificationsService::getUnreadCount(0);
        $this->assertEquals(0, $result);
        
        $result = NotificationsService::getUnreadCount('');
        $this->assertEquals(0, $result);
    }

    /**
     * Test marking notification as read fails with invalid parameters
     */
    public function testMarkNotificationAsReadInvalidParams(): void
    {
        $result = NotificationsService::markAsRead(null, 1);
        $this->assertFalse($result, 'Should fail when notification_id is null');
        
        $result = NotificationsService::markAsRead(1, null);
        $this->assertFalse($result, 'Should fail when user_id is null');
        
        $result = NotificationsService::markAsRead(0, 1);
        $this->assertFalse($result, 'Should fail when notification_id is 0');
        
        $result = NotificationsService::markAsRead(1, 0);
        $this->assertFalse($result, 'Should fail when user_id is 0');
    }

    /**
     * Test marking all notifications as read fails with invalid user_id
     */
    public function testMarkAllAsReadInvalidUserId(): void
    {
        $result = NotificationsService::markAllAsRead(null);
        $this->assertFalse($result, 'Should fail when user_id is null');
        
        $result = NotificationsService::markAllAsRead(0);
        $this->assertFalse($result, 'Should fail when user_id is 0');
        
        $result = NotificationsService::markAllAsRead('');
        $this->assertFalse($result, 'Should fail when user_id is empty string');
    }

    /**
     * Test deleting notification fails with invalid parameters
     */
    public function testDeleteNotificationInvalidParams(): void
    {
        $result = NotificationsService::delete(null, 1);
        $this->assertFalse($result, 'Should fail when notification_id is null');
        
        $result = NotificationsService::delete(1, null);
        $this->assertFalse($result, 'Should fail when user_id is null');
        
        $result = NotificationsService::delete(0, 1);
        $this->assertFalse($result, 'Should fail when notification_id is 0');
        
        $result = NotificationsService::delete(1, 0);
        $this->assertFalse($result, 'Should fail when user_id is 0');
    }


    /**
     * Test createForUsers with empty array returns 0
     */
    public function testCreateForUsersEmptyArray(): void
    {
        $result = NotificationsService::createForUsers(array(), 'Test', 'Text');
        $this->assertEquals(0, $result);
    }

    /**
     * Test createForUsers with invalid input returns 0
     */
    public function testCreateForUsersInvalidInput(): void
    {
        $result = NotificationsService::createForUsers(null, 'Test', 'Text');
        $this->assertEquals(0, $result);
    }

}
