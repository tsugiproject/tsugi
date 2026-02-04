#!/usr/bin/env bash
set -euo pipefail

echo "🔍 Pre-commit vendor sanity check (prod-clean)"

### Must be run from repo root
if [ ! -f composer.json ] || [ ! -d vendor ]; then
  echo "❌ Must be run from repo root with vendor present"
  exit 1
fi

### 0) platform_check.php must not exist
if [ -f vendor/composer/platform_check.php ]; then
  echo "❌ vendor/composer/platform_check.php present"
  echo "   Fix: ensure composer.json has: \"config\": { \"platform-check\": false }"
  exit 1
fi

### 1) Required Tsugi include files must exist
echo "→ Verifying required Tsugi include files exist"
REQUIRED_FILES=(
  vendor/tsugi/lib/include/lms_lib.php
  vendor/tsugi/lib/include/pdo.php
  vendor/tsugi/lib/include/pre_config.php
  vendor/tsugi/lib/include/setup.php
  vendor/tsugi/lib/include/setup_i18n.php
)

missing=0
for f in "${REQUIRED_FILES[@]}"; do
  if [ ! -f "$f" ]; then
    echo "❌ Required file missing: $f"
    missing=1
  fi
done
if [ "$missing" -ne 0 ]; then
  echo "❌ Vendor integrity failure: required Tsugi files missing"
  exit 1
fi

### 2) Hard fail if dev-only PACKAGES are actually installed
echo "→ Checking installed packages for dev-only libs (exact package names)"

DEV_PACKAGES=(
  phpunit/phpunit
  mockery/mockery
  fakerphp/faker
  phpstan/phpstan
  phpstan/phpstan-phpunit
  phpspec/prophecy-phpunit
)

php -r '
$dev = array_flip(array_slice($argv,1));
$path = "vendor/composer/installed.json";
if (!file_exists($path)) { fwrite(STDERR, "missing installed.json\n"); exit(2); }
$j = json_decode(file_get_contents($path), true);
$pkgs = $j["packages"] ?? $j;  // composer v2 may wrap in {packages:[]}
$found = [];
foreach ($pkgs as $p) {
  $name = $p["name"] ?? "";
  if (isset($dev[$name])) $found[] = $name;
}
if ($found) { fwrite(STDERR, implode("\n", $found) . "\n"); exit(1); }
exit(0);
' _ "${DEV_PACKAGES[@]}" 2> /tmp/vendor_dev_found.$$ || {
  echo "❌ Dev packages INSTALLED in vendor/:"
  sed 's/^/   - /' /tmp/vendor_dev_found.$$ || true
  rm -f /tmp/vendor_dev_found.$$
  echo "   Vendor was built with dev deps enabled"
  exit 1
}
rm -f /tmp/vendor_dev_found.$$

### 3) Autoload must work
echo "→ Verifying PHP autoload"
php -r "require 'vendor/autoload.php';" >/dev/null 2>&1 || {
  echo "❌ PHP autoload failed"
  exit 1
}

### 4) Vendor must match composer.lock in prod mode
echo "→ Verifying vendor matches composer.lock (no-dev dry-run)"
composer install \
  --no-dev \
  --prefer-dist \
  --dry-run \
  --no-interaction \
  >/dev/null 2>&1 || {
    echo "❌ vendor/ does NOT match composer.lock for --no-dev"
    echo "   Fix: rm -rf vendor && composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader"
    exit 1
}

### 5) Optional: vendor must be fully tracked (no untracked files)
echo "→ Checking for untracked files under vendor/"
if git ls-files --others --exclude-standard vendor/ | grep -q .; then
  echo "❌ Untracked files exist under vendor/ (vendor must be fully tracked)"
  git ls-files --others --exclude-standard vendor/ | head -n 30
  exit 1
fi

echo "✅ Vendor sanity check PASSED"
echo "👍 Safe to git commit -a && git push"

