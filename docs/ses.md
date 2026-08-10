# Amazon SES outbound mail

Tsugi can send outbound mail through Amazon SES using the SES-only
[`async-aws/ses`](https://async-aws.com/clients/ses.html/) client. When SES is
not configured, mail falls back to PHP `mail()` and the host MTA.

All application mail goes through `Tsugi\Services\Mail\MailService`.
`Tsugi\Core\Mail` remains a thin compatibility wrapper.

For reputation, Tsugi can suppress addresses after permanent bounces,
complaints, and user unsubscribes via an SNS webhook and the `mail_suppress`
table. Bounce/complaint suppressions also set matching `profile` /
`lti_user` rows to `subscribe = -1`. Marketing unsubscribes use the same
opt-out flags but only block **bulk** mail (transactional can still send).

## Prerequisites

1. An AWS account with SES available in your chosen region
2. A **verified** sending identity (email address or domain) in that region
3. IAM credentials or an instance/task role that can call `ses:SendEmail`
4. If your account is still in the **SES sandbox**, you may only send to
   verified recipient addresses until AWS approves production access

## Step 1: Verify a From identity

In the AWS console (SES → Identities), verify either:

- A single address such as `no-reply@mail.example.com`, or
- The whole domain (recommended for production)

The From address Tsugi uses must match a verified identity in the same region
as `$CFG->ses_region`.

## Step 2: IAM permissions

Grant at least:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "ses:SendEmail",
        "ses:SendRawEmail"
      ],
      "Resource": "*"
    }
  ]
}
```

On EC2/ECS/Lambda, prefer an **IAM role** attached to the compute environment
and leave `$CFG->ses_key` / `$CFG->ses_secret` unset so the default credential
chain is used.

For local/dev only, you may set access keys in `config.php` (never commit them).

## Step 3: Configure Tsugi

In `config.php` (see `config-dist.php` for defaults):

```php
// Master enable switch for outbound mail
$CFG->maildomain = 'mail.example.com';
$CFG->mailsecret = 'change-me';
$CFG->maileol = "\n";

// Amazon SES
$CFG->ses_region = 'us-east-1';
// Optional when using IAM role / ~/.aws credentials / env vars:
// $CFG->ses_key = 'AKIA...';
// $CFG->ses_secret = '...';
// Optional verified From (defaults to no-reply@$CFG->maildomain):
// $CFG->ses_from = 'no-reply@mail.example.com';
// Required for bounce/complaint events (must match the SES Configuration Set name):
$CFG->ses_configuration_set = 'tsugi-mail';
// Optional separate set for MailService::sendBulk() (falls back to ses_configuration_set):
// $CFG->ses_configuration_set_bulk = 'tsugi-mail-bulk';
```

### Transactional vs bulk

Call sites should pick the mail class explicitly (`Tsugi\Services\Mail\MailService`
or `Tsugi\Core\Mail` wrappers):

| Method | Use for |
|--------|---------|
| `MailService::sendTransactional(...)` | One-to-one / system responses (key request, approval, peer-grade reset, Test Mail default) |
| `MailService::sendBulk(...)` | List / campaign style mail |

`MailService::send(...)` / `Tsugi\Core\Mail::send(...)` remain as deprecated aliases of transactional.

On SES, Tsugi sets `EmailTags` `mail_type=transactional|bulk` and
`X-Tsugi-Mail-Type`. Bulk can use `$CFG->ses_configuration_set_bulk` when you
want a separate event stream / reputation pool; otherwise it uses
`$CFG->ses_configuration_set`.

Transport selection:

| Condition | Transport |
|-----------|-----------|
| `$CFG->maildomain` is false | Mail disabled (no send) |
| `$CFG->ses_region` set | Amazon SES (`async-aws/ses`) |
| otherwise | PHP `mail()` |

## Step 4: Database upgrade

Create the mail tables:

1. Deploy this code
2. Sign in as admin → **Database Upgrade**
3. Confirm `mail_suppress` and `mail_ses_events` are created
   (plugin path `lib/src/Services/Mail/database.php`)

Without these tables, sends still work, but bounce/complaint/unsubscribe
suppression and SES event audit cannot be recorded.

### `mail_ses_events` (audit)

Each SNS notification Tsugi processes appends one row per recipient (when
applicable), including the **action** taken:

| action | Meaning |
|--------|---------|
| `suppress` | Updated `mail_suppress` (permanent bounce or complaint) |
| `ignore_soft_bounce` | Transient bounce; no suppress |
| `ignore_delivery` | Delivery notification logged only |
| `ignore` | Other/unhandled event type |
| `error` | Processing/suppress failed |

Browse events and suppressions under **Admin → Mail**.

## Step 5: Configuration Set + SNS (bounce / complaint)

Events only fire when sends include a Configuration Set.

1. In SES, create a **Configuration Set** (e.g. `tsugi-mail`)
2. Add an event destination for at least **Bounce** and **Complaint**
   (Delivery optional) that publishes to an **SNS topic**
3. Subscribe the SNS topic to HTTPS at the LMS Controllers home
   (same base as Stripe — `$CFG->apphome` when set, otherwise `$CFG->wwwroot`):
   `{apphome}/ses/sns`  
   Examples: `https://example.com/ses/sns` or `https://example.com/tsugi/ses/sns`
