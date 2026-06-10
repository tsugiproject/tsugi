<?php

namespace Tsugi\Util;

use Tsugi\Controllers\Profile;
use Tsugi\Core\LTIX;

/**
 * Stripe Checkout and payment fulfillment helpers.
 *
 * Supporter display config lives in {@see Profile}; stripe secrets in $CFG->getExtension('stripe').
 */
class Stripe {

    /**
     * Plain-text error for developer-facing stripe endpoints.
     */
    public static function fail(string $message, int $http_status = 500): void
    {
        http_response_code($http_status);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Stripe error: {$message}\n";
        exit;
    }

    /**
     * Read a field from an array or Stripe SDK object (U::get() is array-only).
     *
     * @param array<string,mixed>|object $source
     */
    public static function val($source, string $key, $default = null)
    {
        if (is_array($source)) {
            return array_key_exists($key, $source) ? $source[$key] : $default;
        }
        if (is_object($source)) {
            if ($source instanceof \ArrayAccess && isset($source[$key])) {
                return $source[$key];
            }
            if (isset($source->{$key})) {
                return $source->{$key};
            }
        }

        return $default;
    }

    /**
     * @return array<string,mixed>
     */
    public static function metadataArray($metadata): array
    {
        if (is_array($metadata)) {
            return $metadata;
        }
        if (is_object($metadata)) {
            if ($metadata instanceof \Stripe\StripeObject) {
                return $metadata->toArray();
            }
            if ($metadata instanceof \ArrayAccess) {
                $out = array();
                foreach ($metadata as $key => $value) {
                    $out[$key] = $value;
                }
                return $out;
            }
        }

        return array();
    }

    /**
     * Load and validate the stripe extension config block.
     *
     * @return array<string,mixed>
     */
    public static function config(bool $require_webhook_secret = false): array
    {
        global $CFG;

        $stripe_cfg = $CFG->getExtension('stripe');
        if (!is_array($stripe_cfg)) {
            self::fail(
                'Missing stripe extension config. Add $CFG->setExtension(\'stripe\', [...]) in tsugi/config.php.'
            );
        }

        $secret_key = isset($stripe_cfg['secret_key']) ? trim((string) $stripe_cfg['secret_key']) : '';
        if ($secret_key === '') {
            self::fail('Missing stripe extension config key: secret_key');
        }

        $supporter_price = isset($stripe_cfg['supporter_price']) ? trim((string) $stripe_cfg['supporter_price']) : '';
        if ($supporter_price === '') {
            self::fail('Missing stripe extension config key: supporter_price');
        }
        if ($supporter_price !== '' && !preg_match('/^price_/', $supporter_price)) {
            self::fail(
                'supporter_price must be a Stripe Price ID (e.g. price_1ABC...), not a dollar amount.'
            );
        }

        $site = isset($stripe_cfg['site']) ? trim((string) $stripe_cfg['site']) : '';
        if ($site === '') {
            self::fail('Missing stripe extension config key: site');
        }

        $webhook_secrets = self::collectWebhookSecrets($stripe_cfg);
        if ($require_webhook_secret && count($webhook_secrets) < 1) {
            self::fail(
                'Missing stripe webhook signing secret. When using "stripe listen", copy the whsec_... '
                . 'value from that command into webhook_secret (or webhook_secret_cli) in tsugi/config.php. '
                . 'It is different from the Dashboard webhook secret.'
            );
        }

        return array(
            'secret_key' => $secret_key,
            'supporter_price' => $supporter_price,
            'site' => $site,
            'webhook_secrets' => $webhook_secrets,
        );
    }

