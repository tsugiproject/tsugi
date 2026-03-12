<?php

namespace Tsugi\UI\BadgeShare;

/**
 * Generates Open Graph and Twitter Card meta tags for badge assertion pages.
 * Used by Facebook, Twitter, LinkedIn, Bluesky when they crawl the shared URL.
 * Single place for all meta tags - no platform-specific duplication.
 */
class BadgeShareHead {

    /**
     * Render meta tags for social share previews (OG + Twitter Card).
     *
     * @param string      $assertUrl   Canonical assertion page URL
     * @param string      $badgeTitle  Badge name (e.g. "Origins of Computing")
     * @param string      $courseTitle Course/context title or issuer name
     * @param string      $issuerName  Issuer organization name for fallback
     * @param string      $imageUrl    Badge image URL (relative or absolute)
     * @param string|null $wwwroot     Base URL to make image absolute if needed
     * @return string HTML fragment of meta tags (newline-separated)
     */
    public static function render(
        string $assertUrl,
        string $badgeTitle,
        string $courseTitle,
        string $issuerName,
        string $imageUrl,
        ?string $wwwroot = null
    ): string {
        // Shared data used by all platforms (Facebook, Twitter, LinkedIn, Bluesky)
        $descSource = $courseTitle !== '' ? $courseTitle : $issuerName;
        $ogTitle = $badgeTitle . ' - Badge from ' . $issuerName;
        $ogDesc = sprintf(__('I earned the "%s" badge from %s!'), $badgeTitle, $descSource);

        // Image must be absolute and public for Twitter, LinkedIn, Bluesky crawlers
        $ogImage = self::ensureAbsoluteUrl($imageUrl, $wwwroot ?? '');

        $ogTitle = htmlspecialchars($ogTitle, ENT_QUOTES, 'UTF-8');
        $ogDesc = htmlspecialchars($ogDesc, ENT_QUOTES, 'UTF-8');
        $ogImage = htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8');
        $ogUrl = htmlspecialchars($assertUrl, ENT_QUOTES, 'UTF-8');

        // Open Graph (og:*) - Facebook, LinkedIn, Bluesky; Twitter falls back to these if twitter:* missing
        $out = '<meta property="og:type" content="website">' . "\n";          // Facebook, LinkedIn, Bluesky
        $out .= '<meta property="og:title" content="' . $ogTitle . '">' . "\n";       // Facebook, LinkedIn, Bluesky
        $out .= '<meta property="og:description" content="' . $ogDesc . '">' . "\n";  // Facebook, LinkedIn, Bluesky (preview text)
        $out .= '<meta property="og:image" content="' . $ogImage . '">' . "\n";       // Facebook, LinkedIn, Bluesky (preview image)
        $out .= '<meta property="og:url" content="' . $ogUrl . '">' . "\n";            // Facebook, LinkedIn, Bluesky

        // Twitter Card (twitter:*) - Twitter/X only; prefers these over og: when present
        $out .= '<meta name="twitter:card" content="summary">' . "\n";         // Twitter
        $out .= '<meta name="twitter:title" content="' . $ogTitle . '">' . "\n";       // Twitter
        $out .= '<meta name="twitter:description" content="' . $ogDesc . '">' . "\n";  // Twitter
        $out .= '<meta name="twitter:image" content="' . $ogImage . '">' . "\n";       // Twitter (preview image)
        $out .= '<meta name="twitter:image:alt" content="' . $ogTitle . '">';           // Twitter (accessibility)

        return $out;
    }

    /**
     * Ensure image URL is absolute (required by Twitter, LinkedIn, Bluesky crawlers).
     *
     * @param string $url     Image URL (possibly relative)
     * @param string $wwwroot Base URL (e.g. https://example.com)
     * @return string Absolute URL
     */
    private static function ensureAbsoluteUrl(string $url, string $wwwroot): string {
        if ( strpos($url, 'http') === 0 ) {
            return $url;
        }
        $base = rtrim($wwwroot, '/');
        return $base . ( strpos($url, '/') === 0 ? '' : '/' ) . $url;
    }
}
