#!/usr/bin/env bash
set -euo pipefail

# Mirror tsugi/lib -> tsugi-php (Packagist mirror)
# Run this from the tsugi monorepo root.
#
# Usage:
#   qa/mirror-tsugi-php.sh
#   qa/mirror-tsugi-php.sh master        # target branch in tsugi-php (default: master)

REMOTE="tsugi-php-src"
TARGET_BRANCH="${1:-master}"
PREFIX="lib"
SPLIT_BRANCH="split-lib-$$"

die() { echo "❌ $*" >&2; exit 1; }

# Must be in a git repo
git rev-parse --is-inside-work-tree >/dev/null 2>&1 || die "Not inside a git repository."

# Must run from repo root
ROOT="$(git rev-parse --show-toplevel)"
if [[ "$PWD" != "$ROOT" ]]; then
  die "Run this from the repo root: cd \"$ROOT\""
fi

# Working tree must be clean (subtree split ignores uncommitted changes)
if ! git diff --quiet || ! git diff --cached --quiet; then
  die "Working tree not clean. Commit/stash your changes first."
fi

# Prefix must exist
[[ -d "$PREFIX" ]] || die "Prefix directory '$PREFIX' does not exist."

# Remote must exist
git remote get-url "$REMOTE" >/dev/null 2>&1 || die "Remote '$REMOTE' not found. Add it with:
  git remote add $REMOTE https://github.com/tsugiproject/tsugi-php.git"

echo "🔄 Mirroring '$PREFIX/' to '$REMOTE:$TARGET_BRANCH'"

# Make sure we know the current remote tip (lease safety)
git fetch "$REMOTE" --prune

# Create a split branch containing only lib/ history
echo "→ Creating subtree split branch '$SPLIT_BRANCH'..."
git subtree split --prefix="$PREFIX" -b "$SPLIT_BRANCH" >/dev/null

# Force-with-lease push to the mirror branch
echo "→ Pushing to $REMOTE ($TARGET_BRANCH) with --force-with-lease..."
git push --force-with-lease "$REMOTE" "$SPLIT_BRANCH:$TARGET_BRANCH"

# Cleanup local split branch
echo "→ Cleaning up local branch '$SPLIT_BRANCH'..."
git branch -D "$SPLIT_BRANCH" >/dev/null

echo "✅ Done. '$REMOTE:$TARGET_BRANCH' now mirrors '$PREFIX/'"

