# `lti_client` design

Stored 2026-11-15. This is the plan for splitting an LTI 1.3 platform registration
off `lti_key`. Step 1 is shipped. Later steps are the agreed model and are not
implemented.

Related: [issuer_migration.md](issuer_migration.md) records why the old `lti_issuer`
table was removed. That table was one row per issuer. This table is one row per
`(issuer, client id)`.

## Why

An `(issuer, client id)` pair is one tool registration on the LMS. Canvas uses one
issuer, many client ids, and many deployment ids. Tsugi used to store that
registration on `lti_issuer` and point several keys at it. That grain was wrong, so
the registration columns moved onto each `lti_key` (`lms_issuer`, `lms_client`,
`lms_oidc_auth`, `lms_keyset_url`, `lms_token_url`, `lms_token_audience`).

The tenant is still the key. Courses, users, links, and grades hang off `key_id`.
A deployment is one tenant. The registration URLs are shared by every deployment of
that client, so they belong on a row that those keys can point at.

`lti_key` stays the tenant. `lti_client` becomes the registration.

## Tables

### `lti_client`

One row per platform registration. Unique on `(lti13_issuer, lti13_client_id)`.

| Column | Source today | Role |
|--------|----------------|------|
| `lti13_issuer` | `lti_key.lms_issuer` | `iss` from the launch. Stored and compared as received. |
| `lti13_client_id` | `lti_key.lms_client` | OAuth client id. This is also the launch `aud`. Stored and compared as received. |
| `lti13_oidc_auth` | `lti_key.lms_oidc_auth` | OIDC authentication URL. |
| `lti13_keyset_url` | `lti_key.lms_keyset_url` | Platform public-key URL. |
| `lti13_token_url` | `lti_key.lms_token_url` | Access-token URL. |
| `lti13_token_audience` | `lti_key.lms_token_audience` | Audience on the client-credentials token call. Distinct from the launch `aud`. |

On the day rows are copied, the four URL columns are stored as received. Issuer
and client id are stored as received too. Scheme and host case is ignored when
comparing two URLs. The path, query, and fragment are compared as stored, so a
path that differs only by case is a conflict and stays two values. The diagnostic
in step 1 does not rewrite `lti_key`.

Issuer strings that differ only by a trailing slash or by `http` versus `https`
stay two clients. The conflict check does not fold them, and the copy does not
either.

The foreign key on `lti_key` is `lti_client_id`. The name `client_id` already means
the OAuth client id.

Keyset cache (`lms_cache_keyset`, `lms_cache_pubkey`, `lms_cache_kid`) stays on
`lti_key` until a later step moves it onto the client. `deploy_key` stays on the
key.

A registration can exist before any deployment does. Defaults that a first launch
copies onto the new key live on the client: owner, title, `plus`, and timezone.
There is no key yet to hold them.

### `lti_key`

The tenant. Three optional identities:

- LTI 1.1 consumer (`key_key`, later thought of as `lti11_consumer`)
- `lti_client_id`
- deployment id (`deploy_key`)

Legal rows:

| Kind | Consumer | Client | Deployment |
|------|----------|--------|------------|
| LTI 1.1 only | set | null | null |
| LTI 1.3 deployment | null | set | a real deployment id |
| 1.1 key bound to its first 1.3 deployment | set | set | a real deployment id |
| Draft, before registration finishes | null | null | null |

A 1.3 key has a real deployment id. There is no `(client, null deployment)` row.
A missing `lti_key` row means that deployment has not been created yet.

The `lti1p1` migration claim finds the existing 1.1 key and updates it with the
client and deployment. Courses and users stay on that `key_id`. Binding that
existing key does not mark it pending.

MySQL treats `NULL` as distinct inside a unique index, so one `UNIQUE` of three
nullable columns does not enforce this. Two unique indexes:

- the consumer hash, when the consumer is set
- `(lti_client_id, deployment)`, when both are non-null

A check constraint: deployment is null, or the client is non-null. A blank
deployment is a missing value. It is not a catch-all.

## Launch and registration

Launch authenticates against `lti_client`: issuer, client id, OIDC URL, token URL,
token audience, and the platform keyset. After the JWT is valid, the deployment id
selects the key.

