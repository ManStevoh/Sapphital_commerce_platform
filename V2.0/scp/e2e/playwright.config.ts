import { defineConfig, devices } from '@playwright/test';

/**
 * SAPPHITAL SCP — Playwright E2E (Phase 1 Nigeria GA).
 *
 * API tests: nigeria-ga-api.spec.ts (no browser)
 * UI tests: nigeria-ga.spec.ts (skip automatically if apps not running)
 *
 * Set MARKETING_URL / STOREFRONT_URL when ports differ from defaults.
 */
export default defineConfig({
  testDir: '.',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: process.env.STOREFRONT_URL ?? 'http://localhost:3000',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'mobile',
      use: {
        ...devices['Desktop Chrome'],
        viewport: { width: 375, height: 812 },
        isMobile: true,
      },
    },
  ],
});
