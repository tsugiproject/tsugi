<?php

namespace Tsugi\UI\BadgeShare;

/**
 * Share badge to Twitter/X via web intent.
 */
class TwitterShare implements BadgeSharePlatform {

    public function getShareUrl(string $url, string $text): string {
        $params = array(
            'text' => $text,
            'url'  => $url,
        );
        return 'https://twitter.com/intent/tweet?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    public function getName(): string {
        return 'Twitter';
    }

    public function getIconClass(): string {
        return 'fa-brands fa-x-twitter';
    }
}
