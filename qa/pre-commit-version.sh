#!/usr/bin/env bash
# Point TSUGI_VERSION at the latest release tag when setup.php is untouched.
#
# Release tags look like 26.11.5. "Latest" is the most recently created tag
# with that shape. Version sort is wrong in this repo: 29.10.1 is an older
# mistyped tag and would beat 26.11.5.
#
# If setup.php already has changes, leave the constant alone and print a note.
# The commit still proceeds.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

SETUP="lib/include/setup.php"

tags="$(git for-each-ref --sort=-creatordate --format='%(refname:lstrip=2)' refs/tags)" || {
    echo "TSUGI_VERSION: could not list tags; leaving $SETUP unchanged"
    exit 0
}

latest=""
while read -r tag; do
    if [[ "$tag" =~ ^[0-9]{2}\.[0-9]{1,2}\.[0-9]+$ ]]; then
        latest="$tag"
        break
    fi
done <<< "$tags"

if [[ -z "$latest" ]]; then
    echo "TSUGI_VERSION: no release tag found; leaving $SETUP unchanged"
    exit 0
fi

current="$(php -r '
$source = file_get_contents($argv[1]);
if (!is_string($source) || !preg_match("/define\\('"'"'TSUGI_VERSION'"'"',\\s*'"'"'([^'"'"']*)'"'"'\\)/", $source, $match)) {
    exit(1);
}
echo $match[1];
' "$SETUP" 2>/dev/null || true)"

if [[ "$current" == "$latest" ]]; then
    echo "TSUGI_VERSION $latest matches latest tag"
    exit 0
fi

# Staged or unstaged. Rewriting the file would mix the version into that edit.
if ! git diff --quiet HEAD -- "$SETUP"; then
    echo "TSUGI_VERSION is ${current:-missing}; latest tag is $latest."
    echo "$SETUP has changes, so leaving the constant unchanged."
    exit 0
fi

php -r '
$path = $argv[1];
$latest = $argv[2];
$source = file_get_contents($path);
if (!is_string($source)) {
    exit(1);
}
$updated = preg_replace(
    "/define\\('"'"'TSUGI_VERSION'"'"',\\s*'"'"'[^'"'"']*'"'"'\\)/",
    "define('"'"'TSUGI_VERSION'"'"', '"'"'".$latest."'"'"')",
    $source,
    1,
    $count
);
if ($count !== 1 || file_put_contents($path, $updated) !== strlen($updated)) {
    exit(1);
}
' "$SETUP" "$latest" || {
    echo "TSUGI_VERSION: could not update $SETUP; leaving it unchanged"
    exit 0
}

git add -- "$SETUP"
echo "TSUGI_VERSION ${current:-missing} -> $latest"
