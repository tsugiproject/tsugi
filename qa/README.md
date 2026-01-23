# QA Harness (Panther)

This directory contains a PHP-native end-to-end test harness using Symfony Panther.
It is designed to run against a local Tsugi instance started via Docker Compose.

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

## Quick start (local)

1) Start Tsugi in Docker:

   docker compose up -d

   Optional DB overrides:
   TSUGI_PDO='mysql:host=tsugi_db;dbname=tsugi' \
   TSUGI_DB_USER=ltiuser \
   TSUGI_DB_PASS=ltipassword \
   docker compose up -d

2) Run the QA suite:

   TSUGI_BASE_URL=http://localhost:8888/tsugi \
   TSUGI_ADMIN_PW=tsugi-admin \
   PANTHER_NO_SANDBOX=1 \
   vendor/bin/phpunit -c qa/phpunit.xml

## Tool checkouts (required for tool launch tests)

Tool launch tests use sample tools from `qa/tools.txt`. You can run this manually:

  ./qa/scripts/qa-bootstrap-tools.sh

By default, tools are cloned into `mod/`, and a couple of sample tools are
symlinked into `mod/` root so Tsugi can auto-register them.

## Notes

- The admin test expects `TSUGI_ADMIN_PW` to be set and `config.php` to use it.
- For Docker runs, set database env vars if you are not using the defaults:
  `TSUGI_PDO`, `TSUGI_DB_USER`, and `TSUGI_DB_PASS` (all read from `config.php`).
- Tool launch tests use the built-in store test harness (`/store/test/...`) and
  switch identities via `?identity=instructor|learner1`. You can override roles
  in developer mode with `?roles=Instructor` if needed.
- For CI, see `.github/workflows/qa-panther.yml`.
