#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
TOOLS_DIR="${TSUGI_TOOLS_DIR:-$ROOT_DIR/mod}"
LIST_FILE="${TSUGI_TOOLS_LIST:-$ROOT_DIR/qa/tools.txt}"

if [ ! -f "$LIST_FILE" ]; then
    echo "Tool list not found: $LIST_FILE"
    exit 1
fi

mkdir -p "$TOOLS_DIR"

SAMPLE_TOOLS=(blob grade)

while IFS= read -r repo || [ -n "$repo" ]; do
    repo="${repo%%#*}"
    repo="${repo//[[:space:]]/}"
    if [ -z "$repo" ]; then
        continue
    fi

    name=$(basename "$repo")
    name="${name%.git}"
    target="$TOOLS_DIR/$name"

    if [ -d "$target" ]; then
        echo "Skipping existing tool: $target"
        continue
    fi

    echo "Cloning $repo into $target"
    git clone --depth=1 "$repo" "$target"
done < "$LIST_FILE"

samples_root="$TOOLS_DIR/tsugi-php-samples"
if [ -d "$samples_root" ]; then
    for tool in "${SAMPLE_TOOLS[@]}"; do
        src="$samples_root/$tool"
        dest="$TOOLS_DIR/$tool"
        if [ -L "$dest" ]; then
            target="$(readlink "$dest")"
            if [[ "$target" == /* ]]; then
                rm -f "$dest"
            fi
        fi
        if [ -d "$src" ] && [ ! -e "$dest" ]; then
            echo "Linking sample tool $tool into $dest"
            (cd "$TOOLS_DIR" && ln -s "tsugi-php-samples/$tool" "$tool")
        fi
    done
fi
