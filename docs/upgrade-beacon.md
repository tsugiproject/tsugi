# Upgrade beacon

Stored 2026-11-19. The client is on the `upgrade-beacon` branch. After
`admin/upgrade.php` finishes migrations it POSTs one short JSON body to
tsugi.org. A failed post does not fail the upgrade. The receiver is the
other half of this work.

The client code is `tsugi_admin_upgrade_report()` and the helpers under it
in `admin/upgrade.php`.

## Why

The maintainer needs to see who runs a database upgrade. The post names the
site, the owner from config, the Tsugi version, one database version, and
the PHP and MySQL that ran the upgrade. The comment carries the client id
check as counts, so a site that is not ready for the `varchar(255)`
identity change shows up without a row dump.

No password, database user, DSN, API key, or user row is included. There is
no scale data (no `COUNT(*)` of users, courses, keys, or links). There is no
Google login flag and no key-request flag. There is no install path, no
plugin list, and no `database.php` script name.

## When it runs

The post is the last step of `admin/upgrade.php`, after every `database.php`
migration. The upgrade page says whether the post was accepted. When the beacon is
off, that page says `Beacon disabled - to manually send a beacon type php upgrade.php beacon`. A network
error, a non-2xx response, or a bug in the beacon is logged and the upgrade
result stays what the migrations already did.

`php upgrade.php beacon` runs the upgrade, then posts the beacon at
the end even when the automatic post is turned off. A string in
`$CFG->upgrade_report_url` is still the target. When that setting is not a
string, this command uses `https://www.tsugi.org/api/beacon/upgrade`. The
comment is the client id counts. `php upgrade.php` with no argument
still skips the post when the setting is off.

The administration console has **Send Beacon**. That page shows the
JSON and the URL, then a button posts it. It does not run an upgrade. It
also sends when the automatic post is turned off. The comment box is
optional. A blank comment is `Sent by` plus the email of the logged-in
account. When that email is missing, the comment is `Sent from the admin
console`.

The request is unauthenticated. There is no auth header and no signature
header. The body is the record. Connect timeout is 5 seconds. Total timeout
is 10 seconds. `Net::doBody()` is not used because it has no timeout.

TLS follows `Net::$VERIFY_PEER`, the same switch as the rest of Tsugi's
outbound HTTP. The default in `lib/src/Util/Net.php` is off.

The default URL is `https://www.tsugi.org/api/beacon/upgrade`.

## Turning it off, or sending it somewhere else

`$CFG->beacon = false` skips the post.

`$CFG->upgrade_report_url` replaces the host.

- A string that starts with `http://` or `https://` is the target. A site
  root is enough. `/api/beacon/upgrade` is added when the value does not
  already end with that path.
- `null`, the default when the setting is absent, keeps
  `https://www.tsugi.org/api/beacon/upgrade`.
- `false`, or any other non-string, skips the post.

```php
$CFG->beacon = false;
$CFG->upgrade_report_url = 'http://localhost:8888/tsugi-org';
$CFG->upgrade_report_url = false;
```

## Host

`host` is the hostname only. It comes from `$CFG->wwwroot`. `$CFG->apphome`
is used when `wwwroot` has no host. The URL passes through
`tsugi_admin_upgrade_public_url()` first, which rebuilds an `http` or
`https` URL as scheme, host, port, and path and drops userinfo. The host is
then taken with `parse_url`, lowercased, and sent without scheme, port, or
path. IPv6 brackets are stripped.

`https://owner:secret@Example.EDU:8443/tsugi` is posted as `example.edu`.
A password in the URL does not appear in the JSON or in the `User-Agent`.

The `User-Agent` is `Tsugi/<TSUGI_VERSION> (<host>) PHP/<version>`. A missing
host is the word `unknown` in that header. Carriage returns and line feeds
are stripped.

A local host is `localhost`, `127.0.0.1`, `::1`, a name that starts with
`local.`, or a name that ends with `.local` or `.localhost`. The client
does not send a local flag. The receiver derives it from `host`. Store
those rows. Hide them from the default list.

## What else is sent

`servicename`, `ownername`, and `owneremail` are trimmed. Empty strings are
omitted. The limit is 200 characters. The email string that is posted is
the one that goes into the install id. Do not trim it again and do not
lowercase it.

