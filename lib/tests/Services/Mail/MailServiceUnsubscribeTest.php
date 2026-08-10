<?php

namespace Tsugi\Tests\Services\Mail;

use PHPUnit\Framework\TestCase;
use Tsugi\Services\Mail\MailService;

class MailServiceUnsubscribeTest extends TestCase
{
    public function testBulkHeadersIncludeListUnsubscribeAndOneClick(): void
    {
        $url = 'https://www.cc4e.com/tsugi/util/unsubscribe?id=42&token=abc';
        $headers = MailService::buildOutboundHeaders(MailService::TYPE_BULK, $url);
        $map = array();
        foreach ( $headers as $h ) {
            $map[$h['Name']] = $h['Value'];
        }
        $this->assertArrayHasKey('List-Unsubscribe', $map);
        $this->assertSame('<'.$url.'>', $map['List-Unsubscribe']);
        $this->assertArrayHasKey('List-Unsubscribe-Post', $map);
        $this->assertSame('List-Unsubscribe=One-Click', $map['List-Unsubscribe-Post']);
        $this->assertSame(MailService::TYPE_BULK, $map['X-Tsugi-Mail-Type']);
    }

    public function testTransactionalHeadersOmitListUnsubscribe(): void
    {
        $url = 'https://www.cc4e.com/tsugi/util/unsubscribe?id=42&token=abc';
        $headers = MailService::buildOutboundHeaders(MailService::TYPE_TRANSACTIONAL, $url);
        foreach ( $headers as $h ) {
            $this->assertStringStartsNotWith('List-Unsubscribe', $h['Name']);
        }
        $map = array();
        foreach ( $headers as $h ) {
            $map[$h['Name']] = $h['Value'];
        }
        $this->assertSame(MailService::TYPE_TRANSACTIONAL, $map['X-Tsugi-Mail-Type']);
    }

    public function testBulkFooterContainsUnsubscribeUrl(): void
    {
        $url = 'https://www.cc4e.com/tsugi/util/unsubscribe?id=42&token=abc';
        $body = MailService::appendBulkUnsubscribeFooter("Hello\n", MailService::TYPE_BULK, $url);
        $this->assertStringContainsString('To unsubscribe from these emails:', $body);
        $this->assertStringContainsString($url, $body);
    }

    public function testTransactionalFooterHasNoUnsubscribeUrl(): void
    {
        $url = 'https://www.cc4e.com/tsugi/util/unsubscribe?id=42&token=abc';
        $body = MailService::appendBulkUnsubscribeFooter("Hello\n", MailService::TYPE_TRANSACTIONAL, $url);
        $this->assertSame("Hello\n", $body);
    }

    public function testOneClickRequestDetection(): void
    {
        $this->assertTrue(MailService::isOneClickUnsubscribeRequest(
            array('List-Unsubscribe' => 'One-Click'),
            ''
        ));
        $this->assertTrue(MailService::isOneClickUnsubscribeRequest(
            array(),
            'List-Unsubscribe=One-Click'
        ));
        $this->assertFalse(MailService::isOneClickUnsubscribeRequest(
            array('id' => '1', 'token' => 'x'),
            'id=1&token=x'
        ));
    }

    public function testSuppressReasonBlocksBulkAlways(): void
    {
        $this->assertTrue(MailService::suppressReasonBlocksType('unsubscribe', MailService::TYPE_BULK));
        $this->assertTrue(MailService::suppressReasonBlocksType('bounce', MailService::TYPE_BULK));
        $this->assertTrue(MailService::suppressReasonBlocksType('complaint', MailService::TYPE_BULK));
        $this->assertFalse(MailService::suppressReasonBlocksType(null, MailService::TYPE_BULK));
    }

    public function testSuppressReasonBlocksTransactionalExceptUnsubscribe(): void
    {
        $this->assertFalse(MailService::suppressReasonBlocksType('unsubscribe', MailService::TYPE_TRANSACTIONAL));
        $this->assertTrue(MailService::suppressReasonBlocksType('bounce', MailService::TYPE_TRANSACTIONAL));
        $this->assertTrue(MailService::suppressReasonBlocksType('complaint', MailService::TYPE_TRANSACTIONAL));
        $this->assertFalse(MailService::suppressReasonBlocksType(null, MailService::TYPE_TRANSACTIONAL));
    }
}
