import { expect, test } from '@playwright/test';

test('login page shows the expected fields and action', async ({ page }) => {
    await page.goto('/login');

    await expect(page).toHaveTitle('Login - MTCGS-EMS');
    await expect(page.getByLabel('Email Address')).toBeVisible();
    await expect(page.getByLabel('Password')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Login' })).toBeVisible();
});

test('invalid email is blocked before the login form is submitted', async ({ page }) => {
    let loginPostSent = false;
    page.on('request', (request) => {
        if (request.method() === 'POST' && new URL(request.url()).pathname === '/login') {
            loginPostSent = true;
        }
    });

    await page.goto('/login');
    await page.getByLabel('Email Address').fill('not-an-email');
    await page.getByLabel('Password').fill('test-password');
    await page.getByRole('button', { name: 'Login' }).click();

    const emailIsInvalid = await page.getByLabel('Email Address').evaluate(
        (input: HTMLInputElement) => input.validity.typeMismatch,
    );

    expect(emailIsInvalid).toBe(true);
    expect(loginPostSent).toBe(false);
});

test('server rejects login when credentials are missing', async ({ page }) => {
    await page.goto('/login');
    await page.locator('form').evaluate((form) => {
        (form as HTMLFormElement).noValidate = true;
    });
    await page.getByRole('button', { name: 'Login' }).click();

    await expect(page.getByText(/email field is required/i)).toBeVisible();
    await expect(page.getByText(/password field is required/i)).toBeVisible();
});

test('dedicated test account can log in', async ({ page }) => {
    const email = process.env.PLAYWRIGHT_TEST_EMAIL;
    const password = process.env.PLAYWRIGHT_TEST_PASSWORD;

    test.skip(!email || !password, 'Set credentials for a dedicated test account to run this test.');

    await page.goto('/login');
    await page.getByLabel('Email Address').fill(email!);
    await page.getByLabel('Password').fill(password!);
    await page.getByRole('button', { name: 'Login' }).click();

    await expect(page).toHaveURL(/\/dashboard(?:\?.*)?$/);
});
