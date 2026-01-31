# VAPID Key Setup Guide

VAPID (Voluntary Application Server Identification) keys are required for push notifications. They identify your server to push notification services.

## Step 1: Generate VAPID Keys

You have several options to generate VAPID keys:

### Option A: Online Generator (Easiest)

Choose one of these working online generators:

**Option A1: Giga.tools VAPID Key Generator** (Recommended)
1. Visit: https://giga.tools/developer-tools/vapid-key-generator
2. Click "Generate VAPID Keys"
3. Copy both the **Public Key** and **Private Key** (base64url format)

**Option A2: VapidKeys.com**
1. Visit: https://vapidkeys.com/
2. Click "Generate VAPID Keys"
3. Copy both keys

**Option A3: Steve Seguin's Generator**
1. Visit: https://steveseguin.github.io/vapid/
2. Click "Generate VAPID Keys"
3. Copy both keys

All of these generators run client-side in your browser - your keys are never sent to any server.

### Option B: Using Node.js (if you have Node installed)

```bash
npm install -g web-push
web-push generate-vapid-keys
```

This will output:
- Public Key: (a long base64 string)
- Private Key: (a long base64 string)

### Option C: Using PHP Script (CLI Only)

A PHP script is included in `tsugi/scripts/`:

```bash
php tsugi/scripts/generate-vapid-keys.php
```

This will output the keys ready to paste into `tsugi_settings.php`. Requires PHP with OpenSSL extension.

**Note:** This script is CLI-only and cannot be accessed via web browser (protected by .htaccess).

### Option D: Using Node.js (if you have Node installed)

```bash
npm install -g web-push
web-push generate-vapid-keys
```

This will output:
- Public Key: (a long base64 string)
- Private Key: (a long base64 string)

## Step 2: Add Keys to Configuration

Add the following to `tsugi_settings.php` (or directly in `tsugi/config.php`):

```php
// VAPID keys for push notifications
$CFG->vapid_public_key = 'YOUR_PUBLIC_KEY_HERE';
$CFG->vapid_private_key = 'YOUR_PRIVATE_KEY_HERE';
$CFG->vapid_subject = 'mailto:drchuck@learnxp.com'; // Your email or mailto: URL
```

**Important Notes:**
- The `vapid_subject` should be a `mailto:` URL with your email address
- Keep the private key **SECRET** - never commit it to version control
- The public key is safe to expose (it's sent to browsers)

## Step 3: Verify Configuration

After adding the keys:

1. Visit `/notifications` on your site
2. Click "Enable Notifications"
3. Check the browser console for any errors
4. If you see "VAPID_PUBLIC_KEY_NOT_SET", the keys aren't configured correctly

## Step 4: Test Push Notifications

1. As a logged-in user, enable notifications at `/notifications`
2. As an instructor, visit `/notifications` and click "Send Test Notification"
3. You should receive a notification even if the browser tab is not active

## Troubleshooting

- **"VAPID keys not configured"**: Make sure both `vapid_public_key` and `vapid_private_key` are set in config
- **"Invalid subscription data"**: Check browser console for JavaScript errors
- **Notifications not appearing**: Check browser notification permissions (Settings → Site Settings → Notifications)
