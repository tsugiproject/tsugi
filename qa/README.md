# QA Harness (Panther)

This directory contains a PHP-native end-to-end test harness using Symfony Panther.
It is designed to run against a local Tsugi instance started via Docker Compose.

## Quick start (local)

1) Start Tsugi in Docker with a QA-friendly config:

   ./qa/scripts/qa-start.sh

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
`qa-start.sh` will run this automatically unless you set
`TSUGI_QA_BOOTSTRAP_TOOLS=0`.

## Notes

- The QA start script may overwrite `config.php` to ensure Docker uses the container DB.
  It saves a backup at `qa/config.php.backup` the first time it runs.
- The admin test expects `TSUGI_ADMIN_PW` to be set and `config.php` to use it.
- Tool launch tests use the built-in store test harness (`/store/test/...`) and
  switch identities via `?identity=instructor|learner1`. You can override roles
  in developer mode with `?roles=Instructor` if needed.
- For CI, see `.github/workflows/qa-panther.yml`.
