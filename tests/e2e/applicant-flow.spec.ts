/// <reference types="node" />

import { expect, test } from '@playwright/test';

import { loginViaOtp } from './helpers/login-via-otp';

test.describe('Applicant flow', () => {
    test.describe.configure({ timeout: 60_000 });

    test('grant applicant logs in from landing page', async ({ page }) => {
        await loginViaOtp(page, 'grant-applicant@example.com', '/apply');
        await expect(page).toHaveURL(/\/applicant\/dashboard/, { timeout: 20_000 });
        await expect(
            page.getByRole('heading', { level: 1, name: /grant applicant dashboard/i }),
        ).toBeVisible({ timeout: 10_000 });
    });
});
