#!/usr/bin/env bash
#
# Build a production-clean vendor/ directory
#  - no dev dependencies
#  - platform_check.php disabled via composer.json config
#  - optimized autoloader
#
set -euo pipefail

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

