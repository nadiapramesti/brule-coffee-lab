import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

// Gagal bila ada pelanggaran WCAG 2.1 A/AA berdampak "serious" atau "critical".
const pages = [
    ['beranda', '/'],
    ['roast builder', '/builder'],
    ['perlengkapan', '/perlengkapan'],
    ['lacak pesanan', '/lacak'],
    ['login', '/login'],
];

for (const scheme of ['light', 'dark']) {
    for (const [name, path] of pages) {
        test(`aksesibilitas (${scheme}): ${name}`, async ({ browser }) => {
            const ctx = await browser.newContext({ colorScheme: scheme, reducedMotion: 'reduce' });
            const page = await ctx.newPage();
            await page.goto(path);
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(1500); // tunggu animasi masuk selesai agar kontras terukur stabil
            const { violations } = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']).analyze();
            const berat = violations.filter((v) => ['serious', 'critical'].includes(v.impact));
            const ringkas = berat.map((v) => `${v.id} (${v.impact}) x${v.nodes.length}: ${v.nodes.slice(0, 3).map((n) => n.target.join(' ')).join(' | ')}`);
            expect(ringkas, ringkas.join('\n')).toEqual([]);
            await ctx.close();
        });
    }
}
