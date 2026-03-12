<?php

namespace Tsugi\UI\BadgeShare;

/**
 * Interface for badge sharing to social media platforms.
 * Each platform implements this interface with its own URL format.
 */
interface BadgeSharePlatform {

    /**
     * Build the share URL for this platform.
     *
     * @param string $url   The URL to share (e.g., badge assertion URL)
     * @param string $text  Pre-populated share text (e.g., "I earned the X badge from CA4E!")
     * @return string       Full URL to open for sharing
     */
    public function getShareUrl(string $url, string $text): string;

    /**
     * Human-readable platform name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Optional: Font Awesome icon class (e.g., 'fa-brands fa-x-twitter').
     *
     * @return string
     */
    public function getIconClass(): string;
}