Exact `(lti_client_id, deployment id)` wins. A deployment with no key is inserted.
Each new deployment is its own tenant. There is no catch-all row to copy, and a
second key is not created because some other deployment of the same client already
exists.

Dynamic registration that returns a deployment id writes the `lti_key` row then.
Registration that returns no deployment id writes only `lti_client`. The first
valid launch inserts the key and copies owner, title, `plus`, and timezone from
the client.

Today `lti/oidc_login.php` loads `lti_key` by the numeric `key_id` in
`/lti/oidc_login/{key_id}`, and dynamic registration stores that URL as
`initiate_login_uri`. The login URL becomes per client. Login resolves the client.
The launch `id_token` picks the key. The new path is not chosen yet.

`$LAUNCH->client` is the registration. Launch-row aliases (`lms_oidc_auth`,
`lti13_token_url`, and the other URL fields callers already read) are copied from
the client so `ltiParameter()` stays. `$LAUNCH->key` readers for those fields
delegate to the client when the client is set. The `Key` class does not expose the
URL fields today.

## Tollbooth, later and optional

A property on `lti_client` decides whether a newly inserted key starts `pending`.
LTI 1.1 keys have no client, so a site default covers those. Turning the switch
on or off leaves existing keys as they are.

While a key is pending, the launch still authenticates. The iframe shows a card
form to instructors and a not-activated message to students. Course, user, and
grade rows are not written. Payment clears `pending` on that same `key_id`.

Binding an existing 1.1 key to its first deployment does not set `pending`. A
separate property can turn the same gate on for a new 1.1 key.

## Rows that already have a blank deployment

Today a blank `deploy_key` is a real tenant when that key already has users or
courses. It is not an uncreated row. The migration pins the next launch's
deployment id onto that row. It does not insert a second key.

An empty shell with an issuer and client id and a blank deployment can be dropped.

The local database has one such tenant: key 5, title "PY4E store", issuer
`http://localhost:8080`, client `be929f3f-96ad-46e3-aacb-2a522f7c9e47`, blank
`deploy_key`, three users, no contexts. That pin is deferred. The extract step
leaves deployment matching unchanged, so this row keeps working as it does now.

## Step 1: find URL conflicts (shipped)

