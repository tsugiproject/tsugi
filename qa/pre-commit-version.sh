#!/usr/bin/env bash
# Point the staged TSUGI_VERSION at the latest release tag.
#
# Release tags look like 26.11.5. "Latest" is the most recently created tag
# with that shape. Version sort is wrong in this repo: 29.10.1 is an older
# mistyped tag and would beat 26.11.5.
#
# Only the version line in the index is replaced. Other staged and unstaged
# edits in setup.php stay where they were. A path-limited commit
# (git commit -- path) cannot take the bump: git would keep the old line in
# the real index after the commit, so that case stops and asks for a normal
# commit of the version first.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

SETUP="lib/include/setup.php"

resolve_path() {
    local path="$1"
    if [[ "$path" != /* ]]; then
        path="$PWD/$path"
    fi
    local dir
    dir="$(cd "$(dirname "$path")" && pwd)"
    printf '%s/%s\n' "$dir" "$(basename "$path")"
}

# git commit -- path points GIT_INDEX_FILE at a temporary index. Asking git
# for the index path follows that variable, so compare with the real index.
git_dir="$(git rev-parse --git-dir)"
default_index="$(resolve_path "$git_dir/index")"
active_index="$(resolve_path "${GIT_INDEX_FILE:-$default_index}")"
path_limited=0
if [[ "$active_index" != "$default_index" ]]; then
    path_limited=1
fi

tags="$(git for-each-ref --sort=-creatordate --format='%(refname:lstrip=2)' refs/tags)" || {
    echo "TSUGI_VERSION: could not list tags" >&2
    exit 1
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

# Print the TSUGI_VERSION string from a file, or from stdin when the path is "-".
read_version() {
    php -r '
$path = $argv[1];
$source = $path === "-" ? stream_get_contents(STDIN) : file_get_contents($path);
if (!is_string($source) || !preg_match("/define\\('"'"'TSUGI_VERSION'"'"',\\s*'"'"'([^'"'"']*)'"'"'\\)/", $source, $match)) {
    exit(1);
}
echo $match[1];
' "$1"
}

# Rewrite the define in $1 and write the full result to $2.
# The replacement keeps $2's permission bits. A rename otherwise creates the
# new file with the process umask, so a 0644 setup.php can become 0600.
# When $1 and $2 are the same path, refuse to replace it if the file changed
# after we read it.
write_version() {
    php -r '
$src = $argv[1];
$dest = $argv[2];
$latest = $argv[3];
$source = file_get_contents($src);
if (!is_string($source)) {
    fwrite(STDERR, "Could not read $src\n");
    exit(1);
}
$updated = preg_replace(
    "/define\\('"'"'TSUGI_VERSION'"'"',\\s*'"'"'[^'"'"']*'"'"'\\)/",
    "define('"'"'TSUGI_VERSION'"'"', '"'"'".$latest."'"'"')",
    $source,
    1,
    $count
);
if ($count !== 1 || !is_string($updated)) {
    fwrite(STDERR, "TSUGI_VERSION define not found in $src\n");
    exit(1);
}
$tmp = $dest . ".tmp." . getmypid();
$written = file_put_contents($tmp, $updated);
$check = $written === false ? false : file_get_contents($tmp);
if ($written !== strlen($updated) || $check !== $updated) {
    @unlink($tmp);
    fwrite(STDERR, "Could not write the new TSUGI_VERSION\n");
    exit(1);
}
if (file_exists($dest)) {
    $mode = fileperms($dest);
    if ($mode === false || !chmod($tmp, $mode & 0777)) {
        @unlink($tmp);
        fwrite(STDERR, "Could not preserve the mode of $dest\n");
        exit(1);
    }
    if ($src === $dest) {
        clearstatcache(true, $dest);
        $again = file_get_contents($dest);
        if ($again !== $source) {
            @unlink($tmp);
            fwrite(STDERR, "Working copy of $dest changed during the rewrite\n");
            exit(1);
        }
    }
}
if (!rename($tmp, $dest)) {
    @unlink($tmp);
    fwrite(STDERR, "Could not replace $dest\n");
    exit(1);
}
' "$1" "$2" "$3"
}

staged="$(git show ":$SETUP" | read_version -)" || {
    echo "TSUGI_VERSION define not found in the index copy of $SETUP" >&2
    exit 1
}

if [[ "$staged" == "$latest" ]]; then
    echo "TSUGI_VERSION $latest matches latest tag"
    exit 0
fi

if [[ "$path_limited" -eq 1 ]]; then
    echo "TSUGI_VERSION in this commit is $staged; the latest tag is $latest." >&2
    echo "A path-limited commit cannot take that bump. Stage $SETUP with a normal commit, then retry." >&2
    exit 1
fi

index_copy="$(mktemp)"
updated="$(mktemp)"
cleanup() {
    rm -f "$index_copy" "$updated"
}
trap cleanup EXIT

git show ":$SETUP" > "$index_copy"
seen="$(git rev-parse ":$SETUP")"
write_version "$index_copy" "$updated" "$latest"

# Someone may have staged another hunk of setup.php while we were reading.
# Replacing the index entry would drop that hunk, so refuse instead.
now="$(git rev-parse ":$SETUP")"
if [[ "$now" != "$seen" ]]; then
    echo "TSUGI_VERSION: the index copy of $SETUP changed while preparing the bump. Commit again." >&2
    exit 1
fi

mode="$(git ls-files -s -- "$SETUP" | awk '{print $1; exit}')"
if [[ ! "$mode" =~ ^[0-7]{6}$ ]]; then
    echo "TSUGI_VERSION: could not read the index mode of $SETUP" >&2
    exit 1
fi
blob="$(git hash-object -w -- "$updated")"
git update-index --cacheinfo "$mode,$blob,$SETUP"

# The commit already has the new tag. A failure here must not abort it:
# the next attempt would see a current index and skip the working copy.
if worktree="$(read_version "$SETUP" 2>/dev/null)" && [[ "$worktree" != "$latest" ]]; then
    if ! write_version "$SETUP" "$SETUP" "$latest"; then
        echo "TSUGI_VERSION $latest is staged. The working copy of $SETUP was left unchanged." >&2
    fi
fi

echo "TSUGI_VERSION $staged -> $latest staged"
