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

    public function testPurifyKeepsTargetBlankOnLinks()
    {
        $html = CatalogRepository::purify(
            '<p><a href="https://example.com/docs" target="_blank" rel="noopener noreferrer">Docs</a></p>'
        );
        $this->assertStringContainsString('href="https://example.com/docs"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('noopener', $html);
        $this->assertStringContainsString('noreferrer', $html);
        $this->assertStringNotContainsString('javascript:', CatalogRepository::purify(
            '<a href="javascript:alert(1)" target="_blank">x</a>'
        ));
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

    public function testNormalizeAcceptsHomeContext()
    {
        $out = CatalogRepository::normalizeInput(array(
            'title' => 'Home course',
            'kind' => 'course',
            'context_id' => '9',
        ));
        $this->assertTrue($out['ok']);
        $this->assertSame(9, $out['data']['context_id']);
        $this->assertNull($out['data']['external_url']);
    }

    public function testNormalizeCourseOk()
    {
        $out = CatalogRepository::normalizeInput(array(
            'title' => 'Python',
            'kind' => 'course',
            'context_id' => '12',
            'new_window' => '1',
        ));
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

    public function testPlainTextTruncatesUtf8ByCharacter()
    {
        $this->assertSame('ééé', CatalogRepository::plainText(str_repeat('é', 8), 3));
    }

    public function testNormalizeTruncatesUtf8TitleByCharacter()
    {
        $title = str_repeat('é', CatalogRepository::TITLE_MAX + 8);
        $out = CatalogRepository::normalizeInput(array(
            'title' => $title,
            'kind' => 'link',
            'external_url' => 'https://example.com',
        ));
        $this->assertTrue($out['ok']);
        $this->assertSame(CatalogRepository::TITLE_MAX, mb_strlen($out['data']['title'], 'UTF-8'));
        $this->assertSame(str_repeat('é', CatalogRepository::TITLE_MAX), $out['data']['title']);
    }

    public function testHasRichDescription()
    {
        $this->assertFalse(CatalogRepository::hasRichDescription(null));
        $this->assertFalse(CatalogRepository::hasRichDescription(''));
        $this->assertFalse(CatalogRepository::hasRichDescription('<p>&nbsp;</p>'));
        $this->assertTrue(CatalogRepository::hasRichDescription('<p>Hello</p>'));
        $this->assertTrue(CatalogRepository::hasRichDescription('<img src="x.jpg" alt="">'));
    }
}
