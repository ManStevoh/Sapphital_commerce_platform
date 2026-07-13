# SAPPHITAL SCP — E2E (Phase 1 Nigeria GA)

End-to-end tests for the Nigeria GA launch gate. Complements `tests/Feature/Launch/NigeriaGaFlowTest.php`.

## Test layers

| Layer | File | Runner | Browser |
|-------|------|--------|---------|
| **API E2E (Node)** | `api/nigeria-ga.test.mjs` | Node 22+ | No |
| **API E2E (Playwright)** | `nigeria-ga-api.spec.ts` | Playwright request API | No |
| **UI full stack** | `nigeria-ga.spec.ts`, `shopper-*.spec.ts` | `scripts/run-e2e-ui.sh` | Yes — provisions tenant + starts apps |

API tests: signup → provisioning → catalog → cart → checkout → Paystack verify → paid order.

## Prerequisites

1. **SCP API** at `http://localhost:8000` (Postgres + `QUEUE_CONNECTION=sync` or queue worker)
2. **Node.js 22+** for API E2E
3. **Playwright** (optional) for browser specs

```bash
cd V2.0/scp
php artisan serve
```

## Run API E2E

```bash
cd e2e
bash scripts/run-e2e-api.sh
```

## Run Playwright

```bash
cd e2e
npm install
npx playwright install chromium
BASE_URL=http://localhost:8000 npx playwright test nigeria-ga-api.spec.ts
```

UI specs (marketing `:3003`, storefront `:3000`) — auto-provision tenant:

```bash
bash scripts/run-e2e-ui.sh
```

Or manually with apps already running:

```bash
MARKETING_URL=http://localhost:3003 STOREFRONT_URL=http://localhost:3000 npx playwright test shopper-*.spec.ts
```

## CI

| Job | What runs |
|-----|-----------|
| `php` | PHPUnit (238+ tests) + isolation `--check` |
| `e2e-api` | Node API E2E against `php artisan serve` |
| `e2e-playwright` | Playwright API E2E (`nigeria-ga-api.spec.ts`) |
| `e2e-ui` | Full UI stack — provisions tenant, starts marketing + storefront, runs UI specs |

## Environment variables

| Variable | Default | Used by |
|----------|---------|---------|
| `BASE_URL` | `http://localhost:8000` | API E2E |
| `MARKETING_URL` | `http://localhost:3003` | UI specs |
| `STOREFRONT_URL` | `http://localhost:3000` | UI specs |

## OpenAPI

Phase 1 storefront/admin paths: `docs/openapi/nigeria-ga-v1.yaml`