    public static function webhookSignatureHeader(): string
    {
        if (isset($_SERVER['HTTP_STRIPE_SIGNATURE']) && $_SERVER['HTTP_STRIPE_SIGNATURE'] !== '') {
            return (string) $_SERVER['HTTP_STRIPE_SIGNATURE'];
        }

        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                if (strcasecmp((string) $name, 'Stripe-Signature') === 0) {
                    return trim((string) $value);
                }
            }
        }

        return '';
    }

    /**
     * @param list<string> $webhook_secrets
     */
    public static function constructEvent(string $payload, string $sig_header, array $webhook_secrets): \Stripe\Event
    {
        if (count($webhook_secrets) < 1) {
            self::fail(
                'No webhook signing secret configured. Paste the whsec_... from "stripe listen" into '
                . 'webhook_secret in tsugi/config.php (not the Dashboard endpoint secret unless you use Dashboard forwarding).'
            );
        }

        $last_error = null;
        foreach ($webhook_secrets as $secret) {
            try {
                return \Stripe\Webhook::constructEvent($payload, $sig_header, $secret);
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                $last_error = $e;
            }
        }

        error_log('Stripe webhook signature verification failed: ' . ($last_error ? $last_error->getMessage() : 'unknown'));
        self::fail(
            'Invalid signature. For local dev with "stripe listen", webhook_secret in tsugi/config.php must be '
            . 'the whsec_... printed when listen starts (it changes each run). Dashboard endpoint secrets are different.',
            400
        );
    }

    public static function requireSdk(): void
    {
        if (!class_exists(\Stripe\StripeClient::class)) {
            self::fail(
                'Stripe PHP SDK not installed. From the tsugi directory run: composer require stripe/stripe-php'
            );
        }
    }

    public static function client(string $secret_key): \Stripe\StripeClient
    {
        self::requireSdk();
        return new \Stripe\StripeClient($secret_key);
    }

    public static function bootstrapDb(): void
    {
        LTIX::getConnection();
    }

    /**
     * Grant (or extend) supporter premium for a Tsugi user.
     *
     * @param array<string,mixed> $payment
     * @return array{ok:bool,error?:string,skipped?:bool,profile_id?:int,premium_until?:string}
     */
    public static function grantSupporter(int $user_id, array $payment): array
    {
        global $CFG, $PDOX;

        self::bootstrapDb();

        if ($user_id < 1) {
            return array('ok' => false, 'error' => 'invalid user_id');
        }

        $checkout_session_id = isset($payment['checkout_session_id'])
            ? trim((string) $payment['checkout_session_id']) : '';
        if ($checkout_session_id === '') {
            return array('ok' => false, 'error' => 'missing checkout_session_id');
        }

        $months = isset($payment['premium_months']) ? (int) $payment['premium_months'] : Profile::premiumMonths($CFG);
        if ($months < 1) {
            $months = Profile::premiumMonths($CFG);
        }

        $user_row = $PDOX->rowDie(
            "SELECT user_id, profile_id, email
             FROM {$CFG->dbprefix}lti_user
             WHERE user_id = :UID",
            array(':UID' => $user_id)
        );
        if ($user_row === false) {
            return array('ok' => false, 'error' => 'lti_user not found for user_id=' . $user_id);
        }

        $profile_id = (int) U::get($user_row, 'profile_id', 0);
        if ($profile_id < 1) {
            return array(
                'ok' => false,
                'error' => 'user_id=' . $user_id . ' has no linked profile_id (log in via Google once, then retry)',
            );
        }

        if (!$PDOX->columnExists('premium', "{$CFG->dbprefix}profile")) {
            return array('ok' => false, 'error' => 'profile.premium column is missing (run Tsugi admin upgrade)');
        }

        $profile_row = $PDOX->rowDie(
            "SELECT premium, premium_at, premium_json
             FROM {$CFG->dbprefix}profile
             WHERE profile_id = :PID AND (deleted IS NULL OR deleted = 0)",
            array(':PID' => $profile_id)
        );
        if ($profile_row === false) {
            return array('ok' => false, 'error' => 'profile not found for profile_id=' . $profile_id);
        }

        $premium_json = array();
        $raw_json = U::get($profile_row, 'premium_json', null);
        if (is_string($raw_json) && U::strlen($raw_json) > 0) {
            $decoded = json_decode($raw_json, true);
            if (is_array($decoded)) {
                $premium_json = $decoded;
            }
        }

        $stripe_block = U::get($premium_json, 'stripe', array());
        if (!is_array($stripe_block)) {
            $stripe_block = array();
        }

        $existing_session = U::get($stripe_block, 'checkout_session_id', '');
        if (is_string($existing_session) && $existing_session === $checkout_session_id) {
            return array(
                'ok' => true,
                'skipped' => true,
                'profile_id' => $profile_id,
                'premium_until' => (string) U::get($stripe_block, 'premium_until', ''),
            );
        }

        $premium_until = self::computePremiumUntil($stripe_block, $months);

        $stripe_block['checkout_session_id'] = $checkout_session_id;
        $stripe_block['premium_until'] = $premium_until;
        $stripe_block['fulfilled_at'] = gmdate('c');
        $stripe_block['premium_months'] = $months;
        $site = isset($payment['site']) ? trim((string) $payment['site']) : '';
        if ($site === '') {
            $site = self::config()['site'];
        }
        $stripe_block['site'] = $site;

        foreach (array('payment_intent_id', 'customer_id', 'amount_total', 'currency') as $key) {
            if (isset($payment[$key]) && $payment[$key] !== '' && $payment[$key] !== null) {
                $stripe_block[$key] = $payment[$key];
            }
        }

        $premium_json['stripe'] = $stripe_block;
        $premium_json['premium_until'] = $premium_until;
        $encoded_json = json_encode($premium_json, JSON_UNESCAPED_SLASHES);
        if ($encoded_json === false) {
            return array('ok' => false, 'error' => 'could not encode premium_json');
        }

        $current_premium = (int) U::get($profile_row, 'premium', 0);
        $new_premium = max(1, $current_premium);
        $premium_at = U::get($profile_row, 'premium_at', null);
        if ($premium_at === null || $premium_at === '') {
            $premium_at_sql = 'NOW()';
            $premium_at_params = array();
        } else {
            $premium_at_sql = ':PREMIUM_AT';
            $premium_at_params = array(':PREMIUM_AT' => $premium_at);
        }

        $params = array_merge(
            array(
                ':PREMIUM' => $new_premium,
                ':JSON' => $encoded_json,
                ':PID' => $profile_id,
            ),
            $premium_at_params
        );

        $PDOX->queryDie(
            "UPDATE {$CFG->dbprefix}profile
             SET premium = :PREMIUM,
                 premium_at = {$premium_at_sql},
                 premium_json = :JSON,
                 updated_at = NOW()
             WHERE profile_id = :PID",
            $params
        );

        error_log('Stripe supporter granted profile_id=' . $profile_id . ' user_id=' . $user_id
            . ' until=' . $premium_until . ' session=' . $checkout_session_id);

        return array(
            'ok' => true,
            'profile_id' => $profile_id,
            'premium_until' => $premium_until,
        );
    }

    /**
     * @param object $session Stripe Checkout Session object from webhook or API
     * @return array{ok:bool,error?:string,skipped?:bool,profile_id?:int,premium_until?:string}
     */
    public static function fulfillCheckoutSession(object $session, string $expected_price_id): array
    {
        global $CFG;

        if (self::val($session, 'mode') !== 'payment') {
            return array('ok' => false, 'error' => 'checkout session mode is not payment');
        }

        $payment_status = (string) self::val($session, 'payment_status', '');
        if ($payment_status !== 'paid') {
            return array('ok' => false, 'error' => 'checkout session payment_status=' . $payment_status);
        }

        $metadata = self::metadataArray(self::val($session, 'metadata', array()));
        $expected_site = self::config()['site'];

        if (U::get($metadata, 'site') !== $expected_site) {
            return array(
                'ok' => false,
                'error' => 'checkout session metadata.site is not ' . $expected_site,
            );
        }

        $user_id = (int) U::get($metadata, 'user_id', 0);
        if ($user_id < 1) {
            $user_id = (int) self::val($session, 'client_reference_id', 0);
        }
        if ($user_id < 1) {
            return array('ok' => false, 'error' => 'could not determine user_id from checkout session');
        }

        if ($expected_price_id !== '' && !self::sessionHasPrice($session, $expected_price_id)) {
            return array('ok' => false, 'error' => 'checkout session does not include supporter_price');
        }

        $payment = array(
            'checkout_session_id' => (string) self::val($session, 'id', ''),
            'payment_intent_id' => (string) self::val($session, 'payment_intent', ''),
            'customer_id' => (string) self::val($session, 'customer', ''),
            'amount_total' => self::val($session, 'amount_total', null),
            'currency' => (string) self::val($session, 'currency', ''),
            'premium_months' => (int) U::get($metadata, 'premium_months', Profile::premiumMonths($CFG)),
            'site' => (string) U::get($metadata, 'site', $expected_site),
        );

        return self::grantSupporter($user_id, $payment);
    }

    public static function retrieveCheckoutSession(\Stripe\StripeClient $stripe, string $session_id): object
    {
        return $stripe->checkout->sessions->retrieve(
            $session_id,
            array('expand' => array('line_items.data.price'))
        );
    }

    /**
     * @param array<string,mixed> $stripe_cfg
     * @return list<string>
     */
    protected static function collectWebhookSecrets(array $stripe_cfg): array
    {
        $secrets = array();
        foreach (array('webhook_secret', 'webhook_secret_cli') as $key) {
            if (!isset($stripe_cfg[$key])) {
                continue;
            }
            $value = trim((string) $stripe_cfg[$key]);
            if ($value === '' || $value === 'whsec_...') {
                continue;
            }
            if (strpos($value, 'whsec_') !== 0) {
                continue;
            }
            $secrets[] = $value;
        }

        return array_values(array_unique($secrets));
    }

    /**
     * @param array<string,mixed> $stripe_block
     */
    protected static function computePremiumUntil(array $stripe_block, int $months): string
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $base = $now;

        $existing_until = U::get($stripe_block, 'premium_until', '');
        if (is_string($existing_until) && $existing_until !== '') {
            try {
                $parsed = new \DateTimeImmutable($existing_until);
                if ($parsed > $now) {
                    $base = $parsed;
                }
            } catch (\Exception $e) {
                // ignore bad stored value
            }
        }

        return $base->modify('+' . $months . ' months')->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * @param object $session
     */
    protected static function sessionHasPrice(object $session, string $price_id): bool
    {
        $line_items = self::val($session, 'line_items', null);
        if (!is_object($line_items) || !isset($line_items->data) || !is_array($line_items->data)) {
            return false;
        }

        foreach ($line_items->data as $item) {
            $price = self::val($item, 'price', null);
            $item_price_id = is_object($price) ? (string) self::val($price, 'id', '') : '';
            if ($item_price_id === $price_id) {
                return true;
            }
        }

        return false;
    }
}