4. Confirm the subscription (Tsugi auto-GETs `SubscribeURL` when SNS posts
   `SubscriptionConfirmation`)
5. Set `$CFG->ses_configuration_set` to that Configuration Set name

Webhook notes:

- Route is registered only when `$CFG->ses_region` is set
- SNS message signatures are verified (SigningCertURL must be SNS on
  `amazonaws.com`)
- **Permanent** bounces and **complaints** insert/update `mail_suppress` and
  record `mail_ses_events` with `action=suppress`
- Soft/transient bounces record `action=ignore_soft_bounce` (no suppress)
- Delivery events record `action=ignore_delivery`

## Step 6: Admin Mail UI

**Admin → Mail** (`admin/mail/`):

- Status (transport, configuration sets, table presence)
- **Suppressed addresses** — `mail_suppress`
- **SES events** — `mail_ses_events` (click through for `payload_json`)
- **Sent log** — legacy `mail_sent` rows

## Context bulk mail (admin)

Site admins can send bulk mail to a context audience:

1. **Admin → Contexts** → open a context → **Bulk mail**
   (also linked from Membership / Mailing list)
2. Compose subject + plain-text body; choose audience filters:
   - login within N days (same idea as mailing-list export)
   - **exclude if already got bulk mail in this context** within M days
     (default 30; set 0 to disable) — so you can mail “active 15 days”,
     then later “active 30 days” without re-mailing the first group
   - optional **limit** to the N most recently logged-in matching users
     (e.g. 10; 0 = no limit)
   - optional premium-only / include opted-out
3. Preview recipient count (max **200** per send — tighten filters if larger)
4. Confirm to send via `MailService::sendBulk()` (uses
   `$CFG->ses_configuration_set_bulk` when set)

Each run creates a `mail_bulk` row and per-recipient `mail_sent` rows
(`bulk_id`). The “already mailed” filter looks at successful
`mail_sent` rows for the **same `context_id`** only. Review campaigns under
**Admin → Mail → Bulk campaigns**.

Suppress / `subscribe=-1` still skip recipients at send time. Bulk mail is
**site-admin only** (not instructors).

## Step 7: Test from admin

1. Sign in as a Tsugi admin
2. Open **Admin → Test E-Mail** (`admin/testmail.php`)
3. Confirm the page shows transport `ses` and your region
4. Send a message to an allowed recipient

On success you should see an SES MessageId. On failure the page shows the
API error (common causes: unverified From, wrong region, sandbox recipient
restrictions, or missing IAM permission).

If the address is in `mail_suppress`, Test Mail reports that it is suppressed
and does not call SES. After a bounce/complaint, check **Admin → Mail →
SES events** for the recorded action.

## Suppression rules

Tsugi owns unsubscribe / suppress state in `mail_suppress` (and `subscribe=-1`).
SES transports mail and publishes delivery/bounce/complaint events via SNS;
Tsugi does **not** use SES Contact Lists or SES subscription management.

| Source | Action |
|--------|--------|
| SES permanent bounce (SNS) | Suppress email (`reason=bounce`) and set matching `profile` / `lti_user` `subscribe=-1` — blocks **all** mail |
| SES complaint (SNS) | Suppress email (`reason=complaint`) and set matching `profile` / `lti_user` `subscribe=-1` — blocks **all** mail |
| User unsubscribe (one-click or confirm) | Set `subscribe=-1` and suppress (`reason=unsubscribe`) — blocks **bulk/marketing** only |
| Soft/transient bounce | Log only; do not suppress |

Before every send, `MailService::shouldSkipSend()` is authoritative:
bulk is skipped for any suppress row or `subscribe=-1`; transactional is
skipped for bounce/complaint (not for marketing unsubscribe).

Users can raise their subscribe preference again from **Profile**; clearing
`mail_suppress` currently requires a database update (no admin UI yet).

## Unsubscribe links (bulk only)

Bulk/marketing sends that include a recipient user id + signed token get:

- Headers: `List-Unsubscribe: <{wwwroot}/util/unsubscribe?id=…&token=…>` and
  `List-Unsubscribe-Post: List-Unsubscribe=One-Click` (RFC 8058)
- A visible plain-text **Unsubscribe** URL in the message footer

Transactional mail does **not** include these headers or footer.

`util/unsubscribe` flows:

| Request | Behavior |
|---------|----------|
| **GET** | Validate token → confirmation page → user confirms → suppress |
| **POST** `List-Unsubscribe=One-Click` | Validate token → suppress immediately → HTTP 200 `OK` (no cookies/login/UI). Idempotent. |
| **POST** confirmation form | Same suppress as GET confirm |

Invalid tokens do not suppress. SES is only the transport for the headers;
Tsugi handles the unsubscribe endpoint and stores the result.

## Credential sources (when keys are omitted)

If `$CFG->ses_key` / `$CFG->ses_secret` are not set, AsyncAws uses the standard
AWS credential chain, including:

- Environment variables (`AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_SESSION_TOKEN`)
- Shared credentials/config files (`~/.aws/credentials`, `~/.aws/config`)
- EC2/ECS/Lambda instance or task roles

## Dependency

```bash
composer require async-aws/ses --ignore-platform-reqs
composer run finalize-vendor
```

The package is already listed in this repository’s `composer.json`. After any
Composer change that updates `vendor/`, always finish with
`composer run finalize-vendor` before committing.
