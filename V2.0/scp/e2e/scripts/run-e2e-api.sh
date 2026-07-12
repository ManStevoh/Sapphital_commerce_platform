#!/usr/bin/env bash
# SAPPHITAL SCP — Nigeria GA API E2E runner (no browser).
# Requires: Node.js 22+, SCP API at BASE_URL (default http://localhost:8000).
set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost:8000}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
E2E_DIR="$(dirname "$SCRIPT_DIR")"

echo "==> Checking API health at ${BASE_URL}/api/health"

health_body="$(curl -sf "${BASE_URL}/api/health")" || {
  echo "ERROR: API health check failed. Is the server running at ${BASE_URL}?"
  echo "Start with: php artisan serve  (from V2.0/scp)"
  exit 1
}

echo "${health_body}" | grep -q '"status":"ok"' || {
  echo "ERROR: Unexpected health response: ${health_body}"
  exit 1
}

echo "==> Health OK — running Nigeria GA API E2E"
export BASE_URL
node "${E2E_DIR}/api/nigeria-ga.test.mjs"
