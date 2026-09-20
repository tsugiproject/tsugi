<?php

require_once "src/Services/Catalog/CatalogRepository.php";

use \Tsugi\Services\Catalog\CatalogRepository;

class CatalogRepositoryTest extends \PHPUnit\Framework\TestCase
{
    public function testNormalizeRequiresTitle()
    {
        $out = CatalogRepository::normalizeInput(array('kind' => 'link', 'external_url' => 'https://example.com'));
        $this->assertFalse($out['ok']);
        $this->assertSame('Title is required.', $out['error']);
    }

    public function testNormalizeLinkNeedsHttpUrl()
    {
        $out = CatalogRepository::normalizeInput(array(
            'title' => 'Home',
            'kind' => 'link',
            'external_url' => 'javascript:alert(1)',
        ));
        $this->assertFalse($out['ok']);
    }

    public function testNormalizeLinkOk()
    {
        $out = CatalogRepository::normalizeInput(array(
            'title' => 'Site home',
            'kind' => 'link',
            'external_url' => 'https://example.com/app',
            'new_window' => '1',
            'short_description' => '<b>Hi</b> there',
            'description' => '<p>Hello</p><script>alert(1)</script>',
            'published' => '1',
            'sort_order' => '3',
        ));
        $this->assertTrue($out['ok']);
        $data = $out['data'];
        $this->assertNull($data['context_id']);
        $this->assertSame('https://example.com/app', $data['external_url']);
        $this->assertSame(1, $data['new_window']);
        $this->assertSame('Hi there', $data['short_description']);
        $this->assertSame(1, $data['published']);
        $this->assertSame(3, $data['sort_order']);
        $this->assertStringNotContainsString('<script', (string) $data['description']);
        $this->assertStringContainsString('Hello', (string) $data['description']);
    }

    public function testNormalizeLinkDefaultNewWindowOffWhenUnchecked()
    {
        $out = CatalogRepository::normalizeInput(array(
            'title' => 'Other',
            'kind' => 'link',
            'external_url' => 'https://example.com',
        ));
        $this->assertTrue($out['ok']);
        $this->assertSame(0, $out['data']['new_window']);
    }

    public function testNormalizeCourseRequiresContext()
    {
        $out = CatalogRepository::normalizeInput(array('title' => 'A', 'kind' => 'course'));
        $this->assertFalse($out['ok']);
    }

    public function testNormalizeRejectsHomeContext()
    {
        $out = CatalogRepository::normalizeInput(array(
            'title' => 'Home course',
            'kind' => 'course',
            'context_id' => '9',
        ), 9);
        $this->assertFalse($out['ok']);
        $this->assertStringContainsString('site home', $out['error']);
    }

    public function testNormalizeCourseOk()
    {
        $out = CatalogRepository::normalizeInput(array(
            'title' => 'Python',
            'kind' => 'course',
            'context_id' => '12',
            'new_window' => '1',
        ), 9);
        $this->assertTrue($out['ok']);
        $this->assertSame(12, $out['data']['context_id']);
        $this->assertNull($out['data']['external_url']);
        $this->assertSame(0, $out['data']['new_window']);
    }

    public function testValidHttpUrl()
    {
        $this->assertTrue(CatalogRepository::validHttpUrl('https://www.example.com/x'));
        $this->assertFalse(CatalogRepository::validHttpUrl('ftp://example.com'));
        $this->assertFalse(CatalogRepository::validHttpUrl('not a url'));
    }

    public function testPlainTextStripsTags()
    {
        $this->assertSame('Hello there', CatalogRepository::plainText("Hello\n<b>there</b>", 512));
    }
}
