#!/usr/bin/env bash
# SAPPHITAL SCP — probe all registered health endpoints
# Spec: Vol 10 Ch. 07 (staging parity), Ch. 08 (observability probes)
# Mirrors: tests/Feature/Infrastructure/HealthCheckScriptTest.php

set -euo pipefail

API_URL="${API_URL:-http://localhost:8000}"

readonly ENDPOINTS=(
    "/api/health"
    "/api/v1/platform/tenancy/health"
    "/api/v1/platform/identity/health"
    "/api/v1/platform/billing/health"
    "/api/v1/platform/provisioning/health"
    "/api/v1/platform/financial-services/health"
    "/api/v1/platform/secrets/health"
    "/api/v1/platform/notifications/health"
    "/api/v1/commerce/catalog/health"
    "/api/v1/commerce/cart/health"
    "/api/v1/commerce/checkout/health"
    "/api/v1/commerce/orders/health"
    "/api/v1/commerce/shipping/health"
)

failures=0

for endpoint in "${ENDPOINTS[@]}"; do
    url="${API_URL}${endpoint}"
    printf 'Checking %s ... ' "${endpoint}"

    if response="$(curl -sf "${url}")"; then
        if echo "${response}" | grep -q '"status"[[:space:]]*:[[:space:]]*"ok"'; then
            echo "ok"
        else
            echo "FAIL (unexpected body: ${response})"
            failures=$((failures + 1))
        fi
    else
        echo "FAIL (HTTP error)"
        failures=$((failures + 1))
    fi
done

if [[ "${failures}" -gt 0 ]]; then
    echo "${failures} health endpoint(s) failed." >&2
    exit 1
fi

echo "All ${#ENDPOINTS[@]} health endpoints OK."
