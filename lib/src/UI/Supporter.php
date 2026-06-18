<?php

namespace Tsugi\UI;

use Tsugi\Core\Launch;
use Tsugi\Core\Profile as UserProfile;
use Tsugi\Util\U;

/**
 * Supporter / premium status UI shared across learner pages.
 */
class Supporter {

    protected static function isUiAvailable($CFG): bool
    {
        return $CFG->isSupporterUiAvailable();
    }

    /**
     * @return array<string,mixed>
     */
    protected static function context($CFG) {
        $launch = new Launch();
        $userProfile = UserProfile::fromLaunchRow(
            array('profile_id' => $_SESSION['profile_id']),
            $launch
        );

        $supporter_raw = $CFG->supporterUrl();
        $site_name = $CFG->supporterSiteName();
        $site_label = $CFG->supporterSiteLabel();
        $supporter_label = $CFG->supporterLabel();
        $premium_until = self::premiumUntil($userProfile);

        return array(
            'userProfile' => $userProfile,
            'supporter_url' => $supporter_raw ? htmlspecialchars($supporter_raw) : '',
            'site_name' => $site_name,
            'site_label' => $site_label,
            'supporter_label' => $supporter_label,
            'price_label' => $CFG->supporterPriceLabel(),
            'price_phrase' => $CFG->supporterPricePhrase(),
            'premium_until' => $premium_until,
            'is_active' => self::isActive($userProfile, $premium_until),
        );
    }

    /**
     * Thank-you / status for premium users.
     */
    public static function renderThankYou($CFG) {
        if ( ! self::isUiAvailable($CFG) ) {
            return;
        }

        $ctx = self::context($CFG);
        if ( ! $ctx['userProfile']->isPremium() ) {
            return;
        }

        $label = htmlspecialchars($ctx['supporter_label']);
        $site = htmlspecialchars($ctx['site_label']);
        $premium_until = $ctx['premium_until'];

        echo('<p style="margin: 0.75em 0 1.25em;">' . "\n");
        echo('<strong>' . $label . '</strong> — ');

        if ( is_string($premium_until) && strlen($premium_until) > 0 ) {
            $until_label = htmlspecialchars(self::formatUntil($premium_until, $CFG));
            if ( $ctx['is_active'] ) {
                echo('Thank you for supporting ' . $site . '! Active through <strong>'
                    . $until_label . '</strong>.');
            } else {
                echo('Thank you for supporting ' . $site . '. Ended <strong>'
                    . $until_label . '</strong>.');
            }
        } else {
            echo('Thank you for supporting ' . $site . '!');
        }

        echo("\n</p>\n");
    }

    /**
     * Simple become-a-supporter line for non-premium users.
     */
    public static function renderInvite($CFG) {
        if ( ! self::isUiAvailable($CFG) ) {
            return;
        }

        $ctx = self::context($CFG);
        if ( $ctx['userProfile']->isPremium() ) {
            return;
        }

        echo('<p style="margin: 0.75em 0 1.25em;">' . "\n");
        echo('<a href="' . $ctx['supporter_url'] . '">Become a supporter 💚</a>');
        if ( $ctx['price_phrase'] !== '' ) {
            echo(' <span style="opacity: 0.75;">— '
                . htmlspecialchars($ctx['price_phrase']) . '.</span>' . "\n");
        }
        echo('</p>' . "\n");
    }

    /**
     * Simple renew link for expired supporters.
     */
    public static function renderRenew($CFG) {
        if ( ! self::isUiAvailable($CFG) ) {
            return;
        }

        $ctx = self::context($CFG);
        if ( ! $ctx['userProfile']->isPremium() || $ctx['is_active'] ) {
            return;
        }
        if ( ! is_string($ctx['premium_until']) || strlen($ctx['premium_until']) < 1 ) {
            return;
        }

        echo('<p style="margin: 1.5em 0 0;">' . "\n");
        echo('<a href="' . $ctx['supporter_url'] . '">Renew supporter status</a>');
        if ( $ctx['price_phrase'] !== '' ) {
            echo(' <span style="opacity: 0.75;">(' . htmlspecialchars($ctx['price_phrase']) . ')</span>');
        }
        echo("\n</p>\n");
    }

    /**
     * @return string|false
     */
    public static function premiumUntil(UserProfile $profile) {
        $json = $profile->getPremiumJsonArray();
        $top = U::get($json, 'premium_until', false);
        if ( is_string($top) && strlen($top) > 0 ) {
            return $top;
        }

        $best = false;
        $best_dt = null;
        foreach ( $json as $block ) {
            if ( ! is_array($block) ) {
                continue;
            }
            $candidate = U::get($block, 'premium_until', false);
            if ( ! is_string($candidate) || strlen($candidate) < 1 ) {
                continue;
            }
            try {
                $dt = new \DateTimeImmutable($candidate);
                if ( $best_dt === null || $dt > $best_dt ) {
                    $best_dt = $dt;
                    $best = $candidate;
                }
            } catch (\Exception $e) {
                // ignore bad date values
            }
        }

        return $best;
    }

    protected static function isActive(UserProfile $profile, $premium_until) {
        if ( ! $profile->isPremium() ) {
            return false;
        }
        if ( ! is_string($premium_until) || strlen($premium_until) < 1 ) {
            return true;
        }
        try {
            $until = new \DateTimeImmutable($premium_until);
            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
            return $until > $now;
        } catch (\Exception $e) {
            return $profile->isPremium();
        }
    }

    protected static function formatUntil($premium_until, $CFG) {
        try {
            $tz_name = (isset($CFG->timezone) && $CFG->timezone) ? $CFG->timezone : 'UTC';
            $dt = new \DateTimeImmutable($premium_until);
            return $dt->setTimezone(new \DateTimeZone($tz_name))->format('F j, Y');
        } catch (\Exception $e) {
            return (string) $premium_until;
        }
    }
}
