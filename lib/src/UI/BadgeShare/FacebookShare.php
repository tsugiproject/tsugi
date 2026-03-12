<?php

namespace Tsugi\UI\BadgeShare;

/**
 * Share badge to Facebook via sharer.
 */
class FacebookShare implements BadgeSharePlatform {

    public function getShareUrl(string $url, string $text): string {
        $params = array('u' => $url);
        return 'https://www.facebook.com/sharer/sharer.php?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    public function getName(): string {
        return 'Facebook';
    }

    public function getIconClass(): string {
        return 'fa-brands fa-facebook';
    }
}
