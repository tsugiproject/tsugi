# Identity columns as `varchar(255)`

Stored 2026-11-17. This is the plan for replacing `TEXT` plus a SHA-256 companion
with `varchar(255)` on the LTI identity columns. The length checks are shipped.
No column type has changed.

The length and ASCII results are also listed on Admin → Client ID Check.
See [client-id-check.md](client-id-check.md).

## Why the values are `TEXT` with a hash

These tables are `DEFAULT CHARSET=utf8` (utf8mb3). A full unique index could not
hold the raw strings.

InnoDB's old `COMPACT` row format limited an index to 767 bytes. One
`varchar(255)` in utf8 is 765 bytes, so it fit. Two of them did not. `TEXT`
cannot be the full unique key either. The stored value stayed `TEXT`, and a
`CHAR(64)` column held `lti_sha256()` of that value (`hash('sha256', $val)` in
`lib/src/Util/U.php`). Lookups and unique keys use the hash.

MySQL 8, Aurora MySQL, and MariaDB 10.2 or newer use InnoDB `DYNAMIC`, whose
index limit is 3072 bytes. Two `varchar(255)` columns are 1530 bytes in utf8
and 2040 bytes in utf8mb4. Three utf8 columns are 2295 bytes. Three utf8mb4
columns are 3060 bytes, which fits and leaves little room. A unique index on
the real columns is legal on those servers. The hash columns can stay until a
later pass drops them.

The usual collation is case-insensitive. The stored characters keep their case.
A second row that differs only by case is rejected. A trailing slash stays a
different value, and `http` stays different from `https`. In strict mode an
insert or an `ALTER` to `varchar(255)` fails when a value is longer than 255
characters. It does not cut the value.

## Every value stored as `TEXT` with a SHA-256 companion

| Table | Value | Hash | Unique key |
|-------|--------|------|------------|
| `lti_key` | `key_key` | `key_sha256` | `(key_sha256, deploy_sha256)` |
| `lti_key` | `deploy_key` | `deploy_sha256` | `(key_sha256, deploy_sha256)` |
| `lti_key` | `lms_issuer` | `lms_issuer_sha256` | none. Launch matches this hash. The column is not indexed. |
| `lti_context` | `context_key` | `context_sha256` | `(key_id, context_sha256)` |
| `lti_link` | `link_key` | `link_sha256` | `(link_sha256, context_id)` |
| `lti_content` | `resource_link_id` | `resource_link_sha256` | `(context_id, resource_link_sha256)` |
| `lti_user` | `user_key` | `user_sha256` | `(key_id, user_sha256)` |
| `lti_user` | `subject_key` | `subject_sha256` | `(key_id, subject_sha256)` |
| `lti_service` | `service_key` | `service_sha256` | `(key_id, service_sha256)` |
| `tsugi_string` | `string_text` | `string_sha256` | `(domain, string_sha256)` |

`lti_content.resource_link_sha256` is `lti_sha256(resource_link_id)`. When a
grade column is created, that hash is the same value as `lti_link.link_sha256`.

`lti_key.lms_client` is `TEXT` and has no hash column. Launch matches
`lms_issuer_sha256` plus the client id text.

`lti_key.secret` and `lti_key.new_secret` are `TEXT` and have no hash.
`lti_context.lti13_deployment_id` is `TEXT` and has no hash. `lti_link` has no
deployment id column, and one will not be added. `lti_domain.consumer_key` and
`lti_domain.secret` are `TEXT` and have no hash. `lti_tool_registration.lti11_secret` is `TEXT` and has
no hash. `lti_result.sourcedid` is `TEXT` and has no hash.

`lti_tool_registration_token.token_sha256` is a different use. The raw token is
not stored. The hash is the lookup key.

`lti_user`, `lti_service`, and `tsugi_string` use the same hash trick. They are
not part of the `varchar(255)` change below. User ids and service endpoints were
not in the length survey. `tsugi_string` holds translation text.

## Columns this change would narrow

These are the LTI identity values a `varchar(255)` column would have to hold.
The length check already reads them.

| Value | Columns |
|-------|---------|
| Issuer | `lti_key.lms_issuer` |
| Client id | `lti_key.lms_client` |
| Deployment id | `lti_key.deploy_key`, `lti_context.lti13_deployment_id` |
| Context id | `lti_context.context_key` |
| Resource link id | `lti_link.link_key`, `lti_content.resource_link_id` |
| LTI 1.1 consumer key | `lti_key.key_key`, `lti_domain.consumer_key` |
| Secret | `lti_key.secret`, `lti_key.new_secret`, `lti_domain.secret`, `lti_tool_registration.lti11_secret` |

Already `varchar(255)`, so the check does not read them:
`lti_tool_registration.lti13_client_id`, `lti_tool_registration.lti11_key`, and
`lti_tool_deployment.deployment_id`.