`tsugi_version` is `TSUGI_VERSION`. `database_version` is `$CFG->dbversion`.
`php_version` is the PHP that ran the upgrade (`PHP_VERSION`).
`mysql_version` is `SELECT VERSION()`, or null when that read fails.
`timezone` is `$CFG->timezone`, or PHP's default timezone when that setting
is empty. `reported_at` stays UTC. The minimum PHP and MySQL constants are
not sent. They are the same for every checkout of a release.

`git_commit` is `HEAD` when `git` can read the checkout. `git_branch` is
the branch name, or null on a detached `HEAD` or when git cannot answer. A
zip deploy leaves both null. The client does not fetch. Git is read from
`dirroot`, but `dirroot` itself is not posted. Moving the checkout to
another directory does not mint a new id.

## Install id

`install_id` identifies one site. The same host and the same owner email
are one install, including after a directory move. A different host, or a
different owner email on the same host, is a new id. Group those rows by
`host` in the admin view.

## Comment

`comment` lists the client id checks that need fixing, and each schema step
that was skipped. No key ids, URLs, or value samples. A count of zero is
left out, including duplicate LTI 1.1 consumer keys. A column that is already
the ascii varchar is left out. A unique that is already present is left out.
An empty string means nothing in this check needs fixing and every schema
step has landed.

A column that still has a value longer than its limit, or a character outside
ASCII, stays as it is. The unique on that column is not added, and both skips
are listed. Fix the data and run the upgrade again. The unique is tried only
after the column step has landed. The upgrade output prints the same lines.

```text
duplicate (client id, issuer, deployment id) rows: 2, duplicate lti 1.1 consumer keys: 1
```

The mismatched-URL number is how many key rows sit in a conflicting issuer
and client id pair. The others are the rows or values those checks list.

When `UNIQUE(lms_issuer, lms_client, deploy_key)` is not added, the comment
says why and still includes no key ids or stored values. The same is true
for the other identity uniques. A NULL in any of the three columns is not a
duplicate for that unique. A successful add leaves the phrase out.

```text
lti_key.lms_issuer left unchanged: lms_issuer has 1 value outside ASCII
unique (issuer, client id, deployment id) not added: lms_issuer has 1 value outside ASCII
unique (key_id, user_key) not added: 2 duplicate groups
```

A failed count query does not stop the upgrade. The comment is then the
string `client id check failed`. A failure that is only the schema progress
read adds `schema progress check failed` and leaves the other counts in place.

## JSON shape

`report_version` is `1`. Require `marker` `tsugi-upgrade-beacon`. Ignore
unknown fields so a later client can add some. A nested body from the
earlier draft is rejected.

`reported_at` is UTC `Y-m-d\TH:i:s\Z`.

```json
{
  "report_version": 1,
  "marker": "tsugi-upgrade-beacon",
  "reported_at": "2026-11-18T20:05:33Z",
  "comment": "",
  "host": "learn.example.edu",
  "servicename": "Example",
  "ownername": "Pat Instructor",
  "owneremail": "pat@example.edu",
  "install_id": "9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08",
  "tsugi_version": "2025.12",
  "database_version": 202611070002,
  "php_version": "8.4.1",
  "mysql_version": "8.0.40",
  "timezone": "America/New_York",
  "git_commit": "5e3ae81573a53e0704c94d25ed77b9b8c06932b7",
  "git_branch": "upgrade-beacon"
}
```

`servicename`, `ownername`, and `owneremail` are absent when empty.
`mysql_version`, `git_commit`, and `git_branch` may be null.
`database_version` may be null when `$CFG->dbversion` is unset.

## Receiver

`POST /api/beacon/upgrade`. Return 2xx only after the row is stored. Return
400 when the body is not this JSON. Do not
require a login. Do not write the raw body to a public access log. `GET`
returns 405.

One current row per `install_id`, plus one history row per accepted post
(`reported_at`, `database_version`, git commit, git branch). The history is
how often that site runs upgrades.

The store keeps its existing columns. `host` is saved in the `wwwroot`
column. `database_version` is saved in `software_version`. `php_version` is
saved in `php`. `mysql_version` is saved in `server_version`. `timezone`
is saved in `timezone`. A missing host
is stored as an empty string so the column stays non-null. Unused columns
(`dirroot`, migration path, schema list, recorded overall) stay empty.

Admin page, maintainer only: one row per install, the latest snapshot, the
upgrade count, and `comment`. The list shows when tsugi.org stored the row.
`reported_at` and `timezone` are on the install detail page. The default
filter hides a local host. A second view groups by host.
