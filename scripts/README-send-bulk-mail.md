# CLI bulk mail (`send-bulk-mail.php`)

Send context bulk mail from the shell via `Tsugi\Services\Mail\MailService`
(SES when configured). Same audience filters and logging as
**Admin → Context → Bulk mail**.

## Quick start

```bash
cd /path/to/tsugi

# Calling sequence / help (also printed with no parameters)
php scripts/send-bulk-mail.php
php scripts/send-bulk-mail.php --help

# Dry-run (default): show who would be mailed
php scripts/send-bulk-mail.php \
  --context-id=42 \
  --subject='Hello from the course' \
  --body-file=./campaign.txt \
  --days=30 \
  --exclude-recent-bulk-days=30 \
  --limit=5

# Actually send
php scripts/send-bulk-mail.php \
  --context-id=42 \
  --from-user-id=1 \
  --subject='Hello from the course' \
  --body-file=./campaign.txt \
  --days=30 \
  --exclude-recent-bulk-days=30 \
  --limit=5 \
  --send
```

`--from-user-id` must be a real `lti_user.user_id` (stored on `mail_bulk.user_id`).

## Calling sequence

When you run with **no parameters** or **`--help`**, the script prints usage
and this flow, then exits:

1. Load `config.php` and connect to the database  
2. Resolve audience  
   - `--email=…` → one context member (`mail_context_audience_by_email`)  
   - otherwise → `mail_context_audience()` with days / exclude / limit / flags  
3. Print transport, filters, recipient count, and a sample of emails  
4. If `--send` is omitted → **stop** (dry-run)  
5. If `--send`:  
   - INSERT `mail_bulk`  
   - For each recipient: `MailService::sendDetailedBulk()` (signed List-Unsubscribe)  
   - INSERT `mail_sent` per recipient  
   - UPDATE `mail_bulk.json` with sent/skipped/failed  
6. Print summary and exit  

## Options

| Option | Meaning |
|--------|---------|
| `--context-id=N` | Required. Context to mail |
| `--subject=TEXT` | Required subject |
| `--body-file=PATH` | Plain-text body file (preferred) |
| `--body=TEXT` | Inline body (small only) |
| `--from-user-id=N` | Required with `--send` |
| `--days=N` | Logged in within last N days (default 30) |
| `--exclude-recent-bulk-days=N` | Skip successful bulk in this context within N days (default 30; `0` = off) |
| `--limit=N` | Most recent N by `login_at` (`0` = no limit, max 200) |
| `--email=ADDR` | Single context member (ignores days/limit) |
| `--premium-only` | Premium only |
| `--include-opted-out` | Include opted-out in list (still skipped at send for bulk) |
| `--send` | Deliver; default is dry-run |
| `--help` / `-h` | Usage |

## Walking a large list

Use a small `--limit` and keep `--exclude-recent-bulk-days` set so each run
mails the next batch of people who have not received bulk mail yet:

```bash
php scripts/send-bulk-mail.php --context-id=42 --from-user-id=1 \
  --subject='Update' --body-file=./msg.txt \
  --days=30 --exclude-recent-bulk-days=30 --limit=50 --send
```

Repeat until a dry-run shows **no recipients**.

## Safety

- CLI only (`php_sapi_name() === 'cli'`)  
- Dry-run unless `--send`  
- Max **200** recipients per invocation  
- Suppress / unsubscribe gates still enforced by `MailService`  
- **Local pacing:** if more than **5** bulk sends occur within one second, wait **5s** (message on stderr / error_log) and continue. Unlikely with normal SES latency.  
- **SES rate limit:** the throttled recipient is logged as `rate_limited`, then the run **stops**. Remaining addresses are not attempted (so they stay eligible under `--exclude-recent-bulk-days`). Exit code **3**.  
- Does not use SES Contact Lists — Tsugi owns opt-out state  

See also `docs/ses-design.md` (Context bulk mail, unsubscribe, Cloudflare Bot Fight Mode).
