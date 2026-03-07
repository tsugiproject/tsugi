#!/usr/bin/env bash
#
# Build a production-clean vendor/ directory
#  - no dev dependencies
#  - platform_check.php disabled via composer.json config
#  - optimized autoloader
#
set -euo pipefail

echo ""
echo "This script will:"
echo "  1. Remove the existing vendor/ directory"
echo "  2. Run composer install --no-dev (production deps only)"
echo "  3. Optimize the autoloader"
echo "  4. Restore legacy tsugi include files from git"
echo "  5. Run the vendor hygiene check"
echo ""
read -p "Are you sure? Type Y to continue: " confirm
if [ "$confirm" != "Y" ]; then
  echo "Aborted."
  exit 1
fi
echo ""

echo "🔧 Building production vendor/ (deterministic)"

# Must be run from repo root
if [ ! -f composer.json ]; then
  echo "❌ composer.json not found (run from repo root)"
  exit 1
fi

# Enforce: platform-check must be disabled in composer.json
if ! grep -Eq '"platform-check"\s*:\s*false' composer.json; then
  echo "❌ composer.json must include: \"config\": { \"platform-check\": false }"
  echo "   (This prevents vendor/composer/platform_check.php from being generated.)"
  exit 1
fi

# Nuke vendor to avoid dev residue
if [ -d vendor ]; then
  echo "🧹 Removing existing vendor/"
  rm -rf vendor
fi

echo "📦 Installing composer deps (prod only)"
composer install \
  --no-dev \
  --prefer-dist \
  --no-interaction \
  --optimize-autoloader

echo "⚙️  Finalizing autoloader"
composer dump-autoload \
  --no-dev \
  --classmap-authoritative \
  --no-interaction

echo "Make sure the legacy tsugi stuff is not gone"

git checkout vendor/tsugi/lib/include/lms_lib.php
git checkout vendor/tsugi/lib/include/pdo.php
git checkout vendor/tsugi/lib/include/pre_config.php
git checkout vendor/tsugi/lib/include/setup.php
git checkout vendor/tsugi/lib/include/setup_i18n.php

echo "🔍 Verifying vendor hygiene"
bash qa/pre-commit-vendor-check.sh

