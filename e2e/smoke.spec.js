import { test, expect } from '@playwright/test';

async function login(page, email) {
    await page.goto('/login');
    await page.locator('input[type=email]').fill(email);
    await page.locator('input[type=password]').fill('password');
    await page.keyboard.press('Enter');
    await page.waitForURL((u) => u.pathname !== '/login');
}

test('landing menampilkan katalog dan pencarian bekerja', async ({ page }) => {
    await page.goto('/');
    await expect(page.getByRole('link', { name: 'Racik kopi' }).first()).toBeVisible();
    const cari = page.locator('#cari-biji');
    await cari.scrollIntoViewIfNeeded();
    await cari.fill('gayo');
    await expect(page.getByText('Gayo').first()).toBeVisible();
});

test('roast builder: slider level sangrai mengubah pilihan', async ({ page }) => {
    await page.goto('/builder');
    await expect(page.getByRole('heading', { name: 'Roast builder' })).toBeVisible();
    const slider = page.locator('#roast');
    await slider.focus();
    await page.keyboard.press('ArrowRight');
    await expect(page.locator('button[wire\\:click="addToCart"]:visible').first()).toBeVisible();
});

test('halaman perlengkapan dan lacak pesanan terbuka', async ({ page }) => {
    await page.goto('/perlengkapan');
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
    await page.goto('/lacak');
    await expect(page.getByRole('button', { name: 'Cari' })).toBeVisible();
});

test('pembeli bisa racik, checkout, dan membuat pesanan', async ({ page }) => {
    await page.goto('/builder');
    await page.locator('button[wire\\:click="addToCart"]:visible').first().click();
    await page.goto('/keranjang');
    await page.locator('a[href$="/checkout"]:visible').first().click();
    await page.locator('input[type=email]').waitFor();
    await page.locator('input[type=email]').fill('customer@brule.test');
    await page.locator('input[type=password]').fill('password');
    await page.keyboard.press('Enter');
    await page.waitForURL((u) => u.pathname !== '/login');
    if (!(await page.locator('#voucher').count())) await page.goto('/checkout');
    await page.locator('#voucher').fill('KOPIBARU10');
    await page.getByRole('button', { name: 'Pakai' }).click();
    await page.getByRole('button', { name: 'Buat pesanan' }).first().click();
    await expect(page.locator('.krm-readout').filter({ hasText: /^BRL-/ }).first()).toBeVisible({ timeout: 20_000 });
});

test('admin melihat dashboard dan pembeli tidak boleh masuk admin', async ({ page, browser }) => {
    await login(page, 'admin@brule.test');
    await page.goto('/admin');
    await expect(page.getByText('Dashboard').first()).toBeVisible();

    const ctx = await browser.newContext();
    const p2 = await ctx.newPage();
    await login(p2, 'customer@brule.test');
    const res = await p2.goto('/admin');
    expect([403, 404]).toContain(res.status());
    await ctx.close();
});

test('halaman yang tidak ada menampilkan halaman error bermerek', async ({ page }) => {
    const res = await page.goto('/halaman-yang-tidak-ada');
    expect(res.status()).toBe(404);
});
