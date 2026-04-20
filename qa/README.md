# QA Harness (Panther)

This directory contains a PHP-native end-to-end test harness using Symfony Panther.
It is designed to run against a local Tsugi instance started via Docker Compose.

## Docker config (same idea as CI)

The `web` service bind-mounts your repo for code, but **`config.php` inside the container is not your host file**. Compose overlays **`docker/tsugi-docker-config.php`** onto `tsugi/config.php`. That file is **fully self-contained** (Docker defaults + `TSUGI_*` env); it does **not** include **`config-dist.php`**. Use **`config-dist.php`** as the template you copy to **`config.php`** for MAMP or production and edit there — also self-contained.

See `docker-compose.yml`, `.dockerignore`, and `Dockerfile`.

## Quick start (one command, matches CI)

From the repo root:

```bash
./qa/local-panther.sh
```

The script runs Composer, browser drivers, `docker compose up -d --build`, waits for Tsugi, then PHPUnit. Optional arguments are forwarded to PHPUnit (for example a single test class).

```bash
./qa/local-panther.sh tests/ToolLaunchTest.php
```

Skip steps when iterating:

```bash
./qa/local-panther.sh --skip-composer
```

After changing `Dockerfile`, `.dockerignore`, `docker/tsugi-docker-config.php`, or `config-dist.php`, rebuild the web image (the script uses `docker compose up -d --build`).

## Unit Tests for tsugi/lib

Run unit tests for the Tsugi PHP library:

```bash
# Run all lib unit tests
qa/test-lib.sh

# Run a specific test file
qa/test-lib.sh tests/Core/LaunchTest.php

# Run tests in a directory
qa/test-lib.sh tests/Util/
```

The script automatically detects whether to use `lib/vendor/bin/phpunit` (if lib has its own vendor) or `vendor/bin/phpunit` (using root dependencies).

## Quick start (manual steps)

1) Start Tsugi (export `TSUGI_*` so they are passed into the web container):

   ```bash
   TSUGI_PDO='mysql:host=tsugi_db;dbname=tsugi' \
   TSUGI_DB_USER=ltiuser \
   TSUGI_DB_PASS=ltipassword \
   TSUGI_WWWROOT='http://localhost:8888/tsugi' \
   TSUGI_APPHOME='http://localhost:8888' \
   TSUGI_ADMIN_PW=tsugi-admin \
   docker compose up -d --build
   ```

2) Install browser drivers:

   ```bash
   vendor/bin/bdi detect drivers
   ```

3) Run the QA suite:

   ```bash
   TSUGI_BASE_URL=http://localhost:8888/tsugi \
   TSUGI_ADMIN_PW=tsugi-admin \
   PANTHER_NO_SANDBOX=1 \
   PANTHER_CHROME_ARGUMENTS="--headless=new --no-sandbox --disable-dev-shm-usage" \
   vendor/bin/phpunit -c qa/phpunit.xml
   ```

## Tool launch targets

Tool launch tests run against built-in tools under `tool/`: `gift`, `peer-grade`,
and `tdiscus`.

## Notes

- **Composer and `vendor/composer/*`:** Running `composer install` (including via `./qa/local-panther.sh`) can touch `vendor/composer/autoload_*.php`, `installed.json`, and `installed.php`. Those are not Panther “temp” files; they reflect the install state. If you did **not** intend to change dependencies and `composer.lock` is unchanged, you can discard noise with `git restore vendor/composer` before committing. If you **did** change `composer.json` / `composer.lock` on purpose, commit the lockfile and the tracked Composer metadata together as your team expects.
- Inside Docker, all settings come from `docker/tsugi-docker-config.php` (duplicated from `config-dist.php` where intentional; Docker entry uses `TSUGI_*` for URLs and database).
- The PHPUnit admin test reads `TSUGI_ADMIN_PW` from your shell; keep it in sync with the value passed into the web container.
- Tool launch tests use the built-in store test harness (`/store/test/...`) and
  switch identities via `?identity=instructor|learner1`. You can override roles
  in developer mode with `?roles=Instructor` if needed.
- For CI, see `.github/workflows/ci-qa.yml`.
