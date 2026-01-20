#!/usr/bin/env bash
set -euo pipefail

echo "🔍 Pre-commit vendor sanity check (prod-clean)"

### Must be run from repo root
if [ ! -f composer.json ] || [ ! -d vendor ]; then
  echo "❌ Must be run from repo root with vendor present"
  exit 1
fi

### 1. Hard fail if dev-only packages appear in autoload metadata
echo "→ Checking autoload for dev-only packages"

DEV_PATTERNS=(
  phpunit
  mockery
  myclabs/deep-copy
  fakerphp
)

for pat in "${DEV_PATTERNS[@]}"; do
  if grep -R "$pat" vendor/composer/autoload_files.php >/dev/null 2>&1; then
    echo "❌ Dev package '$pat' referenced in autoload_files.php"
    echo "   Vendor was built with dev deps enabled"
    exit 1
  fi
done

### 2. Autoload must actually work
echo "→ Verifying PHP autoload"
php -r "require 'vendor/autoload.php';" >/dev/null 2>&1 || {
  echo "❌ PHP autoload failed"
  exit 1
}

### 3. Vendor must match composer.lock in *prod* mode
echo "→ Verifying vendor matches composer.lock (no-dev)"
composer install \
  --no-dev \
  --prefer-dist \
  --dry-run \
  --no-interaction \
  >/dev/null 2>&1 || {
    echo "❌ vendor/ does NOT match composer.lock for --no-dev"
    echo "   Rebuild vendor with: composer install --no-dev"
    exit 1
}

### 4. Guard against accidental dev files sneaking in
echo "→ Checking for dev package directories"
for pat in "${DEV_PATTERNS[@]}"; do
  if find vendor -type d -name "*$pat*" | grep -q .; then
    echo "❌ Dev package directory '$pat' present in vendor/"
    exit 1
  fi
done

echo "✅ Vendor sanity check PASSED"
echo "👍 Safe to git commit -a && git push"

