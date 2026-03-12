<?php

namespace Tsugi\UI\BadgeShare;

/**
 * Registry of badge share platforms. Returns platform instances without a case statement.
 */
class BadgeShareRegistry {

    /** @var BadgeSharePlatform[] */
    private static $platforms;

    /**
     * Get all configured share platforms.
     *
     * @return BadgeSharePlatform[]
     */
    public static function getPlatforms(): array {
        if ( self::$platforms === null ) {
            self::$platforms = array(
                new TwitterShare(),
                new FacebookShare(),
                new BlueskyShare(),
                new LinkedInShare(),
            );
        }
        return self::$platforms;
    }

    /**
     * Render share links for a badge.
     *
     * @param string $assertUrl   Badge assertion URL
     * @param string $badgeTitle  Badge title for share text
     * @param string $courseTitle Optional course/service name
     * @return string HTML fragment of share links
     */
    public static function renderShareLinks(string $assertUrl, string $badgeTitle, string $courseTitle = ''): string {
        $text = sprintf(__('I earned the "%s" badge'), $badgeTitle);
        if ( $courseTitle !== '' ) {
            $text .= ' ' . sprintf(__('from %s'), $courseTitle);
        }
        $text .= '!';

        $out = '<ul class="badge-share-links" role="list">';
        foreach ( self::getPlatforms() as $platform ) {
            $shareUrl = $platform->getShareUrl($assertUrl, $text);
            $name = $platform->getName();
            $label = sprintf(__('Post to %s'), $name);
            $out .= '<li><a href="' . htmlspecialchars($shareUrl) . '" target="_blank" rel="noopener noreferrer" ';
            $out .= 'class="badge-share-link">' . htmlspecialchars($label) . '</a></li>';
        }
        $out .= '</ul>';
        return $out;
    }
}
