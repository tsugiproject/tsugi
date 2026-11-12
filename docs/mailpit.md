# Mailpit for local development

[Mailpit](https://mailpit.axllent.org/) catches mail on your machine. Tsugi
still calls PHP `mail()`. Mailpit accepts that message on SMTP port 1025 and
shows it in a web inbox at <http://localhost:8025>. Nothing is delivered to
the internet.

Any To address works. `ada@example.com` and `koseu-test@mailinator.com` both
land in the same inbox. The address is only a label you can search for.

Leave `$CFG->ses_region` unset. If it is set, Tsugi sends through Amazon SES
instead of PHP `mail()`. See [ses-design.md](ses-design.md).

## Install

Mailpit listens on port **8025** (web UI) and port **1025** (SMTP).

### macOS (Homebrew)

```bash
brew install mailpit
brew services start mailpit
```

`brew services` starts it at login. `mailpit` in a terminal runs it in the
foreground instead.

Apple Silicon Homebrew puts the binary at `/opt/homebrew/bin/mailpit`. Intel
Homebrew uses `/usr/local/bin/mailpit`. Check with `which mailpit`.

### Linux or macOS (install script)

This copies a static binary to `/usr/local/bin/mailpit`:

```bash
sudo sh < <(curl -sL https://raw.githubusercontent.com/axllent/mailpit/develop/install.sh)
```

Run it in the foreground with `mailpit`, or install the unit below.

### Linux (systemd)

```ini
# /etc/systemd/system/mailpit.service
[Unit]
Description=Mailpit
After=network.target

[Service]
ExecStart=/usr/local/bin/mailpit
Restart=on-failure

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now mailpit
```

Arch Linux also has an AUR package named `mailpit`.

### Docker (macOS or Linux)

```bash
docker run -d \
  --restart unless-stopped \
  --name mailpit \
  -p 8025:8025 \
  -p 1025:1025 \
  axllent/mailpit
```

Open <http://localhost:8025>. If that page loads, Mailpit is running.

## Point PHP at Mailpit

On macOS and Linux, PHP `mail()` runs the `sendmail_path` program. The
`SMTP` and `smtp_port` settings in `php.ini` are for Windows only.

Find the ini the web server actually loads. CLI and Apache/php-fpm often
differ:

```bash
php --ini
```

MAMP on macOS does not use Homebrew's php.ini. Its Apache FastCGI wrapper
loads `/Applications/MAMP/bin/php/phpX.Y.Z/conf/php.ini` (the version in
`/Applications/MAMP/fcgi-bin/php.fcgi`).

Set `sendmail_path` to the Mailpit binary from `which mailpit`:

```ini
sendmail_path = "/opt/homebrew/bin/mailpit sendmail -t -i -S 127.0.0.1:1025"
```

`-S` is the Mailpit SMTP address. `localhost:1025` is the default, so the
flag is optional when Mailpit is on that port. `-t` and `-i` are ignored by
Mailpit; PHP's mailer expects them.

Restart PHP after saving the ini (php-fpm, Apache, or MAMP). A long-running
FastCGI worker keeps the old ini until it is recycled.

Check from the same PHP binary the site uses:

```bash
php -r 'var_export(mail("anyone@example.com", "Mailpit check", "Hello from PHP.\n")); echo "\n";'
```

`true` means PHP handed the message to Mailpit. Open
<http://localhost:8025> and search for `Mailpit check`.

## Tsugi

In local `config.php` only (do not commit this, and do not set it on a
server that should really send mail):

```php
$CFG->maildomain = 'localhost';
$CFG->maileol = "\n";
```

`$CFG->maildomain = false` disables every send. With a domain set and
`$CFG->ses_region` left false, Tsugi uses PHP `mail()`. The From address is
`no-reply@localhost` unless you set `$CFG->mail_from`.

Send a message from **Admin → Mail → Test E-Mail**
(`$CFG->wwwroot/admin/testmail`). Use any To address. Transactional test
mail does not need that address to exist in `lti_user`. Bulk test mail does,
because it signs an unsubscribe link.

## What this does not do

Mailpit is a local inbox. Recipients at Mailinator, Gmail, or anywhere else
never see these messages. Do not point a production `sendmail_path` at
Mailpit, and do not enable `$CFG->ses_region` on a machine where you meant
to catch mail locally.
