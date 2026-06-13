# Deprecation and removal of the `lti_issuer` table

This document describes why Tsugi is phasing out the separate `lti_issuer` model, what
has shipped, and what remains before the table and related schema can be deleted.

Tracking issue: [tsugiproject/tsugi#226](https://github.com/tsugiproject/tsugi/issues/226)

## Background

Tsugi originally introduced an `lti_issuer` table when multi-tenant LMS platforms were
less well understood. The idea was to separate common LMS configuration (issuer URL,
JWKS URL, authorization endpoints, and so on) from individual tool registrations
(`lti_key` rows), so multiple tenant keys could share one issuer definition.

In practice this turned out to be the wrong abstraction.

Even in Canvas, an `(iss, client_id)` pair is effectively a single LMS tool registration.
If Tsugi runs as a centralized SaaS platform, scaling is handled through
`deployment_id`, not by sharing one issuer across many unrelated keys. Canvas and other
LMS platforms also mint new `deployment_id` values freely, so requiring every deployment
to be pre-configured before launch is impractical.

Tsugi now treats `(iss, client_id)` as the real registration identity. Each `lti_key`
row holds its own `lms_*` columns for platform endpoints. The `deployment_id` from the
launch JWT is a runtime value (wildcard `deploy_key` on the key accepts any deployment
after the first successful launch).

The old `lti_issuer` table added complexity and fragile launch behavior when one issuer
was linked to multiple keys, without enough benefit to justify keeping it.

## Data model (legacy vs target)

### Legacy (removed at runtime)

- **`lti_issuer`** — shared LMS platform configuration (`issuer_key`, `issuer_client`,
  `lti13_oidc_auth`, `lti13_keyset_url`, `lti13_token_url`, and related fields).
- **`lti_key.issuer_id`** — foreign key to `lti_issuer`.

While `issuer_id` was set on a key, launch code read platform configuration from
`lti_issuer` and ignored `lti_key.lms_*` for that purpose.

### Target (current)

- **`lti_key.lms_*`** — per-key LMS configuration (`lms_client`, `lms_oidc_auth`,
  `lms_keyset_url`, `lms_token_url`, `lms_token_audience`, plus cache fields).
- **`lti_key.issuer_id`** — always `NULL` after migration (column remains until phase 3).
- **`lti_key.lms_issuer` / `lms_issuer_sha256`** — `NULL` after migration; launch matching
  uses `lms_client` and wildcard `iss` handling in `LTIX::loadAllData()`.

The `lti_issuer` table is unused at runtime once keys are migrated and this code ships.

## Work already completed

| Area | Change |
|------|--------|
| Launch matching | Match tenant by `(iss, client_id)` only; accept any `deployment_id` from the LMS |
| Dynamic registration | Always writes to `lti_key.lms_*`, never `lti_issuer` |
| Admin UI | Issuer admin pages removed; key forms are `lms_*` only |
| Runtime code | Key-only path in `LTIX`, `oidc_login.php`, `getPlatformPublicKey()` |
| Deprecation alerts | Removed from admin console and upgrade output |

## Phase 1: Automatic migration (shipped)

Implemented in `admin/lti/database.php` inside `$DATABASE_UPGRADE`. Runs during the
normal database upgrade (`admin/upgrade.php`).

### What it does

For every non-deleted `lti_key` row with a non-null `issuer_id` linked to a live
`lti_issuer` row:

1. **Clears issuer linkage on the key**
   - `issuer_id = NULL`
   - `lms_issuer = NULL`
   - `lms_issuer_sha256 = NULL`

2. **Copies LMS endpoint fields onto the key** using the same precedence as launch
   while `issuer_id` was set: **issuer value wins**, then existing key value, then
   `NULL`:
   - `lms_client` ← `issuer_client`
   - `lms_oidc_auth` ← `lti13_oidc_auth`
   - `lms_keyset_url` ← `lti13_keyset_url`
   - `lms_token_url` ← `lti13_token_url`
   - `lms_token_audience` ← `lti13_token_audience`

3. **Does not modify or delete `lti_issuer` rows** — they become orphaned until
   schema cleanup.

Multi-key issuers (one issuer linked to several keys) are migrated the same way; the
upgrade logs a warning per issuer with more than one linked key.

### Behavior

- **Idempotent** — after the first successful pass, keys no longer match the SELECT
  (`issuer_id IS NOT NULL`), so later upgrades are silent.
- **Output** — migration messages appear only when rows still need processing; failures
  are always logged.

### Verification

Before upgrade (linked keys):

```sql
SELECT COUNT(*) FROM lti_key
WHERE issuer_id IS NOT NULL AND issuer_id > 0
  AND (deleted IS NULL OR deleted = 0);
```

After upgrade (should be zero):

```sql
SELECT COUNT(*) FROM lti_key WHERE issuer_id IS NOT NULL;
```

Sample migrated key:

```sql
SELECT key_id, issuer_id, lms_issuer, lms_issuer_sha256,
       lms_client, lms_oidc_auth, lms_keyset_url
FROM lti_key
WHERE key_id = ?;
```

Expect `issuer_id`, `lms_issuer`, and `lms_issuer_sha256` to be `NULL`, with endpoint
fields populated.

## Phase 2: Runtime and admin cleanup (shipped)

After phase 1 migrations have run in production, this phase removes all runtime and
admin dependence on `lti_issuer`.

### Runtime code

| Location | Change |
|----------|--------|
| `lib/src/Core/LTIX.php` | `loadAllData()` — no `lti_issuer` JOIN; match keys by `lms_client` + wildcard `iss`; always map `lms_*` → launch fields |
| `lib/src/Core/LTIX.php` | `getPlatformPublicKey()` — updates only `lti_key.lms_cache_*` columns |
| `lti/oidc_login.php` | Key-only lookup by numeric `key_id` in the login URL; no `issuer_guid` / issuer-table path |
| `lti/oidc_launch.php` | No `issuer_id` session; key-only public key refresh |

OIDC login URLs must use the tenant key id: `/lti/oidc_login/{key_id}` (as dynamic
registration already does via `initiate_login_uri`).

### Admin and settings

- Removed issuer admin UI (`issuers.php`, `issuer-detail.php`, `issuer-add.php`,
  `issuer-maint.php`, and related helpers).
- Removed issuer dropdown from admin key add/edit forms.
- Removed `$CFG->autoissuer` legacy path in `settings/key/auto_common.php`.
- Removed deprecation messaging from `admin/admin_util.php`, `admin/index.php`, and
  `admin/upgrade.php`.

### Rollout note

Deploy phase 2 only **after** phase 1 has migrated all linked keys on that installation.
Sites with remaining `issuer_id` links will fail LTI 1.3 launch until upgraded.

## Phase 3 and beyond (planned, not yet shipped)

After production has baked on phase 2:

### Schema

- Drop `lti_issuer` table.
- Remove `issuer_id` column and foreign key from `lti_key`.
- Remove related unique constraints that reference `issuer_id`.

### Documentation and cleanup

- Delete orphaned `lti_issuer` rows (or rely on table drop after confirming zero
  `issuer_id` links everywhere).
- Update this document when phase 3 ships.

## Operational notes

- **Backup** before upgrade on any installation that may have linked issuers:
  `mysqldump` of `lti_key` and `lti_issuer` is sufficient for rollback of phase 1.
- **Sites that never used issuers** — `SELECT COUNT(issuer_id) FROM lti_key WHERE
  issuer_id IS NOT NULL` returns zero; phase 1 upgrade is silent and unchanged.
- **Orphan issuers after phase 1** — leftover `lti_issuer` rows are inert once keys are
  migrated and phase 2 code is deployed; they can be dropped in phase 3.

## Related files

| File | Role |
|------|------|
| `admin/lti/database.php` | Phase 1 migration in `$DATABASE_UPGRADE` |
| `lib/src/Core/LTIX.php` | Launch data load; key-only `lms_*` path |
| `lti/oidc_login.php` | OIDC login; key-only by `key_id` |
| `settings/key/auto_common.php` | Dynamic registration; writes `lti_key.lms_*` |
