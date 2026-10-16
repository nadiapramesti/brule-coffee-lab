import './chromatogram';
import './flavor-wheel';
import './embers';

// Muncul saat masuk layar. Elemen yang sudah terlihat waktu halaman dibuka
// ikut dianimasikan, jadi hero punya pembuka tanpa kode tambahan.
const reveal = new IntersectionObserver((entries) => {
    entries.forEach((e) => {
        if (e.isIntersecting) {
            e.target.classList.add('is-in');
            reveal.unobserve(e.target);
        }
    });
}, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });

const watchReveals = () =>
    document.querySelectorAll('[data-reveal]:not(.is-in)').forEach((el) => reveal.observe(el));

document.addEventListener('DOMContentLoaded', watchReveals);
document.addEventListener('livewire:navigated', watchReveals);

// Sidebar admin yang diciutkan: pulihkan setelah wire:navigate mengganti <html>.
document.addEventListener('livewire:navigated', () => {
    try {
        document.documentElement.classList.toggle('mini', localStorage.getItem('krm-mini') === '1');
    } catch (e) {}
});

// Tema terang/gelap. Pilihan disimpan; sebelum memilih, ikut setelan sistem.
const systemTheme = () => (matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
const storedTheme = () => { try { const t = localStorage.getItem('krm-theme'); return t === 'light' || t === 'dark' ? t : null; } catch (e) { return null; } };
const applyTheme = (t) => { document.documentElement.dataset.theme = t; };

window.krmTheme = {
    get: () => document.documentElement.dataset.theme || systemTheme(),
    set(t) {
        applyTheme(t);
        try { localStorage.setItem('krm-theme', t); } catch (e) {}
        window.dispatchEvent(new CustomEvent('krm-theme', { detail: t }));
    },
    toggle() { this.set(this.get() === 'light' ? 'dark' : 'light'); },
};

// wire:navigate mengganti <html>; pasang ulang tema tanpa kedipan.
document.addEventListener('livewire:navigated', () => applyTheme(storedTheme() ?? systemTheme()));
matchMedia('(prefers-color-scheme: light)').addEventListener('change', () => { if (!storedTheme()) applyTheme(systemTheme()); });
