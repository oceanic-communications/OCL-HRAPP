/// <reference types="node" />

import { defineConfig, devices } from '@playwright/test';

/** App under test (browser navigates here). */
const baseURL =
    process.env.PLAYWRIGHT_BASE_URL?.replace(/\/$/, '') ||
    'http://localhost:8090';

// OTP role tests read Mailhog from PLAYWRIGHT_MAILHOG_URL (default http://localhost:8025).

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : undefined,
    reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : 'list',
    use: {
        baseURL,
        trace: 'on-first-retry',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
});
