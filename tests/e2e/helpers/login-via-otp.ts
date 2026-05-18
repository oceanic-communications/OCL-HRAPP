/// <reference types="node" />

import { expect, type Page } from '@playwright/test';

import { waitForLoginOtp } from './mailhog';

function mailhogBaseUrl(): string {
    return (
        process.env.PLAYWRIGHT_MAILHOG_URL?.replace(/\/$/, '') ||
        'http://localhost:8025'
    );
}

/**
 * Full OTP login: request code, read OTP from Mailhog, submit (auto-submit when 6 digits are entered).
 */
export async function loginViaOtp(
    page: Page,
    email: string,
    loginPath = '/login',
): Promise<void> {
    const mh = mailhogBaseUrl();
    const notBeforeMs = Date.now() - 5000;

    await page.goto(loginPath);
    await page.getByLabel(/email address/i).fill(email);
    await page.getByRole('button', { name: /send sign-in code/i }).click();
    await expect(
        page.getByRole('heading', { name: /check your email/i }),
    ).toBeVisible({ timeout: 20_000 });

    const code = await waitForLoginOtp({
        mailhogBaseUrl: mh,
        toEmail: email,
        notBeforeMs,
        timeoutMs: 25_000,
    });

    await page.locator('#code').fill(code);
    // Six digits trigger immediate `requestSubmit()`; the field unmounts on navigation.
    await expect(page).not.toHaveURL(/\/login/, { timeout: 20_000 });
}
