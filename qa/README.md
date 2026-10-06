# QA Harness (Panther)

End-to-end tests use Symfony Panther (Chrome) against a Docker Tsugi, not your MAMP site. Run every command from the repo root.

`https://local.dj4e.com/tsugi` is the wrong target. That host sends `/admin/` to the DJ4E login before the admin passphrase, so a fresh Chrome never sees the tests' unlock form. The script always opens `http://localhost:8000/tsugi` and ignores a `TSUGI_BASE_URL` already set in the shell.

## Run the suite

```bash
./qa/local-panther.sh
```

The script installs Composer dev tools, browser drivers, and recreates the Docker stack on port **8000** (MAMP keeps 8888). Inside the container, config is `docker/tsugi-docker-config.php`, not your `config.php`. The admin passphrase is `tsugi-admin`. Demo login is on, with secret `tsugi-demo`.

The first run builds the image. Later runs:

```bash
./qa/local-panther.sh --skip-composer
```

`--skip-composer` is a script flag. Put it before anything meant for PHPUnit.

## One class, and a log you can read

`--filter` matches the class or method name. This is the reliable way to run a subset. Do not pass `tests/AdminTest.php`. Those files are in `qa/tests/`, and PHPUnit looks for a path from the repo root, so that short path does not open.

```bash
./qa/local-panther.sh --filter AdminTest --testdox
```

`--testdox` prints each test name as it finishes. Admin covers the console smoke pages and one more click into each section (add forms and list rows when a row exists). It does not click upgrade, delete, expire, blob cleanup, or send mail.

A single file, if you want the path:

```bash
./qa/local-panther.sh qa/tests/ToolLaunchTest.php --testdox
```

Other classes in `qa/tests/` include `SmokeTest`, `StoreTest`, `ToolLaunchTest`, `ToolHappyPathTest`, `DemoCourseTest`, `CourseControllersTest`, `OrgAdminTest`, and `KeysetTest`. `KeysetTest` fetches `/lti/keyset.php` and checks the JWKS JSON. It does not open Chrome.

## Watch Chrome

`./qa/panther-watch.sh` opens a real Chrome window, prints each test name, and holds each new URL for 3 seconds. Panther stays headless unless `PANTHER_NO_HEADLESS=1`, which that script sets. With no arguments it runs `AdminTest`. Hold longer with `PANTHER_WATCH_PAUSE=5`.

```bash
./qa/panther-watch.sh
./qa/panther-watch.sh --skip-composer
./qa/panther-watch.sh --skip-composer --filter ToolLaunchTest
```

`--skip-composer` still has to come before `--filter`. The headless equivalent is `./qa/local-panther.sh --filter AdminTest --testdox`.

## After a run

`composer install` rewrites tracked files under `vendor/composer/`. If you did not change `composer.json` or `composer.lock`, put those back before you commit:

```bash
git restore vendor/composer
```

## Unit tests for tsugi/lib

These do not start Docker or Chrome:

```bash
./qa/test-lib.sh
./qa/test-lib.sh tests/Core/LaunchTest.php
./qa/test-lib.sh tests/Util/
```

`qa/test-lib.sh` runs from the repo root and then looks inside `lib/`, so those `tests/...` paths are correct for that script only.

## What not to do by hand

`qa/phpunit.xml` still names `http://localhost:8888/tsugi`. The script overrides that with port 8000. Running `vendor/bin/phpunit -c qa/phpunit.xml` yourself, with no `TSUGI_BASE_URL`, hits MAMP on 8888.

CI is `.github/workflows/ci-qa.yml`. It uses port 8888 because nothing else is bound there.
