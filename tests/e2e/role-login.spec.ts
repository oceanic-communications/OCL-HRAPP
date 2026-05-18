/// <reference types="node" />

import { expect, test } from '@playwright/test';

import { loginViaOtp } from './helpers/login-via-otp';

/**
 * Matches `RoleTemplateUserSeeder`: `Str::slug($template->name) . '@example.com'`.
 * Run DB seeders before E2E (`php artisan migrate:fresh --seed` or equivalent).
 */
const roleTemplateUsers: { description: string; email: string; path: RegExp }[] =
    [
        {
            description: 'Grantee Staff',
            email: 'grantee-staff@example.com',
            path: /\/grantee\/dashboard/,
        },
        {
            description: 'Grantee Admin',
            email: 'grantee-admin@example.com',
            path: /\/grantee\/administration\/dashboard/,
        },
        {
            description: 'Staff Senior Program Manager',
            email: 'staff-senior-program-manager@example.com',
            path: /\/staff\/dashboard/,
        },
        {
            description: 'Staff Program Officer',
            email: 'staff-program-officer@example.com',
            path: /\/staff\/dashboard/,
        },
        {
            description: 'Staff Finance',
            email: 'staff-finance@example.com',
            path: /\/staff\/dashboard/,
        },
        {
            description: 'Staff Monitoring and Evaluation',
            email: 'staff-monitoring-and-evaluation@example.com',
            path: /\/staff\/dashboard/,
        },
    ];

test.describe('Role template logins (OTP via Mailhog)', () => {
    test.describe.configure({ timeout: 60_000 });

    for (const { description, email, path } of roleTemplateUsers) {
        test(`${description} signs in with OTP`, async ({ page }) => {
            await loginViaOtp(page, email);
            await expect(page).toHaveURL(path, { timeout: 20_000 });
            await expect(
                page.getByRole('heading', { level: 1, name: /Bula,/ }),
            ).toBeVisible({ timeout: 10_000 });
        });
    }
});

test.describe('optional seeded admin (super user)', () => {
    test('ADMIN_EMAIL user signs in with OTP when E2E_ADMIN_EMAIL is set', async ({
        page,
    }) => {
        const email = process.env.E2E_ADMIN_EMAIL?.trim();
        test.skip(!email, 'Set E2E_ADMIN_EMAIL to the same value as ADMIN_EMAIL after seeding.');

        await loginViaOtp(page, email!);
        await expect(page).toHaveURL(/\/staff\/dashboard/, { timeout: 20_000 });
    });
});
