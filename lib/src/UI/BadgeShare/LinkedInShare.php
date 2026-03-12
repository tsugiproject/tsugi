<?php

namespace Tsugi\UI\BadgeShare;

/**
 * Share badge to LinkedIn (post/share, distinct from adding to LinkedIn certificate).
 */
class LinkedInShare implements BadgeSharePlatform {

    public function getShareUrl(string $url, string $text): string {
        $params = array(
            'mini' => 'true',
            'url'  => $url,
        );
        return 'https://www.linkedin.com/shareArticle?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    public function getName(): string {
        return 'LinkedIn';
    }

    public function getIconClass(): string {
        return 'fa-brands fa-linkedin';
    }
}
