<?php

use Tsugi\Services\Admin\AdminService;

class AdminServiceDeleteConfirmTest extends \PHPUnit\Framework\TestCase
{
    public function testTypedSiteUrlIgnoresCaseAndTrailingSlash() {
        $expected = 'https://local.dj4e.com/tsugi';
        $this->assertTrue(AdminService::typedSiteUrlMatches('https://Local.dj4e.com/tsugi/', $expected));
        $this->assertTrue(AdminService::typedSiteUrlMatches('https://local.dj4e.com/tsugi', $expected));
    }

    public function testTypedSiteUrlRejectsTheHostAlone() {
        $this->assertFalse(AdminService::typedSiteUrlMatches('local.dj4e.com', 'https://local.dj4e.com/tsugi'));
        $this->assertFalse(AdminService::typedSiteUrlMatches('', 'https://local.dj4e.com/tsugi'));
        $this->assertFalse(AdminService::typedSiteUrlMatches('https://local.dj4e.com/tsugi', ''));
        $this->assertFalse(AdminService::typedSiteUrlMatches('https://other.example/tsugi', 'https://local.dj4e.com/tsugi'));
    }
}
