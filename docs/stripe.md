# Stripe supporter checkout

Tsugi supports optional one-time Stripe Checkout payments that grant (or extend)
supporter premium on a user's profile. Payment secrets and API wiring live in the
`stripe` extension; site-facing supporter settings live in the `premium` extension.

## Architecture

| Layer | Role |
|-------|------|
| `stripe` extension (`config.php`) | API keys, Price ID, webhook secret, site id for metadata |
| `premium` extension (`tsugi_settings.php`) | Supporter URL, display price, premium months, refund policy |
| `Tsugi\Controllers\Profile` | Reads `premium` config; profile page invite/thank-you copy |
| `Tsugi\Util\Stripe` | Stripe SDK, webhook verification, fulfillment into `profile.premium_json` |
| `Tsugi\Controllers\Stripe` | HTTP routes: checkout, webhook, success, cancel |

Routes are registered only when the `stripe` extension is configured
(see `Tsugi\Controllers\Tsugi.php`).

Sites may add their own marketing page (for example `support.php` at apphome) that
links to `/stripe` for payment. Profile links use `premium.supporter_url`.

## Dependencies

`stripe/stripe-php` is listed in `tsugi/composer.json`. From the `tsugi` directory:

```bash
composer install
```

## Configuration

### `stripe` extension — `tsugi/config.php`

Set **before** `tsugi_settings.php` is included.

```php
$CFG->setExtension('stripe', [
    'secret_key' => 'sk_test_...',           // or sk_live_...
    'publishable_key' => 'pk_test_...',      // optional for hosted Checkout
    'supporter_price' => 'price_...',        // Stripe Price ID — not a dollar amount
    'webhook_secret' => 'whsec_...',         // see Webhooks below
    'site' => 'py4e',                        // stored in Checkout metadata; must match on fulfillment
]);
```

Optional keys:

- `webhook_secret_cli` — second signing secret (for example when rotating or using CLI + Dashboard)

`supporter_price` must start with `price_`. Create the Price in the Stripe Dashboard
(or API) and paste the ID here.

### `premium` extension — `tsugi_settings.php` (or site settings)

Typically set only when `stripe` is configured:

```php
if ( is_array($CFG->getExtension('stripe')) ) {
    $CFG->setExtension('premium', [
        'supporter_url' => $CFG->apphome . '/support',
        'price' => '$15.00 USD',             // human-facing display only
        'premium_months' => 12,
        'refund_policy' => 'There are no refunds.',
    ]);
}
```

| Key | Purpose |
|-----|---------|
| `supporter_url` | Landing page linked from Profile (not the Stripe checkout URL) |
| `price` | Shown on profile/support/checkout copy |
| `premium_months` | Months of premium granted per payment |
| `refund_policy` | Plain text; shown on support and checkout pages (omit or empty to hide) |

Access these through `Profile::supporterUrl($CFG)`, `Profile::supporterPricePhrase($CFG)`,
`Profile::premiumMonths($CFG)`, `Profile::refundPolicy($CFG)`, etc.

## URLs

Assume `$CFG->apphome` is your public site root (for example `https://www.py4e.com`).

| URL | Method | Purpose |
|-----|--------|---------|
| `/support` | GET | Site supporter landing page (optional; configured via `supporter_url`) |
| `/stripe` | GET | Checkout confirmation page |
| `/stripe` | POST | Create Checkout Session → redirect to Stripe |
| `/stripe/webhook` | POST | Stripe webhook (fulfillment) |
| `/stripe/success` | GET | Thank-you page after payment |
| `/stripe/cancel` | GET | User cancelled hosted Checkout |

The webhook URL for Stripe is:

```text
{apphome}/stripe/webhook
```

`GET /stripe/webhook` returns **404** — only `POST` is accepted.

## User flow

1. User sees “Become a supporter” on **Profile** → `premium.supporter_url`
2. Support/marketing page → **Continue to payment** → `/stripe`
3. User confirms → POST creates a Stripe Checkout Session
4. After payment, Stripe calls **`/stripe/webhook`**
5. Webhook grants premium: `profile.premium = 1`, updates `premium_json`
6. User may land on `/stripe/success` (status display only; fulfillment is via webhook)

Fulfillment is idempotent per `checkout_session_id`.

Requirements:

- User must be logged in to start checkout
- User must have a linked `profile_id` (typically via Google login) or webhook fulfillment fails

## Local development

1. Configure test keys in `config.php` as above.
2. Start webhook forwarding (use your local apphome host):

   ```bash
   stripe listen --forward-to https://local.py4e.com/stripe/webhook
   ```

3. Copy the `whsec_...` secret printed by `stripe listen` into `webhook_secret`
   in `config.php`. This value changes each time `stripe listen` starts.
4. Complete a test checkout at `{apphome}/stripe`.
5. Confirm webhook output and that `profile.premium_json` was updated.

Replay a failed event:

```bash
stripe events resend evt_...
```

(`stripe listen` must be running for local forward.)

## Production webhooks

1. Stripe Dashboard → **Developers → Webhooks → Add endpoint**
2. URL: `{apphome}/stripe/webhook`
3. Event: `checkout.session.completed` (others are ignored with HTTP 200)
4. Copy the endpoint signing secret into `webhook_secret` in `config.php`
5. Use live `sk_live_...` / `pk_live_...` and a live Price ID

The Dashboard `whsec_...` is **different** from the CLI `stripe listen` secret.

## Fulfillment details

- Checkout Session metadata includes `user_id`, `site`, `premium_months`
- `site` must match `stripe.site` in config
- Premium extends from existing `premium_until` if still active
- Stored under `premium_json.stripe` plus top-level `premium_json.premium_until`
- Adaptive pricing is enabled on session create (local currency at Stripe Checkout)

## Tests

```bash
cd tsugi/lib
phpunit -c phpunit.xml.dist tests/Controllers/StripeControllerTest.php
```

## Code locations

- `lib/src/Controllers/Stripe.php` — routes and pages
- `lib/src/Util/Stripe.php` — config validation, SDK, webhook, DB updates
- `lib/src/Controllers/Profile.php` — premium extension accessors and profile UI
- `lib/tests/Controllers/StripeControllerTest.php` — basic route/auth tests

## Troubleshooting

| Symptom | Likely cause |
|---------|----------------|
| Webhook 400 Invalid signature | Wrong `webhook_secret` (CLI vs Dashboard mismatch) |
| Webhook 404 | `stripe` extension not set, or request is GET not POST |
| Fulfillment failed: metadata.site | `site` in config does not match session metadata |
| Fulfillment failed: no profile_id | User has not linked a profile (log in via Google once) |
| Checkout error on `supporter_price` | Value is a dollar amount instead of `price_...` |
| Premium not showing on success page | Normal — wait for webhook; success page only verifies payment status |
