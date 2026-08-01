# LTI key self-service and 1.3 dynamic registration

This document captures the planned design for self-service LTI key management:
instructors can configure a single `lti_key` row as LTI 1.1, LTI 1.3, or both, with
LTI 1.3 provisioned through **dynamic registration only** (no manual platform fields
for end users).

This is a **design summary**, not an implementation checklist. Code may still reflect
the older admin-centric flow until this work ships.

Related: [issuer_migration.md](issuer_migration.md) (per-key `lms_*` model, no
`lti_issuer` table).

## Goals

- One **`lti_key` row** per instructor integration — never split 1.1 and 1.3 across
  two rows.
- Optional columns on that row: 1.1 fields configured or not; 1.3 fields configured or
  not.
- **Convert 1.1 → 1.3** on the same row (add `lms_*` via dynamic registration; keep
  existing `key_key` / `secret`).
- **Self-service parity** with today's LTI 1.1 request/approve flow where
  `$CFG->providekeys` is enabled.
- **Re-registration** must work: refresh platform endpoints or replace config after LMS
  changes without creating a new row.

## One row, optional capabilities

| Capability | Primary fields | Typical completion |
|------------|----------------|--------------------|
| Common | `key_title`, `user_id`, `key_id` | Request / approve |
| LTI 1.1 | `key_key`, `secret` | Auto-generated on approve |
| LTI 1.3 | `lms_client`, `lms_*` endpoints, optional `deploy_key` | Dynamic registration |

A row may be **1.1 only**, **1.3 only**, or **both**. Validation in
`admin/key/key-util.php` (`validate_key_details()`) already allows this: at least one
of OAuth consumer key, specific deployment id, or `(lms_issuer + lms_client)` must be
present.

## Planned UI: tabbed key dialog

Refactor admin and self-service key forms into tabs that separate **editable fields**
from **LMS how-to documentation**:

| Tab | Contents |
|-----|----------|
| **Common** | Title, owner, readiness status, optional analytics URLs, Tsugi tool URLs (read-only) |
| **LTI 1.1** | Consumer key + secret; empty state if not configured |
| **LTI 1.3** | Platform fields (read-only for self-service), dynamic registration URL, deployment id |
| **LMS guides** | Sakai, Canvas, Moodle, etc. (documentation only — not mixed into field tabs) |

**Admin** can edit all fields, including wildcard deployment and manual `lms_*` entry.
**Self-service users** see the same layout with restricted permissions (see below).

## Self-service policy

| Capability | Admin | Self-service user |
|------------|-------|-------------------|
| LTI 1.1 key/secret | Edit / generate | Read-only + copy after issuance |
| Manual `lms_*` entry | Yes | **No** |
| Dynamic registration | Yes | **Yes — only path for 1.3** |
| Wildcard deploy (`deploy_key` blank) | Yes (institution keys) | **No** — require deployment id when LMS provides it |
| Wildcard issuer (`lms_issuer` null) | Yes | **No** |
| Re-registration | Yes | Yes, on owned row |
| Convert 1.1 → 1.3 on same row | Yes | Yes (unlock + dynamic reg, or re-reg rules below) |

End users must not paste `lms_client` or issuer URLs. That removes copy-paste hijacking
and most accidental uniqueness collisions.

## Dynamic registration flow

1. User owns a row (`key_id`, optional existing 1.1 credentials).
2. User opens the dynamic registration URL from the key detail page (includes
   `tsugi_key={key_id}`; first-time may also require a one-time `unlock_code`).
3. User completes registration in the LMS while logged into Tsugi.
4. `settings/key/auto_common.php` POSTs the tool configuration to the LMS registration
   endpoint and receives `client_id`, issuer, OIDC/JWKS/token URLs, and usually
   `deployment_id`.
5. Tsugi **UPDATE**s the same `lti_key` row with `lms_*` (and `deploy_key` when
   returned).

The tool registration body already sets `initiate_login_uri` to
`/lti/oidc_login/{key_id}`, so the target row is known before the LMS responds.

## Re-registration

Re-registration must update an existing owned row, not insert a new one.

### Row resolution (in order)

When dynamic registration completes:

1. **`tsugi_key` + logged-in user owns the row** — primary path (user started from
   their key detail page).
2. **If that row already has `lms_client`:**
   - Same `(iss, client_id)` as the LMS response → refresh endpoints in place.
   - **Different** `client_id` (common on Canvas when a new Developer Key is minted) →
     still update **this** row if the user started from this `tsugi_key` (replace prior
     1.3 config; the old LMS registration is abandoned).
3. **If `(iss, client_id)` matches exactly one row owned by the logged-in user** — update
   that row (handles re-reg when the LMS reuses the same Developer Key).
