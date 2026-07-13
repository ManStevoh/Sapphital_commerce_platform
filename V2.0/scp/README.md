# SAPPHITAL Commerce Platform (SCP)

Implementation monorepo for the **SAPPHITAL Platform OS** ([ADR-023](../docs/00-meta/adr/023-sapphital-platform-os.md)).

Engineering specifications live in [`V2.0/docs/`](../docs/) — that directory is the **source of truth**.

## Phase 1 — Foundation

| Step | Workstream | Status |
|------|------------|--------|
| P1.1 | Tenancy RLS + tenant context | **Done** |
| P1.2 | Identity auth guards | **Done** |
| P1.3 | SaaS billing + plans | **Done** |
| P1.4 | Tenant Provisioning Engine | **Done** |
| P1.5 | Marketing signup funnel | **Done** — `apps/marketing` |
| P1.6 | Platform Admin console | **Done** — `apps/platform-admin` |
| P1.7 | Catalog + inventory | **Done** — full CRUD |
| P1.8 | Cart + checkout | **Done** |
| P1.9 | Storefront | **Done** — `apps/storefront` product grid + cart |
| P1.10 | Orders | **Done** — order creation from checkout |
| P1.11 | FSL + Paystack | **Done** — initialize, verify, webhook |
| P1.12 | Merchant admin UI | **Done** — `apps/admin` |
| P1.10+ | Shipping (Nigeria) | **Done** — rates, shipments, tracking, fulfillment |
| P1.9+ | Themes (×3) | **Done** — scp-dawn, scp-market, scp-terminal |
| Launch | Nigeria GA smoke test | **Done** — `tests/Feature/Launch/NigeriaGaFlowTest.php` |
| E2E | API + Playwright scaffold | **Done** — `e2e/api/nigeria-ga.test.mjs`, UI skipped |

**PHPUnit:** 100 tests, 481 assertions — `composer test`

### Nigeria GA commerce flow

```
signup → catalog → cart → shipping rates → checkout → Paystack → webhook/verify → paid order
```

Reference theme: **Lagos Atelier** (`Themes/scp-dawn/`)

### Key endpoints (latest)

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/api/v1/webhooks/paystack` | Paystack `charge.success` webhook (HMAC verified) |
| GET | `/api/v1/commerce/shipping/rates` | Shipping rates (NGN, free over ₦50k) |
| GET | `/api/v1/commerce/storefront/theme` | Active theme config for tenant |
| POST | `/api/v1/platform/financial-services/payments/initialize` | Paystack redirect |
| POST | `/api/v1/platform/financial-services/payments/verify` | Confirm payment → order |

## Testing

```bash
cd V2.0/scp
composer test
```

CI runs PHPUnit on every push to `main`.

## Local development

```bash
cd V2.0/scp
composer install && cp .env.example .env
php artisan key:generate
composer test
php artisan serve
```

### Client apps

| App | Port | Command |
|-----|------|---------|
| Storefront | 3000 | `cd apps/storefront && npm install && npm run dev` |
| Merchant admin | 3001 | `cd apps/admin && npm install && npm run dev` |
| Platform admin | 3002 | `cd apps/platform-admin && npm install && npm run dev` |
| Marketing | 3003 | `cd apps/marketing && npm install && npm run dev` |

Set `NEXT_PUBLIC_API_URL=http://localhost:8000` in each app's `.env.local`.

Storefront dev: set `NEXT_PUBLIC_DEFAULT_TENANT_SLUG=your-store-slug` or use subdomain `*.shops.sapphital.test`.

### Docker full stack

Local and staging parity stack per [Vol 10 Ch. 04](../docs/10-infrastructure/04-postgresql-redis-meilisearch.md) and [Ch. 07](../docs/10-infrastructure/07-environments-and-config.md): PostgreSQL 16, Redis 7, Meilisearch, and the Laravel API on port 8000.

```bash
cd infra/docker
cp .env.example .env
# Set APP_KEY in .env (e.g. docker compose run --rm api php artisan key:generate --show)
docker compose up -d --build
docker compose exec api php artisan migrate --seed   # first run
```

| Service | Host port | Health |
|---------|-----------|--------|
| API | 8000 | `GET /api/health` |
| PostgreSQL | 5432 | `pg_isready` |
| Redis | 6379 | `PING` |
| Meilisearch | 7700 | `GET /health` |

**Staging deploy** (migrate, cache config/routes, verify API health):

```bash
./scripts/deploy-staging.sh
```

**Probe all package health endpoints** (API root + Platform + Commerce modules):

```bash
./scripts/health-check.sh
# API_URL=http://staging.example.com ./scripts/health-check.sh
```

The endpoint list is mirrored in `tests/Feature/Infrastructure/HealthCheckScriptTest.php`.

### API E2E (Node 22, no browser)

With `php artisan serve` running:

```bash
cd e2e
bash scripts/run-e2e-api.sh
```

Covers signup → provisioning → catalog → cart against `BASE_URL` (default `http://localhost:8000`).

## References

- [Master Execution Plan §5](../docs/21-implementation-playbooks/00-master-execution-plan.md)
- [Launch Readiness Ch. 12](../docs/21-implementation-playbooks/12-launch-readiness-checklist.md) — 94 blockers for Nigeria GA
