import { expect, test } from '@playwright/test';

test.describe('public pages', () => {
    test('login page loads', async ({ page }) => {
        await page.goto('/login');
        await expect(page).toHaveTitle(/sign in/i);
        await expect(page.getByRole('heading', { name: /grants portal/i })).toBeVisible();
        await expect(page.getByRole('heading', { name: /welcome back/i })).toBeVisible();
    });
});
