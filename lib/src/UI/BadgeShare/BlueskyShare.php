<?php

namespace Tsugi\UI\BadgeShare;

/**
 * Share badge to Bluesky via compose intent.
 * Text typically includes both the message and the URL.
 */
class BlueskyShare implements BadgeSharePlatform {

    public function getShareUrl(string $url, string $text): string {
        $combined = $text . ' ' . $url;
        $params = array('text' => $combined);
        return 'https://bsky.app/intent/compose?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    public function getName(): string {
        return 'Bluesky';
    }

    public function getIconClass(): string {
        return 'fa-brands fa-bluesky';
    }
}
