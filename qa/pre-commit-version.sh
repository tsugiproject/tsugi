#!/usr/bin/env bash
# Point TSUGI_VERSION at the latest release tag, and stage that change.
#
# Release tags look like 26.11.5. "Latest" is the most recently created tag
# with that shape. Version sort is wrong in this repo: 29.10.1 is an older
# mistyped tag and would beat 26.11.5.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

SETUP="lib/include/setup.php"

latest=""
while read -r tag; do
    if [[ "$tag" =~ ^[0-9]{2}\.[0-9]{1,2}\.[0-9]+$ ]]; then
        latest="$tag"
        break
    fi
done < <(git for-each-ref --sort=-creatordate --format='%(refname:short)' refs/tags)

if [[ -z "$latest" ]]; then
    echo "TSUGI_VERSION: no release tag found; leaving $SETUP unchanged"
    exit 0
fi

read_version() {
    php -r '
$path = $argv[1];
$source = file_get_contents($path);
if (!preg_match("/define\\('"'"'TSUGI_VERSION'"'"',\\s*'"'"'([^'"'"']*)'"'"'\\)/", $source, $match)) {
    fwrite(STDERR, "TSUGI_VERSION define not found\n");
    exit(2);
}
echo $match[1];
' "$1"
}

current="$(read_version "$SETUP")"

if [[ "$current" != "$latest" ]]; then
    php -r '
$path = $argv[1];
$latest = $argv[2];
$source = file_get_contents($path);
$updated = preg_replace(
    "/define\\('"'"'TSUGI_VERSION'"'"',\\s*'"'"'[^'"'"']*'"'"'\\)/",
    "define('"'"'TSUGI_VERSION'"'"', '"'"'".$latest."'"'"')",
    $source,
    1,
    $count
);
if ($count !== 1 || !is_string($updated)) {
    fwrite(STDERR, "Could not update TSUGI_VERSION in $path\n");
    exit(1);
}
file_put_contents($path, $updated);
' "$SETUP" "$latest"
    echo "TSUGI_VERSION updated $current -> $latest (latest tag)"
fi

# Stage when the commit would still record an older constant. A worktree
# that is already current, but not added, would otherwise stay behind.
staged="$(git show ":$SETUP" | php -r '
$source = stream_get_contents(STDIN);
if (!preg_match("/define\\('"'"'TSUGI_VERSION'"'"',\\s*'"'"'([^'"'"']*)'"'"'\\)/", $source, $match)) {
    exit(0);
}
echo $match[1];
')"

if [[ "$staged" != "$latest" ]]; then
    git add -- "$SETUP"
    echo "TSUGI_VERSION $latest staged"
else
    echo "TSUGI_VERSION $latest matches latest tag"
fi