No new table and no data change. Shipped in
[#361](https://github.com/tsugiproject/tsugi/pull/361) as `1bd332712`.

Two checks, both read-only:

1. **Duplicate identity**, already present. Keys that share
   `(client id, issuer, deployment id)`. Admin → Client ID Check still lists
   them. `php admin/upgrade.php` still prints
   `Duplicate (client id, issuer, deployment id): ...` and stays silent when
   there are none.
2. **Configuration URLs**, new. Keys that share `(issuer, client id)` and disagree
   on a URL that would collapse onto one `lti_client` row. The columns are
   `lms_oidc_auth`, `lms_keyset_url`, `lms_token_url`, and `lms_token_audience`.
   Cache columns and `deploy_key` are not part of this check.

A blank value next to a filled value is not a conflict. The filled value is the
one the later copy keeps. Two different non-blank values are a conflict. Scheme
and host case does not count. Path, query, and fragment case does. These columns
are `utf8mb3_general_ci`, which would hide a path that differs only by case, so
the SQL prefilter is
`COUNT(DISTINCT CAST(NULLIF(TRIM(column), '') AS BINARY)) > 1`. The PHP pass
then folds only the scheme and host. Accent differences still count.

The Client ID Check page says, when this second check is clean:

> There are no (client id, issuer) rows with conflicting configuration URLs.

When it is not, the page names the issuer, the client id, the problematic fields,
and each key. Upgrade prints one line per key:

```text
Conflicting configuration URLs: client_id=… issuer=… key_id=… OIDC auth URL=… …
```

`error_log` and `echo` both run, so cron shows each line twice. A clean database
prints neither the duplicate line nor the conflict line. The deployment-id line
is also silent when every key has one deployment id. Deleted keys are included.
A soft-deleted row can still block the collapse.

A pair that passes this check can share one client row. The check is the gate
before any later copy.

### Deployment ids that would be read from the key

No data change. Before a deployment id stored beside a key can be dropped, that
`key_id` must map to one deployment id. The check reads `lti_key.deploy_key`
and `lti_context.lti13_deployment_id` together. `lti_link` has no deployment id
column, so there is nothing there to collapse. `lti_tool_deployment.deployment_id`
is an outbound tool id. One key may have many of those, and they are not part
of this check.

A blank value, or the literal `null`, does not count. Two different non-blank
values for one key are a conflict. That includes the key's own deployment id, so
a context that disagrees with the key is a conflict. Case counts. Deleted rows
are included.

Admin → Client ID Check names the key, each deployment id, and where it was
seen (the key, or how many contexts, with one example id). Upgrade prints one
line per key and stays silent when every key has a single deployment id:

```text
Non-unique (key id, deployment id): key_id=… deployment_id=… (Key) deployment_id=… (Context x3 example 12)
```

The page says, when this check is clean:

> There are no keys with more than one deployment id.

### What production said

Both production hosts pulled `1bd332712` and ran upgrade. Every site that
finished the LTI upgrade printed no conflict line. The only duplicate identity
is on `sakai.tsugicloud.org`: keys 127 and 128, issuer
`https://dev1.sakaicloud.com`, client `9eecf4e1-7255-473c-85a6-5d211109ab58`,
deployment `1`. Those two also agree on the four URLs, so one client row can
hold the registration. Folding the two `key_id`s together is a separate question,
because courses and users were not compared.

`/var/www/html/tsugi` on both hosts died in `admin/upgrade.php` on a missing
`config.php`. That tree never ran the check. The `old-*.dig4e.com` trees printed
an upgrade header and no table checks.

## Step 2: extract the client (next)

Create `lti_client`. Copy each distinct `(lms_issuer, lms_client)` into it, keeping
the four URLs as stored. Point `lti_key.lti_client_id` at that row. Do this only
when step 1 reports no conflicts for that pair. Where one key has a URL and
another has a blank, keep the filled value.

Leave the `lms_*` columns in place. Launch reads the URLs from the client and
still copies them onto the launch-row aliases. Dynamic registration writes the
issuer, client id, and URLs to `lti_client`. It also keeps writing `lms_issuer`,
`lms_issuer_sha256`, and `lms_client` on `lti_key`. `LTIX::loadAllData()` still
matches the hash and the client id, so a new registration has to keep those
columns on the key or the launch will not find it.

Deployment matching stays as it is. `LTIX::loadAllData()` matches
`lms_issuer_sha256` and `lms_client`, ignores `deploy_key`, and takes the lowest
`key_id` (`ORDER BY k.key_id ASC LIMIT 1`). A blank `deploy_key` still matches
any deployment. This step does not drop columns and does not change that query.

## Later

These wait until step 2 is in and the alias readers are the only remaining use
of the URL columns on the key.

1. **Deployment lookup.** After the JWT is valid, select the key by
   `(lti_client_id, deployment id)`. Insert a key when that pair is missing.
   Stop treating a blank `deploy_key` as a match for every deployment. Pin a
   blank-deployment key that already has users or courses to the deployment on
   the next launch. Drop empty shells.
2. **Login URL.** Identify the client in `initiate_login_uri`. Login loads the
   client. The `id_token` loads the key.
3. **Cache.** Move `lms_cache_keyset`, `lms_cache_pubkey`, and `lms_cache_kid`
   onto `lti_client`.
4. **Drop the copied columns** from `lti_key` once nothing reads them there.
5. **Uniqueness.** Add the consumer unique index, the
   `(lti_client_id, deployment)` unique index, and the check that a deployment
   implies a client.
6. **Tollbooth.** The optional `pending` property above.

## Related files

| File | Role |
|------|------|
| `lib/src/Services/Admin/AdminService.php` | `clientConfigColumns()`, `clientConfigConflictGroups()`, `keyDeploymentConflictGroups()`, and the existing duplicate-identity queries |
| `lib/src/Controllers/templates/Admin/key/client-id-check.php` | Admin page for the three checks |
| `lib/src/Services/Lti/database.php` | Upgrade notices for the three checks. `lti_key` schema. No `lti_client` table yet |
| `lib/src/Core/LTIX.php` | Launch lookup by issuer and client id, lowest `key_id` |
| `lti/oidc_login.php` | Login by `key_id` in the URL |
| `lib/src/Services/Settings/DynamicRegistration.php` | Writes issuer, client id, URLs, and an optional deployment onto the draft key |
