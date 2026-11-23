# Client ID Check

Stored 2026-11-22. Admin → Client ID Check reads `lti_key` and reports.
`php admin/upgrade.php` prints the same reports. It also adds
`UNIQUE(lms_issuer, lms_client, deploy_key)` when that alter can succeed.

The registration lives on the key: `lms_issuer`, `lms_client`, `lms_oidc_auth`,
`lms_keyset_url`, `lms_token_url`, and `lms_token_audience`. `deploy_key` is the
deployment id. `LTIX::loadAllData()` matches `lms_issuer_sha256` and
`lms_client`, ignores `deploy_key`, and takes the lowest `key_id`. A blank
`deploy_key` matches any deployment.

## Duplicate (client id, issuer, deployment id)

Keys that share `lms_client`, `lms_issuer`, and `deploy_key`. A blank deployment
is shown as `(none)`. Deleted keys are included. Upgrade prints one line per
key and stays silent when there are none:

```text
Duplicate (client id, issuer, deployment id): client_id=… issuer=… deployment_id=… key_id=…
```

`lti_key` also has `UNIQUE(lms_issuer, lms_client, deploy_key)`. Launch does
not look the key up by that triple. That unique is added only when
`lms_issuer`, `lms_client`, and `deploy_key` are already `varchar(255)`
character set ascii collation `ascii_bin` and nullable. A value longer than
255 characters, or a character outside ASCII, leaves the column unchanged, and
the unique is not added. The upgrade output and the beacon comment list both
skips. Run the upgrade again after the data is fixed. The unique is tried
only once the column step has landed. A `NULL` in any of the three columns
stays repeatable, so several `(issuer, client id, NULL deployment)` rows are
allowed before the unique and after it. The upgrade
counts only rows where all three values are non-NULL. A group with count
greater than 1 is printed, the unique is not added, and the beacon comment
says why. An empty string is a value, so two empty deployment ids can block
the unique. The report above still lists a blank deployment as `(none)`. That
report does not decide the alter.

`lti_context.lti13_deployment_id` is not unique: many courses share one
deployment.

The other uniques on `lti_key` are `UNIQUE(key_sha256, deploy_sha256)`,
`UNIQUE(key_key)`, and `UNIQUE(guid)`.

## Configuration URLs

Keys that share an issuer and a client id and disagree on `lms_oidc_auth`,
`lms_keyset_url`, `lms_token_url`, or `lms_token_audience`. Cache columns and
`deploy_key` are not part of this check.

A blank value next to a filled value is not a conflict. Two different non-blank
values are a conflict. Scheme and host case does not count. Path, query, and
fragment case does. These columns are `utf8mb3_general_ci`, which would hide a
path that differs only by case, so the SQL prefilter is
`COUNT(DISTINCT CAST(NULLIF(TRIM(column), '') AS BINARY)) > 1`. The PHP pass
then folds only the scheme and host. Accent differences still count. Deleted
keys are included.

The page says, when this check is clean:

> There are no (client id, issuer) rows with conflicting configuration URLs.

Upgrade prints one line per key:

```text
Conflicting configuration URLs: client_id=… issuer=… key_id=… OIDC auth URL=… …
```

## Deployment ids

Each key should name one deployment id. The check reads `lti_key.deploy_key`
and `lti_context.lti13_deployment_id` together. `lti_link` has no deployment id
column. `lti_tool_deployment.deployment_id` is an outbound tool id and is not
part of this check.

A blank value, or the literal word `null` in any ASCII case, does not count.
Every other value is compared as stored bytes, so a difference in case or
accents is a conflict. A context that disagrees with its key is a conflict.
Deleted rows are included.

The page says, when this check is clean:

> There are no keys with more than one deployment id.

Upgrade prints one line per key:

```text
Non-unique (key id, deployment id): key_id=… deployment_id=… (Key) deployment_id=… (Context x3 example 12)
```

## Length and characters outside ASCII

Same admin page. The columns and the rules are in
[identity-varchar.md](identity-varchar.md).

## What production said

Both production hosts ran the URL check. Every site that finished printed no
conflict line. The only duplicate identity is on `sakai.tsugicloud.org`: keys
127 and 128, issuer `https://dev1.sakaicloud.com`, client
`9eecf4e1-7255-473c-85a6-5d211109ab58`, deployment `1`. Those two agree on the
four URLs. Launch uses the lower `key_id`. Combining the two keys is a separate
question, because courses and users were not compared.

## Related files

| File | Role |
|------|------|
| `lib/src/Services/Admin/AdminService.php` | The duplicate, URL, deployment, length, and ASCII queries |
| `lib/src/Controllers/templates/Admin/key/client-id-check.php` | Admin → Client ID Check |
| `lib/src/Services/Lti/database.php` | Upgrade notices for the same checks, and the `lti_key` schema |
| `lib/src/Core/LTIX.php` | Launch lookup by issuer and client id, lowest `key_id` |
| `lti/oidc_login.php` | Login by `key_id` in the URL |
| `lib/src/Services/Settings/DynamicRegistration.php` | Writes issuer, client id, URLs, and an optional deployment onto the key |
