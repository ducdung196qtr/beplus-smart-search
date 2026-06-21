#!/usr/bin/env bash
# Cross-platform wrapper — delegates to Node (PHP auto-discovery on Windows).
set -euo pipefail
exec node "$(dirname "$0")/lint-staged-php.mjs" "$@"
