<?php

require_once "src/UI/BadgeShare/BadgeSharePlatform.php";
require_once "src/UI/BadgeShare/TwitterShare.php";
require_once "src/UI/BadgeShare/FacebookShare.php";
require_once "src/UI/BadgeShare/BlueskyShare.php";
require_once "src/UI/BadgeShare/LinkedInShare.php";
require_once "src/UI/BadgeShare/BadgeShareRegistry.php";

if (!function_exists('__')) {
    function __($str) { return $str; }
}

use \Tsugi\UI\BadgeShare\TwitterShare;
use \Tsugi\UI\BadgeShare\FacebookShare;
use \Tsugi\UI\BadgeShare\BlueskyShare;
use \Tsugi\UI\BadgeShare\LinkedInShare;
use \Tsugi\UI\BadgeShare\BadgeShareRegistry;

class BadgeShareTest extends \PHPUnit\Framework\TestCase
{
    private string $testUrl = 'https://example.com/assertions/m1234567890abcdef1234567890abcdef.html';
    private string $testText = 'I earned the "Origins of Computing" badge from CA4E!';

    public function testTwitterShareGetShareUrl(): void
    {
        $platform = new TwitterShare();
        $url = $platform->getShareUrl($this->testUrl, $this->testText);

        $this->assertStringStartsWith('https://twitter.com/intent/tweet?', $url);
        $this->assertStringContainsString('text=', $url);
        $this->assertStringContainsString('url=', $url);
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        $this->assertEquals($this->testText, $params['text']);
        $this->assertEquals($this->testUrl, $params['url']);
    }

    public function testTwitterShareGetName(): void
    {
        $platform = new TwitterShare();
        $this->assertEquals('Twitter', $platform->getName());
    }

    public function testTwitterShareGetIconClass(): void
    {
        $platform = new TwitterShare();
        $this->assertEquals('fa-brands fa-x-twitter', $platform->getIconClass());
    }

    public function testFacebookShareGetShareUrl(): void
    {
        $platform = new FacebookShare();
        $url = $platform->getShareUrl($this->testUrl, $this->testText);

        $this->assertStringStartsWith('https://www.facebook.com/sharer/sharer.php?', $url);
        $this->assertStringContainsString('u=', $url);
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        $this->assertEquals($this->testUrl, $params['u']);
    }

    public function testFacebookShareGetName(): void
    {
        $platform = new FacebookShare();
        $this->assertEquals('Facebook', $platform->getName());
    }

    public function testFacebookShareGetIconClass(): void
    {
        $platform = new FacebookShare();
        $this->assertEquals('fa-brands fa-facebook', $platform->getIconClass());
    }

    public function testBlueskyShareGetShareUrl(): void
    {
        $platform = new BlueskyShare();
        $url = $platform->getShareUrl($this->testUrl, $this->testText);

        $this->assertStringStartsWith('https://bsky.app/intent/compose?', $url);
        $this->assertStringContainsString('text=', $url);
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        $expectedText = $this->testText . ' ' . $this->testUrl;
        $this->assertEquals($expectedText, $params['text']);
    }

    public function testBlueskyShareGetName(): void
    {
        $platform = new BlueskyShare();
        $this->assertEquals('Bluesky', $platform->getName());
    }

    public function testBlueskyShareGetIconClass(): void
    {
        $platform = new BlueskyShare();
        $this->assertEquals('fa-brands fa-bluesky', $platform->getIconClass());
    }

    public function testLinkedInShareGetShareUrl(): void
    {
        $platform = new LinkedInShare();
        $url = $platform->getShareUrl($this->testUrl, $this->testText);

        $this->assertStringStartsWith('https://www.linkedin.com/shareArticle?', $url);
        $this->assertStringContainsString('mini=true', $url);
        $this->assertStringContainsString('url=', $url);
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        $this->assertEquals('true', $params['mini']);
        $this->assertEquals($this->testUrl, $params['url']);
    }

    public function testLinkedInShareGetName(): void
    {
        $platform = new LinkedInShare();
        $this->assertEquals('LinkedIn', $platform->getName());
    }

    public function testLinkedInShareGetIconClass(): void
    {
        $platform = new LinkedInShare();
        $this->assertEquals('fa-brands fa-linkedin', $platform->getIconClass());
    }

    public function testBadgeShareRegistryGetPlatforms(): void
    {
        $platforms = BadgeShareRegistry::getPlatforms();

        $this->assertCount(4, $platforms);
        $names = array_map(fn($p) => $p->getName(), $platforms);
        $this->assertContains('Twitter', $names);
        $this->assertContains('Facebook', $names);
        $this->assertContains('Bluesky', $names);
        $this->assertContains('LinkedIn', $names);
    }

    public function testBadgeShareRegistryRenderShareLinksStructure(): void
    {
        $html = BadgeShareRegistry::renderShareLinks(
            $this->testUrl,
            'Origins of Computing',
            'CA4E'
        );

        $this->assertStringContainsString('<ul class="badge-share-links"', $html);
        $this->assertStringContainsString('role="list"', $html);
        $this->assertStringContainsString('</ul>', $html);
        $this->assertStringContainsString('class="badge-share-link"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
    }

    public function testBadgeShareRegistryRenderShareLinksContainsAllPlatforms(): void
    {
        $html = BadgeShareRegistry::renderShareLinks($this->testUrl, 'Test Badge', 'CA4E');

        $this->assertStringContainsString('Post to Twitter', $html);
        $this->assertStringContainsString('Post to Facebook', $html);
        $this->assertStringContainsString('Post to Bluesky', $html);
        $this->assertStringContainsString('Post to LinkedIn', $html);
    }

    public function testBadgeShareRegistryRenderShareLinksUrlEncoding(): void
    {
        $html = BadgeShareRegistry::renderShareLinks($this->testUrl, 'Test Badge', 'CA4E');

        // URL is encoded in href (e.g. https%3A%2F%2F...) - verify it appears safely
        $this->assertStringContainsString('example.com', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function testBadgeShareRegistryRenderShareLinksWithoutCourseTitle(): void
    {
        $html = BadgeShareRegistry::renderShareLinks($this->testUrl, 'Test Badge', '');

        $this->assertStringContainsString('<ul class="badge-share-links"', $html);
        $this->assertMatchesRegularExpression('/<li>/', $html);
    }

    public function testAllPlatformsImplementInterface(): void
    {
        $platforms = BadgeShareRegistry::getPlatforms();

        foreach ($platforms as $platform) {
            $this->assertInstanceOf(\Tsugi\UI\BadgeShare\BadgeSharePlatform::class, $platform);
            $this->assertIsString($platform->getName());
            $this->assertIsString($platform->getIconClass());
            $shareUrl = $platform->getShareUrl('https://example.com', 'test');
            $this->assertIsString($shareUrl);
            $this->assertStringStartsWith('http', $shareUrl);
        }
    }

    public function testShareUrlsAreValid(): void
    {
        $platforms = BadgeShareRegistry::getPlatforms();
        $url = 'https://example.com/badge.html';
        $text = 'I earned a badge!';

        foreach ($platforms as $platform) {
            $shareUrl = $platform->getShareUrl($url, $text);
            $parsed = parse_url($shareUrl);
            $this->assertArrayHasKey('scheme', $parsed, $platform->getName() . ' should produce valid URL');
            $this->assertArrayHasKey('host', $parsed, $platform->getName() . ' should produce valid URL');
            $this->assertContains($parsed['scheme'], ['http', 'https'], $platform->getName() . ' should use http or https');
        }
    }
}
