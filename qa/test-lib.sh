#!/usr/bin/env bash
set -euo pipefail

# Run unit tests for tsugi/lib
# Run this from the tsugi monorepo root.
#
# Usage:
#   qa/test-lib.sh
#   qa/test-lib.sh tests/Core/LaunchTest.php    # Run specific test
#   qa/test-lib.sh tests/Util/                  # Run tests in directory

LIB_DIR="lib"
TEST_TARGET="${1:-}"

die() { echo "❌ $*" >&2; exit 1; }

# Must be in a git repo
git rev-parse --is-inside-work-tree >/dev/null 2>&1 || die "Not inside a git repository."

# Must run from repo root
ROOT="$(git rev-parse --show-toplevel)"
if [[ "$PWD" != "$ROOT" ]]; then
  die "Run this from the repo root: cd \"$ROOT\""
fi

# lib directory must exist
[[ -d "$LIB_DIR" ]] || die "Lib directory '$LIB_DIR' does not exist."

# Change to lib directory
cd "$LIB_DIR"

# Check if vendor exists, if not run composer install
if [[ ! -d "vendor" ]]; then
  echo "📦 Installing lib dependencies..."
  composer install
fi

# Check if phpunit exists
PHPUNIT="vendor/bin/phpunit"
[[ -f "$PHPUNIT" ]] || die "PHPUnit not found after composer install"

# Run tests using composer test script (which uses phpunit.xml.dist)
if [[ -n "$TEST_TARGET" ]]; then
  echo "🧪 Running lib tests: $TEST_TARGET"
  "$PHPUNIT" --configuration phpunit.xml.dist "$TEST_TARGET"
else
  echo "🧪 Running all lib tests..."
  composer test
fi

echo "✅ Tests completed"
