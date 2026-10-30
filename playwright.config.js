import { defineConfig, devices } from '@playwright/test';

// Tes E2E dijalankan terhadap server yang sudah hidup (lokal: php artisan serve; CI: lihat .github/workflows/ci.yml).
// PW_CHROMIUM opsional: path Chromium yang sudah terpasang (dipakai di lingkungan tanpa `playwright install`).
export default defineConfig({
    testDir: './e2e',
    timeout: 60_000,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : 'list',
    use: {
        baseURL: process.env.E2E_BASE_URL || 'http://127.0.0.1:8000',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        launchOptions: process.env.PW_CHROMIUM ? { executablePath: process.env.PW_CHROMIUM, args: ['--no-sandbox'] } : {},
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
});
