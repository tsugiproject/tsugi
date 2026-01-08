#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
BACKUP_FILE="$ROOT_DIR/qa/config.php.backup"

if [ ! -f "$BACKUP_FILE" ]; then
    echo "No backup found at $BACKUP_FILE"
    exit 1
fi

cp "$BACKUP_FILE" "$ROOT_DIR/config.php"
echo "Restored config.php from backup."
