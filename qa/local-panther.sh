#!/usr/bin/env bash
# Run the same Panther QA flow as CI (.github/workflows/ci-qa.yml), locally.
# Run from the Tsugi repo root.
#
# Usage:
#   qa/local-panther.sh
#   qa/local-panther.sh tests/ToolLaunchTest.php
#   qa/local-panther.sh --skip-composer
#   qa/local-panther.sh --skip-bootstrap tests/SomeTest.php

set -euo pipefail

die() { echo "❌ $*" >&2; exit 1; }

usage() {
  cat <<'EOF'
Run Panther E2E tests (Composer → drivers → tool bootstrap → Docker → phpunit).

  qa/local-panther.sh [options] [phpunit-args...]

Options:
  --skip-composer     Skip composer install
  --skip-bootstrap    Skip qa/scripts/qa-bootstrap-tools.sh
  -h, --help          Show this help

Environment (optional overrides):
  TSUGI_HOST_PORT     Host port for web (maps docker web:80). Default for this script: 8000 so MAMP can use 8888.
                      Set TSUGI_HOST_PORT=8888 to match CI / plain "docker compose up" (default in docker-compose.yml).
  TSUGI_BASE_URL      Default: http://localhost:<TSUGI_HOST_PORT>/tsugi
  TSUGI_ADMIN_PW      Default: tsugi-admin
  TSUGI_PDO           Default: mysql:host=tsugi_db;dbname=tsugi
  TSUGI_DB_USER       Default: ltiuser
  TSUGI_DB_PASS       Default: ltipassword
  TSUGI_WWWROOT       Default: http://localhost:<TSUGI_HOST_PORT>/tsugi
  TSUGI_APPHOME       Default: http://localhost:<TSUGI_HOST_PORT>

Requires: Docker, Chrome/Chromium, repo root. The web container loads config via
docker/tsugi-docker-config.php (see docker-compose.yml), not your host config.php.

After composer/phpunit, unwanted changes under vendor/composer/ can be discarded with:
  git restore vendor/composer
EOF
}

SKIP_COMPOSER=0
SKIP_BOOTSTRAP=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --skip-composer) SKIP_COMPOSER=1; shift ;;
    --skip-bootstrap) SKIP_BOOTSTRAP=1; shift ;;
    -h|--help) usage; exit 0 ;;
    *) break ;;
  esac
done

git rev-parse --is-inside-work-tree >/dev/null 2>&1 || die "Not inside a git repository."

ROOT="$(git rev-parse --show-toplevel)"
if [[ "$PWD" != "$ROOT" ]]; then
  die "Run this from the repo root: cd \"$ROOT\""
fi

[[ -f "docker-compose.yml" ]] || die "docker-compose.yml not found in repo root."
[[ -f "qa/phpunit.xml" ]] || die "qa/phpunit.xml not found."

# Keep host port, published port in docker-compose.yml, and Tsugi URL env in sync.
# Default 8000 here so Apache/MAMP can keep 8888. CI does not run this script; it uses docker-compose's 8888 default.
TSUGI_HOST_PORT="${TSUGI_HOST_PORT:-8000}"
export TSUGI_HOST_PORT
TSUGI_BASE_URL="${TSUGI_BASE_URL:-http://localhost:${TSUGI_HOST_PORT}/tsugi}"
TSUGI_ADMIN_PW="${TSUGI_ADMIN_PW:-tsugi-admin}"
TSUGI_PDO="${TSUGI_PDO:-mysql:host=tsugi_db;dbname=tsugi}"
TSUGI_DB_USER="${TSUGI_DB_USER:-ltiuser}"
TSUGI_DB_PASS="${TSUGI_DB_PASS:-ltipassword}"
TSUGI_WWWROOT="${TSUGI_WWWROOT:-http://localhost:${TSUGI_HOST_PORT}/tsugi}"
TSUGI_APPHOME="${TSUGI_APPHOME:-http://localhost:${TSUGI_HOST_PORT}}"
export TSUGI_BASE_URL TSUGI_ADMIN_PW TSUGI_PDO TSUGI_DB_USER TSUGI_DB_PASS TSUGI_WWWROOT TSUGI_APPHOME
export PANTHER_NO_SANDBOX="${PANTHER_NO_SANDBOX:-1}"
export PANTHER_CHROME_ARGUMENTS="${PANTHER_CHROME_ARGUMENTS:---headless=new --no-sandbox --disable-dev-shm-usage}"
export PANTHER_EXTERNAL_BASE_URI="${PANTHER_EXTERNAL_BASE_URI:-$TSUGI_BASE_URL}"

echo "🌐 Host port ${TSUGI_HOST_PORT} (MAMP can use 8888; TSUGI_HOST_PORT=8888 to match CI)"

command -v docker >/dev/null 2>&1 || die "docker not found in PATH."

if [[ "$SKIP_COMPOSER" -eq 0 ]]; then
  echo "📦 composer install..."
  composer install --no-interaction --prefer-dist
fi

BDI="vendor/bin/bdi"
PHPUNIT="vendor/bin/phpunit"
[[ -f "$BDI" ]] || die "Run composer install first ($BDI missing)."
[[ -f "$PHPUNIT" ]] || die "Run composer install first ($PHPUNIT missing)."

echo "🌐 Installing browser drivers..."
"$BDI" detect drivers

if [[ "$SKIP_BOOTSTRAP" -eq 0 ]]; then
  echo "🔧 Bootstrapping sample tools..."
  ./qa/scripts/qa-bootstrap-tools.sh
fi

# If something (often MAMP on 8888) already listens on this host port and it is not our stack, docker bind will fail.
if command -v lsof >/dev/null 2>&1; then
  if lsof -iTCP:"${TSUGI_HOST_PORT}" -sTCP:LISTEN >/dev/null 2>&1; then
    if ! docker ps --format '{{.Names}}' 2>/dev/null | grep -qx tsugi_web; then
      die "Port ${TSUGI_HOST_PORT} is already in use. Stop the other service or set TSUGI_HOST_PORT to a free port (e.g. 8001)."
    fi
  fi
fi

echo "🐳 Starting Tsugi (docker compose up -d --build)..."
docker compose up -d --build

echo "⏳ Waiting for Tsugi at ${TSUGI_BASE_URL}..."
ok=0
for _ in $(seq 1 30); do
  if curl -fsS "${TSUGI_BASE_URL}" >/dev/null 2>&1; then
    ok=1
    break
  fi
  sleep 3
done
if [[ "$ok" -ne 1 ]]; then
  echo "Timed out waiting for ${TSUGI_BASE_URL}" >&2
  docker compose logs --no-color >&2 || true
  exit 1
fi

echo ""
echo "💡 Composer / PHPUnit touch tracked files under vendor/composer/ (autoload maps, installed.json, etc.)."
echo "   If you did not change composer.json or composer.lock and do not want those in your commit, run:"
echo "     git restore vendor/composer"
echo ""

echo "🧪 Running Panther QA (phpunit -c qa/phpunit.xml)..."

exec "$PHPUNIT" -c qa/phpunit.xml "$@"
