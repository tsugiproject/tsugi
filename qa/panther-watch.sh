#!/usr/bin/env bash
# Visible Chrome plus a test name in the terminal as each one finishes.
# Same Docker stack as qa/local-panther.sh. Run from anywhere inside the repo.
#
#   ./qa/panther-watch.sh
#   ./qa/panther-watch.sh --skip-composer
#   ./qa/panther-watch.sh --skip-composer --filter ToolLaunchTest

set -euo pipefail

ROOT="$(git rev-parse --show-toplevel)"
cd "$ROOT"

# Panther adds --headless unless this is set. Dropping it from
# PANTHER_CHROME_ARGUMENTS is not enough.
export PANTHER_NO_HEADLESS=1
export PANTHER_CHROME_ARGUMENTS="--no-sandbox --disable-dev-shm-usage --window-size=1200,1100"
# Seconds to leave each new URL on screen. CI does not set this.
export PANTHER_WATCH_PAUSE="${PANTHER_WATCH_PAUSE:-3}"

args=("$@")
if [[ $# -eq 0 ]]; then
  args=(--filter AdminTest)
fi

has_testdox=0
for arg in "${args[@]}"; do
  if [[ "$arg" == "--testdox" ]]; then
    has_testdox=1
  fi
done
if [[ "$has_testdox" -eq 0 ]]; then
  args+=(--testdox)
fi

exec ./qa/local-panther.sh "${args[@]}"
