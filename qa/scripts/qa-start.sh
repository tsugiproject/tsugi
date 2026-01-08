#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

export TSUGI_BASE_URL="${TSUGI_BASE_URL:-http://localhost:8888/tsugi}"
export TSUGI_ADMIN_PW="${TSUGI_ADMIN_PW:-tsugi-admin}"
export TSUGI_PDO="${TSUGI_PDO:-mysql:host=tsugi_db;dbname=tsugi}"
export TSUGI_DB_USER="${TSUGI_DB_USER:-ltiuser}"
export TSUGI_DB_PASS="${TSUGI_DB_PASS:-ltipassword}"

if [ ! -f "$ROOT_DIR/config.php" ]; then
    cp "$ROOT_DIR/config-dist.php" "$ROOT_DIR/config.php"
fi

if [ "${TSUGI_QA_BOOTSTRAP_TOOLS:-1}" = "1" ]; then
    if [ ! -e "$ROOT_DIR/mod/grade" ] || [ ! -e "$ROOT_DIR/mod/blob" ]; then
        "$ROOT_DIR/qa/scripts/qa-bootstrap-tools.sh"
    fi
fi

docker compose up -d

echo "Waiting for ${TSUGI_BASE_URL} ..."
for _ in {1..30}; do
    if curl -fsS "${TSUGI_BASE_URL}" >/dev/null; then
        echo "Tsugi is up."
        exit 0
    fi
    sleep 3
    echo "Still waiting..."
done

echo "Timed out waiting for ${TSUGI_BASE_URL}."
exit 1
