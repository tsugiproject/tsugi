#!/usr/bin/env bash
# Fail if committed vendor/composer autoload references gitignored dev packages.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

AUTOLOAD_FILES=(
    vendor/composer/autoload_files.php
    vendor/composer/autoload_static.php
    vendor/composer/autoload_classmap.php
)

# Paths under vendor/ that .gitignore excludes from production deploys.
FORBIDDEN=(
    myclabs/deep-copy
    phpunit/phpunit
    phpstan/phpstan
    php-webdriver/webdriver
    dbrekelmans/bdi
    symfony/panther
    symfony/dependency-injection
    symfony/var-exporter
    nikic/php-parser
    sebastian/
    phar-io/
    staabm/
    theseer/
)

fail=0
for file in "${AUTOLOAD_FILES[@]}"; do
    if [[ ! -f "$file" ]]; then
        echo "Missing $file" >&2
        fail=1
        continue
    fi
    for pattern in "${FORBIDDEN[@]}"; do
        if grep -q "$pattern" "$file"; then
            echo "ERROR: $file references gitignored dev package: $pattern" >&2
            echo "Run: composer run finalize-vendor" >&2
            fail=1
        fi
    done
done

php -r "require 'vendor/autoload.php';" || {
    echo "ERROR: vendor/autoload.php failed to load" >&2
    fail=1
}

if [[ "$fail" -ne 0 ]]; then
    exit 1
fi

echo "vendor autoload OK (production / --no-dev)"
