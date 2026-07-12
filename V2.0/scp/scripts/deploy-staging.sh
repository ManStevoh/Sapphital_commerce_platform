#!/usr/bin/env bash
# SAPPHITAL SCP — staging deploy helper (Docker Compose stack)
# Spec: Vol 10 Ch. 06 (CI/CD), Ch. 07 (config cache on staging)
# Steps: migrate → cache config/routes → verify /api/health

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
DOCKER_DIR="${ROOT_DIR}/infra/docker"
API_URL="${API_URL:-http://localhost:8000}"

cd "${DOCKER_DIR}"

if [[ ! -f .env ]]; then
    echo "Missing ${DOCKER_DIR}/.env — copy from .env.example and set APP_KEY." >&2
    exit 1
fi

echo "Running database migrations..."
docker compose exec -T api php artisan migrate --force

echo "Caching configuration and routes..."
docker compose exec -T api php artisan config:cache
docker compose exec -T api php artisan route:cache

echo "Verifying API health at ${API_URL}/api/health ..."
curl -sf "${API_URL}/api/health" | grep -q '"status"[[:space:]]*:[[:space:]]*"ok"'

echo "Staging deploy complete."
