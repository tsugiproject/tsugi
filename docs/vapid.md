# VAPID key setup

VAPID (Voluntary Application Server Identification) keys are required for push
notifications. They identify your server to push notification services.

## Step 1: Generate VAPID keys

### Option A: Online generator (easiest)

Choose one of these working online generators:

**Option A1: Giga.tools VAPID Key Generator** (recommended)

1. Visit: https://giga.tools/developer-tools/vapid-key-generator
2. Click "Generate VAPID Keys"
3. Copy both the **Public Key** and **Private Key** (base64url format)

**Option A2: VapidKeys.com**

1. Visit: https://vapidkeys.com/
2. Click "Generate VAPID Keys"
3. Copy both keys

**Option A3: Steve Seguin's generator**

1. Visit: https://steveseguin.github.io/vapid/
2. Click "Generate VAPID Keys"
3. Copy both keys

All of these generators run client-side in your browser — your keys are never sent
to any server.

### Option B: Node.js

```bash
npm install -g web-push
web-push generate-vapid-keys
```

This outputs a public key and private key (long base64 strings).

### Option C: PHP script (CLI only)

A PHP script is included in `scripts/`:

```bash
php scripts/generate-vapid-keys.php
```

This outputs keys ready to paste into `tsugi_settings.php`. Requires PHP with the
OpenSSL extension.

**Note:** This script is CLI-only and cannot be accessed via web browser (protected
by `.htaccess`).

## Step 2: Add keys to configuration

Add the following to `tsugi_settings.php` (or directly in `config.php`):

```php
// VAPID keys for push notifications
$CFG->vapid_public_key = 'YOUR_PUBLIC_KEY_HERE';
$CFG->vapid_private_key = 'YOUR_PRIVATE_KEY_HERE';
$CFG->vapid_subject = 'mailto:drchuck@learnxp.com'; // Your email or mailto: URL
```

**Important notes:**

- `vapid_subject` should be a `mailto:` URL with your email address
- Keep the private key **secret** — never commit it to version control
- The public key is safe to expose (it is sent to browsers)

## Step 3: Verify configuration

After adding the keys:

1. Visit `/notifications` on your site
2. Click "Enable Notifications"
3. Check the browser console for any errors
4. If you see `VAPID_PUBLIC_KEY_NOT_SET`, the keys are not configured correctly

## Step 4: Test push notifications

1. As a logged-in user, enable notifications at `/notifications`
2. As an instructor, visit `/notifications` and click "Send Test Notification"
3. You should receive a notification even if the browser tab is not active

## Troubleshooting

| Symptom | Likely cause |
|---------|----------------|
| "VAPID keys not configured" | `vapid_public_key` or `vapid_private_key` not set in config |
| "Invalid subscription data" | Check browser console for JavaScript errors |
| Notifications not appearing | Check browser notification permissions (Settings → Site Settings → Notifications) |