4. **If `(iss, client_id)` matches a row owned by another user** — **reject**.
5. **New `(iss, client_id)` and owned row has empty 1.3** — first-time 1.3 setup on
   that row.

Before commit, call `validate_issuer_client_unique()` (with `exclude_key_id`) on the
auto-registration path — today this runs on admin form save but not yet in
`auto_common.php`.

### Unlock code

Today `unlock_code` is single-use and cleared at the **start** of registration, which
blocks re-registration and burns the code on failure. Planned behavior:

| Event | Rule |
|-------|------|
| First 1.3 setup | `tsugi_key` + ownership + (`unlock_code` **or** `lms_client` empty) |
| Re-registration | `tsugi_key` + ownership **or** owned `(iss, client)` match — **no** new unlock code |

Re-reg URL can remain `auto.php?tsugi_key={key_id}` for owned keys that already have
1.3 configured.

## Uniqueness and multi-tenant safety

### What must stay globally unique

Launch matching in `LTIX::loadAllData()` resolves LTI 1.3 by **`lms_client` + issuer**
(not by `deploy_key`). The first matching row wins (`ORDER BY key_id ASC LIMIT 1`).

Software check `validate_issuer_client_unique()` (in `key-util.php`) prevents a **new
wildcard deployment** key from colliding with an existing key that already owns the same
`(issuer, lms_client)` with blank `deploy_key`. Keys with a **specific** `deploy_key`
may share the same issuer and client id.

Database constraint `UNIQUE(key_sha256, deploy_sha256)` applies when both OAuth key and
deployment id are set; it is secondary for the self-service 1.3 story.

### Why Canvas makes self-service workable

On **Instructure-hosted Canvas**, the platform **`iss` is shared** across tenant
subdomains on a given environment (production / beta / test each have a fixed issuer).
**`client_id` is unique per Developer Key.** Dynamic registration creates a new Developer
Key and returns a new `client_id` for each registration.

So many instructor keys on Tsugi may share the same `lms_issuer` (for example
`https://canvas.instructure.com`); **`lms_client` is the per-registration
discriminator.** Honest self-service via dynamic registration rarely collides.

**Self-hosted Canvas** uses that instance's URL as `iss`, so `(iss, client_id)` is
naturally scoped per institution.

**Edge case:** one shared Developer Key deployed to multiple courses shares one
`client_id`; deployments differ by **`deployment_id`**. Self-service should store
deployment id when the LMS returns it and not use wildcard deploy for instructor keys.

### Security boundary

- Updates are allowed only when **`user_id` matches the logged-in user** (admin
  override optional).
- Matching `(iss, client_id)` alone is insufficient — **ownership must match**.
- Never allow one user's registration to overwrite another user's row.

## Converting LTI 1.1 to LTI 1.3

Same row, additive:

1. User already has `key_key` + `secret`.
2. User sets `unlock_code` (first time) or uses re-reg rules if 1.3 was partially
   configured.
3. Dynamic registration writes `lms_*` onto the existing row.
4. 1.1 launches continue to work; 1.3 launches use the new platform fields.

Show per-tab readiness in the UI (for example “LTI 1.1 configured”, “LTI 1.3 — run
dynamic registration”).

## Request and approve flow (planned extension)

Today's `key_request` flow (`settings/key/requests.php`, `admin/key/approve-key.php`)
creates **LTI 1.1 keys only** (`lti = 1` hard-coded). Planned extension:

- Let the request form specify **1.1**, **1.3**, or **both** (extend `lti` column or
  use `key_request.json`).
- On approve / auto-approve:
  - **1.1** — mint `key_key` + `secret` as today.
  - **1.3 or both** — ensure `key_id` exists, set `unlock_code` (or equivalent), email
    dynamic registration instructions.
- List views should show key type (reuse logic from `admin/key/index.php`: Draft / 1.1 /
  1.3 / both).

## Implementation gaps (current code)

| Gap | Notes |
|-----|--------|
| `auto_common.php` | No `validate_issuer_client_unique()` before UPDATE |
| `settings/key/auto.php` | Does not pass `unlock_code`; `auto_common.php` requires it |
| `settings/key/key-detail.php` | Registration URL omits `unlock_code` |
| Re-registration | Blocked after first reg clears `unlock_code` |
| Request flow | No 1.3 / both option |

## Related files

| Area | Path |
|------|------|
| Dynamic registration | `settings/key/auto.php`, `settings/key/auto_common.php` |
| Self-service keys | `settings/key/index.php`, `settings/key/key-detail.php`, `settings/key/requests.php` |
| Admin keys | `admin/key/key-add.php`, `admin/key/key-detail.php`, `admin/key/key-util.php` |
| Launch matching | `lib/src/Core/LTIX.php` |
| OIDC login (per `key_id`) | `lti/oidc_login.php` |
