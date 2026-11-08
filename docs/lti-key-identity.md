# LTI key identity

How an `lti_key` row becomes a tenant, and which values have to be unique
before a launch can find exactly one row.

Related: [issuer_migration.md](issuer_migration.md),
[lti_key_self_service.md](lti_key_self_service.md).

## Draft rows

The admin add form requires a title. Every other field can be blank. Blank
`key_key`, `lms_issuer`, `lms_client`, and `deploy_key` are stored as `NULL`,
and their `*_sha256` columns are `NULL` too. A hash of the empty string would
take a slot in a unique index, so it is not stored.

A draft does not launch. That is expected. Fill in an OAuth consumer key, or
an issuer and a client id, when the row should start receiving launches.
Dynamic registration is the usual way to fill in the LTI 1.3 side.

Titles are not unique. Many drafts can share a title.

## The two identities

A row is launchable when it has one of these:

| Identity | Columns | Lookup |
|----------|---------|--------|
| LTI 1.1 | `key_key` / `key_sha256` | `LTIX::loadAllData()` matches `key_sha256` |
| LTI 1.3 | `lms_issuer` / `lms_issuer_sha256` and `lms_client` / `lms_client_sha256` | `lms_issuer_sha256` equals the issuer hash, and `lms_client` equals the client id |

`deploy_key` and `deploy_sha256` are stored when you or the LMS supply a
deployment id. They are not part of either identity. A launch still reads the
deployment id from the JWT for services such as grading. A second deployment
id does not make a second copy of the same issuer and client id safe: both
rows would match the same launch, and the lower `key_id` would win.

MySQL `UNIQUE` allows many `NULL`s. These indexes use that:

- `lti_key_const_1` on `key_sha256`
- `lti_key_const_3` on `(lms_issuer_sha256, lms_client_sha256)`

A draft with either half of the issuer/client pair still blank does not
collide. A finished consumer key can exist once. A finished issuer and client
id pair can exist once. The same row may carry both a consumer key and an
issuer/client pair.

`lms_client` is `TEXT`, so the unique key uses `lms_client_sha256` rather than
the text itself. The hash is SHA-256 of the stored client id. Launch lookup
compares the stored `lms_client` string.

A row with `deleted = 1` still holds its hashes. Launch lookup skips deleted
rows. The unique indexes do not. Clear the identity columns, or delete the
row, before reusing that consumer key or issuer and client id.

## Where the checks run

Admin create and update (`key-add.php`, `key-detail.php`) call
`AdminService::validate_key_details()` after blank fields are normalized.
There is no rule that a row must already have a consumer key, a deployment id,
or an issuer and client id. The method rejects a second copy of a consumer
key, and a second copy of an issuer and client id, including when a deployment
id is filled in.

`DynamicRegistration::run()` learns the client id only after the LMS accepts
the registration. Before it writes the key, it calls
`AdminService::issuer_client_conflict()`, excluding this `key_id`. On a hit it
writes nothing and leaves the unlock code in place. The page names the key
that already owns the pair. The LMS may already have created a client; Tsugi
does not attach that same issuer and client id to a second tenant.

The unlock code is cleared only after the issuer, client, and optional
deployment id have been stored. A failed LMS call or a rejected pair leaves
the draft retryable.

`LTI13::jonPostel()` can still require a `deployment_id` claim on the launch
JWT unless `$CFG->jon_postel` is set. That check is on the token. The key row
does not need a deployment id for the tenant to be found.

## Upgrade

`admin/upgrade.php` adds `lms_client_sha256`, fills hashes for non-blank
issuer and client values, and sets hashes back to `NULL` when the source text
is blank. It then replaces `UNIQUE(key_sha256, deploy_sha256)` with
`UNIQUE(key_sha256)` and adds `UNIQUE(lms_issuer_sha256, lms_client_sha256)`.

If existing rows already share a non-null consumer-key hash, or a non-null
issuer and client hash pair, upgrade prints those `key_id` values and does not
add that index. The old index is left in place when the new one cannot be
added. Clear or merge the listed rows and run upgrade again.

The admin key list repeats the hash backfill so a blank value cannot pick up
a hash of `""` between upgrades.
