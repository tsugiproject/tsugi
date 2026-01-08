#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

export TSUGI_BASE_URL="${TSUGI_BASE_URL:-http://localhost:8888/tsugi}"
export TSUGI_ADMIN_PW="${TSUGI_ADMIN_PW:-tsugi-admin}"
export TSUGI_DOCKER_FORCE_CONFIG="${TSUGI_DOCKER_FORCE_CONFIG:-1}"

BACKUP_FILE="$ROOT_DIR/qa/config.php.backup"
if [ -f "$ROOT_DIR/config.php" ] && [ ! -f "$BACKUP_FILE" ]; then
    cp "$ROOT_DIR/config.php" "$BACKUP_FILE"
fi

if [ "${TSUGI_DOCKER_FORCE_CONFIG}" = "1" ] || [ ! -f "$ROOT_DIR/config.php" ]; then
    cp "$ROOT_DIR/config-dist.php" "$ROOT_DIR/config.php"
    php <<'PHP'
<?php
$path = 'config.php';
$contents = file_get_contents($path);
if ($contents === false) {
    fwrite(STDERR, "Unable to read config.php\n");
    exit(1);
}
$contents = str_replace('127.0.0.1', 'tsugi_db', $contents);
file_put_contents($path, $contents);
PHP
fi

if [ -n "${TSUGI_ADMIN_PW}" ]; then
    php <<'PHP'
<?php
$path = 'config.php';
$pw = getenv('TSUGI_ADMIN_PW');
if ($pw === false || $pw === '') {
    exit(0);
}
$contents = file_get_contents($path);
if ($contents === false) {
    fwrite(STDERR, "Unable to read config.php\n");
    exit(1);
}
$escaped = str_replace("\\", "\\\\", $pw);
$escaped = str_replace("'", "\\'", $escaped);
$replacement = "\$CFG->adminpw = '" . $escaped . "';";
$count = 0;
$contents = preg_replace("/\\\$CFG->adminpw\\s*=\\s*[^;]*;/", $replacement, $contents, 1, $count);
if ($count === 0) {
    $contents .= "\n" . $replacement . "\n";
}
file_put_contents($path, $contents);
PHP
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
