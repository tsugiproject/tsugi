#!/usr/bin/env bash
# After composer update/require: ensure production (--no-dev) autoload for committed vendor/.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

AUTOLOAD_PROBE="vendor/composer/autoload_files.php"
DEV_MARKERS=(myclabs/deep-copy phpunit/phpunit phpstan/phpstan php-webdriver/webdriver)

needs_no_dev=0
if [[ -f "$AUTOLOAD_PROBE" ]]; then
    for marker in "${DEV_MARKERS[@]}"; do
        if grep -q "$marker" "$AUTOLOAD_PROBE"; then
            needs_no_dev=1
            break
        fi
    done
else
    echo "ERROR: missing $AUTOLOAD_PROBE" >&2
    exit 1
fi

if [[ "$needs_no_dev" -eq 1 ]]; then
    echo "⚠️  Dev-mode autoload detected — running composer install --no-dev ..."
    composer install --no-dev --ignore-platform-reqs --no-interaction
fi

bash qa/pre-commit-vendor-check.sh