A unique index on the real columns sits beside the hash unique for that pair.
`lti_key` has `UNIQUE(lms_issuer, lms_client, deploy_key)`. Launch matches
issuer and client id and does not use `deploy_key`. The upgrade adds that
unique only when `lms_issuer`, `lms_client`, and `deploy_key` are already
`varchar(255)` ascii. A value that is too long or outside ASCII leaves the
column unchanged and leaves the unique off. A `NULL` in any of those three
columns stays repeatable, including a NULL deployment id. When the columns
are already ascii, the unique is added only when every non-NULL triple is
already unique.
`lti_context.lti13_deployment_id` is not unique: many courses share one
deployment.

## What other LMS systems and libraries store

Checked November 2026. Lengths below are the columns those projects declare, or
the value they send when they do not store one.

### Issuer, client id, and deployment id

| System | What they store |
|--------|-----------------|
| Canvas (`instructure/canvas-lms`) | The LTI 1.3 client id in a launch is the developer key id, a decimal string, not a long token. `developer_keys.api_key` is `varchar(255)`. The issuer is the configured `lti_iss` URL, for example `https://canvas.instructure.com`. |
| Moodle (`moodle/moodle`, `public/mod/lti`) | `lti_types.clientid` is `char(255)` and unique. `new_clientid()` returns `random_string(15)`. The JWT `iss` is `$CFG->wwwroot`. That setting lives in `config.value`, which is `TEXT`, so Moodle does not cap the issuer at 255. The value it sends is the site URL. |
| Saltire (`celtic-project/LTI-PHP`, `sql/lti4-tables-mysql.sql`) | `lti2_consumer.platform_id`, `client_id`, and `deployment_id` are `varchar(255)`. Charset utf8. Unique on `(platform_id, client_id, deployment_id)`. |
| Atomic Jolt (`atomicjolt/atomic_lti`) | Rails 6.1 `t.string`, which is `varchar(255)`, for `iss`, `client_id`, and `deployment_id`. Unique on `(client_id, iss)` for an install, and on `(deployment_id, iss)` for a deployment. No hash columns. |
| `csev/pylti1.3` | `issuer` and `client_id` are `CharField(max_length=255)`, unique together. `deployment_ids` is a JSON `TextField` and is not part of that unique key. |

### Context id and resource link id

| System | Context id | Resource link id |
|--------|------------|------------------|
| Canvas | `lti_context_id` is `varchar(255)` and unique on accounts, courses, users, and groups. Assignments use a Rails string, which is `varchar(255)`, and that column is unique. Schema: `db/migrate/20101210192618_init_canvas_db.rb`. | `lti_resource_links.resource_link_uuid` is a UUID column, 36 characters, and unique. `lti_1_1_id` on that table is a string (`varchar(255)`). The launch sends the UUID, or the context id when there is no resource link row. |
| Moodle | No context-id column. The launch claim is the course primary key, an integer. | No `resource_link_id` column in the LTI schema. The claim is the activity primary key, an integer, unless the in-memory tool instance already has one set. `lti.resourcekey` and `lti.password` (the LTI 1.1 consumer key and secret) are `char(255)`. |
| Saltire | `lti2_context.lti_context_id` is `varchar(255) NOT NULL`. | `lti2_resource_link.lti_resource_link_id` is `varchar(255) NOT NULL`. `lti_user_id` is also `varchar(255)`. `lti_result_sourcedid` is `varchar(1024)`, so Saltire does not cap every LTI string at 255. |
| Atomic Jolt | `atomic_lti_contexts.context_id` is `varchar(255)`, unique with deployment id and issuer. | Not stored. The library reads it from the launch token. |
| `pylti1.3` | Not stored. | Not stored. |

## The length check

Shipped before any `ALTER`. `CHAR_LENGTH` counts characters. A blank value is
short, so it is absent. Deleted rows are included. A missing column is skipped.
`lti_link` has no deployment id column, and one will not be added.

Admin → Client ID Check lists the field, the key, the row, the length, and the
first 80 characters. A secret is reported by length only. The page says "(not
shown)" and the query does not select the secret text. The admin console and the
tenant key list show a red banner when any value is longer than 255 characters.
Upgrade prints one line per value and stays silent when every value fits:

```text
Value longer than 255: field=Issuer table=lti_key column=lms_issuer key_id=… row_id=… length=…
```

The first check, issuer, client id, and deployment id, shipped in #374. Both
production hosts ran that upgrade. Every site that finished printed no
"Value longer than 255" line. Context id, resource link id, consumer key, and
secret shipped in #375. Those columns have not been read from production yet.

## Characters outside ASCII

Same columns as the length check. No character set has changed.

These columns are utf8. A character outside ASCII takes more than one byte, so
`LENGTH` is greater than `CHAR_LENGTH`. An `ascii` column cannot store that
character. `ascii_bin` would keep case distinct if these identifiers move to
`ascii`. A blank value is one byte per character, so it is absent. Deleted rows
are included. A missing column is skipped.

Admin → Client ID Check lists the field, the key, the row, the character
length, the byte length, and a short sample around the first character outside
ASCII. A secret is reported by its lengths only. The page says "(not shown)"
and the query does not select the secret text. The admin console and the tenant
key list show a red banner when any value contains a character outside ASCII.
Upgrade prints one line per value and stays silent when every value is ASCII:

```text
Value outside ASCII: field=Issuer table=lti_key column=lms_issuer key_id=… row_id=… length=… bytes=…
```

