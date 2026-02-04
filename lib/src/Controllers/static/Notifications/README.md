# Push Notifications Setup

## Database Setup

Run the SQL migration to create the `push_subscriptions` table:

```sql
-- See push_subscriptions.sql for the table schema
```

Or manually create the table using the SQL in `push_subscriptions.sql`.

## VAPID Keys Setup

You need to generate VAPID (Voluntary Application Server Identification) keys for push notifications.

### Option 1: Use an online generator
Visit https://web-push-codelab.glitch.me/ and generate keys, then add them to `tsugi/config.php`:

```php
$CFG->vapid_public_key = 'your-public-key-here';
$CFG->vapid_private_key = 'your-private-key-here';
$CFG->vapid_subject = 'mailto:your-email@example.com'; // Required for VAPID
```

### Option 2: Use web-push-php library (recommended for production)

Install via Composer:
```bash
composer require minishlink/web-push
```

Then generate keys programmatically or use the library's methods.

## Usage

1. Users visit `/notifications` to enable/disable push notifications
2. Instructors can send test notifications from the same page
3. The service worker handles receiving and displaying notifications

## Testing

1. Enable notifications as a user
2. As an instructor, click "Send Test Notification"
3. You should receive a notification even if the browser tab is not active
