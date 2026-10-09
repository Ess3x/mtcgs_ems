import { expect, test } from '@playwright/test';

test('homepage responds successfully and renders a title', async ({ page }) => {
    const response = await page.goto('/');

    expect(response?.status()).toBe(200);
    await expect(page).toHaveTitle(/.+/);
});
