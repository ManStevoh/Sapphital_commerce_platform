#!/usr/bin/env bash
# SAPPHITAL SCP — Nigeria GA UI E2E (marketing + storefront dev servers).
set -euo pipefail

BASE_URL="${BASE_URL:-http://127.0.0.1:8000}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
E2E_DIR="$(dirname "$SCRIPT_DIR")"
ROOT_DIR="$(dirname "$E2E_DIR")"
MARKETING_DIR="${ROOT_DIR}/apps/marketing"
STOREFRONT_DIR="${ROOT_DIR}/apps/storefront"

echo "==> Provisioning UI tenant via ${BASE_URL}"
TENANT_JSON="$(node "${SCRIPT_DIR}/provision-ui-tenant.mjs")"
TENANT_SLUG="$(echo "${TENANT_JSON}" | node -e "process.stdout.write(JSON.parse(require('fs').readFileSync(0,'utf8')).slug)")"

echo "==> Tenant slug: ${TENANT_SLUG}"

echo "==> Installing marketing + storefront dependencies"
npm install --no-audit --no-fund --prefix "${MARKETING_DIR}"
npm install --no-audit --no-fund --prefix "${STOREFRONT_DIR}"

export NEXT_PUBLIC_API_URL="${BASE_URL}"
export NEXT_PUBLIC_DEFAULT_TENANT_SLUG="${TENANT_SLUG}"

echo "==> Starting marketing (:3003) and storefront (:3000)"
npm run dev --prefix "${MARKETING_DIR}" &
MARKETING_PID=$!
npm run dev --prefix "${STOREFRONT_DIR}" &
STOREFRONT_PID=$!

cleanup() {
  kill "${MARKETING_PID}" "${STOREFRONT_PID}" 2>/dev/null || true
}
trap cleanup EXIT

echo "==> Waiting for UI servers"
for i in $(seq 1 60); do
  if curl -sf "http://127.0.0.1:3003/" >/dev/null && curl -sf "http://127.0.0.1:3000/" >/dev/null; then
    break
  fi
  sleep 2
done

export MARKETING_URL="http://127.0.0.1:3003"
export STOREFRONT_URL="http://127.0.0.1:3000"

echo "==> Running Playwright UI specs"
npm install --no-save @playwright/test@^1.52.0 --prefix "${E2E_DIR}"
npx --prefix "${E2E_DIR}" playwright install --with-deps chromium
npx --prefix "${E2E_DIR}" playwright test nigeria-ga.spec.ts shopper-cart-persistence.spec.ts shopper-sold-out.spec.ts
