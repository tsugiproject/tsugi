# Deprecation and removal of the `lti_issuer` table

This document describes why Tsugi is phasing out the separate `lti_issuer` model, what
has already shipped, and what remains before the table and related code can be deleted.

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

### Legacy (being removed)

- **`lti_issuer`** — shared LMS platform configuration (`issuer_key`, `issuer_client`,
  `lti13_oidc_auth`, `lti13_keyset_url`, `lti13_token_url`, and related fields).
- **`lti_key.issuer_id`** — foreign key to `lti_issuer`.

While `issuer_id` is set on a key, launch code reads platform configuration from
`lti_issuer` and ignores `lti_key.lms_*` for that purpose.

### Target (current direction)

- **`lti_key.lms_*`** — per-key LMS configuration (`lms_client`, `lms_oidc_auth`,
  `lms_keyset_url`, `lms_token_url`, `lms_token_audience`, plus optional cache fields).
- **`lti_key.issuer_id`** — always `NULL` after migration.
- **`lti_key.lms_issuer` / `lms_issuer_sha256`** — `NULL` after migration; launch matching
  uses `lms_client` and wildcard `iss` handling in `LTIX::loadAllData()`.

The `lti_issuer` table becomes unused at runtime once all keys are migrated.

## Work already completed

Several changes landed on `master` before and during phase 1:

| Area | Change |
|------|--------|
| Launch matching | Match tenant by `(iss, client_id)` only; accept any `deployment_id` from the LMS |
| Dynamic registration | New registrations write to `lti_key.lms_*`, not `lti_issuer` |
| Admin UI | Issuer nav hidden when no live issuers exist; deprecation warnings when keys are still linked |
| New issuers | Creation of new issuers disabled on production installations (reduces risk during migration) |

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

3. **Does not modify or delete `lti_issuer` rows** — they become orphaned until a
   later cleanup phase.

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

The admin deprecation banner (linked keys warning) disappears after migration because
it counts keys with non-null `issuer_id`.

### Manual tool (still present)

`admin/key/issuer-maint.php` remains for optional manual moves and deleting unused
issuer rows. Phase 1 does not depend on it; the upgrade hook is the primary path.

## Phase 2 and beyond (planned, not yet shipped)

After production upgrades have baked for a while:

### Schema

- Drop `lti_issuer` table.
- Remove `issuer_id` column and foreign key from `lti_key`.
- Remove related unique constraints that reference `issuer_id`.

### Runtime code

Remove all runtime use of `lti_issuer`, including:

| Location | Change |
|----------|--------|
| `lib/src/Core/LTIX.php` | Remove `lti_issuer` JOIN in `loadAllData()`; key-only `lms_*` path |
| `lti/oidc_login.php` | Remove issuer-table lookup and `issuer_guid` path; key-only by `key_id` |
| `lti/oidc_launch.php` | Remove any `issuer_id` session assumptions tied to `lti_issuer` |
| `lib/src/Core/LTIX.php` | `getPlatformPublicKey()` — update only `lti_key` cache columns |

### Admin and settings

- Remove issuer admin UI (`issuers.php`, `issuer-detail.php`, `issuer-add.php`,
  `issuer-maint.php`, and related helpers).
- Remove issuer dropdown from key add/edit forms.
- Remove `$CFG->autoissuer` legacy path in `settings/key/auto_common.php`.
- Update or remove deprecation messaging in `admin/admin_util.php` once the table is
  gone.

### Documentation and cleanup

- Delete orphaned `lti_issuer` rows (or rely on table drop after confirming zero
  `issuer_id` links everywhere).
- Update this document when phase 2 ships.

## Operational notes

- **Backup** before upgrade on any installation that may have linked issuers:
  `mysqldump` of `lti_key` and `lti_issuer` is sufficient for rollback of this migration.
- **Sites that never used issuers** — `SELECT COUNT(issuer_id) FROM lti_key WHERE
  issuer_id IS NOT NULL` returns zero; upgrade is silent and unchanged.
- **Orphan issuers after phase 1** — the Issuers admin page may show `Key Count = 0` for
  all rows; that is expected. Issuer `Created At` dates reflect when the issuer was
  originally configured, not when migration ran.

## Related files

| File | Role |
|------|------|
| `admin/lti/database.php` | Phase 1 migration in `$DATABASE_UPGRADE` |
| `admin/admin_util.php` | Deprecation stats and admin warnings |
| `admin/key/issuer-*.php` | Legacy issuer admin UI (to be removed) |
| `lib/src/Core/LTIX.php` | Launch data load; issuer JOIN to be removed |
| `lti/oidc_login.php` | OIDC login; issuer lookup to be removed |
| `settings/key/auto_common.php` | Dynamic registration; `$CFG->autoissuer` to be removed |
