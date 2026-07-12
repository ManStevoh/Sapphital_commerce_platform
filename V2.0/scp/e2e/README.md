# SAPPHITAL SCP — E2E (Phase 1 Nigeria GA)

End-to-end tests for the Nigeria GA launch gate. Complements the PHP integration smoke test at `tests/Feature/Launch/NigeriaGaFlowTest.php`.

## Test layers

| Layer | File | Runner | Browser |
|-------|------|--------|---------|
| **API E2E (primary)** | `api/nigeria-ga.test.mjs` | Node 22+ | No |
| **API E2E (Playwright)** | `nigeria-ga-api.spec.ts` | Playwright request API | No |
| **UI E2E (stubbed)** | `nigeria-ga.spec.ts` | Playwright | Yes — `test.skip` + TODO |

API tests hit `localhost:8000` directly: signup → poll provisioning → catalog → cart. No marketing or storefront apps required.

## Prerequisites

1. **SCP API** running at `http://localhost:8000` (with Postgres + queue worker for provisioning)
2. **Node.js 22+** for API E2E (`node --test` built-in)
3. **Playwright** (optional) — only for `nigeria-ga-api.spec.ts` or future UI flows

Start the API from `V2.0/scp`:

```bash
php artisan serve
# In another terminal (if QUEUE_CONNECTION=redis):
php artisan queue:work
```

## Run API E2E (recommended)

```bash
cd e2e
bash scripts/run-e2e-api.sh
```

Custom base URL:

```bash
BASE_URL=http://127.0.0.1:8000 bash scripts/run-e2e-api.sh
```

Or run the Node test directly:

```bash
cd e2e
BASE_URL=http://localhost:8000 node api/nigeria-ga.test.mjs
```

## Run Playwright API E2E

Requires `npm install` in `e2e/` (not needed for Node-only CI):

```bash
cd e2e
npm install
BASE_URL=http://localhost:8000 npx playwright test nigeria-ga-api.spec.ts
```

## Run UI E2E (stubbed)

UI flows remain `test.skip` until marketing (`:3003`) and storefront (`:3000`) are CI-ready.

```bash
cd e2e
npm install
npx playwright install chromium
npx playwright test nigeria-ga.spec.ts
```

## View Playwright report

```bash
npx playwright show-report
```

## CI

- **PHPUnit** — runs on every push via `.github/workflows/ci.yml` (`NigeriaGaFlowTest` covers the full API flow in-process).
- **API E2E** — commented `e2e-api` job in `ci.yml`; enable when `php artisan serve` + queue worker are wired post-phpunit.
- **Playwright UI** — commented `e2e` job; enable when frontend apps have `webServer` config.

See `e2e/playwright.config.ts` for the planned `webServer` block.

## Environment variables

| Variable | Default | Used by |
|----------|---------|---------|
| `BASE_URL` | `http://localhost:8000` | `run-e2e-api.sh`, `nigeria-ga.test.mjs`, `nigeria-ga-api.spec.ts` |
| `SCP_API_URL` | `http://localhost:8000` | `nigeria-ga.spec.ts` (legacy) |
